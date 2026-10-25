<?php
// admin/header.php - Admin Panel Navigation & Layout
require_once __DIR__ . '/../includes/functions.php';

$admin = requireRole('admin', 'login.php');
$db = getDb();
$appUrl = baseAppUrl();
$adminNav = $adminNav ?? 'dashboard';

// Count unread support messages
$unreadMessages = (int)$db->query("
    SELECT COUNT(*) FROM support_messages 
    WHERE sender_type = 'visitor' AND is_read = 0
")->fetchColumn();

// Count pending platforms
$pendingPlatformsCount = (int)$db->query("
    SELECT COUNT(*) FROM platforms WHERE verification_status = 'pending'
")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($adminTitle ?? 'Admin Control') ?> &middot; CAINT EARN HUB</title>
    <link rel="icon" type="image/png" href="<?= $appUrl ?>/assets/images/logo.png">
    <link rel="stylesheet" href="<?= $appUrl ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= $appUrl ?>/assets/css/admin.css">
</head>
<body>

<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-brand">
            <img src="<?= $appUrl ?>/assets/images/logo.png" alt="CAINT" style="height: 32px; width: auto; border-radius: var(--radius-sm);">
            <div>
                <strong style="color: #FFFFFF; font-size: 0.95rem; letter-spacing: 0.05em;">CAINT EARN HUB</strong>
                <div style="font-size: 0.72rem; color: #8A9289;">Admin Workspace</div>
            </div>
        </div>

        <ul class="sidebar-nav">
            <li>
                <a href="<?= $appUrl ?>/admin/index.php" class="sidebar-link <?= $adminNav === 'dashboard' ? 'active' : '' ?>">
                    <span>Overview</span>
                </a>
            </li>
            <li>
                <a href="<?= $appUrl ?>/admin/platforms.php" class="sidebar-link <?= $adminNav === 'platforms' ? 'active' : '' ?>">
                    <span>Task Catalog</span>
                    <?php if ($pendingPlatformsCount > 0): ?>
                        <span style="background-color: var(--warning); color: #fff; font-size: 0.72rem; padding: 0.1rem 0.4rem; border-radius: var(--radius-full); margin-left: auto;">
                            <?= $pendingPlatformsCount ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="<?= $appUrl ?>/admin/transactions.php" class="sidebar-link <?= $adminNav === 'transactions' ? 'active' : '' ?>">
                    <span>Activation Transactions</span>
                </a>
            </li>
            <li>
                <a href="<?= $appUrl ?>/admin/support_desk.php" class="sidebar-link <?= $adminNav === 'support' ? 'active' : '' ?>">
                    <span>Live Support Desk</span>
                    <?php if ($unreadMessages > 0): ?>
                        <span style="background-color: var(--danger); color: #fff; font-size: 0.72rem; padding: 0.1rem 0.4rem; border-radius: var(--radius-full); margin-left: auto;">
                            <?= $unreadMessages ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="<?= $appUrl ?>/admin/users.php" class="sidebar-link <?= $adminNav === 'users' ? 'active' : '' ?>">
                    <span>Users &amp; Roles</span>
                </a>
            </li>
            <li>
                <a href="<?= $appUrl ?>/admin/settings.php" class="sidebar-link <?= $adminNav === 'settings' ? 'active' : '' ?>">
                    <span>Platform Settings</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <a href="<?= $appUrl ?>/index.php" target="_blank" style="color: #A2A9A0; font-size: 0.82rem; font-weight: 600;">View Website &rarr;</a>
            <a href="<?= $appUrl ?>/logout.php" style="color: #E57373; font-size: 0.82rem; font-weight: 600;">Sign Out</a>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="admin-main">
        <header class="admin-topbar">
            <div style="font-size: 0.92rem; color: var(--text-secondary);">
                Logged in as <strong style="color: var(--text-primary);"><?= e($admin['username']) ?></strong> (<?= e($admin['email']) ?>)
            </div>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <a href="<?= $appUrl ?>/admin/platform_edit.php" class="btn btn-primary btn-sm">+ Post Verified Task</a>
            </div>
        </header>

        <div class="admin-content">
            <?php $flash = getFlash(); if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>" role="alert">
                    <span><?= e($flash['message']) ?></span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; cursor:pointer; font-size:1.1rem; color:inherit;">&times;</button>
                </div>
            <?php endif; ?>
