<?php
// api/support_messages.php - Fetch and Post Live Support Chat Messages
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

$action = trim($_GET['action'] ?? $_POST['action'] ?? 'fetch');
$db = getDb();

if ($action === 'fetch') {
    $convId = (int)($_GET['conversation_id'] ?? 0);
    $sinceId = (int)($_GET['since_id'] ?? 0);

    if ($convId <= 0) {
        echo json_encode(['success' => false, 'messages' => []]);
        exit;
    }

    $stmt = $db->prepare("
        SELECT id, sender_type, sender_name, message, created_at
        FROM support_messages
        WHERE conversation_id = ? AND id > ?
        ORDER BY id ASC
    ");
    $stmt->execute([$convId, $sinceId]);
    $messages = $stmt->fetchAll();

    // Map timestamps to readable format
    foreach ($messages as &$m) {
        $m['time_str'] = date('H:i', strtotime($m['created_at']));
    }

    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
    exit;
}

if ($action === 'post' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? $_POST;

    $convId = (int)($data['conversation_id'] ?? 0);
    $senderName = trim($data['sender_name'] ?? 'Visitor');
    $message = trim($data['message'] ?? '');
    $senderType = trim($data['sender_type'] ?? 'visitor');

    if ($convId <= 0 || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Empty message or invalid conversation']);
        exit;
    }

    $ins = $db->prepare("
        INSERT INTO support_messages (conversation_id, sender_type, sender_name, message)
        VALUES (?, ?, ?, ?)
    ");
    $ins->execute([$convId, $senderType, $senderName, $message]);
    $msgId = (int)$db->lastInsertId();

    $db->prepare("UPDATE support_conversations SET last_message_at = NOW() WHERE id = ?")->execute([$convId]);

    echo json_encode([
        'success' => true,
        'message_id' => $msgId,
        'time_str' => date('H:i')
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
