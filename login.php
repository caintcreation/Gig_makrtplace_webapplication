<?php
// login.php - Member Authentication
require_once __DIR__ . '/includes/functions.php';

$currentNav = 'login';
$pageTitle = 'Sign In &middot; CAINT EARN HUB';

if (isLoggedIn()) {
    $user = currentUser();
    if ($user['role'] === 'admin') {
        header('Location: admin/index.php');
    } else {
        header('Location: dashboard/index.php');
    }
    exit;
}

$errors = [];
$identifier = trim($_POST['identifier'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Please try again.';
    } else {
        $password = $_POST['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            $errors[] = 'Please provide both your email/username and password.';
        } else {
            $db = getDb();
            $stmt = $db->prepare("
                SELECT * FROM users 
                WHERE (email = ? OR username = ?) AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$identifier, $identifier]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];

                logAudit($user['id'], 'user_login', 'User logged in');
                setFlash('success', 'Welcome back, ' . ($user['username']) . '!');

                if ($user['role'] === 'admin') {
                    header('Location: admin/index.php');
                } else {
                    header('Location: dashboard/index.php');
                }
                exit;
            } else {
                $errors[] = 'Invalid email/username or password.';
                logAudit(null, 'failed_login_attempt', 'Attempted login with identifier: ' . $identifier);
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 3.5rem; margin-bottom: 5rem;">
    <div class="form-card">
        <div style="text-align: center; margin-bottom: 2rem;">
            <img src="<?= $appUrl ?>/assets/images/logo.png" alt="CAINT EARN HUB" style="height: 48px; width: auto; margin-bottom: 0.75rem; border-radius: var(--radius-sm);">
            <h1 style="font-size: 1.8rem; margin-bottom: 0.5rem;">Sign In</h1>
            <p style="color: var(--text-secondary); font-size: 0.95rem;">
                Access your unlocked tasks, recovery keys, and member dashboard.
            </p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul style="padding-left: 1.25rem;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $appUrl ?>/login.php">
            <?= csrfInput() ?>

            <div class="form-group">
                <label class="form-label" for="loginIdentifier">Email or Username</label>
                <input type="text" id="loginIdentifier" name="identifier" value="<?= e($identifier) ?>" required class="form-control" autofocus placeholder="username or email">
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: baseline;">
                    <label class="form-label" for="loginPass">Password</label>
                    <a href="<?= $appUrl ?>/forgot_password.php" style="font-size: 0.82rem; color: var(--accent);">Forgot password?</a>
                </div>
                <input type="password" id="loginPass" name="password" required class="form-control" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
            </div>

            <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top: 1rem;">
                Sign In
            </button>
        </form>

        <div style="margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid var(--divider); text-align: center; font-size: 0.92rem; color: var(--text-secondary);">
            Don't have an account yet? <a href="<?= $appUrl ?>/register.php" style="font-weight: 600;">Create one now</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
