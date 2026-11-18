<?php
declare(strict_types=1);

// Sesuaikan dengan pengaturan MySQL Anda (XAMPP default: root tanpa password).
const DB_HOST = '127.0.0.1';
const DB_NAME = 'sdm_klub';
const DB_USER = 'root';
const DB_PASS = '';

/**
 * Mengembalikan satu koneksi PDO yang dipakai ulang selama request.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // prepared statement asli
        ]);
    }

    return $pdo;
}
