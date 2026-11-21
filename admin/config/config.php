<?php
/**
 * Admin panel configuration.
 * Secrets live in admin/.env (see admin/.env.example) - never hard-code them here.
 */

require_once __DIR__ . '/../core/Env.php';
Env::load(__DIR__ . '/../.env');

// Business time zone (India). Used for "today", payment dates and note timestamps;
// the database session is aligned to it in Database::getConnection().
date_default_timezone_set(Env::get('APP_TIMEZONE', 'Asia/Kolkata'));

define('DB_HOST', Env::get('DB_HOST', '127.0.0.1'));
define('DB_NAME', Env::get('DB_NAME', 'blossom_counseling'));
define('DB_USER', Env::get('DB_USER', 'root'));
define('DB_PASS', Env::get('DB_PASS', ''));

// Token (JWT) authentication
define('JWT_SECRET', Env::get('JWT_SECRET', ''));
define('TOKEN_TTL', max(5, (int) Env::get('TOKEN_TTL_MINUTES', '120')) * 60);
define('TOKEN_COOKIE', 'blossom_admin_token');

// Gmail SMTP (password reset emails)
define('SMTP_HOST', Env::get('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', (int) Env::get('SMTP_PORT', '465'));
define('SMTP_USER', Env::get('SMTP_USER', ''));
define('SMTP_PASS', str_replace(' ', '', Env::get('SMTP_PASS', '')));
define('SMTP_FROM_NAME', Env::get('SMTP_FROM_NAME', 'Blossom Counseling'));

// Absolute URL path of the admin folder with trailing slash (e.g. "/admin/").
// Keeps CSS/logo/redirect links working even when visited as "/admin" (no slash).
define('ADMIN_URL', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/index.php')), '/') . '/');

// Public website root (the folder that contains /admin), e.g. "/" or "/blossom/".
define('SITE_URL', rtrim(str_replace('\\', '/', dirname(rtrim(ADMIN_URL, '/'))), '/') . '/');
// Where uploaded blog/gallery/profile images are stored on disk.
define('UPLOAD_DIR', str_replace('\\', '/', dirname(__DIR__, 2)) . '/uploads');

define('APP_NAME', 'Blossom Counseling');
define('SESSION_NAME', 'blossom_admin_session');

// The session only carries short-lived flash messages (errors, reset-flow state).
// Authentication itself is done with the signed token cookie.
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'path' => ADMIN_URL,
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}
