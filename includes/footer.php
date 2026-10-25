<?php
// includes/footer.php - Global Footer & Common Scripts
$appUrl = baseAppUrl();
$supportPhone1 = '+254 714 772 901';
$supportPhone2 = '+254 756 963 430';
$supportEmail = getSetting('admin_email', 'caintcreation@gmail.com');
$tiktokUrl = getSetting('tiktok_url', 'https://www.tiktok.com/@caintcraetion?_r=1&_t=ZS-9Ap0hcO2YbP');
$facebookUrl = getSetting('facebook_url', 'https://www.facebook.com/share/1KWiW3kgzY/ ');
$telegramUrl = getSetting('telegram_url', 'https://t.me/Caintmanu');
$xUrl = getSetting('x_url', 'https://x.com/Caintmanu');
$snapchatUrl = getSetting('snapchat_url', 'https://www.snapchat.com/add/caint56?share_id=fAwN2xjyEvQ&locale=en-US');
$mainWebUrl = getSetting('main_website_url', 'https://caintcreation.wuaze.com/');
$mainWebLabel = parse_url($mainWebUrl, PHP_URL_HOST) ?: $mainWebUrl;
?>
<footer class="site-footer">
    <div class="container">
        <!-- Prominent Research & Maintenance Notice Box -->
        <div style="background-color: #212922; border: 1px solid #364238; border-radius: var(--radius-md); padding: 1.25rem 1.5rem; margin-bottom: 3rem; display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
            <div style="width: 38px; height: 38px; border-radius: var(--radius-full); background-color: rgba(36, 107, 69, 0.25); color: #4ADE80; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
            </div>
            <div style="flex: 1; min-width: 260px;">
                <strong style="color: #FFFFFF; font-size: 0.95rem; display: block; margin-bottom: 0.2rem;">Transparent Platform Mission</strong>
                <p style="color: #C1C9BF; font-size: 0.88rem; margin: 0; line-height: 1.5;">
                    The activation amount paid on this hub is a one-time fee specifically dedicated to enhance platform testing research and cover continuous site maintenance. It is not an employment charge from third-party platforms.
                </p>
            </div>
        </div>

        <div class="footer-columns">
            <div class="footer-brand">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <img src="<?= $appUrl ?>/assets/images/logo.png" alt="CAINT EARN HUB" style="height: 38px; width: auto; border-radius: var(--radius-sm);">
                    <span class="footer-brand-title">CAINT EARN HUB</span>
                </div>
                <p class="footer-brand-desc">
                    Every task and platform featured here is verified with real payout evidence before publication. No scams. No unverified portals.
                </p>
                <div style="margin-top: 0.5rem;">
                    <a href="<?= e($mainWebUrl) ?>" target="_blank" rel="noopener" style="font-weight: 600; font-size: 0.85rem; color: #4ADE80;">
                        Visit Developer Portfolio: <?= e($mainWebLabel) ?> &rarr;
                    </a>
                </div>
            </div>

            <div>
                <h4 class="footer-heading">Platform Links</h4>
                <ul class="footer-links">
                    <li><a href="<?= $appUrl ?>/index.php">Verified Tasks</a></li>
                    <li><a href="<?= $appUrl ?>/platforms.php">Browse All Opportunities</a></li>
                    <li><a href="<?= $appUrl ?>/track.php">Recover Access Links</a></li>
                    <li><a href="<?= $appUrl ?>/index.php#how-it-works">How Research Works</a></li>
                </ul>
            </div>

            <div>
                <h4 class="footer-heading">Connect With Us</h4>
                <ul class="footer-links">
                    <li><a href="<?= e($tiktokUrl) ?>" target="_blank" rel="noopener">TikTok (@caintcraetion)</a></li>
                    <li><a href="<?= e($facebookUrl) ?>" target="_blank" rel="noopener">Facebook Page</a></li>
                    <li><a href="<?= e($telegramUrl) ?>" target="_blank" rel="noopener">Telegram (@Caintmanu)</a></li>
                    <li><a href="<?= e($xUrl) ?>" target="_blank" rel="noopener">X / Twitter (@Caintmanu)</a></li>
                    <li><a href="<?= e($snapchatUrl) ?>" target="_blank" rel="noopener">Snapchat (@caint56)</a></li>
                </ul>
            </div>

            <div>
                <h4 class="footer-heading">Customer Support</h4>
                <ul class="footer-links">
                    <li><a href="tel:+254714772901" style="color: #FFFFFF; font-weight: 600;"><?= e($supportPhone1) ?></a></li>
                    <li><a href="tel:+254756963430" style="color: #FFFFFF; font-weight: 600;"><?= e($supportPhone2) ?></a></li>
                    <li><a href="mailto:<?= e($supportEmail) ?>"><?= e($supportEmail) ?></a></li>
                    <li><a href="<?= $appUrl ?>/support.php">24/7 Live Support Desk</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <div>
                &copy; <?= date('Y') ?> CAINT EARN HUB. All rights reserved. Built with pride by <a href="<?= e($mainWebUrl) ?>" target="_blank" rel="noopener" style="color:#FFFFFF; text-decoration:underline;"><?= e($mainWebLabel) ?></a>.
            </div>
            <div>
                Hand-tested digital opportunities for Kenyan creators, students, and freelancers.
            </div>
        </div>
    </div>
</footer>

<script src="<?= $appUrl ?>/assets/js/main.js"></script>
</body>
</html>
