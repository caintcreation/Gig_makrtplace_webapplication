<?php
// support.php - 24/7 Live Support Chat Desk
require_once __DIR__ . '/includes/functions.php';

$currentNav = 'support';
$pageTitle = 'Live Support Desk &middot; CAINT EARN HUB';
$pageDesc = 'Get real-time assistance with task vetting, activation prompts, or recovery queries.';

$user = currentUser();
$defaultName = $user ? ($user['display_name'] ?? $user['username']) : 'Guest Member';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 2.5rem; margin-bottom: 4rem;">
    <div style="max-width: 780px; margin: 0 auto; text-align: center; margin-bottom: 1.5rem;">
        <h1 style="font-size: 2.2rem; margin-bottom: 0.5rem;">Live Support Desk</h1>
        <p style="color: var(--text-secondary); font-size: 1rem;">
            Have questions about a verified task, need assistance with your Safaricom prompt, or need help recovering a link? Chat below or call our phone support.
        </p>
    </div>

    <!-- Chat Container -->
    <div class="chat-container">
        <!-- Header -->
        <div class="chat-header">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 10px; height: 10px; border-radius: var(--radius-full); background-color: var(--success);"></div>
                <div>
                    <strong style="font-size: 1rem; color: var(--text-primary);">CAINT EARN HUB Support Desk</strong>
                    <div style="font-size: 0.78rem; color: var(--text-secondary);">Customer Care &middot; Active Online</div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <label style="font-size: 0.8rem; color: var(--text-secondary);" for="chatVisitorNameInput">Your Name:</label>
                <input type="text" id="chatVisitorNameInput" value="<?= e($defaultName) ?>" class="form-control" style="width: 150px; padding: 0.35rem 0.65rem; font-size: 0.85rem;">
            </div>
        </div>

        <!-- Messages Area -->
        <div class="chat-messages" id="chatMessages">
            <div style="text-align: center; color: var(--text-secondary); font-size: 0.82rem; margin: 0.5rem 0;">
                Connecting to live desk session...
            </div>
        </div>

        <!-- Input Bar -->
        <form class="chat-input-bar" id="chatForm" onsubmit="sendChatMessage(event)">
            <input type="text" id="chatMsgInput" placeholder="Type your question here..." required class="form-control" autocomplete="off">
            <button type="submit" id="chatSendBtn" class="btn btn-primary" style="white-space: nowrap; padding: 0.65rem 1.25rem;">
                Send
            </button>
        </form>
    </div>

    <!-- Direct Contact Numbers -->
    <div style="max-width: 780px; margin: 1.5rem auto 0; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
        <div style="background-color: var(--surface-main); border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1.25rem 1.5rem;">
            <strong style="font-size: 0.95rem; display: block; margin-bottom: 0.2rem; color: var(--text-primary);">Primary Customer Care</strong>
            <span style="font-size: 0.85rem; color: var(--text-secondary); display: block; margin-bottom: 0.5rem;">Call or WhatsApp for immediate assistance</span>
            <a href="tel:+254714772901" style="font-size: 1.1rem; font-weight: 700; color: var(--accent);">+254 714 772 901</a>
        </div>
        <div style="background-color: var(--surface-main); border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 1.25rem 1.5rem;">
            <strong style="font-size: 0.95rem; display: block; margin-bottom: 0.2rem; color: var(--text-primary);">Secondary Support Line</strong>
            <span style="font-size: 0.85rem; color: var(--text-secondary); display: block; margin-bottom: 0.5rem;">Support &amp; platform onboarding queries</span>
            <a href="tel:+254756963430" style="font-size: 1.1rem; font-weight: 700; color: var(--accent);">+254 756 963 430</a>
        </div>
    </div>
</div>

<script>
let convId = null;
let visitorToken = localStorage.getItem('caint_support_token') || '';
let lastMsgId = 0;
let isPolling = false;

async function initChat() {
    const nameInput = document.getElementById('chatVisitorNameInput');
    const name = nameInput.value.trim() || 'Guest';

    try {
        const resp = await fetch('<?= $appUrl ?>/api/support_bootstrap.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: name, visitor_token: visitorToken })
        });
        const data = await resp.json();
        if (data.success) {
            convId = data.conversation_id;
            visitorToken = data.visitor_token;
            localStorage.setItem('caint_support_token', visitorToken);
            loadMessages();
            if (!isPolling) {
                isPolling = true;
                setInterval(loadMessages, 3500);
            }
        }
    } catch (e) {
        console.error('Chat error:', e);
    }
}

async function loadMessages() {
    if (!convId) return;
    try {
        const resp = await fetch(`<?= $appUrl ?>/api/support_messages.php?action=fetch&conversation_id=${convId}&since_id=${lastMsgId}`);
        const data = await resp.json();
        if (data.success && data.messages.length > 0) {
            const container = document.getElementById('chatMessages');
            data.messages.forEach(m => {
                if (m.id > lastMsgId) lastMsgId = m.id;
                appendMessageBubble(m);
            });
            container.scrollTop = container.scrollHeight;
        }
    } catch (e) {
        console.error('Message fetch error:', e);
    }
}

function appendMessageBubble(m) {
    const container = document.getElementById('chatMessages');
    const isMe = (m.sender_type === 'visitor' || m.sender_type === 'user');
    
    const bubble = document.createElement('div');
    bubble.className = `chat-bubble ${isMe ? 'bubble-me' : 'bubble-admin'}`;
    
    bubble.innerHTML = `
        <div style="font-weight: 700; font-size: 0.78rem; margin-bottom: 0.2rem; opacity: 0.9;">
            ${escapeHtml(m.sender_name)}
        </div>
        <div>${escapeHtml(m.message)}</div>
        <div class="bubble-meta" style="text-align: right;">${m.time_str || ''}</div>
    `;
    container.appendChild(bubble);
}

async function sendChatMessage(e) {
    e.preventDefault();
    if (!convId) return;
    const input = document.getElementById('chatMsgInput');
    const msg = input.value.trim();
    if (!msg) return;

    const nameInput = document.getElementById('chatVisitorNameInput');
    const name = nameInput.value.trim() || 'Guest';

    input.value = '';
    const btn = document.getElementById('chatSendBtn');
    btn.disabled = true;

    try {
        const resp = await fetch('<?= $appUrl ?>/api/support_messages.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'post',
                conversation_id: convId,
                sender_name: name,
                sender_type: 'visitor',
                message: msg
            })
        });
        const res = await resp.json();
        btn.disabled = false;
        if (res.success) {
            loadMessages();
        }
    } catch (err) {
        btn.disabled = false;
        alert('Could not deliver message. Please retry.');
    }
}

function escapeHtml(str) {
    return (str || '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

document.addEventListener('DOMContentLoaded', initChat);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
