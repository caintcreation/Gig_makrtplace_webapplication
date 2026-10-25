<?php
// platforms.php - Browse & Discover All Available Verified Tasks
require_once __DIR__ . '/includes/functions.php';

$currentNav = 'platforms';
$pageTitle = 'Available Verified Tasks & Opportunities &middot; CAINT EARN HUB';
$pageDesc = 'Explore hand-tested online earning tasks in Kenya. Hand-tested before listing.';

$db = getDb();
$search = trim($_GET['q'] ?? '');

$sql = "
    SELECT p.*
    FROM platforms p
    WHERE p.verification_status = 'verified'
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (p.title LIKE ? OR p.short_desc LIKE ? OR p.requirements LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY p.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$platforms = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 2.5rem; margin-bottom: 5rem;">
    <!-- Page Header -->
    <div style="text-align: center; max-width: 760px; margin: 0 auto 2rem;">
        <h1 style="font-size: 2.2rem; margin-bottom: 0.5rem;">Available Earning Tasks</h1>
        <p style="color: var(--text-secondary); font-size: 1.05rem;">
            Browse our catalog of vetted digital tasks. The small activation amount paid is dedicated to enhance platform testing research and site maintenance.
        </p>
    </div>

    <!-- Simple Keyword Search -->
    <form method="GET" action="<?= $appUrl ?>/platforms.php" class="search-bar-wrap" style="max-width: 650px; margin: 0 auto 2.5rem;">
        <div class="search-input-field">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#626A61" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search task name, skills, or keywords...">
        </div>
        <button type="submit" class="btn btn-primary">Search Tasks</button>
        <?php if (!empty($search)): ?>
            <a href="<?= $appUrl ?>/platforms.php" class="btn btn-secondary">Reset</a>
        <?php endif; ?>
    </form>

    <!-- Tasks Grid -->
    <?php if (empty($platforms)): ?>
        <div style="background-color: var(--surface-main); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 4rem 2rem; text-align: center;">
            <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">No tasks found matching "<?= e($search) ?>"</h3>
            <p style="color: var(--text-secondary); max-width: 420px; margin: 0 auto 1.5rem;">
                Try clearing your search terms to view all available verified tasks.
            </p>
            <a href="<?= $appUrl ?>/platforms.php" class="btn btn-primary">View All Tasks</a>
        </div>
    <?php else: ?>
        <div class="platforms-grid">
            <?php foreach ($platforms as $p): ?>
                <article class="platform-card">
                    <?php if (!empty($p['task_image'])): ?>
                        <div style="height: 180px; overflow: hidden; background-color: var(--surface-subtle); border-bottom: 1px solid var(--border-light);">
                            <img src="<?= $appUrl . '/' . ltrim(e($p['task_image']), '/') ?>" alt="<?= e($p['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    <?php endif; ?>

                    <div class="card-top">
                        <div class="platform-avatar">
                            <img src="<?= $appUrl ?>/assets/images/logo.png" alt="CAINT" style="width: 28px; height: 28px; object-fit: contain;">
                        </div>
                        <div class="card-meta">
                            <span class="category-tag">Verified Earning Task</span>
                            <h2 class="card-title">
                                <a href="<?= $appUrl ?>/platform.php?id=<?= e($p['slug']) ?>"><?= e($p['title']) ?></a>
                            </h2>
                        </div>
                    </div>

                    <div class="card-body">
                        <p class="card-description"><?= e($p['short_desc']) ?></p>

                        <div class="card-attributes">
                            <div class="attribute-row">
                                <span class="attr-name">Earning Potential:</span>
                                <span class="attr-val"><?= e($p['earning_potential']) ?></span>
                            </div>
                            <div class="attribute-row">
                                <span class="attr-name">Disbursement:</span>
                                <span class="attr-val"><?= e($p['payout_method']) ?></span>
                            </div>
                            <div class="attribute-row">
                                <span class="attr-name">Work Type:</span>
                                <span class="attr-val"><?= e($p['work_type']) ?></span>
                            </div>
                        </div>

                        <div class="card-badges">
                            <span class="badge badge-verified">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                Payout Verified
                            </span>
                            <span class="badge badge-open"><?= ucfirst(e($p['listing_status'])) ?></span>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="token-price-display">
                            <span class="token-label">Activation Fee</span>
                            <span class="token-amount"><?= formatKsh($p['token_price']) ?></span>
                            <small style="font-size: 0.68rem; color: var(--text-secondary);">Research &amp; Maintenance</small>
                        </div>
                        <a href="<?= $appUrl ?>/platform.php?id=<?= e($p['slug']) ?>" class="btn btn-primary btn-sm">
                            Inspect &amp; Unlock
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
