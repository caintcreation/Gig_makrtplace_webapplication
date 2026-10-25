<?php
// admin/index.php - Administrator Overview Dashboard
require_once __DIR__ . '/../includes/functions.php';

$adminNav = 'dashboard';
$adminTitle = 'Administration Console';

$db = getDb();

// Statistics
$totalPlatforms = (int)$db->query("SELECT COUNT(*) FROM platforms WHERE verification_status = 'verified'")->fetchColumn();
$pendingAudits = (int)$db->query("SELECT COUNT(*) FROM platforms WHERE verification_status = 'pending'")->fetchColumn();

$txStats = $db->query("
    SELECT COUNT(*) AS total_tx, COALESCE(SUM(amount), 0) AS total_revenue 
    FROM unlock_transactions 
    WHERE status = 'completed'
")->fetch();

$totalRevenue = (float)$txStats['total_revenue'];
$totalTransactions = (int)$txStats['total_tx'];

$totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalWorkers = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'worker'")->fetchColumn();
$totalClients = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();

// Recent transactions
$recentTx = $db->query("
    SELECT t.*, p.title AS platform_title
    FROM unlock_transactions t
    JOIN platforms p ON t.platform_id = p.id
    ORDER BY t.id DESC
    LIMIT 6
")->fetchAll();

// Recent audit logs
$recentLogs = $db->query("
    SELECT l.*, u.username
    FROM audit_logs l
    LEFT JOIN users u ON l.user_id = u.id
    ORDER BY l.id DESC
    LIMIT 6
")->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="page-title-row">
    <div>
        <h1 style="font-size: 1.85rem; margin-bottom: 0.25rem;">Marketplace Operations</h1>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">
            Real-time tracking of platform audits, verified payouts, member transactions, and support.
        </p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="<?= $appUrl ?>/admin/platform_edit.php" class="btn btn-primary btn-sm">+ Post &amp; Verify Platform</a>
    </div>
</div>

<!-- Key Metric Cards -->
<div class="stats-grid-admin">
    <div class="stat-box">
        <div class="stat-box-title">Total Verified Platforms</div>
        <div class="stat-box-value" style="color: var(--accent);"><?= $totalPlatforms ?></div>
        <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.35rem;">
            <?= $pendingAudits ?> pending audit review
        </div>
    </div>

    <div class="stat-box">
        <div class="stat-box-title">Total M-Pesa Token Revenue</div>
        <div class="stat-box-value" style="color: var(--success);"><?= formatKsh($totalRevenue) ?></div>
        <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.35rem;">
            From <?= $totalTransactions ?> confirmed unlocks
        </div>
    </div>

    <div class="stat-box">
        <div class="stat-box-title">Registered Members</div>
        <div class="stat-box-value"><?= $totalUsers ?></div>
        <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.35rem;">
            <?= $totalWorkers ?> workers &middot; <?= $totalClients ?> coordinators
        </div>
    </div>

    <div class="stat-box">
        <div class="stat-box-title">Live Support Inquiries</div>
        <div class="stat-box-value" style="color: var(--info);">
            <?= (int)$db->query("SELECT COUNT(*) FROM support_conversations")->fetchColumn() ?>
        </div>
        <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.35rem;">
            <?= $unreadMessages ?> unread visitor messages
        </div>
    </div>
</div>

<!-- Two Column Row: Transactions + Audit Logs -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
    <!-- Recent Transactions -->
    <div style="background-color: var(--surface-main); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.75rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--divider);">
            <h2 style="font-size: 1.2rem; font-weight: 700;">Recent M-Pesa Token Unlocks</h2>
            <a href="<?= $appUrl ?>/admin/transactions.php" style="font-size: 0.85rem; font-weight: 600;">View All Unlocks</a>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Platform</th>
                        <th>Phone</th>
                        <th>Receipt</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentTx)): ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--text-secondary);">No token transactions recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentTx as $tx): ?>
                            <tr>
                                <td><strong><?= e($tx['platform_title']) ?></strong></td>
                                <td><?= e(formatPhoneDisplay($tx['phone'])) ?></td>
                                <td><code><?= e($tx['mpesa_receipt'] ?: 'PENDING') ?></code></td>
                                <td><strong><?= formatKsh($tx['amount']) ?></strong></td>
                                <td>
                                    <span class="status-pill status-<?= e($tx['status']) ?>">
                                        <?= ucfirst(e($tx['status'])) ?>
                                    </span>
                                </td>
                                <td style="font-size: 0.8rem;"><?= timeAgo($tx['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- System Activity Log -->
    <div style="background-color: var(--surface-main); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.75rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--divider);">
            <h2 style="font-size: 1.2rem; font-weight: 700;">Audit Activity</h2>
        </div>

        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <?php foreach ($recentLogs as $log): ?>
                <div style="background-color: var(--surface-subtle); padding: 0.85rem; border-radius: var(--radius-md); font-size: 0.85rem;">
                    <div style="display: flex; justify-content: space-between; font-weight: 700; color: var(--text-primary); margin-bottom: 0.2rem;">
                        <span><?= e($log['action']) ?></span>
                        <span style="font-size: 0.75rem; color: var(--text-secondary);"><?= timeAgo($log['created_at']) ?></span>
                    </div>
                    <div style="color: var(--text-secondary); font-size: 0.8rem;">
                        <?= e($log['details'] ?: 'No additional details') ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
