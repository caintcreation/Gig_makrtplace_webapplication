<?php
// api/initiate_payment.php - Initiates Direct Safaricom M-Pesa Prompt via Paystack Charge API
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrf)) {
    echo json_encode(['success' => false, 'message' => 'Security token invalid or expired. Please refresh the page.']);
    exit;
}

$platformId = (int)($_POST['platform_id'] ?? 0);
$rawPhone = trim($_POST['phone'] ?? '');

if ($platformId <= 0 || empty($rawPhone)) {
    echo json_encode(['success' => false, 'message' => 'Invalid task or mobile number.']);
    exit;
}

// Assume Safaricom Kenyan mobile number
$phone = normalizePhone($rawPhone);
if (strlen($phone) !== 12 || !str_starts_with($phone, '254')) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid 10 or 12 digit Safaricom number (e.g. 0714772901).']);
    exit;
}

$db = getDb();
$stmt = $db->prepare("SELECT * FROM platforms WHERE id = ? AND verification_status = 'verified' LIMIT 1");
$stmt->execute([$platformId]);
$platform = $stmt->fetch();

if (!$platform) {
    echo json_encode(['success' => false, 'message' => 'Task not found or not currently active.']);
    exit;
}

$checkoutId = 'caint_pay_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
$accessToken = bin2hex(random_bytes(24));
$user = currentUser();
$userId = $user ? $user['id'] : null;
$customerEmail = $user ? $user['email'] : ('member_' . substr($phone, -4) . '@caintcreation.wuaze.com');
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

// Check if Paystack Secret Key is configured
$paystackSecret = trim(getenv('PAYSTACK_SECRET_KEY') ?: getSetting('paystack_secret_key', ''));
$paystackLive = false;
$paystackResponse = null;

if (!empty($paystackSecret) && str_starts_with($paystackSecret, 'sk_')) {
    // Format phone with +254 required by Paystack M-Pesa API
    $payPhone = str_starts_with($phone, '+') ? $phone : ('+' . $phone);
    $amountInCents = (int)round($platform['token_price'] * 100);

    $payload = [
        'email' => $customerEmail,
        'amount' => $amountInCents,
        'currency' => 'KES',
        'reference' => $checkoutId,
        'mobile_money' => [
            'phone' => $payPhone,
            'provider' => 'mpesa'
        ]
    ];

    $ch = curl_init('https://api.paystack.co/charge');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $paystackSecret,
        'Content-Type: application/json'
    ]);
    // Essential for InfinityFree & shared hosting SSL certificates
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        echo json_encode([
            'success' => false,
            'message' => 'Connection error reaching Paystack gateway: ' . $curlErr
        ]);
        exit;
    }

    if ($result) {
        $paystackResponse = json_decode($result, true);
        
        // Paystack returns 200 OK with status: true and data.status: "pay_offline" for pending STK push
        if ($httpCode === 200 && !empty($paystackResponse['status'])) {
            $paystackLive = true;
            if (!empty($paystackResponse['data']['reference'])) {
                $checkoutId = $paystackResponse['data']['reference'];
            }
        } else {
            // Paystack returned an explicit error (e.g. mobile money not enabled, invalid key, or invalid amount)
            $paystackError = $paystackResponse['message'] ?? 'Paystack could not trigger M-Pesa prompt.';
            if (!empty($paystackResponse['data']['message'])) {
                $paystackError .= ' (' . $paystackResponse['data']['message'] . ')';
            }
            echo json_encode([
                'success' => false,
                'message' => 'Paystack Error: ' . $paystackError
            ]);
            exit;
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Empty response from Paystack gateway (HTTP ' . $httpCode . '). Please check internet or hosting firewall.'
        ]);
        exit;
    }
}

// Record transaction in database
$insert = $db->prepare("
    INSERT INTO unlock_transactions 
    (platform_id, user_id, phone, amount, checkout_id, status, access_token, ip_address)
    VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)
");
$insert->execute([
    $platform['id'],
    $userId,
    $phone,
    $platform['token_price'],
    $checkoutId,
    $accessToken,
    $ip
]);

echo json_encode([
    'success' => true,
    'checkout_id' => $checkoutId,
    'amount' => (float)$platform['token_price'],
    'phone' => $phone,
    'paystack_live' => $paystackLive,
    'message' => 'Safaricom prompt dispatched to ' . formatPhoneDisplay($phone)
]);
