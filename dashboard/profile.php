<?php
// dashboard/profile.php - Edit Profile & Payment / Payout Uploads
require_once __DIR__ . '/../includes/functions.php';

$user = requireLogin('../login.php');
$db = getDb();
$appUrl = baseAppUrl();
$userId = $user['id'];
$canPost = ($user['role'] === 'client' || $user['role'] === 'admin');

$stmt = $db->prepare("SELECT * FROM user_profiles WHERE user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

if (!$profile) {
    $db->prepare("INSERT INTO user_profiles (user_id, display_name) VALUES (?, ?)")->execute([$userId, $user['username']]);
    $stmt->execute([$userId]);
    $profile = $stmt->fetch();
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Please retry.';
    } else {
        $displayName = trim($_POST['display_name'] ?? '');
        $headline = trim($_POST['headline'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $skills = trim($_POST['skills'] ?? '');
        $location = trim($_POST['location'] ?? 'Nairobi, Kenya');
        $availability = trim($_POST['availability'] ?? 'Available now');
        $hourlyRate = (float)($_POST['hourly_rate'] ?? 0.00);
        $portfolioUrl = trim($_POST['portfolio_url'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $payoutMethod = trim($_POST['payout_method'] ?? 'M-Pesa');
        $payoutDetails = trim($_POST['payout_details'] ?? '');

        // Handle Payment Proof / ID Upload
        $paymentProofPath = $profile['payment_proof'] ?? null;
        if (!empty($_FILES['payment_proof_file']['name']) && $_FILES['payment_proof_file']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['payment_proof_file']['tmp_name'];
            $origName = $_FILES['payment_proof_file']['name'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'pdf'];

            if (!in_array($ext, $allowedExts, true)) {
                $errors[] = 'Payment proof file must be an image (JPG, PNG) or PDF document.';
            } else {
                $destFileName = 'proof_' . $userId . '_' . time() . '.' . $ext;
                $destPath = __DIR__ . '/../uploads/proofs/' . $destFileName;
                if (move_uploaded_file($tmp, $destPath)) {
                    $paymentProofPath = 'uploads/proofs/' . $destFileName;
                } else {
                    $errors[] = 'Could not save proof upload. Check directory permissions.';
                }
            }
        }

        if (empty($displayName)) {
            $errors[] = 'Display name cannot be empty.';
        }

        if (empty($errors)) {
            $upProf = $db->prepare("
                UPDATE user_profiles 
                SET display_name = ?, headline = ?, bio = ?, skills = ?, location = ?, availability = ?,
                    hourly_rate = ?, portfolio_url = ?, payout_method = ?, payout_details = ?, payment_proof = ?
                WHERE user_id = ?
            ");
            $upProf->execute([
                $displayName,
                $headline,
                $bio,
                $skills,
                $location,
                $availability,
                $hourlyRate,
                $portfolioUrl,
                $payoutMethod,
                $payoutDetails,
                $paymentProofPath,
                $userId
            ]);

            if (!empty($phone)) {
                $db->prepare("UPDATE users SET phone = ? WHERE id = ?")->execute([normalizePhone($phone), $userId]);
            }

            logAudit($userId, 'profile_updated', 'Updated profile and payout settings');
            setFlash('success', 'Your profile and payout preferences have been saved successfully.');
            header('Location: profile.php');
            exit;
        }
    }
}

$currentNav = 'dashboard';
$pageTitle = 'Payment & Profile Settings &middot; CAINT EARN HUB';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="margin-top: 2.5rem; margin-bottom: 5rem;">
    <!-- Navigation Tabs -->
    <div style="display: flex; gap: 0.75rem; border-bottom: 1px solid var(--divider); margin-bottom: 2rem; overflow-x: auto; padding-bottom: 0.5rem;">
        <a href="<?= $appUrl ?>/dashboard/index.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">Overview</a>
        <a href="<?= $appUrl ?>/dashboard/my_unlocked.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">My Unlocked Links</a>
        <a href="<?= $appUrl ?>/dashboard/my_applications.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">My Applications</a>
        <?php if ($canPost): ?>
            <a href="<?= $appUrl ?>/dashboard/my_listings.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">My Listings</a>
            <a href="<?= $appUrl ?>/dashboard/post_gig.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">Post Task</a>
        <?php endif; ?>
        <a href="<?= $appUrl ?>/dashboard/profile.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--accent); color: #fff; font-weight: 600; font-size: 0.92rem;">Payment &amp; Profile</a>
    </div>

    <div style="max-width: 780px; margin: 0 auto;">
        <div style="margin-bottom: 2rem;">
            <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Payment Preferences &amp; Profile</h1>
            <p style="color: var(--text-secondary); font-size: 0.95rem;">
                Configure where you receive earnings and upload mode of payment verification.
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
            <form method="POST" action="<?= $appUrl ?>/dashboard/profile.php" enctype="multipart/form-data">
                <?= csrfInput() ?>

                <!-- Mode of Payment Upload Section -->
                <h2 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1.25rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--divider); color: var(--accent);">
                    Mode of Payment &amp; Payout Information
                </h2>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label" for="payMethod">Preferred Payment Method</label>
                        <select id="payMethod" name="payout_method" class="form-control">
                            <?php $curMethod = $profile['payout_method'] ?? 'M-Pesa'; ?>
                            <option value="M-Pesa" <?= $curMethod === 'M-Pesa' ? 'selected' : '' ?>>Safaricom M-Pesa</option>
                            <option value="Airtel Money" <?= $curMethod === 'Airtel Money' ? 'selected' : '' ?>>Airtel Money</option>
                            <option value="Bank Transfer" <?= $curMethod === 'Bank Transfer' ? 'selected' : '' ?>>Local Bank Transfer</option>
                            <option value="PayPal" <?= $curMethod === 'PayPal' ? 'selected' : '' ?>>PayPal</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="payDetails">Payout Account / Number</label>
                        <input type="text" id="payDetails" name="payout_details" class="form-control" placeholder="07XXXXXXXX or Bank Account Details" value="<?= e($profile['payout_details'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="proofFile">Upload Payment Proof / Identification Document</label>
                    <input type="file" id="proofFile" name="payment_proof_file" accept="image/*,.pdf" class="form-control" style="padding: 0.5rem;">
                    <small class="form-help">Upload an M-Pesa statement screenshot or identification copy if manual verification is requested.</small>

                    <?php if (!empty($profile['payment_proof'])): ?>
                        <div style="margin-top: 0.75rem; background-color: var(--surface-subtle); padding: 0.75rem; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: space-between;">
                            <span style="font-size: 0.82rem; color: var(--text-primary); font-weight: 600;">
                                Current File: <?= e(basename($profile['payment_proof'])) ?>
                            </span>
                            <a href="<?= $appUrl . '/' . ltrim(e($profile['payment_proof']), '/') ?>" target="_blank" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;">View Upload</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Personal & Professional Information -->
                <h2 style="font-size: 1.15rem; font-weight: 700; margin: 2rem 0 1.25rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--divider);">
                    Member Profile Information
                </h2>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label" for="profDisplay">Display Name *</label>
                        <input type="text" id="profDisplay" name="display_name" required class="form-control" value="<?= e($profile['display_name']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="profPhone">Safaricom Mobile Number</label>
                        <input type="tel" id="profPhone" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>" placeholder="07XXXXXXXX">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="profHead">Professional Headline / Bio</label>
                    <input type="text" id="profHead" name="headline" class="form-control" placeholder="e.g. Remote Data Annotation &amp; Video Review Specialist" value="<?= e($profile['headline'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="profSkills">Skills (Comma-separated)</label>
                    <input type="text" id="profSkills" name="skills" class="form-control" placeholder="e.g. Swahili Transcription, UGC Video, Email Verification" value="<?= e($profile['skills'] ?? '') ?>">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label" for="profLoc">County / City</label>
                        <input type="text" id="profLoc" name="location" class="form-control" value="<?= e($profile['location'] ?? 'Nairobi, Kenya') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="profAvail">Availability</label>
                        <select id="profAvail" name="availability" class="form-control">
                            <option value="Available now" <?= ($profile['availability'] ?? '') === 'Available now' ? 'selected' : '' ?>>Available now (Daily)</option>
                            <option value="Part-time" <?= ($profile['availability'] ?? '') === 'Part-time' ? 'selected' : '' ?>>Part-time (Evenings / Weekends)</option>
                            <option value="Flexible" <?= ($profile['availability'] ?? '') === 'Flexible' ? 'selected' : '' ?>>Flexible / Gig-based</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top: 1rem;">
                    Save Payment &amp; Profile Details
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
