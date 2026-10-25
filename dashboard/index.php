<?php
// dashboard/index.php - Member Overview Dashboard
require_once __DIR__ . '/../includes/functions.php';

$user = requireLogin('../login.php');
$db = getDb();
$appUrl = baseAppUrl();

$userId = $user['id'];
$userPhone = $user['phone'] ?? '';
$canPost = ($user['role'] === 'client' || $user['role'] === 'admin');

// Total unlocked tasks
$unlockedCount = (int)$db->query("
    SELECT COUNT(DISTINCT platform_id) FROM unlock_transactions 
    WHERE (user_id = {$userId} OR (phone = '{$userPhone}' AND phone != '')) AND status = 'completed'
")->fetchColumn();

// Total submitted applications
$appCount = (int)$db->query("SELECT COUNT(*) FROM applications WHERE user_id = {$userId}")->fetchColumn();

// If upgraded poster/client, count listings posted
$listingsCount = 0;
if ($canPost) {
    $listingsCount = (int)$db->query("SELECT COUNT(*) FROM platforms WHERE user_id = {$userId}")->fetchColumn();
}

// Recent unlocked opportunities
$recentUnlocks = $db->query("
    SELECT t.*, p.title, p.slug, p.access_url, p.earning_potential
    FROM unlock_transactions t
    JOIN platforms p ON t.platform_id = p.id
    WHERE (t.user_id = {$userId} OR (t.phone = '{$userPhone}' AND t.phone != '')) AND t.status = 'completed'
    ORDER BY t.id DESC
    LIMIT 4
")->fetchAll();

// Recent applications
$recentApps = $db->query("
    SELECT a.*, p.title AS platform_title, p.slug AS platform_slug
    FROM applications a
    JOIN platforms p ON a.platform_id = p.id
    WHERE a.user_id = {$userId}
    ORDER BY a.id DESC
    LIMIT 4
")->fetchAll();

$currentNav = 'dashboard';
$pageTitle = 'Member Dashboard &middot; CAINT EARN HUB';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="margin-top: 2.5rem; margin-bottom: 5rem;">
    <!-- Welcome Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 2.1rem; margin-bottom: 0.25rem;">
                Hello, <?= e($user['display_name'] ?: $user['username']) ?>
            </h1>
            <p style="color: var(--text-secondary); font-size: 0.95rem;">
                Role: <strong style="text-transform: capitalize; color: var(--text-primary);"><?= e($user['role']) ?></strong> 
                <?php if ($canPost): ?>
                    <span class="badge badge-verified" style="margin-left: 0.5rem;">Content Creator (Upgraded)</span>
                <?php endif; ?>
                &middot; Member since <?= date('M Y', strtotime($user['created_at'])) ?>
            </p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="<?= $appUrl ?>/dashboard/profile.php" class="btn btn-secondary btn-sm">Payout Settings</a>
            <?php if ($canPost): ?>
                <a href="<?= $appUrl ?>/dashboard/post_gig.php" class="btn btn-primary btn-sm">+ Post New Task</a>
            <?php else: ?>
                <a href="<?= $appUrl ?>/platforms.php" class="btn btn-primary btn-sm">Browse Tasks</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Metrics -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
        <div style="background-color: var(--surface-main); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.5rem;">
            <div style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 0.5rem;">
                Unlocked Tasks
            </div>
            <div style="font-family: var(--font-heading); font-size: 2rem; font-weight: 800; color: var(--accent);">
                <?= $unlockedCount ?>
            </div>
        </div>

        <div style="background-color: var(--surface-main); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.5rem;">
            <div style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 0.5rem;">
                Applications Submitted
            </div>
            <div style="font-family: var(--font-heading); font-size: 2rem; font-weight: 800; color: var(--text-primary);">
                <?= $appCount ?>
            </div>
        </div>

        <?php if ($canPost): ?>
            <div style="background-color: var(--surface-main); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.5rem;">
                <div style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 0.5rem;">
                    My Posted Tasks
                </div>
                <div style="font-family: var(--font-heading); font-size: 2rem; font-weight: 800; color: var(--text-primary);">
                    <?= $listingsCount ?>
                </div>
            </div>
        <?php endif; ?>

        <div style="background-color: var(--surface-main); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.5rem;">
            <div style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 0.5rem;">
                Membership Status
            </div>
            <div style="font-size: 1.15rem; font-weight: 700; color: var(--success); margin-top: 0.5rem; display: flex; align-items: center; gap: 0.4rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Active Member
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div style="display: flex; gap: 0.75rem; border-bottom: 1px solid var(--divider); margin-bottom: 2rem; overflow-x: auto; padding-bottom: 0.5rem;">
        <a href="<?= $appUrl ?>/dashboard/index.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--accent); color: #fff; font-weight: 600; font-size: 0.92rem;">Overview</a>
        <a href="<?= $appUrl ?>/dashboard/my_unlocked.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">My Unlocked Links (<?= $unlockedCount ?>)</a>
        <a href="<?= $appUrl ?>/dashboard/my_applications.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">My Applications (<?= $appCount ?>)</a>
        <?php if ($canPost): ?>
            <a href="<?= $appUrl ?>/dashboard/my_listings.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">My Listings (<?= $listingsCount ?>)</a>
            <a href="<?= $appUrl ?>/dashboard/post_gig.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">Post Task</a>
        <?php endif; ?>
        <a href="<?= $appUrl ?>/dashboard/profile.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">Payment &amp; Profile</a>
    </div>

    <!-- Main Grid -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Unlocked Opportunities -->
        <div style="background-color: var(--surface-main); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.75rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--divider);">
                <h2 style="font-size: 1.2rem; font-weight: 700;">My Unlocked Direct Links</h2>
                <a href="<?= $appUrl ?>/dashboard/my_unlocked.php" style="font-size: 0.85rem; font-weight: 600;">View All</a>
            </div>

            <?php if (empty($recentUnlocks)): ?>
                <div style="text-align: center; padding: 2.5rem 1rem; color: var(--text-secondary);">
                    <p style="font-size: 0.95rem; margin-bottom: 1rem;">You haven't unlocked any verified tasks yet.</p>
                    <a href="<?= $appUrl ?>/platforms.php" class="btn btn-secondary btn-sm">Explore Tasks</a>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($recentUnlocks as $u): ?>
                        <div style="background-color: var(--surface-subtle); padding: 1rem; border-radius: var(--radius-md); display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                            <div>
                                <div style="font-weight: 700; font-size: 0.95rem;">
                                    <a href="<?= $appUrl ?>/platform.php?id=<?= e($u['slug']) ?>" style="color: var(--text-primary);"><?= e($u['title']) ?></a>
                                </div>
                                <div style="font-size: 0.78rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                    Ref: <?= e($u['mpesa_receipt'] ?: 'N/A') ?>
                                </div>
                            </div>
                            <a href="<?= e($u['access_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm" style="white-space: nowrap;">
                                Open Link &rarr;
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Submitted Applications -->
        <div style="background-color: var(--surface-main); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.75rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--divider);">
                <h2 style="font-size: 1.2rem; font-weight: 700;">My Application Statuses</h2>
                <a href="<?= $appUrl ?>/dashboard/my_applications.php" style="font-size: 0.85rem; font-weight: 600;">View All</a>
            </div>

            <?php if (empty($recentApps)): ?>
                <div style="text-align: center; padding: 2.5rem 1rem; color: var(--text-secondary);">
                    <p style="font-size: 0.95rem; margin-bottom: 1rem;">No applications submitted yet.</p>
                    <a href="<?= $appUrl ?>/platforms.php" class="btn btn-secondary btn-sm">Find Tasks</a>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($recentApps as $app): ?>
                        <div style="background-color: var(--surface-subtle); padding: 1rem; border-radius: var(--radius-md); display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: 700; font-size: 0.95rem;">
                                    <?= e($app['platform_title']) ?>
                                </div>
                                <div style="font-size: 0.78rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                    Submitted <?= timeAgo($app['created_at']) ?>
                                </div>
                            </div>
                            <span class="status-pill status-<?= e($app['status']) ?>">
                                <?= ucfirst(e($app['status'])) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
