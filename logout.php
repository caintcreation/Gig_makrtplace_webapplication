<?php
// logout.php - Safe Sign Out
require_once __DIR__ . '/includes/functions.php';

$user = currentUser();
if ($user) {
    logAudit($user['id'], 'user_logout', 'User signed out');
}

if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

if (session_status() === PHP_SESSION_NONE) session_start();
setFlash('info', 'You have been signed out safely.');
header('Location: login.php');
exit;
