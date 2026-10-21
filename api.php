<?php
/**
 * API Papan Pengumuman — Kelompok 4 · S1 Informatika
 *   GET  api.php?aksi=daftar&halaman=1&kategori=Akademik&status=aktif&q=uts
 *   POST api.php  aksi=tambah  judul, isi, kategori, tanggal_kedaluwarsa
 * Respons JSON menyertakan "served_by" = laman/server yang melayani request ini.
 * Daftar dikirim sebagai HTML yang sudah di-escape dengan htmlspecialchars() di partials/daftar.php.
 */
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/pengumuman.php';

header('Cache-Control: no-store');            // data harus selalu segar dari database
header('X-Served-By: ' . MEMBER_ROUTE);

$ajax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
$aksi = $_POST['aksi'] ?? $_GET['aksi'] ?? 'daftar';

function kirim_json(int $kode, array $data): void
{
    http_response_code($kode);
    header('Content-Type: application/json; charset=utf-8');
    $data['served_by'] = served_by();
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Tanpa JavaScript: kembali ke halaman form dengan pesan status. */
function kembali(bool $ok, string $pesan = ''): void
{
    $tujuan = 'index.php?status=' . ($ok ? 'ok' : 'gagal') . ($pesan !== '' ? '&pesan=' . rawurlencode($pesan) : '');
    header('Location: ' . $tujuan . ($ok ? '#pengumuman' : '#tulis'), true, 303);
    exit;
}

try {
    if ($aksi === 'daftar') {
        $hasil = pengumuman_cari($_GET);
        ob_start();
        include __DIR__ . '/partials/daftar.php';
        $html = ob_get_clean();
        kirim_json(200, [
            'html'          => $html,
            'halaman'       => $hasil['halaman'],
            'total_halaman' => $hasil['total_halaman'],
            'total'         => $hasil['total'],
            'total_aktif'   => $hasil['total_aktif'],
        ]);
    }

    if ($aksi === 'tambah') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            kirim_json(405, ['error' => 'Gunakan metode POST.']);
        }
        $r = pengumuman_tambah($_POST);
        if (!$ajax) {
            kembali($r['ok'], $r['ok'] ? '' : implode(' ', $r['galat']));
        }
        if (!$r['ok']) {
            kirim_json(422, ['error' => implode(' ', $r['galat']), 'galat' => $r['galat']]);
        }
        kirim_json(201, ['pesan' => 'Pengumuman diterbitkan.', 'id' => $r['id']]);
    }

    kirim_json(400, ['error' => 'Aksi tidak dikenal.']);
} catch (Throwable $ex) {
    error_log('[papan] ' . $ex->getMessage());
    if (!$ajax && $aksi === 'tambah') {
        kembali(false, 'Basis data tidak dapat dihubungi.');
    }
    kirim_json(500, ['error' => 'Basis data tidak dapat dihubungi.']);
}
