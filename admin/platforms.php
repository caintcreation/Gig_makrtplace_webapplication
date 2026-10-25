<?php
// admin/platforms.php - Moderate, Verify, and Share Platform Opportunities
require_once __DIR__ . '/../includes/functions.php';

$adminNav = 'platforms';
$adminTitle = 'Platforms Management';
$db = getDb();

// Handle quick actions: verify, reject, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token expired.');
    } else {
        $action = $_POST['action'] ?? '';
        $platformId = (int)($_POST['platform_id'] ?? 0);

        if ($platformId > 0) {
            if ($action === 'verify') {
                $db->prepare("UPDATE platforms SET verification_status = 'verified' WHERE id = ?")->execute([$platformId]);
                logAudit($_SESSION['user_id'], 'platform_verified', 'Verified platform ID: ' . $platformId);
                setFlash('success', 'Platform approved and marked as verified!');
            } elseif ($action === 'reject') {
                $db->prepare("UPDATE platforms SET verification_status = 'rejected' WHERE id = ?")->execute([$platformId]);
                logAudit($_SESSION['user_id'], 'platform_rejected', 'Rejected platform ID: ' . $platformId);
                setFlash('warning', 'Platform status set to rejected.');
            } elseif ($action === 'delete') {
                $db->prepare("DELETE FROM platforms WHERE id = ?")->execute([$platformId]);
                logAudit($_SESSION['user_id'], 'platform_deleted', 'Deleted platform ID: ' . $platformId);
                setFlash('info', 'Platform permanently deleted.');
            }
        }
    }
    header('Location: platforms.php');
    exit;
}

$statusFilter = trim($_GET['status'] ?? '');
$sql = "
    SELECT p.*, u.username AS creator_username,
           (SELECT COUNT(*) FROM unlock_transactions t WHERE t.platform_id = p.id AND t.status = 'completed') AS paid_unlocks,
           (SELECT COUNT(*) FROM applications a WHERE a.platform_id = p.id) AS app_count
    FROM platforms p
    JOIN users u ON p.user_id = u.id
";
$params = [];
if (!empty($statusFilter)) {
    $sql .= " WHERE p.verification_status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY p.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$platforms = $stmt->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="page-title-row">
    <div>
        <h1 style="font-size: 1.85rem; margin-bottom: 0.25rem;">Task Catalog &amp; Link Distribution</h1>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">
            Verify opportunities, manage activation amounts, and share direct links with users.
        </p>
    </div>
    <div>
        <a href="<?= $appUrl ?>/admin/platform_edit.php" class="btn btn-primary btn-sm">+ Post &amp; Verify Task</a>
    </div>
</div>

<!-- Filter Bar -->
<div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
    <a href="platforms.php" class="btn btn-sm <?= empty($statusFilter) ? 'btn-primary' : 'btn-secondary' ?>">All Tasks (<?= count($platforms) ?>)</a>
    <a href="platforms.php?status=verified" class="btn btn-sm <?= $statusFilter === 'verified' ? 'btn-primary' : 'btn-secondary' ?>">Verified</a>
    <a href="platforms.php?status=pending" class="btn btn-sm <?= $statusFilter === 'pending' ? 'btn-primary' : 'btn-secondary' ?>">Pending Audit</a>
    <a href="platforms.php?status=rejected" class="btn btn-sm <?= $statusFilter === 'rejected' ? 'btn-primary' : 'btn-secondary' ?>">Rejected</a>
</div>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Task Title</th>
                <th>Activation Amount</th>
                <th>Creator</th>
                <th>Verification</th>
                <th>Unlocks</th>
                <th>Share Link</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($platforms)): ?>
                <tr><td colspan="7" style="text-align: center; color: var(--text-secondary);">No tasks found.</td></tr>
            <?php else: ?>
                <?php foreach ($platforms as $p): ?>
                    <?php
                        $directUrl = $appUrl . '/platform.php?id=' . urlencode($p['slug']);
                        $waText = urlencode("Verified paying task on CAINT EARN HUB: " . $p['title'] . " - " . $directUrl);
                    ?>
                    <tr>
                        <td>
                            <strong style="color: var(--text-primary);"><?= e($p['title']) ?></strong>
                            <div style="font-size: 0.78rem; color: var(--text-secondary);">
                                <?= e($p['earning_potential']) ?>
                            </div>
                        </td>
                        <td>
                            <strong style="color: var(--accent);"><?= formatKsh($p['token_price']) ?></strong>
                            <div style="font-size: 0.72rem; color: var(--text-secondary);">Research Fee</div>
                        </td>
                        <td><?= e($p['creator_username']) ?></td>
                        <td>
                            <span class="status-pill status-<?= e($p['verification_status']) ?>">
                                <?= ucfirst(e($p['verification_status'])) ?>
                            </span>
                        </td>
                        <td><strong><?= (int)$p['paid_unlocks'] ?></strong> members</td>
                        <td>
                            <!-- Share Buttons -->
                            <div style="display: flex; gap: 0.35rem; align-items: center;">
                                <button type="button" class="btn btn-secondary btn-sm" style="font-size: 0.75rem; padding: 0.25rem 0.6rem;" onclick="navigator.clipboard.writeText('<?= $directUrl ?>'); alert('Task URL copied!');">
                                    Copy Link
                                </button>
                                <a href="https://wa.me/?text=<?= $waText ?>" target="_blank" rel="noopener" class="btn btn-whatsapp btn-sm" style="font-size: 0.75rem; padding: 0.25rem 0.6rem;">
                                    WhatsApp
                                </a>
                            </div>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="<?= $directUrl ?>" target="_blank" class="btn btn-outline btn-sm">Preview</a>
                            <a href="platform_edit.php?id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>

                            <?php if ($p['verification_status'] === 'pending'): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Verify and publish this task?');">
                                    <?= csrfInput() ?>
                                    <input type="hidden" name="action" value="verify">
                                    <input type="hidden" name="platform_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-primary btn-sm" style="background-color: var(--success); border-color: var(--success);">Approve</button>
                                </form>
                            <?php endif; ?>

                            <form method="POST" style="display: inline;" onsubmit="return confirm('Permanently delete this task?');">
                                <?= csrfInput() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="platform_id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger); border-color: #F8B4B4;">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
