<?php
// dashboard/my_listings.php - Client's Posted Listings & Applicant Management
require_once __DIR__ . '/../includes/functions.php';

$user = requireLogin('../login.php');
$db = getDb();
$appUrl = baseAppUrl();
$userId = $user['id'];

$stmt = $db->prepare("
    SELECT p.*, c.name AS category_name,
           (SELECT COUNT(*) FROM applications a WHERE a.platform_id = p.id) AS app_count,
           (SELECT COUNT(*) FROM unlock_transactions t WHERE t.platform_id = p.id AND t.status = 'completed') AS paid_unlocks
    FROM platforms p
    JOIN categories c ON p.category_id = c.id
    WHERE p.user_id = ?
    ORDER BY p.id DESC
");
$stmt->execute([$userId]);
$myListings = $stmt->fetchAll();

$currentNav = 'dashboard';
$pageTitle = 'My Submitted Opportunities';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="margin-top: 2.5rem; margin-bottom: 5rem;">
    <!-- Navigation Tabs -->
    <div style="display: flex; gap: 0.75rem; border-bottom: 1px solid var(--divider); margin-bottom: 2rem; overflow-x: auto; padding-bottom: 0.5rem;">
        <a href="<?= $appUrl ?>/dashboard/index.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">Overview</a>
        <a href="<?= $appUrl ?>/dashboard/my_unlocked.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">My Unlocked Links</a>
        <a href="<?= $appUrl ?>/dashboard/my_applications.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">My Applications</a>
        <a href="<?= $appUrl ?>/dashboard/my_listings.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--accent); color: #fff; font-weight: 600; font-size: 0.92rem;">My Listings (<?= count($myListings) ?>)</a>
        <a href="<?= $appUrl ?>/dashboard/post_gig.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">Post Opportunity</a>
        <a href="<?= $appUrl ?>/dashboard/profile.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">Profile Settings</a>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 2rem; margin-bottom: 0.25rem;">My Submitted Opportunities</h1>
            <p style="color: var(--text-secondary); font-size: 0.95rem;">
                Review the audit status, application count, and member response to your posted platforms.
            </p>
        </div>
        <a href="<?= $appUrl ?>/dashboard/post_gig.php" class="btn btn-primary btn-sm">+ Post New Opportunity</a>
    </div>

    <?php if (empty($myListings)): ?>
        <div style="background-color: var(--surface-main); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 4rem 2rem; text-align: center;">
            <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">No Opportunities Posted Yet</h3>
            <p style="color: var(--text-secondary); max-width: 450px; margin: 0 auto 1.5rem;">
                Looking for Kenyan remote workers, testers, or transcribers? Submit your platform for review and recruitment.
            </p>
            <a href="<?= $appUrl ?>/dashboard/post_gig.php" class="btn btn-primary">Submit First Listing</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Platform</th>
                        <th>Category</th>
                        <th>Verification</th>
                        <th>Unlocks</th>
                        <th>Applications</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($myListings as $item): ?>
                        <tr>
                            <td>
                                <strong><?= e($item['title']) ?></strong>
                                <div style="font-size: 0.78rem; color: var(--text-secondary);">
                                    Added <?= date('M j, Y', strtotime($item['created_at'])) ?>
                                </div>
                            </td>
                            <td><?= e($item['category_name']) ?></td>
                            <td>
                                <span class="status-pill status-<?= e($item['verification_status']) ?>">
                                    <?= ucfirst(e($item['verification_status'])) ?>
                                </span>
                            </td>
                            <td><?= (int)$item['paid_unlocks'] ?></td>
                            <td><strong><?= (int)$item['app_count'] ?></strong> applicants</td>
                            <td>
                                <span class="badge badge-<?= e($item['listing_status']) ?>">
                                    <?= ucfirst(e($item['listing_status'])) ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= $appUrl ?>/platform.php?id=<?= e($item['slug']) ?>" class="btn btn-outline btn-sm">Preview</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
