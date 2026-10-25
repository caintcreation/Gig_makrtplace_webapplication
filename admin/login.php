<?php
// admin/login.php - Dedicated Administrator Login for CAINT EARN HUB
require_once __DIR__ . '/../includes/functions.php';

$user = currentUser();
if ($user && $user['role'] === 'admin') {
    header('Location: index.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token expired. Please reload.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $db = getDb();
        $stmt = $db->prepare("
            SELECT * FROM users 
            WHERE (username = ? OR email = ?) AND role = 'admin' AND status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['role'] = 'admin';

            logAudit($admin['id'], 'admin_login', 'Admin logged into control console');
            setFlash('success', 'Welcome to CAINT EARN HUB Administration.');
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid administrative credentials.';
            logAudit(null, 'failed_admin_login', 'Attempted admin login with identifier: ' . $username);
        }
    }
}
$appUrl = baseAppUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sign In &middot; CAINT EARN HUB</title>
    <link rel="icon" type="image/png" href="<?= $appUrl ?>/assets/images/logo.png">
    <link rel="stylesheet" href="<?= $appUrl ?>/assets/css/main.css">
</head>
<body style="background-color: var(--bg-primary); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1.5rem;">

<div style="background-color: var(--surface-main); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2.5rem; width: 100%; max-width: 440px; box-shadow: var(--shadow-md);">
    <div style="text-align: center; margin-bottom: 2rem;">
        <img src="<?= $appUrl ?>/assets/images/logo.png" alt="CAINT EARN HUB" style="height: 48px; width: auto; margin-bottom: 0.75rem; border-radius: var(--radius-sm);">
        <h1 style="font-size: 1.6rem; margin-bottom: 0.25rem;">Admin Control</h1>
        <p style="color: var(--text-secondary); font-size: 0.88rem;">
            CAINT EARN HUB Management Portal
        </p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= $appUrl ?>/admin/login.php">
        <?= csrfInput() ?>

        <div class="form-group">
            <label class="form-label" for="admUser">Admin Email or Username</label>
            <input type="text" id="admUser" name="username" required autofocus class="form-control" placeholder="caintcreatin@gmail.com">
        </div>

        <div class="form-group">
            <label class="form-label" for="admPass">Password</label>
            <input type="password" id="admPass" name="password" required class="form-control" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top: 1rem;">
            Authenticate Admin
        </button>
    </form>

    <div style="margin-top: 1.75rem; text-align: center;">
        <a href="<?= $appUrl ?>/index.php" style="font-size: 0.88rem; color: var(--text-secondary);">&larr; Return to Public Website</a>
    </div>
</div>

</body>
</html>
