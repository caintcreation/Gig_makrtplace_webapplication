<?php
// dashboard/my_unlocked.php - List of All Opportunities Unlocked by User
require_once __DIR__ . '/../includes/functions.php';

$user = requireLogin('../login.php');
$db = getDb();
$appUrl = baseAppUrl();

$userId = $user['id'];
$userPhone = $user['phone'] ?? '';

$stmt = $db->prepare("
    SELECT t.*, p.title, p.slug, p.short_desc, p.access_url, p.steps_guide,
           c.name AS category_name
    FROM unlock_transactions t
    JOIN platforms p ON t.platform_id = p.id
    JOIN categories c ON p.category_id = c.id
    WHERE (t.user_id = ? OR (t.phone = ? AND t.phone != '')) AND t.status = 'completed'
    ORDER BY t.id DESC
");
$stmt->execute([$userId, $userPhone]);
$unlocked = $stmt->fetchAll();

$currentNav = 'dashboard';
$pageTitle = 'My Unlocked Direct Links';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="margin-top: 2.5rem; margin-bottom: 5rem;">
    <!-- Navigation Tabs -->
    <div style="display: flex; gap: 0.75rem; border-bottom: 1px solid var(--divider); margin-bottom: 2rem; overflow-x: auto; padding-bottom: 0.5rem;">
        <a href="<?= $appUrl ?>/dashboard/index.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">Overview</a>
        <a href="<?= $appUrl ?>/dashboard/my_unlocked.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--accent); color: #fff; font-weight: 600; font-size: 0.92rem;">My Unlocked Links (<?= count($unlocked) ?>)</a>
        <a href="<?= $appUrl ?>/dashboard/my_applications.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">My Applications</a>
        <?php if ($user['role'] === 'client'): ?>
            <a href="<?= $appUrl ?>/dashboard/my_listings.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">My Listings</a>
            <a href="<?= $appUrl ?>/dashboard/post_gig.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">Post Opportunity</a>
        <?php endif; ?>
        <a href="<?= $appUrl ?>/dashboard/profile.php" style="padding: 0.6rem 1.1rem; border-radius: var(--radius-md); background-color: var(--surface-main); border: 1px solid var(--border-light); color: var(--text-primary); font-weight: 600; font-size: 0.92rem;">Profile Settings</a>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 2rem; margin-bottom: 0.25rem;">My Unlocked Direct Links</h1>
            <p style="color: var(--text-secondary); font-size: 0.95rem;">
                All verified platform onboarding links unlocked with your account or phone number.
            </p>
        </div>
        <a href="<?= $appUrl ?>/platforms.php" class="btn btn-secondary btn-sm">+ Unlock Another Portal</a>
    </div>

    <?php if (empty($unlocked)): ?>
        <div style="background-color: var(--surface-main); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 4rem 2rem; text-align: center;">
            <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">No Unlocked Portals Found</h3>
            <p style="color: var(--text-secondary); max-width: 450px; margin: 0 auto 1.5rem;">
                When you send a token to unlock a hand-tested platform, your direct onboarding link and setup guide will permanently appear here.
            </p>
            <a href="<?= $appUrl ?>/platforms.php" class="btn btn-primary">Browse Verified Directory</a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 1.75rem;">
            <?php foreach ($unlocked as $item): ?>
                <div style="background-color: var(--surface-main); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
                        <div>
                            <span class="category-tag"><?= e($item['category_name']) ?></span>
                            <h2 style="font-size: 1.35rem; margin-top: 0.25rem;">
                                <a href="<?= $appUrl ?>/platform.php?id=<?= e($item['slug']) ?>" style="color: var(--text-primary);"><?= e($item['title']) ?></a>
                            </h2>
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: flex-end;">
                            <span class="badge badge-verified">Access Confirmed</span>
                            <small style="color: var(--text-secondary); margin-top: 0.35rem; font-size: 0.78rem;">
                                M-Pesa Receipt: <strong><?= e($item['mpesa_receipt'] ?: 'N/A') ?></strong> &middot; <?= date('M j, Y', strtotime($item['created_at'])) ?>
                            </small>
                        </div>
                    </div>

                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 1.25rem;">
                        <?= e($item['short_desc']) ?>
                    </p>

                    <!-- Unlocked Link Box -->
                    <div style="background-color: var(--surface-subtle); padding: 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.25rem;">
                        <label style="display: block; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 0.5rem;">
                            Direct Registration URL
                        </label>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <input type="text" readonly value="<?= e($item['access_url']) ?>" class="form-control" style="flex: 1; min-width: 260px;" id="unlockedInput_<?= $item['id'] ?>">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('unlockedInput_<?= $item['id'] ?>').value); alert('Direct link copied!');">Copy Link</button>
                            <a href="<?= e($item['access_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm">
                                Open Website &rarr;
                            </a>
                        </div>
                    </div>

                    <?php if (!empty($item['steps_guide'])): ?>
                        <details style="font-size: 0.9rem; color: var(--text-secondary); cursor: pointer;">
                            <summary style="font-weight: 600; color: var(--text-primary);">View Step-by-Step Instructions</summary>
                            <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--divider); white-space: pre-line; line-height: 1.6;">
                                <?= e($item['steps_guide']) ?>
                            </div>
                        </details>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
