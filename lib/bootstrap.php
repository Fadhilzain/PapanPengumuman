<?php
/**
 * Dimuat oleh setiap halaman aplikasi.
 * Tidak berisi kredensial: identitas dibaca dari ../identity.php,
 * koneksi database dari ../config/db.php (keduanya di luar folder app/).
 */
ini_set('display_errors', '0');      // error PHP tidak boleh tampil ke pengunjung
ini_set('log_errors', '1');
error_reporting(E_ALL);
date_default_timezone_set('Asia/Jakarta');

const APP_KELOMPOK    = 'KELOMPOK 4';
const APP_PRODI       = 'S1 INFORMATIKA';
const APP_KATEGORI    = ['Akademik', 'Kegiatan', 'Layanan', 'Umum'];
const APP_PER_HALAMAN = 5;

$__identitas = dirname(__DIR__, 2) . '/identity.php';
if (is_file($__identitas)) {
    require_once $__identitas;
}
defined('MEMBER_ROUTE') || define('MEMBER_ROUTE', '-');
defined('MEMBER_NAMA')  || define('MEMBER_NAMA', 'Laman belum dikonfigurasi');
defined('MEMBER_NIM')   || define('MEMBER_NIM', '-');

require_once __DIR__ . '/db.php';

/** Escaping output: semua data yang ditampilkan wajib melewati fungsi ini. */
function e($nilai): string
{
    return htmlspecialchars((string)$nilai, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Tanggal 'YYYY-MM-DD ...' → '3 NOV 2026'. */
function tgl_id(string $tanggal): string
{
    static $bulan = ['JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGU', 'SEP', 'OKT', 'NOV', 'DES'];
    $t = strtotime($tanggal);
    if ($t === false) {
        return $tanggal;
    }
    return date('j', $t) . ' ' . $bulan[(int)date('n', $t) - 1] . ' ' . date('Y', $t);
}

/** Identitas laman yang sedang melayani, dikirim juga lewat AJAX. */
function served_by(): array
{
    return ['route' => MEMBER_ROUTE, 'nama' => MEMBER_NAMA, 'nim' => MEMBER_NIM];
}
