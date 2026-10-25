<?php
// admin/settings.php - Configurable Site & Gateway Settings
require_once __DIR__ . '/../includes/functions.php';

$adminNav = 'settings';
$adminTitle = 'System & Gateway Settings';
$db = getDb();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired.';
    } else {
        $allowedKeys = [
            'site_name', 'site_tagline', 'support_phone', 'admin_email',
            'tiktok_url', 'facebook_url', 'telegram_url', 'x_url', 'snapchat_url', 'main_website_url',
            'default_token_price', 'paystack_public_key', 'paystack_secret_key', 'paystack_currency'
        ];

        $stmt = $db->prepare("
            INSERT INTO site_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");

        foreach ($allowedKeys as $k) {
            if (isset($_POST[$k])) {
                $val = trim($_POST[$k]);
                $stmt->execute([$k, $val]);
            }
        }

        logAudit($_SESSION['user_id'], 'settings_updated', 'Updated system and Paystack gateway settings');
        setFlash('success', 'Platform settings saved successfully.');
        header('Location: settings.php');
        exit;
    }
}

$settingsRaw = $db->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll(PDO::FETCH_KEY_PAIR);

require_once __DIR__ . '/header.php';
?>

<div class="page-title-row">
    <div>
        <h1 style="font-size: 1.85rem; margin-bottom: 0.25rem;">Site &amp; Integration Settings</h1>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">
            Configure CAINT EARN HUB branding, customer support channels, social handles, and Paystack direct M-Pesa keys.
        </p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul style="padding-left: 1.25rem;">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div style="max-width: 820px;">
    <div style="background-color: var(--surface-main); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 2.25rem;">
        <form method="POST" action="settings.php">
            <?= csrfInput() ?>

            <!-- General Platform Identity -->
            <h2 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1.25rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--divider);">
                General Marketplace Info
            </h2>

            <div class="form-group">
                <label class="form-label" for="stName">Platform Name</label>
                <input type="text" id="stName" name="site_name" class="form-control" value="<?= e($settingsRaw['site_name'] ?? 'CAINT EARN HUB') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="stTag">Mission Tagline</label>
                <input type="text" id="stTag" name="site_tagline" class="form-control" value="<?= e($settingsRaw['site_tagline'] ?? 'Hand-tested online platforms and legit paying tasks in Kenya') ?>">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                <div class="form-group">
                    <label class="form-label" for="stPhone">Customer Support Phone</label>
                    <input type="text" id="stPhone" name="support_phone" class="form-control" value="<?= e($settingsRaw['support_phone'] ?? '+254 714 772 901 / +254 756 963 430') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="stEmail">Support Email</label>
                    <input type="email" id="stEmail" name="admin_email" class="form-control" value="<?= e($settingsRaw['admin_email'] ?? 'caintcreation@gmail.com') ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="stDefToken">Default Research &amp; Activation Fee (KSh)</label>
                <input type="number" id="stDefToken" name="default_token_price" class="form-control" style="width: 200px;" value="<?= (float)($settingsRaw['default_token_price'] ?? 50.00) ?>">
            </div>

            <!-- Social Media & Portfolio Links -->
            <h2 style="font-size: 1.2rem; font-weight: 700; margin: 2rem 0 1.25rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--divider);">
                Social Media Handles &amp; Developer Website
            </h2>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                <div class="form-group">
                    <label class="form-label" for="stTiktok">TikTok URL</label>
                    <input type="url" id="stTiktok" name="tiktok_url" class="form-control" value="<?= e($settingsRaw['tiktok_url'] ?? 'https://www.tiktok.com/@caintcraetion?_r=1&_t=ZS-9Ap0hcO2YbP') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="stFb">Facebook Page URL</label>
                    <input type="url" id="stFb" name="facebook_url" class="form-control" value="<?= e($settingsRaw['facebook_url'] ?? 'https://www.facebook.com/share/1KWiW3kgzY/') ?>">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                <div class="form-group">
                    <label class="form-label" for="stTg">Telegram URL</label>
                    <input type="text" id="stTg" name="telegram_url" class="form-control" value="<?= e($settingsRaw['telegram_url'] ?? 'https://t.me/Caintmanu') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="stX">X / Twitter URL</label>
                    <input type="url" id="stX" name="x_url" class="form-control" value="<?= e($settingsRaw['x_url'] ?? 'https://x.com/Caintmanu') ?>">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                <div class="form-group">
                    <label class="form-label" for="stSnap">Snapchat URL</label>
                    <input type="url" id="stSnap" name="snapchat_url" class="form-control" value="<?= e($settingsRaw['snapchat_url'] ?? 'https://www.snapchat.com/add/caint56?share_id=fAwN2xjyEvQ&locale=en-US') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="stDev">Developer Portfolio Website</label>
                    <input type="url" id="stDev" name="main_website_url" class="form-control" value="<?= e($settingsRaw['main_website_url'] ?? 'https://caintcreation.wuaze.com/') ?>">
                </div>
            </div>

            <!-- Paystack Payment Integration -->
            <h2 style="font-size: 1.2rem; font-weight: 700; margin: 2rem 0 1.25rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--divider); color: var(--accent);">
                Paystack Direct Mobile Money (Safaricom M-Pesa) Settings
            </h2>
            <p style="font-size: 0.88rem; color: var(--text-secondary); margin-bottom: 1rem;">
                Paystack triggers a direct prompt to the customer's phone without taking them to an external checkout page. Leave blank to run in local testing mode.
            </p>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                <div class="form-group">
                    <label class="form-label" for="psPub">Paystack Public Key (pk_test_... or pk_live_...)</label>
                    <input type="text" id="psPub" name="paystack_public_key" class="form-control" placeholder="pk_live_..." value="<?= e($settingsRaw['paystack_public_key'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="psSec">Paystack Secret Key (sk_test_... or sk_live_...)</label>
                    <input type="password" id="psSec" name="paystack_secret_key" class="form-control" placeholder="sk_live_..." value="<?= e($settingsRaw['paystack_secret_key'] ?? '') ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="margin-top: 1rem;">
                Save Configuration Changes
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
