<?php
// admin/users.php - Manage Marketplace Users & Account Roles
require_once __DIR__ . '/../includes/functions.php';

$adminNav = 'users';
$adminTitle = 'User Management';
$db = getDb();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token expired.');
    } else {
        $action = $_POST['action'] ?? '';
        $targetUserId = (int)($_POST['target_user_id'] ?? 0);

        if ($targetUserId > 0 && $targetUserId !== (int)$_SESSION['user_id']) {
            if ($action === 'toggle_status') {
                $cur = $db->query("SELECT status FROM users WHERE id = {$targetUserId}")->fetchColumn();
                $newStatus = ($cur === 'active') ? 'suspended' : 'active';
                $db->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $targetUserId]);
                logAudit($_SESSION['user_id'], 'user_status_changed', "Changed status of user #{$targetUserId} to {$newStatus}");
                setFlash('info', "User account status updated to {$newStatus}.");
            } elseif ($action === 'change_role') {
                $newRole = $_POST['new_role'] ?? 'worker';
                if (in_array($newRole, ['worker', 'client', 'admin'], true)) {
                    $db->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$newRole, $targetUserId]);
                    logAudit($_SESSION['user_id'], 'user_role_changed', "Changed role of user #{$targetUserId} to {$newRole}");
                    setFlash('success', "User role updated to {$newRole}.");
                }
            }
        }
    }
    header('Location: users.php');
    exit;
}

$roleFilter = trim($_GET['role'] ?? '');
$sql = "
    SELECT u.*, p.display_name, p.location,
           (SELECT COUNT(*) FROM unlock_transactions t WHERE t.user_id = u.id AND t.status = 'completed') AS paid_unlocks,
           (SELECT COUNT(*) FROM applications a WHERE a.user_id = u.id) AS applications_count
    FROM users u
    LEFT JOIN user_profiles p ON u.id = p.user_id
";
$params = [];
if (!empty($roleFilter)) {
    $sql .= " WHERE u.role = ?";
    $params[] = $roleFilter;
}
$sql .= " ORDER BY u.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$usersList = $stmt->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="page-title-row">
    <div>
        <h1 style="font-size: 1.85rem; margin-bottom: 0.25rem;">User &amp; Member Directory</h1>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">
            Inspect registered workers, clients, and manage administrative privileges.
        </p>
    </div>
</div>

<!-- Role Filters -->
<div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem;">
    <a href="users.php" class="btn btn-sm <?= empty($roleFilter) ? 'btn-primary' : 'btn-secondary' ?>">All Users (<?= count($usersList) ?>)</a>
    <a href="users.php?role=worker" class="btn btn-sm <?= $roleFilter === 'worker' ? 'btn-primary' : 'btn-secondary' ?>">Workers / Earners</a>
    <a href="users.php?role=client" class="btn btn-sm <?= $roleFilter === 'client' ? 'btn-primary' : 'btn-secondary' ?>">Clients / Posters</a>
    <a href="users.php?role=admin" class="btn btn-sm <?= $roleFilter === 'admin' ? 'btn-primary' : 'btn-secondary' ?>">Administrators</a>
</div>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Member / Name</th>
                <th>Role</th>
                <th>Phone Number</th>
                <th>Status</th>
                <th>Unlocks</th>
                <th>Applications</th>
                <th>Joined</th>
                <th style="text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usersList as $u): ?>
                <tr>
                    <td>
                        <strong style="color: var(--text-primary);"><?= e($u['display_name'] ?: $u['username']) ?></strong>
                        <div style="font-size: 0.78rem; color: var(--text-secondary);"><?= e($u['email']) ?> &middot; @<?= e($u['username']) ?></div>
                    </td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <?= csrfInput() ?>
                            <input type="hidden" name="action" value="change_role">
                            <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                            <select name="new_role" onchange="this.form.submit()" style="font-size: 0.8rem; padding: 0.25rem 0.5rem; border-radius: var(--radius-sm); border: 1px solid var(--border-light);" <?= $u['id'] === (int)$_SESSION['user_id'] ? 'disabled' : '' ?>>
                                <option value="worker" <?= $u['role'] === 'worker' ? 'selected' : '' ?>>Worker</option>
                                <option value="client" <?= $u['role'] === 'client' ? 'selected' : '' ?>>Client</option>
                                <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </form>
                    </td>
                    <td><?= e(formatPhoneDisplay($u['phone'] ?: 'N/A')) ?></td>
                    <td>
                        <span class="status-pill status-<?= e($u['status']) ?>">
                            <?= ucfirst(e($u['status'])) ?>
                        </span>
                    </td>
                    <td><strong><?= (int)$u['paid_unlocks'] ?></strong></td>
                    <td><?= (int)$u['applications_count'] ?></td>
                    <td style="font-size: 0.82rem;"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                    <td style="text-align: right;">
                        <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Toggle status for this account?');">
                                <?= csrfInput() ?>
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm">
                                    <?= $u['status'] === 'active' ? 'Suspend' : 'Reactivate' ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <span style="font-size: 0.78rem; color: var(--text-secondary); font-style: italic;">Current session</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
