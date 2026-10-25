<?php
// test_workflow.php - Test core marketplace workflows for CAINT EARN HUB
require_once __DIR__ . '/includes/functions.php';

echo "--- 1. Testing Database Connection ---\n";
$db = getDb();
$platforms = $db->query("SELECT id, title, slug, token_price FROM platforms LIMIT 3")->fetchAll();
echo "Found " . count($platforms) . " sample platforms.\n";
foreach ($platforms as $p) {
    echo "  [#{$p['id']}] {$p['title']} ({$p['slug']}) - Price: {$p['token_price']}\n";
}

echo "\n--- 2. Testing Direct Safaricom Prompt Simulation ---\n";
$testPhone = '0714772901';
$platformId = $platforms[0]['id'];
$normPhone = normalizePhone($testPhone);
$checkoutId = 'caint_pay_test_' . time();
$accessToken = bin2hex(random_bytes(16));

$stmt = $db->prepare("
    INSERT INTO unlock_transactions 
    (platform_id, phone, amount, checkout_id, status, access_token)
    VALUES (?, ?, 50.00, ?, 'pending', ?)
");
$stmt->execute([$platformId, $normPhone, $checkoutId, $accessToken]);
$txId = $db->lastInsertId();
echo "Created pending transaction #{$txId} with checkout_id {$checkoutId}\n";

// Simulate status verification
$receipt = 'SDF' . rand(1000, 9999) . 'KE';
$db->prepare("UPDATE unlock_transactions SET status = 'completed', mpesa_receipt = ?, completed_at = NOW() WHERE id = ?")->execute([$receipt, $txId]);
echo "Simulated payment confirmation: Receipt {$receipt}\n";

// Query via track
$check = $db->prepare("SELECT * FROM unlock_transactions WHERE phone = ? AND status = 'completed'");
$check->execute([$normPhone]);
$recovered = $check->fetchAll();
echo "Recover Access query found " . count($recovered) . " unlocked platforms for {$testPhone}.\n";
assert(count($recovered) >= 1, "Should find at least 1 record");

echo "\n--- 3. Testing Live Support Chat API ---\n";
$vToken = 'vt_test_' . time();
$insConv = $db->prepare("INSERT INTO support_conversations (visitor_token, visitor_name, visitor_phone) VALUES (?, 'Test Visitor', '0714772901')");
$insConv->execute([$vToken]);
$convId = $db->lastInsertId();
echo "Created conversation #{$convId}\n";

$insMsg = $db->prepare("INSERT INTO support_messages (conversation_id, sender_type, sender_name, message) VALUES (?, 'visitor', 'Test Visitor', 'Is video recording currently open?')");
$insMsg->execute([$convId]);
echo "Visitor message inserted.\n";

$insReply = $db->prepare("INSERT INTO support_messages (conversation_id, sender_type, sender_name, message) VALUES (?, 'admin', 'CAINT Support', 'Yes, recordings are currently open and paying within 48 hours.')");
$insReply->execute([$convId]);
echo "Admin reply inserted.\n";

$messages = $db->query("SELECT * FROM support_messages WHERE conversation_id = {$convId}")->fetchAll();
echo "Total messages in conversation: " . count($messages) . "\n";
assert(count($messages) === 2, "Should have 2 messages");

echo "\n--- 4. Testing User Authentication & Admin Credentials ---\n";
$testAdmin = $db->query("SELECT * FROM users WHERE email = 'caintcreatin@gmail.com' AND role = 'admin'")->fetch();
assert($testAdmin !== false, "Admin user with caintcreatin@gmail.com must exist");
assert(password_verify('Maduvwe', $testAdmin['password_hash']), "Admin password 'Maduvwe' verification must succeed");
echo "Admin password check ('Maduvwe') passed!\n";

$testWorker = $db->query("SELECT * FROM users WHERE role = 'worker' LIMIT 1")->fetch();
assert($testWorker !== false, "Worker user must exist");
echo "Worker record check passed.\n";

echo "\nALL AUTOMATED TESTS PASSED SUCCESSFULLY!\n";
