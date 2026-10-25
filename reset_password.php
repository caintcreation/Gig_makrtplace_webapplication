<?php
// reset_password.php - Complete Password Reset
require_once __DIR__ . '/includes/functions.php';

$currentNav = 'login';
$pageTitle = 'Create New Password';
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

$db = getDb();
$stmt = $db->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry > NOW() AND status = 'active' LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user && empty($_POST)) {
    setFlash('error', 'The reset link is invalid or has expired.');
    header('Location: forgot_password.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token expired. Please try again.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $db->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?")->execute([$hash, $user['id']]);
            logAudit($user['id'], 'password_reset_completed', 'Password updated successfully');
            setFlash('success', 'Your password has been successfully updated! Please sign in.');
            header('Location: login.php');
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 3.5rem; margin-bottom: 5rem;">
    <div class="form-card">
        <div style="text-align: center; margin-bottom: 2rem;">
            <h1 style="font-size: 1.8rem; margin-bottom: 0.5rem;">New Password</h1>
            <p style="color: var(--text-secondary); font-size: 0.95rem;">
                Set a secure password for user <strong><?= e($user['username']) ?></strong>
            </p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= $appUrl ?>/reset_password.php">
            <?= csrfInput() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">

            <div class="form-group">
                <label class="form-label" for="newPass">New Password</label>
                <input type="password" id="newPass" name="password" required class="form-control" placeholder="Min. 6 chars">
            </div>

            <div class="form-group">
                <label class="form-label" for="confirmPass">Confirm Password</label>
                <input type="password" id="confirmPass" name="confirm_password" required class="form-control" placeholder="Repeat new password">
            </div>

            <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top: 1rem;">
                Save New Password
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
