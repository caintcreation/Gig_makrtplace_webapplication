<?php
// dashboard/my_applications.php - User Applications Tracker
require_once __DIR__ . '/../includes/functions.php';

$user = requireLogin('../login.php');
$db = getDb();
$appUrl = baseAppUrl();
$userId = $user['id'];

$stmt = $db->prepare("
    SELECT a.*, p.title AS platform_title, p.slug AS platform_slug, p.earning_potential,
           c.name AS category_name
    FROM applications a
    JOIN platforms p ON a.platform_id = p.id
    JOIN categories c ON p.category_id = c.id
    WHERE a.user_id = ?
    ORDER BY a.id DESC
");
$stmt->execute([$userId]);
$applications = $stmt->fetchAll();

$currentNav = 'dashboard';
$pageTitle = 'My Submitted Applications';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="margin-top: 2.5rem; margin-bottom: 5rem;">
    <!-- Navigation Tabs -->
    <div style="display: flex; gap: 0.75rem; border-bottom: 1px solid var(--divider); margin-bottom: 2rem; overflow-x: auto; padding-bottom: 0.5rem;">
        <a href="<?= $appUrl ?>/dashboard/index.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">Overview</a>
        <a href="<?= $appUrl ?>/dashboard/my_unlocked.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">My Unlocked Links</a>
        <a href="<?= $appUrl ?>/dashboard/my_applications.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--accent); color: #fff; font-weight: 600; font-size: 0.92rem;">My Applications (<?= count($applications) ?>)</a>
        <?php if ($user['role'] === 'client'): ?>
            <a href="<?= $appUrl ?>/dashboard/my_listings.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">My Listings</a>
            <a href="<?= $appUrl ?>/dashboard/post_gig.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">Post Opportunity</a>
        <?php endif; ?>
        <a href="<?= $appUrl ?>/dashboard/profile.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">Profile Settings</a>
    </div>

    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 2rem; margin-bottom: 0.25rem;">My Submitted Applications</h1>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">
            Track the status of your applications and contributor pitches submitted to platform coordinators.
        </p>
    </div>

    <?php if (empty($applications)): ?>
        <div style="background-color: var(--surface-main); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 4rem 2rem; text-align: center;">
            <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">No Applications Found</h3>
            <p style="color: var(--text-secondary); max-width: 450px; margin: 0 auto 1.5rem;">
                You haven't submitted any direct contributor applications yet.
            </p>
            <a href="<?= $appUrl ?>/platforms.php" class="btn btn-primary">Find Gigs to Apply</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Platform / Opportunity</th>
                        <th>Category</th>
                        <th>Submitted On</th>
                        <th>Status</th>
                        <th>Cover Note Excerpt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td>
                                <a href="<?= $appUrl ?>/platform.php?id=<?= e($app['platform_slug']) ?>" style="font-weight: 700; color: var(--text-primary);">
                                    <?= e($app['platform_title']) ?>
                                </a>
                                <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                    <?= e($app['earning_potential']) ?>
                                </div>
                            </td>
                            <td><?= e($app['category_name']) ?></td>
                            <td><?= date('M j, Y', strtotime($app['created_at'])) ?></td>
                            <td>
                                <span class="status-pill status-<?= e($app['status']) ?>">
                                    <?= ucfirst(e($app['status'])) ?>
                                </span>
                            </td>
                            <td style="max-width: 300px; font-size: 0.85rem; color: var(--text-secondary);">
                                <?= e(substr($app['cover_note'], 0, 90)) ?>...
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
