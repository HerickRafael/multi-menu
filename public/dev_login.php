<?php
// Dev helper: login as an existing user without password (LOCAL USE ONLY)
// Usage: /dev_login.php?email=owner@wollburger.local&slug=wollburger&token=DEV_TOKEN

// Quick guard: only allow from localhost
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
$allowed = in_array($remote, ['127.0.0.1', '::1']);

// Also allow when Host header contains 'localhost' as extra guard
$host = $_SERVER['HTTP_HOST'] ?? '';
if (!$allowed && strpos($host, 'localhost') === false) {
    http_response_code(403);
    echo "Forbidden: dev login is only allowed on localhost.\n";
    exit;
}

// Replace this token if you want extra safety
define('DEV_LOGIN_TOKEN', 'dev-secret');

$email = trim((string)($_GET['email'] ?? ''));
$slug  = trim((string)($_GET['slug'] ?? ''));
$token = trim((string)($_GET['token'] ?? ''));

if ($token !== DEV_LOGIN_TOKEN) {
    http_response_code(403);
    echo "Forbidden: invalid token.\n";
    exit;
}

if ($email === '' || $slug === '') {
    echo "Usage: dev_login.php?email=you@example&slug=your-slug&token=DEV_TOKEN\n";
    exit;
}

// Bootstrap minimal app pieces (require config + helpers to make config() and base_url() available)
require_once __DIR__ . '/../app/config/app.php';
require_once __DIR__ . '/../app/core/Helpers.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Company.php';

Auth::start();

$user = User::findByEmail($email);
if (!$user) {
    echo "User not found: " . htmlspecialchars($email) . "\n";
    exit;
}

// login and set active company context when slug exists
Auth::login($user);

$company = Company::findBySlug($slug);
if ($company) {
    $_SESSION['active_company_id'] = (int)$company['id'];
    $_SESSION['active_company_slug'] = $slug;
}

// Redirect to admin dashboard for convenience
$redirect = '/admin/' . rawurlencode($slug) . '/dashboard';
header('Location: ' . $redirect);
exit;
