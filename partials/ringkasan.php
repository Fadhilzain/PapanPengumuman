<?php
/**
 * Ringkasan papan pengumuman untuk disematkan di personal page.
 * Pemakaian di personal/index.php:  include __DIR__ . '/app/partials/ringkasan.php';
 * Tautan memakai path relatif dari personal page (app/...).
 */
require_once dirname(__DIR__) . '/lib/bootstrap.php';
require_once dirname(__DIR__) . '/lib/pengumuman.php';

try {
    $__terbaru = pengumuman_terbaru(3);
    $__galat   = null;
} catch (Throwable $ex) {
    error_log('[papan] ringkasan: ' . $ex->getMessage());
    $__terbaru = [];
    $__galat   = 'Papan pengumuman belum dapat dimuat. Periksa koneksi database.';
}
?>
<div class="ringkasan">
  <?php if ($__galat): ?>
    <p class="ringkasan__kosong"><?= e($__galat) ?></p>
  <?php elseif (!$__terbaru): ?>
    <p class="ringkasan__kosong">Belum ada pengumuman aktif.</p>
  <?php else: ?>
    <ul class="ringkasan__daftar">
      <?php foreach ($__terbaru as $p): ?>
        <li class="ringkasan__item">
          <span class="ringkasan__kategori"><?= e($p['kategori']) ?></span>
          <a class="ringkasan__judul" href="app/index.php#pengumuman"><?= e($p['judul']) ?></a>
          <span class="ringkasan__tanggal">Berlaku s/d <?= e(tgl_id($p['tanggal_kedaluwarsa'])) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <a class="ringkasan__lanjut" href="app/index.php">Buka Papan Pengumuman →</a>
</div>
