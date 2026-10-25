<?php
// register.php - Member Registration (Default Worker Role)
require_once __DIR__ . '/includes/functions.php';

$currentNav = 'register';
$pageTitle = 'Join CAINT EARN HUB';

if (isLoggedIn()) {
    header('Location: dashboard/index.php');
    exit;
}

$errors = [];
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$displayName = trim($_POST['display_name'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid or expired. Please retry.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Default role is always worker; only admin can upgrade
        $role = 'worker';

        if (empty($username) || strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Username must be at least 3 characters and contain only letters, numbers, and underscores.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid email address.';
        }

        if (empty($phone)) {
            $errors[] = 'Please provide your Safaricom mobile phone number.';
        }

        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($displayName)) {
            $displayName = $username;
        }

        if (empty($errors)) {
            $db = getDb();
            // Check uniqueness
            $chk = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
            $chk->execute([$email, $username]);
            if ($chk->fetch()) {
                $errors[] = 'An account with that email or username already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $ins = $db->prepare("
                    INSERT INTO users (role, email, username, password_hash, phone, status)
                    VALUES ('worker', ?, ?, ?, ?, 'active')
                ");
                $ins->execute([$email, $username, $hash, normalizePhone($phone)]);
                $newUserId = (int)$db->lastInsertId();

                // Create profile record
                $prof = $db->prepare("
                    INSERT INTO user_profiles (user_id, display_name, location)
                    VALUES (?, ?, 'Nairobi, Kenya')
                ");
                $prof->execute([$newUserId, $displayName]);

                logAudit($newUserId, 'user_registered', 'User registered as worker');

                // Auto login
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['role'] = 'worker';

                setFlash('success', 'Your account has been created! Welcome to CAINT EARN HUB.');
                header('Location: dashboard/index.php');
                exit;
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 3rem; margin-bottom: 5rem;">
    <div class="form-card">
        <div style="text-align: center; margin-bottom: 2rem;">
            <img src="<?= $appUrl ?>/assets/images/logo.png" alt="CAINT EARN HUB" style="height: 48px; width: auto; margin-bottom: 0.75rem; border-radius: var(--radius-sm);">
            <h1 style="font-size: 1.8rem; margin-bottom: 0.5rem;">Join CAINT EARN HUB</h1>
            <p style="color: var(--text-secondary); font-size: 0.95rem;">
                Create your member account to unlock verified tasks and track payouts.
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

        <form method="POST" action="<?= $appUrl ?>/register.php">
            <?= csrfInput() ?>

            <div class="form-group">
                <label class="form-label" for="regName">Full Display Name</label>
                <input type="text" id="regName" name="display_name" value="<?= e($displayName) ?>" class="form-control" placeholder="e.g. Samuel Mwangi">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="regUser">Username *</label>
                    <input type="text" id="regUser" name="username" value="<?= e($username) ?>" required class="form-control" placeholder="samuel_m">
                </div>

                <div class="form-group">
                    <label class="form-label" for="regPhone">Safaricom Mobile *</label>
                    <input type="tel" id="regPhone" name="phone" value="<?= e($phone) ?>" required class="form-control" placeholder="07XXXXXXXX">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="regEmail">Email Address *</label>
                <input type="email" id="regEmail" name="email" value="<?= e($email) ?>" required class="form-control" placeholder="you@domain.com">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="regPass">Password *</label>
                    <input type="password" id="regPass" name="password" required class="form-control" placeholder="Min. 6 chars">
                </div>

                <div class="form-group">
                    <label class="form-label" for="regConfirm">Confirm *</label>
                    <input type="password" id="regConfirm" name="confirm_password" required class="form-control" placeholder="Repeat password">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top: 1rem;">
                Complete Registration
            </button>
        </form>

        <div style="margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid var(--divider); text-align: center; font-size: 0.92rem; color: var(--text-secondary);">
            Already have an account? <a href="<?= $appUrl ?>/login.php" style="font-weight: 600;">Sign in here</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
