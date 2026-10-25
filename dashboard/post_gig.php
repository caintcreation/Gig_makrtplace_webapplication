<?php
// dashboard/post_gig.php - Submit Task with Picture & Activation Amount (Upgraded Users & Admin Only)
require_once __DIR__ . '/../includes/functions.php';

$user = requireLogin('../login.php');
$db = getDb();
$appUrl = baseAppUrl();

// Only allow admin or upgraded clients
if ($user['role'] !== 'client' && $user['role'] !== 'admin') {
    setFlash('warning', 'Only upgraded content coordinators can post tasks. Please contact admin to upgrade your account.');
    header('Location: index.php');
    exit;
}

$categories = $db->query("SELECT * FROM categories ORDER BY sort_order ASC")->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Please try again.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? ($categories[0]['id'] ?? 1));
        $shortDesc = trim($_POST['short_desc'] ?? '');
        $fullDesc = trim($_POST['full_desc'] ?? '');
        $requirements = trim($_POST['requirements'] ?? '');
        $stepsGuide = trim($_POST['steps_guide'] ?? '');
        $earningPotential = trim($_POST['earning_potential'] ?? '');
        $payoutMethod = trim($_POST['payout_method'] ?? 'M-Pesa');
        $workType = trim($_POST['work_type'] ?? 'Remote');
        $accessUrl = trim($_POST['access_url'] ?? '');
        $tokenPrice = (float)($_POST['token_price'] ?? 50.00);

        // Handle Task Picture Upload
        $taskImagePath = null;
        if (!empty($_FILES['task_image']['name']) && $_FILES['task_image']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['task_image']['tmp_name'];
            $origName = $_FILES['task_image']['name'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ext, $allowedExts, true)) {
                $errors[] = 'Task picture must be a JPG, PNG, or WebP image.';
            } else {
                $destFileName = 'task_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $destPath = __DIR__ . '/../uploads/tasks/' . $destFileName;
                if (move_uploaded_file($tmp, $destPath)) {
                    $taskImagePath = 'uploads/tasks/' . $destFileName;
                } else {
                    $errors[] = 'Failed to upload task image. Check directory permissions.';
                }
            }
        }

        if (empty($title) || strlen($title) < 5) {
            $errors[] = 'Title must be at least 5 characters long.';
        }
        if (empty($shortDesc) || strlen($shortDesc) < 15) {
            $errors[] = 'Please provide a brief summary of the task.';
        }
        if (empty($accessUrl) || !filter_var($accessUrl, FILTER_VALIDATE_URL)) {
            $errors[] = 'Please provide a valid onboarding URL.';
        }

        if (empty($errors)) {
            $slug = slugify($title) . '-' . substr(bin2hex(random_bytes(3)), 0, 5);
            $proofs = json_encode(['proof_submission.png']);
            $status = ($user['role'] === 'admin') ? 'verified' : 'pending';

            $ins = $db->prepare("
                INSERT INTO platforms 
                (slug, title, category_id, user_id, short_desc, full_desc, requirements, steps_guide, earning_potential, payout_method, payout_proofs, task_image, work_type, token_price, access_url, verification_status, listing_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'open')
            ");
            $ins->execute([
                $slug,
                $title,
                $categoryId,
                $user['id'],
                $shortDesc,
                $fullDesc,
                $requirements,
                $stepsGuide,
                $earningPotential ?: 'KSh 1,000 - KSh 3,000 / day',
                $payoutMethod ?: 'M-Pesa',
                $proofs,
                $taskImagePath,
                $workType,
                $tokenPrice > 0 ? $tokenPrice : 50.00,
                $accessUrl,
                $status
            ]);

            logAudit($user['id'], 'task_posted', 'Posted task: ' . $title);

            if ($status === 'verified') {
                setFlash('success', 'Task published successfully! You can now share the link with workers.');
                header('Location: ../platform.php?id=' . urlencode($slug));
            } else {
                setFlash('success', 'Task submitted for audit! Our verification team will review and approve.');
                header('Location: my_listings.php');
            }
            exit;
        }
    }
}

$currentNav = 'dashboard';
$pageTitle = 'Post a Task &middot; CAINT EARN HUB';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="margin-top: 2.5rem; margin-bottom: 5rem;">
    <!-- Navigation Tabs -->
    <div style="display: flex; gap: 0.75rem; border-bottom: 1px solid var(--divider); margin-bottom: 2rem; overflow-x: auto; padding-bottom: 0.5rem;">
        <a href="<?= $appUrl ?>/dashboard/index.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">Overview</a>
        <a href="<?= $appUrl ?>/dashboard/my_unlocked.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">My Unlocked Links</a>
        <a href="<?= $appUrl ?>/dashboard/my_applications.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">My Applications</a>
        <a href="<?= $appUrl ?>/dashboard/my_listings.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">My Listings</a>
        <a href="<?= $appUrl ?>/dashboard/post_gig.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--accent); color: #fff; font-weight: 600; font-size: 0.92rem;">Post Task</a>
        <a href="<?= $appUrl ?>/dashboard/profile.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">Payment &amp; Profile</a>
    </div>

    <div style="max-width: 800px; margin: 0 auto;">
        <div style="margin-bottom: 2rem;">
            <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Post a Task with Picture &amp; Activation Fee</h1>
            <p style="color: var(--text-secondary); font-size: 0.95rem;">
                Specify the task details, attach a banner image, and set the activation amount for members.
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

        <div class="form-card" style="margin: 0; max-width: 100%;">
            <form method="POST" action="<?= $appUrl ?>/dashboard/post_gig.php" enctype="multipart/form-data">
                <?= csrfInput() ?>

                <div class="form-group">
                    <label class="form-label" for="taskTitle">Task / Opportunity Title *</label>
                    <input type="text" id="taskTitle" name="title" required class="form-control" placeholder="e.g. Sheng Audio Annotation & Prompt Review" value="<?= e($_POST['title'] ?? '') ?>">
                </div>

                <!-- Picture Upload -->
                <div class="form-group">
                    <label class="form-label" for="taskImage">Upload Task Picture / Banner (JPG, PNG, WebP)</label>
                    <input type="file" id="taskImage" name="task_image" accept="image/*" class="form-control" style="padding: 0.5rem;">
                    <small class="form-help">Attach a screenshot or banner showing the task.</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="taskShort">Short Summary (Preview) *</label>
                    <textarea id="taskShort" name="short_desc" rows="2" required class="form-control" placeholder="Brief 1-2 sentence description..."><?= e($_POST['short_desc'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="taskFull">Detailed Task Instructions *</label>
                    <textarea id="taskFull" name="full_desc" rows="5" required class="form-control" placeholder="Detailed explanation of the task, how members submit work, and payout schedules..."><?= e($_POST['full_desc'] ?? '') ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label" for="taskEarn">Estimated Member Earning</label>
                        <input type="text" id="taskEarn" name="earning_potential" class="form-control" placeholder="e.g. KSh 1,200 - KSh 3,500 daily" value="<?= e($_POST['earning_potential'] ?? '') ?>">
                    </div>

                    <!-- Activation Amount -->
                    <div class="form-group">
                        <label class="form-label" for="taskToken">Required Activation Fee (KSh) *</label>
                        <input type="number" id="taskToken" name="token_price" min="0" step="5" required class="form-control" value="<?= e($_POST['token_price'] ?? '50.00') ?>">
                        <small class="form-help">Amount required to unlock onboarding instructions.</small>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="taskReq">Skills &amp; Equipment Needed</label>
                    <textarea id="taskReq" name="requirements" rows="3" class="form-control" placeholder="e.g. Android smartphone, good internet, English/Swahili fluency..."><?= e($_POST['requirements'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="taskUrl">Direct Registration URL *</label>
                    <input type="url" id="taskUrl" name="access_url" required class="form-control" placeholder="https://workspace.example.com/join?ref=..." value="<?= e($_POST['access_url'] ?? '') ?>">
                    <small class="form-help">Unlocked to members once activation prompt is approved.</small>
                </div>

                <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top: 1rem;">
                    Submit Task for Verification
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
