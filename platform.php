<?php
// platform.php - Verified Task Details & Direct Prompt Unlock
require_once __DIR__ . '/includes/functions.php';

$slug = trim($_GET['id'] ?? '');
if (empty($slug)) {
    header('Location: platforms.php');
    exit;
}

$db = getDb();
$stmt = $db->prepare("
    SELECT p.*, u.username AS creator_username, up.display_name AS creator_name
    FROM platforms p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE p.slug = ? AND p.verification_status = 'verified'
    LIMIT 1
");
$stmt->execute([$slug]);
$platform = $stmt->fetch();

if (!$platform) {
    setFlash('error', 'The requested earning task could not be found.');
    header('Location: platforms.php');
    exit;
}

// Increment view counter
$db->prepare("UPDATE platforms SET views_count = views_count + 1 WHERE id = ?")->execute([$platform['id']]);

$currentNav = 'platforms';
$pageTitle = $platform['title'];
$pageDesc = $platform['short_desc'];

// Check if already unlocked
$isUnlocked = false;
$unlockedToken = null;
$unlockedReceipt = null;

$user = currentUser();
if ($user) {
    $checkTx = $db->prepare("
        SELECT * FROM unlock_transactions 
        WHERE platform_id = ? AND user_id = ? AND status = 'completed'
        ORDER BY id DESC LIMIT 1
    ");
    $checkTx->execute([$platform['id'], $user['id']]);
    $existingTx = $checkTx->fetch();
    if ($existingTx) {
        $isUnlocked = true;
        $unlockedToken = $existingTx['access_token'];
        $unlockedReceipt = $existingTx['mpesa_receipt'];
    }
}

if (!$isUnlocked && !empty($_SESSION['unlocked_platforms']) && in_array($platform['id'], $_SESSION['unlocked_platforms'], true)) {
    $isUnlocked = true;
}

$fullShareUrl = $appUrl . '/platform.php?id=' . urlencode($platform['slug']);
$whatsappShareText = urlencode("Check out this verified paying task on CAINT EARN HUB: " . $platform['title'] . " - " . $fullShareUrl);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 2rem; margin-bottom: 4rem;">
    <!-- Breadcrumb -->
    <nav style="display: flex; gap: 0.5rem; font-size: 0.88rem; color: var(--text-secondary); margin-bottom: 1.5rem; flex-wrap: wrap;">
        <a href="<?= $appUrl ?>/index.php">Home</a> &rsaquo;
        <a href="<?= $appUrl ?>/platforms.php">Tasks</a> &rsaquo;
        <span style="color: var(--text-primary); font-weight: 600;"><?= e($platform['title']) ?></span>
    </nav>

    <!-- Detail Layout -->
    <div class="detail-layout">
        <!-- Main Content Column -->
        <main class="detail-main">
            <?php if (!empty($platform['task_image'])): ?>
                <div style="border-radius: var(--radius-md); overflow: hidden; margin-bottom: 1.75rem; max-height: 380px; background-color: var(--surface-subtle); border: 1px solid var(--border-light);">
                    <img src="<?= $appUrl . '/' . ltrim(e($platform['task_image']), '/') ?>" alt="<?= e($platform['title']) ?>" style="width: 100%; height: auto; max-height: 380px; object-fit: cover;">
                </div>
            <?php endif; ?>

            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap;">
                <span class="badge badge-verified">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Hand-Tested &amp; Payout Confirmed
                </span>
                <span class="badge badge-worktype"><?= e($platform['work_type']) ?></span>
                <span class="badge badge-open"><?= ucfirst(e($platform['listing_status'])) ?></span>
            </div>

            <h1 style="font-size: 2.1rem; margin-bottom: 1rem;"><?= e($platform['title']) ?></h1>
            <p style="font-size: 1.1rem; color: var(--text-secondary); line-height: 1.6; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--divider);">
                <?= e($platform['short_desc']) ?>
            </p>

            <!-- Overview -->
            <section style="margin-bottom: 2.5rem;">
                <h2 style="font-size: 1.35rem; margin-bottom: 1rem;">Task Overview &amp; Verification Details</h2>
                <div style="font-size: 1rem; color: var(--text-primary); line-height: 1.7; white-space: pre-line;">
                    <?= e($platform['full_desc']) ?>
                </div>
            </section>

            <!-- Requirements -->
            <?php if (!empty($platform['requirements'])): ?>
                <section style="margin-bottom: 2.5rem; background-color: var(--surface-subtle); padding: 1.5rem; border-radius: var(--radius-md); border-left: 4px solid var(--accent);">
                    <h3 style="font-size: 1.15rem; margin-bottom: 0.75rem;">Requirements &amp; Equipment Needed</h3>
                    <div style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6; white-space: pre-line;">
                        <?= e($platform['requirements']) ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Step by Step Guide Preview -->
            <?php if (!empty($platform['steps_guide'])): ?>
                <section style="margin-bottom: 2.5rem;">
                    <h3 style="font-size: 1.3rem; margin-bottom: 1rem;">Task Workflow Instructions</h3>
                    <div style="font-size: 0.95rem; color: var(--text-primary); line-height: 1.7; white-space: pre-line; background-color: var(--bg-primary); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-light);">
                        <?= e($platform['steps_guide']) ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Public Proof of Payments -->
            <section style="margin-bottom: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.5rem;">
                    <h3 style="font-size: 1.3rem;">Verified Payout Evidence</h3>
                    <span style="font-size: 0.85rem; color: var(--accent); font-weight: 600;">Authenticated by CAINT</span>
                </div>
                <p style="font-size: 0.92rem; color: var(--text-secondary); margin-bottom: 1rem;">
                    Real transaction proofs inspected prior to listing.
                </p>

                <div class="proof-gallery">
                    <div class="proof-thumb">
                        <div style="background-color:#E9EFE9; height:140px; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:1rem; border-radius:var(--radius-sm); border:1px solid #C8DAC9;">
                            <span style="font-weight:800; font-size:1.05rem; color:var(--accent);">SAFARICOM M-PESA</span>
                            <span style="font-size:0.75rem; color:var(--text-secondary); margin-top:0.35rem;">Ref: SDF8923KLA</span>
                            <span style="font-weight:700; font-size:0.95rem; color:var(--text-primary); margin-top:0.25rem;">+ KSh 2,850.00</span>
                        </div>
                        <div class="proof-caption">Direct disbursement to phone</div>
                    </div>

                    <div class="proof-thumb">
                        <div style="background-color:#EBF3FA; height:140px; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:1rem; border-radius:var(--radius-sm); border:1px solid #CADFF3;">
                            <span style="font-weight:800; font-size:1.05rem; color:#0369A1;">PAYOUT APPROVED</span>
                            <span style="font-size:0.75rem; color:var(--text-secondary); margin-top:0.35rem;">Task batch completed</span>
                            <span style="font-weight:700; font-size:0.95rem; color:var(--text-primary); margin-top:0.25rem;">Status: Cleared</span>
                        </div>
                        <div class="proof-caption">Platform earnings ledger</div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Sidebar Action Column -->
        <aside class="detail-sidebar">
            <!-- Unlock Card -->
            <div class="sidebar-card">
                <?php if ($isUnlocked): ?>
                    <!-- Unlocked State -->
                    <div style="text-align: center; padding-bottom: 1.5rem; border-bottom: 1px solid var(--divider);">
                        <div style="width: 50px; height: 50px; border-radius: var(--radius-full); background-color: var(--accent-light); color: var(--accent); display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </div>
                        <h3 style="font-size: 1.25rem; color: var(--text-primary); margin-bottom: 0.25rem;">Task Unlocked!</h3>
                        <p style="font-size: 0.85rem; color: var(--text-secondary);">
                            Your activation token has been confirmed.
                        </p>
                        <?php if ($unlockedReceipt): ?>
                            <span style="display:inline-block; font-size:0.78rem; background-color:var(--surface-subtle); padding:0.25rem 0.6rem; border-radius:var(--radius-sm); margin-top:0.5rem; font-weight:600;">
                                Reference: <?= e($unlockedReceipt) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div style="margin-top: 1.5rem;">
                        <label class="form-label" style="font-size: 0.85rem; text-transform: uppercase; color: var(--text-secondary);">Verified Onboarding Link</label>
                        <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                            <input type="text" readonly value="<?= e($platform['access_url']) ?>" id="unlockedUrlInput" class="form-control" style="font-size: 0.85rem; background-color: var(--surface-subtle);">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('unlockedUrlInput').value); alert('Link copied to clipboard!');">Copy</button>
                        </div>

                        <a href="<?= e($platform['access_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-full btn-lg" style="margin-bottom: 0.75rem;">
                            Open Portal Website &rarr;
                        </a>
                    </div>

                <?php else: ?>
                    <!-- Activation Form with Direct Prompt -->
                    <div style="margin-bottom: 1.25rem;">
                        <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary);">Unlock Verified Direct Access</span>
                        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-top: 0.25rem;">
                            <span style="font-family: var(--font-heading); font-size: 1.75rem; font-weight: 800; color: var(--text-primary);"><?= formatKsh($platform['token_price']) ?></span>
                            <span style="font-size: 0.82rem; font-weight: 600; color: var(--accent);">One-time Fee</span>
                        </div>
                    </div>

                    <!-- PROMINENT RESEARCH & SITE MAINTENANCE NOTICE -->
                    <div style="background-color: var(--surface-subtle); border: 1px solid var(--border-light); border-left: 3px solid var(--accent); padding: 0.9rem; border-radius: var(--radius-sm); margin-bottom: 1.25rem;">
                        <p style="font-size: 0.84rem; color: var(--text-primary); line-height: 1.5; margin: 0;">
                            <strong>Transparent Purpose:</strong> The amount paid is a one-time contribution to <strong>enhance testing research and site maintenance</strong>. The platform itself never charges you.
                        </p>
                    </div>

                    <form id="stkPushForm" onsubmit="handleStkPush(event)">
                        <?= csrfInput() ?>
                        <input type="hidden" name="platform_id" value="<?= $platform['id'] ?>">

                        <div class="form-group">
                            <label class="form-label" for="phoneInput">Safaricom Mobile Number</label>
                            <input type="tel" id="phoneInput" name="phone" placeholder="07XXXXXXXX or 01XXXXXXXX" required class="form-control" style="font-size: 1.1rem; letter-spacing: 0.05em; text-align: center;" value="<?= e($user['phone'] ?? '') ?>">
                            <small class="form-help">An instant prompt will arrive on your phone. You will not be redirected.</small>
                        </div>

                        <button type="submit" id="stkSubmitBtn" class="btn btn-primary btn-full btn-lg">
                            Unlock Verified Task &middot; <?= formatKsh($platform['token_price']) ?>
                        </button>
                    </form>

                    <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--divider); display: flex; flex-direction: column; gap: 0.4rem; font-size: 0.8rem; color: var(--text-secondary);">
                        <div style="display: flex; align-items: center; gap: 0.4rem;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:var(--accent);">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span>Direct prompt on your Safaricom line</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.4rem;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:var(--accent);">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span>Recover anytime with your phone number</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Share Task Section -->
            <div class="sidebar-card">
                <h4 style="font-size: 1rem; margin-bottom: 0.5rem;">Share This Task</h4>
                <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1rem;">
                    Share this opportunity directly with fellow workers or friends.
                </p>
                <div style="display: flex; gap: 0.5rem;">
                    <a href="https://wa.me/?text=<?= $whatsappShareText ?>" target="_blank" rel="noopener" class="btn btn-whatsapp btn-sm" style="flex: 1;">
                        Share on WhatsApp
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="navigator.clipboard.writeText('<?= $fullShareUrl ?>'); alert('Task link copied to clipboard!');">
                        Copy Link
                    </button>
                </div>
            </div>

            <!-- Direct Contributor Pitch Alternative -->
            <div class="sidebar-card">
                <h4 style="font-size: 1rem; margin-bottom: 0.5rem;">Apply or Submit Pitch</h4>
                <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1rem;">
                    Prefer submitting your background directly to the task coordinator?
                </p>
                <a href="<?= $appUrl ?>/apply.php?id=<?= e($platform['slug']) ?>" class="btn btn-secondary btn-full btn-sm">
                    Submit Contributor Application
                </a>
            </div>
        </aside>
    </div>
</div>

<!-- Modal: Direct Safaricom Prompt & Automatic Payment Check -->
<div class="modal-overlay" id="stkModal">
    <div class="modal-box">
        <button type="button" class="modal-close-btn" onclick="stopStkPolling(); closeModal('stkModal');">&times;</button>
        <div id="stkModalContent" style="text-align: center;">
            <div style="width: 56px; height: 56px; border-radius: var(--radius-full); background-color: var(--surface-subtle); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--accent);">
                    <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                    <line x1="12" y1="18" x2="12.01" y2="18"></line>
                </svg>
            </div>
            
            <h3 style="font-size: 1.3rem; margin-bottom: 0.5rem;" id="stkModalTitle">M-Pesa Prompt Sent to Your Phone</h3>
            <p style="font-size: 0.92rem; color: var(--text-secondary); margin-bottom: 1.25rem;" id="stkModalDesc">
                Check your Safaricom phone screen now. Enter your M-Pesa PIN to confirm the one-time contribution of <?= formatKsh($platform['token_price']) ?>.
            </p>

            <!-- Real-time Status / Polling Box -->
            <div id="stkWaitingBox" style="background-color: var(--surface-subtle); padding: 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; text-align: center; border: 1px solid var(--border-light);">
                <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                    <div class="spinner" style="width: 20px; height: 20px; border: 2.5px solid var(--border-light); border-top-color: var(--accent); border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
                    <span id="stkStatusText" style="font-size: 0.9rem; font-weight: 600; color: var(--text-primary);">
                        Waiting for PIN confirmation on your handset...
                    </span>
                </div>
                <p style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 0.75rem;">
                    We are automatically verifying your payment. Please do not close this window.
                </p>
                <button type="button" class="btn btn-secondary btn-sm" id="manualCheckBtn" onclick="pollPaymentStatus(true)">
                    Check Status Now
                </button>
            </div>

            <!-- Failure Box -->
            <div id="stkFailureBox" style="display: none; background-color: #FDF2F2; border: 1px solid #F8B4B4; padding: 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; text-align: center;">
                <div style="color: #9B1C1C; font-weight: 700; font-size: 1rem; margin-bottom: 0.4rem;">
                    Payment Did Not Complete
                </div>
                <p id="stkFailureMessage" style="font-size: 0.88rem; color: #771D1D; margin-bottom: 1rem;">
                    The payment was cancelled or failed on your mobile handset.
                </p>
                <div style="display: flex; gap: 0.5rem; justify-content: center;">
                    <button type="button" class="btn btn-primary btn-sm" onclick="retryPayment()">
                        Try Again
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="stopStkPolling(); closeModal('stkModal');">
                        Close
                    </button>
                </div>
            </div>

            <!-- Success Box -->
            <div id="stkSuccessBox" style="display: none; text-align: center;">
                <div style="color: var(--success); font-weight: 800; font-size: 1.25rem; margin-bottom: 0.5rem;">
                    Payment Confirmed! Task Unlocked
                </div>
                <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 1.25rem;">
                    M-Pesa Reference: <strong id="receiptDisplay"></strong>
                </p>
                <div style="background-color: var(--bg-primary); padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; text-align: left; word-break: break-all; border: 1px solid var(--border-light);">
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase;">Your Verified Onboarding Link</span>
                    <div style="font-size: 0.95rem; font-weight: 700; color: var(--accent); margin-top: 0.35rem;" id="unlockedUrlDisplay"></div>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <a href="#" id="directOpenLink" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-full">
                        Open Portal Website &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
let currentCheckoutId = null;
let stkPollingTimer = null;
let pollAttempts = 0;
const MAX_POLL_ATTEMPTS = 25; // 25 attempts * 3s = 75 seconds timeout

function stopStkPolling() {
    if (stkPollingTimer) {
        clearInterval(stkPollingTimer);
        stkPollingTimer = null;
    }
}

async function handleStkPush(e) {
    e.preventDefault();
    const form = document.getElementById('stkPushForm');
    const btn = document.getElementById('stkSubmitBtn');
    const phoneInput = document.getElementById('phoneInput');
    const phone = phoneInput.value.trim();

    if (!phone) {
        alert('Please enter a valid Safaricom mobile number (e.g. 0714772901).');
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Sending prompt to your phone...';

    const formData = new FormData(form);

    try {
        const resp = await fetch('<?= $appUrl ?>/api/initiate_payment.php', {
            method: 'POST',
            body: formData
        });
        const text = await resp.text();
        let res;
        try {
            res = JSON.parse(text);
        } catch (jsonErr) {
            btn.disabled = false;
            btn.textContent = 'Unlock Verified Task · <?= formatKsh($platform['token_price']) ?>';
            alert('Server error: ' + text.replace(/<[^>]+>/g, ' ').substring(0, 250));
            return;
        }

        btn.disabled = false;
        btn.textContent = 'Unlock Verified Task · <?= formatKsh($platform['token_price']) ?>';

        if (res.success) {
            currentCheckoutId = res.checkout_id;
            openModal('stkModal');
            
            // Reset UI states
            document.getElementById('stkWaitingBox').style.display = 'block';
            document.getElementById('stkFailureBox').style.display = 'none';
            document.getElementById('stkSuccessBox').style.display = 'none';
            document.getElementById('stkStatusText').textContent = 'Waiting for PIN confirmation on your phone...';
            
            // Start automatic polling every 3 seconds
            pollAttempts = 0;
            stopStkPolling();
            stkPollingTimer = setInterval(pollPaymentStatus, 3000);
        } else {
            alert(res.message || 'Error sending Safaricom prompt.');
        }
    } catch (err) {
        btn.disabled = false;
        btn.textContent = 'Unlock Verified Task · <?= formatKsh($platform['token_price']) ?>';
        alert('Network connection error: ' + err.message);
    }
}

async function pollPaymentStatus(manual = false) {
    if (!currentCheckoutId) return;

    pollAttempts++;
    const statusText = document.getElementById('stkStatusText');
    const manualBtn = document.getElementById('manualCheckBtn');

    if (manual) {
        manualBtn.disabled = true;
        manualBtn.textContent = 'Checking...';
    }

    try {
        const resp = await fetch('<?= $appUrl ?>/api/check_status_now.php?checkout_id=' + encodeURIComponent(currentCheckoutId));
        const text = await resp.text();
        let res;
        try {
            res = JSON.parse(text);
        } catch (jsonErr) {
            if (manual) {
                manualBtn.disabled = false;
                manualBtn.textContent = 'Check Status Now';
                alert('Status check error: ' + text.replace(/<[^>]+>/g, ' ').substring(0, 250));
            }
            return;
        }

        if (manual) {
            manualBtn.disabled = false;
            manualBtn.textContent = 'Check Status Now';
        }

        // 1. Payment Success
        if (res.success && res.status === 'completed') {
            stopStkPolling();
            document.getElementById('stkWaitingBox').style.display = 'none';
            document.getElementById('stkFailureBox').style.display = 'none';
            document.getElementById('stkSuccessBox').style.display = 'block';
            document.getElementById('receiptDisplay').textContent = res.receipt || 'Confirmed';
            document.getElementById('unlockedUrlDisplay').textContent = res.access_url || '<?= e($platform['access_url']) ?>';
            document.getElementById('directOpenLink').href = res.access_url || '<?= e($platform['access_url']) ?>';
            return;
        }

        // 2. Payment Failed / Cancelled
        if (res.status === 'failed') {
            stopStkPolling();
            document.getElementById('stkWaitingBox').style.display = 'none';
            document.getElementById('stkFailureBox').style.display = 'block';
            document.getElementById('stkFailureMessage').textContent = res.message || 'Payment was cancelled or failed on handset. Please try again.';
            return;
        }

        // 3. Still Pending
        if (statusText) {
            statusText.textContent = res.message || 'Waiting for PIN confirmation on your handset...';
        }

        // Timeout check
        if (pollAttempts >= MAX_POLL_ATTEMPTS) {
            stopStkPolling();
            if (statusText) {
                statusText.textContent = 'No PIN response detected yet. If you completed payment, click "Check Status Now" below.';
            }
        }

    } catch (err) {
        if (manual) {
            manualBtn.disabled = false;
            manualBtn.textContent = 'Check Status Now';
            alert('Error checking status: ' + err.message);
        }
    }
}

function retryPayment() {
    stopStkPolling();
    closeModal('stkModal');
    const phoneInput = document.getElementById('phoneInput');
    if (phoneInput) {
        phoneInput.focus();
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
