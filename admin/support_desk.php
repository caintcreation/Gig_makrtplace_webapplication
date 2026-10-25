<?php
// admin/support_desk.php - Live Support Chat Helpdesk for Admins
require_once __DIR__ . '/../includes/functions.php';

$adminNav = 'support';
$adminTitle = 'Live Support Desk';
$db = getDb();

// Fetch all conversations
$conversations = $db->query("
    SELECT c.*,
           (SELECT message FROM support_messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_message,
           (SELECT COUNT(*) FROM support_messages m WHERE m.conversation_id = c.id AND m.sender_type = 'visitor' AND m.is_read = 0) AS unread_count
    FROM support_conversations c
    ORDER BY c.last_message_at DESC
")->fetchAll();

$selectedConvId = (int)($_GET['conv_id'] ?? ($conversations[0]['id'] ?? 0));

// Mark messages as read for selected conversation
if ($selectedConvId > 0) {
    $db->prepare("UPDATE support_messages SET is_read = 1 WHERE conversation_id = ? AND sender_type = 'visitor'")->execute([$selectedConvId]);
}

require_once __DIR__ . '/header.php';
?>

<div class="page-title-row">
    <div>
        <h1 style="font-size: 1.85rem; margin-bottom: 0.25rem;">Live Support Desk</h1>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">
            Answer member questions, provide token recovery help, and advise applicants.
        </p>
    </div>
</div>

<div class="chat-desk-grid">
    <!-- Conversations List -->
    <div class="conv-list">
        <div style="padding: 1rem 1.25rem; background-color: var(--surface-subtle); border-bottom: 1px solid var(--divider); font-weight: 700; font-size: 0.88rem; color: var(--text-secondary); text-transform: uppercase;">
            Active Conversations (<?= count($conversations) ?>)
        </div>

        <?php if (empty($conversations)): ?>
            <div style="padding: 2rem 1rem; text-align: center; color: var(--text-secondary); font-size: 0.9rem;">
                No support conversations initiated yet.
            </div>
        <?php else: ?>
            <?php foreach ($conversations as $c): ?>
                <div class="conv-item <?= $selectedConvId === (int)$c['id'] ? 'active' : '' ?>" onclick="window.location.href='support_desk.php?conv_id=<?= $c['id'] ?>'">
                    <div class="conv-name">
                        <span><?= e($c['visitor_name']) ?></span>
                        <span style="font-size: 0.72rem; color: var(--text-secondary); font-weight: normal;"><?= timeAgo($c['last_message_at']) ?></span>
                    </div>
                    <div class="conv-preview">
                        <?= e($c['last_message'] ?: 'No messages yet') ?>
                    </div>
                    <?php if ($c['unread_count'] > 0): ?>
                        <span style="display: inline-block; background-color: var(--danger); color: #fff; font-size: 0.7rem; font-weight: 700; padding: 0.1rem 0.45rem; border-radius: var(--radius-full); margin-top: 0.35rem;">
                            <?= $c['unread_count'] ?> unread
                        </span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Active Conversation View -->
    <div style="display: flex; flex-direction: column; height: 100%;">
        <?php if ($selectedConvId > 0): ?>
            <?php
            $stmt = $db->prepare("SELECT * FROM support_conversations WHERE id = ? LIMIT 1");
            $stmt->execute([$selectedConvId]);
            $currentConv = $stmt->fetch();
            ?>
            <div style="padding: 1.1rem 1.5rem; background-color: var(--surface-subtle); border-bottom: 1px solid var(--divider); display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong style="font-size: 1.05rem;"><?= e($currentConv['visitor_name']) ?></strong>
                    <?php if ($currentConv['visitor_phone']): ?>
                        <span style="font-size: 0.82rem; color: var(--text-secondary); margin-left: 0.5rem;">
                            Phone: <?= e($currentConv['visitor_phone']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <span class="badge badge-verified">Direct Desk Session #<?= $currentConv['id'] ?></span>
            </div>

            <div class="chat-messages" id="adminChatMessages" style="flex: 1; padding: 1.5rem; overflow-y: auto; display: flex; flex-direction: column; gap: 1rem; background-color: #FAFAF8;">
                <!-- Messages populated via JS and PHP -->
            </div>

            <form class="chat-input-bar" id="adminReplyForm" onsubmit="sendAdminMessage(event)">
                <input type="text" id="adminMsgInput" placeholder="Type your response to this member..." required class="form-control" autocomplete="off">
                <button type="submit" id="adminSendBtn" class="btn btn-primary" style="white-space: nowrap; padding: 0.65rem 1.4rem;">
                    Send Response
                </button>
            </form>
        <?php else: ?>
            <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--text-secondary);">
                Select a conversation from the left panel to begin replying.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
const activeConvId = <?= $selectedConvId ?>;
let lastAdminMsgId = 0;

async function loadAdminChat() {
    if (!activeConvId) return;
    try {
        const resp = await fetch(`<?= $appUrl ?>/api/support_messages.php?action=fetch&conversation_id=${activeConvId}&since_id=${lastAdminMsgId}`);
        const data = await resp.json();
        if (data.success && data.messages.length > 0) {
            const container = document.getElementById('adminChatMessages');
            data.messages.forEach(m => {
                if (m.id > lastAdminMsgId) lastAdminMsgId = m.id;
                appendAdminBubble(m);
            });
            container.scrollTop = container.scrollHeight;
        }
    } catch (e) {
        console.error('Error fetching admin messages:', e);
    }
}

function appendAdminBubble(m) {
    const container = document.getElementById('adminChatMessages');
    const isMe = (m.sender_type === 'admin');

    const bubble = document.createElement('div');
    bubble.className = `chat-bubble ${isMe ? 'bubble-me' : 'bubble-them'}`;
    
    bubble.innerHTML = `
        <div style="font-weight: 700; font-size: 0.78rem; margin-bottom: 0.2rem; opacity: 0.9;">
            ${escapeHtml(m.sender_name)}
        </div>
        <div>${escapeHtml(m.message)}</div>
        <div class="bubble-meta" style="text-align: right;">${m.time_str || ''}</div>
    `;
    container.appendChild(bubble);
}

async function sendAdminMessage(e) {
    e.preventDefault();
    if (!activeConvId) return;
    const input = document.getElementById('adminMsgInput');
    const msg = input.value.trim();
    if (!msg) return;

    input.value = '';
    const btn = document.getElementById('adminSendBtn');
    btn.disabled = true;

    try {
        const resp = await fetch('<?= $appUrl ?>/api/support_messages.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'post',
                conversation_id: activeConvId,
                sender_name: 'NJORONATION Admin',
                sender_type: 'admin',
                message: msg
            })
        });
        const res = await resp.json();
        btn.disabled = false;
        if (res.success) {
            loadAdminChat();
        }
    } catch (err) {
        btn.disabled = false;
        alert('Could not send message.');
    }
}

function escapeHtml(str) {
    return (str || '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

document.addEventListener('DOMContentLoaded', () => {
    if (activeConvId > 0) {
        loadAdminChat();
        setInterval(loadAdminChat, 3000);
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
