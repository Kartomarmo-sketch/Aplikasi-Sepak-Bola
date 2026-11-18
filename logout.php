<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';

start_secure_session();

// Logout hanya lewat POST + token CSRF, agar tidak bisa dipicu dari link atau gambar di situs lain.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid($_POST['csrf'] ?? null)) {
    logout_user();
    header('Location: login.php?logout=1');
    exit;
}

header('Location: dashboard.php');
exit;
