<?php
// apply.php - Submit Direct Contributor Application / Pitch
require_once __DIR__ . '/includes/functions.php';

$slug = trim($_GET['id'] ?? '');
if (empty($slug)) {
    header('Location: platforms.php');
    exit;
}

$db = getDb();
$stmt = $db->prepare("
    SELECT p.*, c.name AS category_name 
    FROM platforms p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.slug = ? AND p.verification_status = 'verified'
    LIMIT 1
");
$stmt->execute([$slug]);
$platform = $stmt->fetch();

if (!$platform) {
    setFlash('error', 'Platform not found.');
    header('Location: platforms.php');
    exit;
}

$currentNav = 'platforms';
$pageTitle = 'Apply for ' . $platform['title'];
$user = currentUser();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Please try again.';
    } else {
        $name = trim($_POST['applicant_name'] ?? '');
        $email = trim($_POST['applicant_email'] ?? '');
        $phone = trim($_POST['applicant_phone'] ?? '');
        $coverNote = trim($_POST['cover_note'] ?? '');
        $portfolio = trim($_POST['portfolio_links'] ?? '');

        if (empty($name) || strlen($name) < 2) {
            $errors[] = 'Please enter your full name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (empty($phone)) {
            $errors[] = 'Please enter your contact phone number.';
        }
        if (empty($coverNote) || strlen($coverNote) < 20) {
            $errors[] = 'Please write at least 20 characters explaining your experience or relevant skills.';
        }

        if (empty($errors)) {
            $userId = $user ? $user['id'] : null;
            $ins = $db->prepare("
                INSERT INTO applications 
                (platform_id, user_id, applicant_name, applicant_email, applicant_phone, cover_note, portfolio_links, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'submitted')
            ");
            $ins->execute([
                $platform['id'],
                $userId,
                $name,
                $email,
                normalizePhone($phone),
                $coverNote,
                $portfolio
            ]);

            setFlash('success', 'Your application has been successfully submitted! The platform coordinator will review your profile.');
            header('Location: platform.php?id=' . urlencode($platform['slug']));
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 2.5rem; margin-bottom: 5rem;">
    <div style="max-width: 680px; margin: 0 auto;">
        <!-- Header -->
        <div style="margin-bottom: 2rem;">
            <a href="<?= $appUrl ?>/platform.php?id=<?= e($platform['slug']) ?>" style="font-size: 0.88rem; font-weight: 600; color: var(--text-secondary);">&larr; Back to Platform Details</a>
            <h1 style="font-size: 2rem; margin-top: 0.75rem; margin-bottom: 0.5rem;">Submit Contributor Application</h1>
            <p style="color: var(--text-secondary); font-size: 1rem;">
                Applying for: <strong style="color: var(--text-primary);"><?= e($platform['title']) ?></strong> (<?= e($platform['category_name']) ?>)
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

        <!-- Application Form -->
        <div class="form-card" style="margin: 0; max-width: 100%;">
            <form method="POST" action="<?= $appUrl ?>/apply.php?id=<?= urlencode($platform['slug']) ?>">
                <?= csrfInput() ?>

                <div class="form-group">
                    <label class="form-label" for="appName">Full Name *</label>
                    <input type="text" id="appName" name="applicant_name" required class="form-control" value="<?= e($_POST['applicant_name'] ?? $user['display_name'] ?? '') ?>" placeholder="e.g. Grace Wanjiku">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label" for="appEmail">Email Address *</label>
                        <input type="email" id="appEmail" name="applicant_email" required class="form-control" value="<?= e($_POST['applicant_email'] ?? $user['email'] ?? '') ?>" placeholder="you@example.com">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="appPhone">Phone Number *</label>
                        <input type="tel" id="appPhone" name="applicant_phone" required class="form-control" value="<?= e($_POST['applicant_phone'] ?? $user['phone'] ?? '') ?>" placeholder="07XXXXXXXX">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="coverNote">Relevant Experience &amp; Motivation *</label>
                    <textarea id="coverNote" name="cover_note" rows="5" required class="form-control" placeholder="Describe your background with similar digital gigs, your weekly availability, and why you are a great fit..."><?= e($_POST['cover_note'] ?? '') ?></textarea>
                    <small class="form-help">Minimum 20 characters.</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="portfolioLinks">Work Samples / LinkedIn / Portfolio URL (Optional)</label>
                    <input type="url" id="portfolioLinks" name="portfolio_links" class="form-control" value="<?= e($_POST['portfolio_links'] ?? '') ?>" placeholder="https://...">
                </div>

                <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top: 1rem;">
                    Submit Application for Review
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
