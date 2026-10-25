<?php
// admin/platform_edit.php - Create or Update Platform Details with Picture Upload
require_once __DIR__ . '/../includes/functions.php';

$adminNav = 'platforms';
$db = getDb();

$platformId = (int)($_GET['id'] ?? 0);
$platform = null;
$isEditing = false;

if ($platformId > 0) {
    $stmt = $db->prepare("SELECT * FROM platforms WHERE id = ? LIMIT 1");
    $stmt->execute([$platformId]);
    $platform = $stmt->fetch();
    if ($platform) {
        $isEditing = true;
    }
}

$adminTitle = $isEditing ? 'Edit Task: ' . $platform['title'] : 'Add New Verified Task';
$categories = $db->query("SELECT * FROM categories ORDER BY sort_order ASC")->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Please retry.';
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
        $tokenPrice = (float)($_POST['token_price'] ?? 50.00);
        $accessUrl = trim($_POST['access_url'] ?? '');
        $verificationStatus = trim($_POST['verification_status'] ?? 'verified');
        $listingStatus = trim($_POST['listing_status'] ?? 'open');

        // Handle Image Upload
        $taskImagePath = $platform['task_image'] ?? null;
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
                }
            }
        }

        if (empty($title)) $errors[] = 'Title cannot be blank.';
        if (empty($accessUrl)) $errors[] = 'Direct onboarding URL is required.';

        if (empty($errors)) {
            if ($isEditing) {
                $up = $db->prepare("
                    UPDATE platforms SET 
                        title = ?, category_id = ?, short_desc = ?, full_desc = ?, requirements = ?, steps_guide = ?,
                        earning_potential = ?, payout_method = ?, task_image = ?, work_type = ?, token_price = ?, access_url = ?,
                        verification_status = ?, listing_status = ?
                    WHERE id = ?
                ");
                $up->execute([
                    $title, $categoryId, $shortDesc, $fullDesc, $requirements, $stepsGuide,
                    $earningPotential, $payoutMethod, $taskImagePath, $workType, $tokenPrice, $accessUrl,
                    $verificationStatus, $listingStatus, $platform['id']
                ]);

                logAudit($_SESSION['user_id'], 'platform_updated', 'Updated task ID: ' . $platform['id']);
                setFlash('success', 'Task details saved successfully.');
                header('Location: platforms.php');
                exit;
            } else {
                $slug = slugify($title) . '-' . substr(bin2hex(random_bytes(3)), 0, 5);
                $proofs = json_encode(['proof_verified_admin.png']);

                $ins = $db->prepare("
                    INSERT INTO platforms 
                    (slug, title, category_id, user_id, short_desc, full_desc, requirements, steps_guide, earning_potential, payout_method, payout_proofs, task_image, work_type, token_price, access_url, verification_status, listing_status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $ins->execute([
                    $slug, $title, $categoryId, $_SESSION['user_id'], $shortDesc, $fullDesc, $requirements, $stepsGuide,
                    $earningPotential ?: 'KSh 1,000 - KSh 3,000 / day',
                    $payoutMethod ?: 'M-Pesa, PayPal',
                    $proofs, $taskImagePath, $workType, $tokenPrice, $accessUrl, $verificationStatus, $listingStatus
                ]);

                logAudit($_SESSION['user_id'], 'platform_created', 'Created task: ' . $title);
                setFlash('success', 'New verified task added successfully.');
                header('Location: platforms.php');
                exit;
            }
        }
    }
}

require_once __DIR__ . '/header.php';
?>

<div style="max-width: 840px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <a href="platforms.php" style="font-size: 0.88rem; font-weight: 600; color: var(--text-secondary);">&larr; Back to Task List</a>
            <h1 style="font-size: 1.85rem; margin-top: 0.5rem;"><?= $isEditing ? 'Edit Task' : 'Add New Task with Picture' ?></h1>
        </div>
        <?php if ($isEditing): ?>
            <a href="<?= $appUrl ?>/platform.php?id=<?= e($platform['slug']) ?>" target="_blank" class="btn btn-outline btn-sm">Preview on Site &rarr;</a>
        <?php endif; ?>
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

    <div style="background-color: var(--surface-main); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2.25rem;">
        <form method="POST" enctype="multipart/form-data">
            <?= csrfInput() ?>

            <div class="form-group">
                <label class="form-label" for="plTitle">Task Opportunity Title *</label>
                <input type="text" id="plTitle" name="title" required class="form-control" value="<?= e($_POST['title'] ?? $platform['title'] ?? '') ?>">
            </div>

            <!-- Task Image Upload -->
            <div class="form-group">
                <label class="form-label" for="plImage">Task Banner / Screenshot (JPG, PNG, WebP)</label>
                <input type="file" id="plImage" name="task_image" accept="image/*" class="form-control" style="padding: 0.5rem;">
                <?php if (!empty($platform['task_image'])): ?>
                    <div style="margin-top: 0.5rem; display: flex; align-items: center; gap: 0.75rem;">
                        <img src="<?= $appUrl . '/' . ltrim(e($platform['task_image']), '/') ?>" alt="Current preview" style="height: 60px; border-radius: 4px; border: 1px solid var(--border-light);">
                        <span style="font-size: 0.8rem; color: var(--text-secondary);">Current task picture</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="plShort">Short Description (Preview Cards) *</label>
                <textarea id="plShort" name="short_desc" rows="2" required class="form-control"><?= e($_POST['short_desc'] ?? $platform['short_desc'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="plFull">Detailed Task Instructions *</label>
                <textarea id="plFull" name="full_desc" rows="6" required class="form-control"><?= e($_POST['full_desc'] ?? $platform['full_desc'] ?? '') ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                <div class="form-group">
                    <label class="form-label" for="plEarn">Earning Potential</label>
                    <input type="text" id="plEarn" name="earning_potential" class="form-control" value="<?= e($_POST['earning_potential'] ?? $platform['earning_potential'] ?? 'KSh 1,200 - KSh 3,500 daily') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="plPayout">Disbursement Method</label>
                    <input type="text" id="plPayout" name="payout_method" class="form-control" value="<?= e($_POST['payout_method'] ?? $platform['payout_method'] ?? 'M-Pesa, PayPal') ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="plReq">Requirements &amp; Hardware</label>
                <textarea id="plReq" name="requirements" rows="3" class="form-control"><?= e($_POST['requirements'] ?? $platform['requirements'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="plSteps">Step-by-Step Task Workflow</label>
                <textarea id="plSteps" name="steps_guide" rows="4" class="form-control"><?= e($_POST['steps_guide'] ?? $platform['steps_guide'] ?? '') ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.25rem;">
                <div class="form-group">
                    <label class="form-label" for="plUrl">Verified Direct Link *</label>
                    <input type="url" id="plUrl" name="access_url" required class="form-control" value="<?= e($_POST['access_url'] ?? $platform['access_url'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="plToken">Activation Amount (KSh) *</label>
                    <input type="number" id="plToken" name="token_price" min="0" step="5" class="form-control" value="<?= (float)($_POST['token_price'] ?? $platform['token_price'] ?? 50.00) ?>">
                    <small class="form-help">Research &amp; site maintenance fee</small>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                <div class="form-group">
                    <label class="form-label" for="plVerif">Verification Status</label>
                    <select id="plVerif" name="verification_status" class="form-control">
                        <?php $curVer = $_POST['verification_status'] ?? $platform['verification_status'] ?? 'verified'; ?>
                        <option value="verified" <?= $curVer === 'verified' ? 'selected' : '' ?>>Verified (Public)</option>
                        <option value="pending" <?= $curVer === 'pending' ? 'selected' : '' ?>>Pending Audit</option>
                        <option value="rejected" <?= $curVer === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="plStatus">Listing Status</label>
                    <select id="plStatus" name="listing_status" class="form-control">
                        <?php $curStat = $_POST['listing_status'] ?? $platform['listing_status'] ?? 'open'; ?>
                        <option value="open" <?= $curStat === 'open' ? 'selected' : '' ?>>Open for Submissions</option>
                        <option value="waitlist" <?= $curStat === 'waitlist' ? 'selected' : '' ?>>Waitlist Active</option>
                        <option value="closed" <?= $curStat === 'closed' ? 'selected' : '' ?>>Closed / Inactive</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top: 1rem;">
                <?= $isEditing ? 'Save Changes' : 'Publish Verified Task' ?>
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
