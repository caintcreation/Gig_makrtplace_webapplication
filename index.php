<?php
// index.php - Public Homepage for CAINT EARN HUB
require_once __DIR__ . '/includes/functions.php';

$currentNav = 'home';
$pageTitle = 'CAINT EARN HUB - Hand-Tested Paying Tasks & Portals';
$pageDesc = 'Explore hand-tested online earning tasks verified by CAINT EARN HUB. View public payout proof and unlock verified direct onboarding links.';

$db = getDb();

// Fetch statistics
$totalPlatforms = (int)$db->query("SELECT COUNT(*) FROM platforms WHERE verification_status = 'verified'")->fetchColumn();
$totalUnlocks = (int)$db->query("SELECT COUNT(*) FROM unlock_transactions WHERE status = 'completed'")->fetchColumn();

// Fetch active search query
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

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-headline">Legitimate Paying Tasks. Hand-Tested in Kenya.</h1>
            <p class="hero-description">
                Online work has too many deceptive sites that waste your time without paying. Every task and platform listed here is personally tested, verified with real payout evidence, and vetted before public listing.
            </p>
            <div class="hero-cta-group">
                <a href="#tasks" class="btn btn-primary btn-lg">Browse Verified Tasks</a>
                <a href="<?= $appUrl ?>/support.php" class="btn btn-secondary btn-lg">Live Support Desk</a>
            </div>

            <!-- Prominent Research & Maintenance Notice -->
            <div style="background-color: var(--surface-main); border: 2px solid var(--accent); border-radius: var(--radius-lg); padding: 1.25rem 1.75rem; max-width: 720px; margin: 0 auto 2rem; text-align: left; box-shadow: var(--shadow-sm);">
                <div style="display: flex; gap: 0.85rem; align-items: flex-start;">
                    <div style="color: var(--accent); font-size: 1.3rem; line-height: 1; margin-top: 2px;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                    </div>
                    <div>
                        <strong style="color: var(--text-primary); font-size: 0.98rem; display: block; margin-bottom: 0.25rem;">
                            Transparent Activation Policy
                        </strong>
                        <p style="color: var(--text-secondary); font-size: 0.92rem; margin: 0; line-height: 1.6;">
                            <strong>Notice:</strong> The small activation amount paid is a one-time fee specifically used to <strong>enhance testing research and fund continuous website maintenance</strong>. It ensures only tested, scam-free tasks are shared.
                        </p>
                    </div>
                </div>
            </div>

            <div class="trust-signals-row">
                <div class="trust-signal-item">
                    <span class="trust-signal-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </span>
                    <span>Hand-Tested Before Listing</span>
                </div>
                <div class="trust-signal-item">
                    <span class="trust-signal-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </span>
                    <span>Real Payment Proofs Shown</span>
                </div>
                <div class="trust-signal-item">
                    <span class="trust-signal-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </span>
                    <span>Direct Safaricom STK Prompt</span>
                </div>
                <div class="trust-signal-item">
                    <span class="trust-signal-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </span>
                    <span>Phone Support: 0714 772 901</span>
                </div>
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-number"><?= $totalPlatforms ?></div>
                <div class="metric-label">Vetted Portals</div>
            </div>
            <div class="metric-card">
                <div class="metric-number"><?= number_format($totalUnlocks + 150) ?>+</div>
                <div class="metric-label">Active Members</div>
            </div>
            <div class="metric-card">
                <div class="metric-number">100%</div>
                <div class="metric-label">Payout Vetting</div>
            </div>
            <div class="metric-card">
                <div class="metric-number">24/7</div>
                <div class="metric-label">Customer Support</div>
            </div>
        </div>
    </div>
</section>

<!-- Main Tasks Section -->
<section class="listing-section" id="tasks">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Available Earning Tasks</h2>
                <p class="section-subtitle">Select any verified task below to inspect payout evidence, instructions, and unlock direct access.</p>
            </div>
            <div>
                <a href="<?= $appUrl ?>/track.php" class="btn btn-outline btn-sm">Recover Access by Phone</a>
            </div>
        </div>

        <!-- Clean Keyword Search (Categories & Complex Filters Removed as Requested) -->
        <form method="GET" action="<?= $appUrl ?>/index.php#tasks" class="search-bar-wrap" style="max-width: 780px; margin: 0 auto 2.5rem;">
            <div class="search-input-field">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#626A61" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search by task title, keyword, or requirements...">
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if (!empty($search)): ?>
                <a href="<?= $appUrl ?>/index.php#tasks" class="btn btn-secondary">Reset</a>
            <?php endif; ?>
        </form>

        <!-- Tasks Grid -->
        <?php if (empty($platforms)): ?>
            <div style="background-color:var(--surface-main); border:1px solid var(--border-light); border-radius:var(--radius-lg); padding:4rem 2rem; text-align:center;">
                <h3 style="font-size:1.3rem; margin-bottom:0.5rem;">No tasks found matching "<?= e($search) ?>"</h3>
                <p style="color:var(--text-secondary); max-width:450px; margin:0 auto 1.5rem;">
                    Try clearing your search keyword to view all currently vetted tasks.
                </p>
                <a href="<?= $appUrl ?>/index.php#tasks" class="btn btn-primary">View All Available Tasks</a>
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
                                <h3 class="card-title">
                                    <a href="<?= $appUrl ?>/platform.php?id=<?= e($p['slug']) ?>"><?= e($p['title']) ?></a>
                                </h3>
                            </div>
                        </div>

                        <div class="card-body">
                            <p class="card-description"><?= e($p['short_desc']) ?></p>

                            <div class="card-attributes">
                                <div class="attribute-row">
                                    <span class="attr-name">Earning Range:</span>
                                    <span class="attr-val"><?= e($p['earning_potential']) ?></span>
                                </div>
                                <div class="attribute-row">
                                    <span class="attr-name">Payout Method:</span>
                                    <span class="attr-val"><?= e($p['payout_method']) ?></span>
                                </div>
                            </div>

                            <div class="card-badges">
                                <span class="badge badge-verified">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    Tested &amp; Paid
                                </span>
                                <span class="badge badge-open"><?= ucfirst(e($p['listing_status'])) ?></span>
                                <span class="badge badge-worktype"><?= e($p['work_type']) ?></span>
                            </div>
                        </div>

                        <div class="card-footer">
                            <div class="token-price-display">
                                <span class="token-label">Activation Fee</span>
                                <span class="token-amount"><?= formatKsh($p['token_price']) ?></span>
                                <small style="font-size: 0.7rem; color: var(--text-secondary);">Research &amp; Maintenance</small>
                            </div>
                            <a href="<?= $appUrl ?>/platform.php?id=<?= e($p['slug']) ?>" class="btn btn-primary btn-sm">
                                View Proof &amp; Unlock
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Why You Can Trust Us Section -->
<section style="background-color: var(--surface-main); padding: 4.5rem 0; border-top: 1px solid var(--divider);">
    <div class="container">
        <div style="max-width: 680px; margin: 0 auto 3rem; text-align: center;">
            <h2 style="font-size: 2rem; margin-bottom: 0.75rem;">Why You Can Trust CAINT EARN HUB</h2>
            <p style="color: var(--text-secondary); font-size: 1.05rem;">
                Built from direct experience with fake online job portals. Our protocol guarantees manual confirmation before any link is shared.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 2rem;">
            <div style="background-color: var(--bg-primary); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem;">
                <div style="width: 44px; height: 44px; border-radius: var(--radius-md); background-color: var(--accent-light); color: var(--accent); display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </div>
                <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem;">Hand-Tested First</h3>
                <p style="font-size: 0.92rem; color: var(--text-secondary); line-height: 1.6;">
                    No task appears here until CAINT personally creates an account, performs the tasks, and successfully withdraws real Kenyan currency.
                </p>
            </div>

            <div style="background-color: var(--bg-primary); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem;">
                <div style="width: 44px; height: 44px; border-radius: var(--radius-md); background-color: var(--accent-light); color: var(--accent); display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                </div>
                <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem;">Public Payment Proofs</h3>
                <p style="font-size: 0.92rem; color: var(--text-secondary); line-height: 1.6;">
                    Inspect authentic transaction receipts and dashboard payout statements directly on each listing detail page before sending any token.
                </p>
            </div>

            <div style="background-color: var(--bg-primary); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem;">
                <div style="width: 44px; height: 44px; border-radius: var(--radius-md); background-color: var(--accent-light); color: var(--accent); display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                </div>
                <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem;">Research &amp; Maintenance Fee</h3>
                <p style="font-size: 0.92rem; color: var(--text-secondary); line-height: 1.6;">
                    The small activation fee covers ongoing testing expenses and server maintenance costs. There are never recurring charges or subscriptions.
                </p>
            </div>

            <div style="background-color: var(--bg-primary); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2rem;">
                <div style="width: 44px; height: 44px; border-radius: var(--radius-md); background-color: var(--accent-light); color: var(--accent); display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </div>
                <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem;">Direct Helpdesk Support</h3>
                <p style="font-size: 0.92rem; color: var(--text-secondary); line-height: 1.6;">
                    Need onboarding guidance or hit a roadblock? Call 0714 772 901 or chat on our live desk for real-time human assistance.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="how-it-works-section" id="how-it-works">
    <div class="container">
        <div style="max-width: 680px; margin: 0 auto; text-align: center;">
            <h2 style="font-size: 2rem; margin-bottom: 0.75rem;">How the Platform Works</h2>
            <p style="color: var(--text-secondary); font-size: 1.05rem;">
                A straightforward path from discovery to legitimate online task earnings.
            </p>
        </div>

        <div class="steps-container">
            <div class="step-item">
                <span class="step-number">01</span>
                <h3 class="step-title">Select a Verified Task</h3>
                <p class="step-desc">
                    Review the task specifications, equipment needed, and payment proofs. Every opportunity is pre-qualified.
                </p>
            </div>

            <div class="step-item">
                <span class="step-number">02</span>
                <h3 class="step-title">Direct Safaricom Prompt</h3>
                <p class="step-desc">
                    Enter your Safaricom mobile number. A prompt arrives directly on your handset without any external redirects.
                </p>
            </div>

            <div class="step-item">
                <span class="step-number">03</span>
                <h3 class="step-title">Instant Access Unlock</h3>
                <p class="step-desc">
                    Your unique registration access link and step-by-step onboarding guidelines appear immediately on your screen.
                </p>
            </div>

            <div class="step-item">
                <span class="step-number">04</span>
                <h3 class="step-title">Permanent Recovery</h3>
                <p class="step-desc">
                    Lost your unlocked URL or switched phones? Use the Recover Access tool with your phone number to restore all links.
                </p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
