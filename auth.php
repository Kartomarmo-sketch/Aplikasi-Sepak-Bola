<?php
declare(strict_types=1);

// Fungsi bantu untuk sesi, CSRF, dan pemeriksaan login.

const SESSION_IDLE_TIMEOUT = 1800; // 30 menit tanpa aktivitas

const ROLE_LABELS = [
    'admin'         => 'Admin / Manager',
    'pelatih_medis' => 'Pelatih / Medis',
    'pemain'        => 'Pemain',
];

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,   // otomatis aktif bila memakai HTTPS
        'httponly' => true,     // tidak bisa dibaca JavaScript
        'samesite' => 'Lax',
    ]);
    session_start();

    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
}

/** Escape output HTML. */
function e(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valid(mixed $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $token);
}

function is_logged_in(): bool
{
    if (empty($_SESSION['user_id'])) {
        return false;
    }

    $last = (int) ($_SESSION['last_activity'] ?? 0);
    if (time() - $last > SESSION_IDLE_TIMEOUT) {
        $_SESSION = [];
        return false;
    }

    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Dipakai di setiap halaman yang butuh login.
 * Bila belum login (atau sesi habis), arahkan ke login.php.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: login.php?expired=1');
        exit;
    }
    header('Cache-Control: no-store');
}

/** Batasi halaman hanya untuk peran tertentu. */
function require_role(string ...$roles): void
{
    require_login();
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        http_response_code(403);
        exit('Anda tidak memiliki akses ke halaman ini.');
    }
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 3600,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?: 'Lax',
        ]);
    }

    session_destroy();
}
