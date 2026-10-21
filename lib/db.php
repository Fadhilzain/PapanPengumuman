<?php
/**
 * Koneksi PDO ke MySQL/MariaDB.
 * Kredensial SELALU dibaca dari config/db.php di luar folder app/, sehingga
 * perpindahan ke GCP setelah UTS cukup dengan mengganti isi file konfigurasi.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $file = dirname(__DIR__, 2) . '/config/db.php';
    if (!is_file($file)) {
        throw new RuntimeException('config/db.php belum dibuat. Salin config/db.example.php menjadi config/db.php.');
    }
    $c = require $file;

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $c['host'], $c['name']);
    $pdo = new PDO($dsn, $c['user'], $c['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    $pdo->exec("SET time_zone = '+07:00'");   // WIB
    return $pdo;
}
