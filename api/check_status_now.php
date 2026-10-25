<?php
// api/check_status_now.php - Verifies Safaricom Prompt status via Paystack or Simulation
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

$checkoutId = trim($_GET['checkout_id'] ?? '');

if (empty($checkoutId)) {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Missing transaction identifier.']);
    exit;
}

$db = getDb();
$stmt = $db->prepare("
    SELECT t.*, p.title, p.access_url, p.id AS p_id
    FROM unlock_transactions t
    JOIN platforms p ON t.platform_id = p.id
    WHERE t.checkout_id = ?
    LIMIT 1
");
$stmt->execute([$checkoutId]);
$tx = $stmt->fetch();

if (!$tx) {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Transaction not found.']);
    exit;
}

$paystackSecret = getenv('PAYSTACK_SECRET_KEY') ?: getSetting('paystack_secret_key', '');

// If already pending, check Paystack API or fallback simulation
if ($tx['status'] === 'pending') {
    if (!empty($paystackSecret) && str_starts_with($paystackSecret, 'sk_')) {
        // Query Paystack Transaction Verify endpoint (official verification endpoint)
        $ch = curl_init('https://api.paystack.co/transaction/verify/' . urlencode($checkoutId));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . trim($paystackSecret)
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $paystackData = $res ? json_decode($res, true) : null;
        
        // If transaction verify didn't find it or was empty, check charge endpoint
        if (!$paystackData || empty($paystackData['data']['status'])) {
            $ch = curl_init('https://api.paystack.co/charge/' . urlencode($checkoutId));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . trim($paystackSecret)
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $res = curl_exec($ch);
            curl_close($ch);
            $paystackData = $res ? json_decode($res, true) : null;
        }

        if ($paystackData && !empty($paystackData['data']['status'])) {
            $pStatus = strtolower($paystackData['data']['status']);
            
            if ($pStatus === 'success') {
                $receipt = $paystackData['data']['reference'] ?? ('S' . rand(1000, 9999) . 'KE');
                $up = $db->prepare("
                    UPDATE unlock_transactions 
                    SET status = 'completed', mpesa_receipt = ?, completed_at = NOW() 
                    WHERE id = ?
                ");
                $up->execute([$receipt, $tx['id']]);

                // Increment unlocks count
                $db->prepare("UPDATE platforms SET unlocks_count = unlocks_count + 1 WHERE id = ?")->execute([$tx['p_id']]);

                $tx['status'] = 'completed';
                $tx['mpesa_receipt'] = $receipt;
            } elseif (in_array($pStatus, ['failed', 'abandoned', 'reversed'], true)) {
                $gatewayMessage = $paystackData['data']['gateway_response'] ?? ($paystackData['message'] ?? 'Payment was cancelled or failed.');
                $db->prepare("UPDATE unlock_transactions SET status = 'failed' WHERE id = ?")->execute([$tx['id']]);
                
                echo json_encode([
                    'success' => false,
                    'status' => 'failed',
                    'message' => 'Payment Failed: ' . $gatewayMessage . '. Please try again.'
                ]);
                exit;
            } else {
                // Still waiting on phone PIN
                echo json_encode([
                    'success' => false,
                    'status' => 'pending',
                    'message' => 'Waiting for you to enter your M-Pesa PIN on your phone...'
                ]);
                exit;
            }
        } else {
            // Still in progress
            echo json_encode([
                'success' => false,
                'status' => 'pending',
                'message' => 'Waiting for response from Safaricom...'
            ]);
            exit;
        }
    } else {
        // Local simulation / demo mode when Paystack live key is not yet set
        $receipt = 'S' . chr(rand(65, 90)) . rand(1000, 9999) . chr(rand(65, 90)) . 'KE';
        $up = $db->prepare("
            UPDATE unlock_transactions 
            SET status = 'completed', mpesa_receipt = ?, completed_at = NOW() 
            WHERE id = ?
        ");
        $up->execute([$receipt, $tx['id']]);
        $db->prepare("UPDATE platforms SET unlocks_count = unlocks_count + 1 WHERE id = ?")->execute([$tx['p_id']]);

        $tx['status'] = 'completed';
        $tx['mpesa_receipt'] = $receipt;
    }
}

if ($tx['status'] === 'completed') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['unlocked_platforms'])) {
        $_SESSION['unlocked_platforms'] = [];
    }
    if (!in_array($tx['p_id'], $_SESSION['unlocked_platforms'], true)) {
        $_SESSION['unlocked_platforms'][] = $tx['p_id'];
    }

    echo json_encode([
        'success' => true,
        'status' => 'completed',
        'receipt' => $tx['mpesa_receipt'],
        'access_url' => $tx['access_url'],
        'platform_title' => $tx['title'],
        'completed_at' => $tx['completed_at'] ?? date('Y-m-d H:i:s')
    ]);
} elseif ($tx['status'] === 'failed') {
    echo json_encode([
        'success' => false,
        'status' => 'failed',
        'message' => 'Payment failed or was cancelled. Please try again.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'status' => 'pending',
        'message' => 'Waiting for confirmation on your phone.'
    ]);
}
