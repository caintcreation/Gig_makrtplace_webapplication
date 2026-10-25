<?php
// setup.php - Safe Database Initialization and Migration Tool for CAINT EARN HUB
require_once __DIR__ . '/includes/db.php';

$message = '';
$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = getDb();

        // 1. Run Schema
        $schemaSql = file_get_contents(__DIR__ . '/database/schema.sql');
        $db->exec($schemaSql);

        // 2. Run Seeds
        $seedSql = file_get_contents(__DIR__ . '/database/seed.sql');
        $db->exec($seedSql);

        $status = 'success';
        $message = 'Database schema and seed records successfully executed!';
    } catch (Exception $e) {
        $status = 'error';
        $message = 'Error executing setup: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup &amp; Migration &middot; CAINT EARN HUB</title>
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body style="background-color: var(--bg-primary); padding: 3rem 1.5rem;">

<div style="max-width: 640px; margin: 0 auto; background-color: var(--surface-main); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2.5rem; box-shadow: var(--shadow-md);">
    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
        <img src="assets/images/logo.png" alt="CAINT EARN HUB" style="height: 40px; width: auto; border-radius: 4px;">
        <div>
            <h1 style="font-size: 1.5rem;">Database Setup &amp; Migration</h1>
            <p style="color: var(--text-secondary); font-size: 0.88rem;">Automated MySQL tables migration and default seeds</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $status ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <p style="color: var(--text-primary); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.5rem;">
        This tool connects to your MySQL database and automatically creates all required tables, verified tasks, and the administrator account.
    </p>

    <div style="background-color: var(--surface-subtle); padding: 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-size: 0.88rem; line-height: 1.6;">
        <strong>Administrator Account:</strong><br>
        The administrator account is created from the imported seed data. Change its password immediately after setup.<br>
        &bull; <strong>Admin Portal:</strong> <code>/admin/login.php</code> (Kept private and unlinked from the main website)
    </div>

    <form method="POST">
        <button type="submit" class="btn btn-primary btn-full btn-lg">
            Initialize Database Now
        </button>
    </form>

    <div style="margin-top: 1.5rem; text-align: center; display: flex; justify-content: center; gap: 1.5rem;">
        <a href="index.php" style="font-size: 0.9rem;">&larr; View Website</a>
        <a href="admin/login.php" style="font-size: 0.9rem;">Admin Portal &rarr;</a>
    </div>
</div>

</body>
</html>
