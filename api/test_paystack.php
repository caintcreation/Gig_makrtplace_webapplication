<?php
// api/test_paystack.php - Diagnostic tool to verify Paystack keys and connectivity
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: text/html; charset=UTF-8');

$db = getDb();
$paystackSecret = trim(getenv('PAYSTACK_SECRET_KEY') ?: getSetting('paystack_secret_key', ''));
$paystackPublic = trim(getenv('PAYSTACK_PUBLIC_KEY') ?: getSetting('paystack_public_key', ''));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Paystack Integration Diagnostics</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #1e293b; padding: 2rem; }
        .card { max-width: 680px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        h1 { font-size: 1.5rem; margin-top: 0; }
        .item { padding: 0.75rem 0; border-bottom: 1px solid #e2e8f0; }
        .badge { display: inline-block; padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: bold; font-size: 0.85rem; }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-danger { background: #fee2e2; color: #b91c1c; }
        .badge-warning { background: #fef3c7; color: #b45309; }
        pre { background: #0f172a; color: #f8fafc; padding: 1rem; border-radius: 6px; overflow-x: auto; font-size: 0.85rem; }
    </style>
</head>
<body>
<div class="card">
    <h1>Paystack Connection Diagnostics</h1>
    
    <div class="item">
        <strong>PHP cURL Extension:</strong>
        <?php if (function_exists('curl_version')): ?>
            <span class="badge badge-success">Enabled</span>
        <?php else: ?>
            <span class="badge badge-danger">Disabled</span>
        <?php endif; ?>
    </div>

    <div class="item">
        <strong>Paystack Secret Key:</strong>
        <?php if (empty($paystackSecret)): ?>
            <span class="badge badge-danger">Not Configured</span>
            <p style="color:#64748b; font-size:0.9rem;">Add your key in Admin &rarr; Settings.</p>
        <?php elseif (!str_starts_with($paystackSecret, 'sk_')): ?>
            <span class="badge badge-danger">Invalid Format</span>
            <p style="color:#b91c1c; font-size:0.9rem;">Secret key must begin with <code>sk_live_</code> or <code>sk_test_</code>.</p>
        <?php else: ?>
            <span class="badge badge-success">Configured</span> (<?= substr($paystackSecret, 0, 7) ?>...<?= substr($paystackSecret, -4) ?>)
        <?php endif; ?>
    </div>

    <div class="item">
        <strong>Paystack Public Key:</strong>
        <?php if (empty($paystackPublic)): ?>
            <span class="badge badge-warning">Empty</span>
        <?php else: ?>
            <span class="badge badge-success">Configured</span> (<?= substr($paystackPublic, 0, 7) ?>...)
        <?php endif; ?>
    </div>

    <?php if (!empty($paystackSecret) && str_starts_with($paystackSecret, 'sk_')): ?>
        <div class="item">
            <strong>Connectivity &amp; Authorization Test:</strong>
            <?php
            // Test 1: Check balance or integration status
            $ch = curl_init('https://api.paystack.co/balance');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $paystackSecret
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($curlErr): ?>
                <span class="badge badge-danger">cURL Network Error</span>
                <p style="color:#b91c1c; font-size:0.9rem;"><strong>Error:</strong> <?= htmlspecialchars($curlErr) ?></p>
            <?php else:
                $data = json_decode($res, true);
                if ($httpCode === 200 && !empty($data['status'])): ?>
                    <span class="badge badge-success">Connected Successfully (HTTP 200)</span>
                    <p style="color:#15803d; font-size:0.9rem; margin-top:0.5rem;">
                        Paystack API recognized your secret key!
                    </p>
                    <pre><?= htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) ?></pre>
                <?php else: ?>
                    <span class="badge badge-danger">Rejected by Paystack (HTTP <?= $httpCode ?>)</span>
                    <pre><?= htmlspecialchars($res ?: 'No response body') ?></pre>
                <?php endif;
            endif; ?>
        </div>
    <?php endif; ?>

    <p style="margin-top: 1.5rem; font-size: 0.9rem;">
        <a href="../admin/settings.php">&larr; Return to Admin Settings</a>
    </p>
</div>
</body>
</html>
