<?php
// track.php - Recover Unlocked Tasks by Mobile Number
require_once __DIR__ . '/includes/functions.php';

$currentNav = 'track';
$pageTitle = 'Recover Unlocked Tasks &middot; CAINT EARN HUB';
$pageDesc = 'Restore your verified direct access links and onboarding instructions using your Safaricom phone number.';

$db = getDb();
$searchPhone = trim($_POST['phone'] ?? $_GET['phone'] ?? '');
$unlockedRecords = [];
$searched = false;

if (!empty($searchPhone)) {
    $searched = true;
    $normPhone = normalizePhone($searchPhone);
    
    $stmt = $db->prepare("
        SELECT t.*, p.title, p.slug, p.short_desc, p.access_url, p.steps_guide, p.task_image
        FROM unlock_transactions t
        JOIN platforms p ON t.platform_id = p.id
        WHERE (t.phone = ? OR t.phone = ?) AND t.status = 'completed'
        ORDER BY t.id DESC
    ");
    $stmt->execute([$normPhone, $searchPhone]);
    $unlockedRecords = $stmt->fetchAll();
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 3rem; margin-bottom: 5rem;">
    <div style="max-width: 680px; margin: 0 auto; text-align: center; margin-bottom: 2.5rem;">
        <h1 style="font-size: 2.2rem; margin-bottom: 0.75rem;">Recover Unlocked Tasks</h1>
        <p style="color: var(--text-secondary); font-size: 1.05rem;">
            Misplaced your verified link or switched phones? Enter the Safaricom mobile number used for the activation token. Your tasks and instructions will be restored instantly.
        </p>
    </div>

    <!-- Lookup Form -->
    <div class="form-card" style="margin: 0 auto 3rem;">
        <form method="POST" action="<?= $appUrl ?>/track.php">
            <?= csrfInput() ?>
            <div class="form-group">
                <label class="form-label" for="trackPhone">Safaricom Mobile Number</label>
                <input type="tel" id="trackPhone" name="phone" value="<?= e($searchPhone) ?>" placeholder="07XXXXXXXX or 01XXXXXXXX" required class="form-control" style="font-size: 1.1rem; text-align: center; letter-spacing: 0.05em;">
                <small class="form-help">Enter the phone number that received the prompt.</small>
            </div>
            <button type="submit" class="btn btn-primary btn-full btn-lg">Search My Unlocked Tasks</button>
        </form>
    </div>

    <!-- Search Results -->
    <?php if ($searched): ?>
        <div style="max-width: 860px; margin: 0 auto;">
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 1.5rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--divider);">
                <h2 style="font-size: 1.4rem;">Restored Tasks</h2>
                <span style="font-size: 0.92rem; color: var(--text-secondary);">
                    Found <strong style="color: var(--text-primary);"><?= count($unlockedRecords) ?></strong> task(s) for <?= e($searchPhone) ?>
                </span>
            </div>

            <?php if (empty($unlockedRecords)): ?>
                <div style="background-color: var(--surface-main); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 3rem 2rem; text-align: center;">
                    <h3 style="font-size: 1.2rem; margin-bottom: 0.5rem;">No confirmed tasks found for this phone number</h3>
                    <p style="color: var(--text-secondary); max-width: 450px; margin: 0 auto 1.5rem; font-size: 0.95rem;">
                        Double-check the mobile number entered. If you need help, reach our customer support at 0714 772 901.
                    </p>
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <a href="<?= $appUrl ?>/platforms.php" class="btn btn-primary">Browse Available Tasks</a>
                        <a href="<?= $appUrl ?>/support.php" class="btn btn-secondary">Contact Support Desk</a>
                    </div>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1.75rem;">
                    <?php foreach ($unlockedRecords as $rec): ?>
                        <div style="background-color: var(--surface-main); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
                                <div>
                                    <h3 style="font-size: 1.35rem; margin-top: 0.25rem;">
                                        <a href="<?= $appUrl ?>/platform.php?id=<?= e($rec['slug']) ?>" style="color: var(--text-primary);"><?= e($rec['title']) ?></a>
                                    </h3>
                                </div>
                                <div style="display: flex; flex-direction: column; align-items: flex-end;">
                                    <span class="badge badge-verified">Access Confirmed</span>
                                    <small style="color: var(--text-secondary); margin-top: 0.35rem; font-size: 0.78rem;">
                                        Ref: <strong><?= e($rec['mpesa_receipt'] ?: 'N/A') ?></strong>
                                    </small>
                                </div>
                            </div>

                            <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 1.25rem;">
                                <?= e($rec['short_desc']) ?>
                            </p>

                            <!-- Access Action Area -->
                            <div style="background-color: var(--surface-subtle); padding: 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.25rem;">
                                <label style="display: block; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 0.5rem;">
                                    Verified Direct Link:
                                </label>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <input type="text" readonly value="<?= e($rec['access_url']) ?>" class="form-control" style="flex: 1; min-width: 260px; font-size: 0.9rem;" id="recUrl_<?= $rec['id'] ?>">
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('recUrl_<?= $rec['id'] ?>').value); alert('Link copied!');">Copy Link</button>
                                    <a href="<?= e($rec['access_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm">
                                        Open Website &rarr;
                                    </a>
                                </div>
                            </div>

                            <?php if (!empty($rec['steps_guide'])): ?>
                                <details style="font-size: 0.9rem; color: var(--text-secondary); cursor: pointer;">
                                    <summary style="font-weight: 600; color: var(--text-primary);">View Step-by-Step Task Instructions</summary>
                                    <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--divider); white-space: pre-line; line-height: 1.6;">
                                        <?= e($rec['steps_guide']) ?>
                                    </div>
                                </details>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
