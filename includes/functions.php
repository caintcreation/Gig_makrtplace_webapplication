<?php
// includes/functions.php - Core helper utilities

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function baseAppUrl(): string {
    static $base = null;
    if ($base === null) {
        $env = getenv('APP_URL');
        if ($env !== false && $env !== '') {
            $base = rtrim($env, '/');
        } else {
            $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
            $dir = dirname($script);
            $dir = preg_replace('#/(admin|dashboard|api|includes).*$#', '', $dir);
            $base = ($dir === '/' || $dir === '.' || $dir === '') ? '' : rtrim($dir, '/');
        }
    }
    return $base;
}

function getSetting(string $key, string $default = ''): string {
    static $settings = null;
    if ($settings === null) {
        $db = getDb();
        try {
            $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $ex) {
            $settings = [];
        }
    }
    return $settings[$key] ?? $default;
}

function normalizePhone(string $phone): string {
    $clean = preg_replace('/[^0-9]/', '', $phone);
    if (str_starts_with($clean, '254') && strlen($clean) === 12) {
        return $clean;
    }
    if (str_starts_with($clean, '0') && strlen($clean) === 10) {
        return '254' . substr($clean, 1);
    }
    if (strlen($clean) === 9) {
        return '254' . $clean;
    }
    return $clean;
}

function formatPhoneDisplay(string $phone): string {
    $norm = normalizePhone($phone);
    if (strlen($norm) === 12 && str_starts_with($norm, '254')) {
        return '0' . substr($norm, 3, 3) . ' ' . substr($norm, 6, 3) . ' ' . substr($norm, 9);
    }
    return $phone;
}

function formatKsh($amount): string {
    return 'KSh ' . number_format((float)$amount, 2);
}

function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

function getFlash(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function logAudit(?int $userId, string $action, ?string $details = null): void {
    try {
        $db = getDb();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $details, $ip]);
    } catch (Exception $e) {
        error_log('Audit log error: ' . $e->getMessage());
    }
}

function currentUser(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id'])) return null;

    static $cachedUser = null;
    if ($cachedUser === null) {
        $db = getDb();
        $stmt = $db->prepare("
            SELECT u.*, p.display_name, p.headline, p.bio, p.avatar_url, p.location
            FROM users u
            LEFT JOIN user_profiles p ON u.id = p.user_id
            WHERE u.id = ? AND u.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $cachedUser = $stmt->fetch() ?: null;
    }
    return $cachedUser;
}

function isLoggedIn(): bool {
    return currentUser() !== null;
}

function requireLogin(string $redirectTo = 'login.php'): array {
    $user = currentUser();
    if (!$user) {
        setFlash('warning', 'Please sign in to access this page.');
        header('Location: ' . $redirectTo);
        exit;
    }
    return $user;
}

function requireRole($roles, string $redirectTo = 'login.php'): array {
    $user = requireLogin($redirectTo);
    $allowed = (array)$roles;
    if (!in_array($user['role'], $allowed, true)) {
        setFlash('error', 'You do not have permission to view that section.');
        header('Location: ' . ($user['role'] === 'admin' ? 'admin/index.php' : 'dashboard/index.php'));
        exit;
    }
    return $user;
}

function timeAgo(string $datetime): string {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' mins ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hrs ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M j, Y', $time);
}

function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'listing-' . time() : $text;
}
