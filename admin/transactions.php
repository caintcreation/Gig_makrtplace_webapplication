<?php
// admin/transactions.php - Review All M-Pesa Token Unlock Transactions
require_once __DIR__ . '/../includes/functions.php';

$adminNav = 'transactions';
$adminTitle = 'M-Pesa Token Unlocks';
$db = getDb();

$search = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$sql = "
    SELECT t.*, p.title AS platform_title, p.slug AS platform_slug,
           u.username AS member_username
    FROM unlock_transactions t
    JOIN platforms p ON t.platform_id = p.id
    LEFT JOIN users u ON t.user_id = u.id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (t.phone LIKE ? OR t.checkout_id LIKE ? OR t.mpesa_receipt LIKE ? OR p.title LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (!empty($statusFilter)) {
    $sql .= " AND t.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY t.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Totals
$totalCollected = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM unlock_transactions WHERE status = 'completed'")->fetchColumn();

require_once __DIR__ . '/header.php';
?>

<div class="page-title-row">
    <div>
        <h1 style="font-size: 1.85rem; margin-bottom: 0.25rem;">M-Pesa Token Unlock Audit</h1>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">
            Total Collected to Date: <strong style="color: var(--success);"><?= formatKsh($totalCollected) ?></strong>
        </p>
    </div>
</div>

<!-- Search & Filter Bar -->
<form method="GET" action="transactions.php" style="display: flex; gap: 0.75rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search phone, receipt code, or platform..." class="form-control" style="width: 300px;">
    
    <select name="status" class="form-control" style="width: 160px;">
        <option value="">All Statuses</option>
        <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
    </select>

    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <?php if (!empty($search) || !empty($statusFilter)): ?>
        <a href="transactions.php" class="btn btn-secondary btn-sm">Reset</a>
    <?php endif; ?>
</form>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Phone Number</th>
                <th>Platform Title</th>
                <th>Amount</th>
                <th>M-Pesa Receipt</th>
                <th>Checkout ID</th>
                <th>Status</th>
                <th>Date &amp; Time</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($transactions)): ?>
                <tr><td colspan="8" style="text-align: center; color: var(--text-secondary);">No transaction records found matching query.</td></tr>
            <?php else: ?>
                <?php foreach ($transactions as $tx): ?>
                    <tr>
                        <td>#<?= $tx['id'] ?></td>
                        <td>
                            <strong><?= e(formatPhoneDisplay($tx['phone'])) ?></strong>
                            <?php if ($tx['member_username']): ?>
                                <div style="font-size: 0.75rem; color: var(--text-secondary);">Member: <?= e($tx['member_username']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= $appUrl ?>/platform.php?id=<?= e($tx['platform_slug']) ?>" target="_blank" style="font-weight: 600; color: var(--text-primary);">
                                <?= e($tx['platform_title']) ?>
                            </a>
                        </td>
                        <td><strong><?= formatKsh($tx['amount']) ?></strong></td>
                        <td>
                            <?php if ($tx['mpesa_receipt']): ?>
                                <code><?= e($tx['mpesa_receipt']) ?></code>
                            <?php else: ?>
                                <span style="color: var(--text-secondary);">&mdash;</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size: 0.75rem; font-family: monospace; color: var(--text-secondary);">
                            <?= e($tx['checkout_id']) ?>
                        </td>
                        <td>
                            <span class="status-pill status-<?= e($tx['status']) ?>">
                                <?= ucfirst(e($tx['status'])) ?>
                            </span>
                        </td>
                        <td style="font-size: 0.82rem; white-space: nowrap;">
                            <?= date('M j, Y H:i', strtotime($tx['created_at'])) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
