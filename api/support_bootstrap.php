<?php
// api/support_bootstrap.php - Initiates or resumes live support chat session
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$visitorName = trim($data['name'] ?? 'Guest Visitor');
$visitorToken = trim($data['visitor_token'] ?? '');
$visitorPhone = trim($data['phone'] ?? '');

$db = getDb();
$user = currentUser();
$userId = $user ? $user['id'] : null;

$conv = null;
if (!empty($visitorToken)) {
    $stmt = $db->prepare("SELECT * FROM support_conversations WHERE visitor_token = ? LIMIT 1");
    $stmt->execute([$visitorToken]);
    $conv = $stmt->fetch();
}

if (!$conv) {
    $visitorToken = 'vt_' . bin2hex(random_bytes(16));
    if ($user && empty($data['name'])) {
        $visitorName = $user['display_name'] ?? $user['username'];
    }
    $ins = $db->prepare("
        INSERT INTO support_conversations (visitor_token, visitor_name, visitor_phone, user_id, status)
        VALUES (?, ?, ?, ?, 'active')
    ");
    $ins->execute([$visitorToken, $visitorName, $visitorPhone, $userId]);
    $convId = (int)$db->lastInsertId();

    // Add initial automated greeting from Support
    $greet = $db->prepare("
        INSERT INTO support_messages (conversation_id, sender_type, sender_name, message, is_read)
        VALUES (?, 'admin', 'NJORONATION Support Desk', 'Hello! Welcome to the verified directory. Have a question about any platform or token unlock? We are ready to assist.', 0)
    ");
    $greet->execute([$convId]);
} else {
    $convId = (int)$conv['id'];
    if (!empty($visitorName) && $visitorName !== $conv['visitor_name']) {
        $db->prepare("UPDATE support_conversations SET visitor_name = ? WHERE id = ?")->execute([$visitorName, $convId]);
    }
}

echo json_encode([
    'success' => true,
    'conversation_id' => $convId,
    'visitor_token' => $visitorToken,
    'visitor_name' => $visitorName
]);
