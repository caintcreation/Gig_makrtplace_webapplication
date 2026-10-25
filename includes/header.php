<?php
// includes/header.php - Global Header & Navigation
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? getSetting('site_name', 'CAINT EARN HUB');
$pageDesc = $pageDesc ?? getSetting('site_tagline', 'Hand-tested online platforms and legit paying tasks in Kenya');
$currentNav = $currentNav ?? 'home';
$user = currentUser();
$appUrl = baseAppUrl();
$supportPhone = getSetting('support_phone', '+254 714 772 901 / +254 756 963 430');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?= e($pageTitle) ?> | Verified Earning Tasks</title>
    <meta name="description" content="<?= e($pageDesc) ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= $appUrl ?>/assets/images/logo.png">
    <link rel="shortcut icon" href="<?= $appUrl ?>/favicon.ico">
    <link rel="apple-touch-icon" href="<?= $appUrl ?>/assets/images/logo.png">
    
    <!-- Open Graph Metadata -->
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDesc) ?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?= $appUrl ?>/assets/images/logo.png">
    
    <!-- Design System CSS -->
    <link rel="stylesheet" href="<?= $appUrl ?>/assets/css/main.css">
    
    <style>
        .brand-logo-img {
            height: 42px;
            width: auto;
            max-width: 46px;
            object-fit: contain;
            border-radius: var(--radius-sm);
        }
    </style>
</head>
<body>

<header class="site-header">
    <div class="container">
        <div class="nav-wrapper">
            <a href="<?= $appUrl ?>/index.php" class="brand" aria-label="CAINT EARN HUB Homepage">
                <img src="<?= $appUrl ?>/assets/images/logo.png" alt="CAINT EARN HUB Logo" class="brand-logo-img">
                <div class="brand-text">
                    <span class="brand-title">CAINT EARN HUB</span>
                    <span class="brand-subtitle">Verified Earning Tasks</span>
                </div>
            </a>

            <nav class="main-nav" id="mainNav">
                <ul class="nav-links">
                    <li><a href="<?= $appUrl ?>/index.php" class="nav-link <?= $currentNav === 'home' ? 'active' : '' ?>">Home</a></li>
                    <li><a href="<?= $appUrl ?>/platforms.php" class="nav-link <?= $currentNav === 'platforms' ? 'active' : '' ?>">Available Tasks</a></li>
                    <li><a href="<?= $appUrl ?>/track.php" class="nav-link <?= $currentNav === 'track' ? 'active' : '' ?>">Recover Access</a></li>
                    <li><a href="<?= $appUrl ?>/support.php" class="nav-link <?= $currentNav === 'support' ? 'active' : '' ?>">Support Desk</a></li>
                    <li>
                        <a href="tel:+254714772901" class="btn btn-secondary btn-sm" style="font-size:0.8rem; padding:0.4rem 0.75rem;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                            <span>Support: 0714 772 901</span>
                        </a>
                    </li>
                </ul>

                <div class="nav-actions">
                    <?php if ($user): ?>
                        <a href="<?= $appUrl ?>/dashboard/index.php" class="btn btn-secondary btn-sm">My Dashboard</a>
                        <a href="<?= $appUrl ?>/logout.php" class="btn btn-outline btn-sm">Sign Out</a>
                    <?php else: ?>
                        <a href="<?= $appUrl ?>/login.php" class="btn btn-outline btn-sm">Sign In</a>
                        <a href="<?= $appUrl ?>/register.php" class="btn btn-primary btn-sm">Join Hub</a>
                    <?php endif; ?>
                </div>
            </nav>

            <button class="nav-toggle-btn" id="navToggleBtn" aria-label="Toggle navigation menu">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
        </div>
    </div>
</header>

<div class="container flash-container">
    <?php $flash = getFlash(); if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="alert">
            <span><?= e($flash['message']) ?></span>
            <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; cursor:pointer; font-size:1.1rem; color:inherit;">&times;</button>
        </div>
    <?php endif; ?>
</div>
