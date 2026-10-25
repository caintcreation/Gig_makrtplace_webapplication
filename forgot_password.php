<?php
// forgot_password.php - Password Recovery Request
require_once __DIR__ . '/includes/functions.php';

$currentNav = 'login';
$pageTitle = 'Reset Your Password';
$infoMsg = null;
$resetLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token expired. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $db = getDb();
        $stmt = $db->prepare("SELECT id, username FROM users WHERE email = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', time() + 3600);
            $db->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?")->execute([$token, $expiry, $user['id']]);
            $resetLink = $appUrl . '/reset_password.php?token=' . $token;
            $infoMsg = 'A password recovery link has been generated for your account.';
            logAudit($user['id'], 'password_reset_requested', 'Reset token generated');
        } else {
            $infoMsg = 'If that email is registered in our database, a recovery link has been sent.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 3.5rem; margin-bottom: 5rem;">
    <div class="form-card">
        <div style="text-align: center; margin-bottom: 2rem;">
            <h1 style="font-size: 1.8rem; margin-bottom: 0.5rem;">Reset Password</h1>
            <p style="color: var(--text-secondary); font-size: 0.95rem;">
                Enter your registered email address to receive recovery instructions.
            </p>
        </div>

        <?php if (!empty($infoMsg)): ?>
            <div class="alert alert-info">
                <?= e($infoMsg) ?>
            </div>
            <?php if (!empty($resetLink)): ?>
                <div style="background-color: var(--surface-subtle); padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; word-break: break-all;">
                    <span style="font-size: 0.78rem; text-transform: uppercase; font-weight: 700; color: var(--text-secondary);">Direct Reset URL (Local Demo):</span><br>
                    <a href="<?= e($resetLink) ?>" style="font-size: 0.88rem; font-weight: 600;"><?= e($resetLink) ?></a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="POST" action="<?= $appUrl ?>/forgot_password.php">
            <?= csrfInput() ?>

            <div class="form-group">
                <label class="form-label" for="resetEmail">Email Address</label>
                <input type="email" id="resetEmail" name="email" required class="form-control" placeholder="you@domain.com">
            </div>

            <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top: 1rem;">
                Send Recovery Link
            </button>
        </form>

        <div style="margin-top: 1.5rem; text-align: center;">
            <a href="<?= $appUrl ?>/login.php" style="font-size: 0.9rem;">&larr; Back to Sign In</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
