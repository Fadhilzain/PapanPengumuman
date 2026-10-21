<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/pengumuman.php';

try {
    $hasil   = pengumuman_cari(['status' => 'aktif']);
    $dbGalat = null;
} catch (Throwable $ex) {
    error_log('[papan] ' . $ex->getMessage());
    $hasil   = ['data' => [], 'halaman' => 1, 'total_halaman' => 1, 'total' => 0, 'total_aktif' => 0, 'q' => ''];
    $dbGalat = 'Basis data tidak dapat dihubungi. Periksa config/db.php.';
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=1280">
  <title>Papan Pengumuman · Kelompok 4 (Desktop)</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap">
  <link rel="stylesheet" href="assets/desktop.css">
</head>
<body>

  <!-- Bilah atas + banner "Anda dilayani oleh" -->
  <header class="bar">
    <a href="desktop.php" class="bar__logo"><?= e(APP_KELOMPOK) ?></a>
    <?php include __DIR__ . '/partials/served_by.php'; ?>
    <nav class="bar__right" aria-label="Menu">
      <span class="bar__server"><span class="dot"></span>ROUTE <b data-served-route><?= e(strtoupper(MEMBER_ROUTE)) ?></b></span>
      <a href="index.php" class="bar__link">VERSI LANDING</a>
      <a href="../index.php" class="bar__link">PERSONAL</a>
    </nav>
  </header>

  <main class="shell">

    <!-- KOLOM KIRI: identitas + form -->
    <aside class="col col--left">
      <h1 class="display">PAPAN<br>PENGUMUMAN</h1>
      <p class="body">Pengumuman dengan judul, isi, kategori, dan tanggal kedaluwarsa. Yang lewat masa berlakunya otomatis turun ke arsip.</p>
      <hr class="dash">

      <form id="form" class="form" action="api.php" method="post" novalidate>
        <input type="hidden" name="aksi" value="tambah">
        <h2 class="subhead">TULIS PENGUMUMAN</h2>
        <label class="field">
          <span class="label">JUDUL</span>
          <input name="judul" maxlength="150" required placeholder="Contoh: Jadwal UTS diundur">
          <span class="field__err" data-err="judul"></span>
        </label>
        <label class="field">
          <span class="label">ISI</span>
          <textarea name="isi" rows="5" maxlength="2000" required placeholder="Tulis detail pengumuman…"></textarea>
          <span class="field__err" data-err="isi"></span>
        </label>
        <div class="form__row">
          <label class="field">
            <span class="label">KATEGORI</span>
            <select name="kategori" required>
              <?php foreach (APP_KATEGORI as $k): ?><option value="<?= e($k) ?>"><?= e($k) ?></option><?php endforeach; ?>
            </select>
            <span class="field__err" data-err="kategori"></span>
          </label>
          <label class="field">
            <span class="label">KEDALUWARSA</span>
            <input type="date" name="tanggal_kedaluwarsa" required min="<?= e(date('Y-m-d')) ?>">
            <span class="field__err" data-err="tanggal_kedaluwarsa"></span>
          </label>
        </div>
        <div class="form__actions">
          <button type="submit" class="pill">TERBITKAN</button>
          <span id="form-pesan" class="label" role="status"></span>
        </div>
        <p class="hint">CTRL + ENTER UNTUK MENERBITKAN</p>
      </form>
    </aside>

    <!-- KOLOM TENGAH: daftar pengumuman -->
    <section id="pengumuman" class="col col--center">
      <div class="center__head">
        <h2 class="heading">BUKAN SEKADAR PAPAN.</h2>
        <label class="search">
          <span class="label">CARI</span>
          <input type="search" id="cari" maxlength="100" placeholder="Cari judul atau isi pengumuman…" autocomplete="off">
        </label>
        <div class="filters">
          <div class="filters__group" role="group" aria-label="Kategori">
            <button type="button" class="ghost is-on" data-kategori="">SEMUA</button>
            <?php foreach (APP_KATEGORI as $k): ?>
              <button type="button" class="ghost" data-kategori="<?= e($k) ?>"><?= e(strtoupper($k)) ?></button>
            <?php endforeach; ?>
          </div>
          <div class="filters__group" role="group" aria-label="Status">
            <button type="button" class="ghost is-on" data-status="aktif">AKTIF</button>
            <button type="button" class="ghost" data-status="kedaluwarsa">ARSIP</button>
            <button type="button" class="ghost" data-status="semua">SEMUA</button>
          </div>
        </div>
        <?php if ($dbGalat): ?><p class="notice notice--err"><?= e(mb_strtoupper($dbGalat, 'UTF-8')) ?></p><?php endif; ?>
      </div>

      <div id="daftar" class="list" aria-live="polite"><?php include __DIR__ . '/partials/daftar.php'; ?></div>

      <div class="pager">
        <button type="button" class="ghost" id="prev" disabled>← SEBELUMNYA</button>
        <span id="info-halaman" class="label">HALAMAN <?= e($hasil['halaman']) ?> / <?= e($hasil['total_halaman']) ?> · <?= e($hasil['total']) ?> ENTRI</span>
        <button type="button" class="ghost" id="next" <?= $hasil['total_halaman'] <= 1 ? 'disabled' : '' ?>>BERIKUTNYA →</button>
      </div>
    </section>

    <!-- KOLOM KANAN: status server -->
    <aside id="server" class="col col--right">
      <span class="label muted">DILAYANI OLEH LAMAN</span>
      <p class="server-name" data-served-nama><?= e(MEMBER_NAMA) ?></p>
      <p class="label server-meta">ROUTE <span data-served-route><?= e(strtoupper(MEMBER_ROUTE)) ?></span> · NIM <span data-served-nim><?= e(MEMBER_NIM) ?></span></p>
      <hr class="dash">

      <div class="stat">
        <span class="label muted">PENGUMUMAN AKTIF</span>
        <strong id="stat-aktif"><?= e($hasil['total_aktif']) ?></strong>
      </div>
      <div class="stat">
        <span class="label muted">RESPONS TERAKHIR</span>
        <strong class="stat__small" id="waktu-respons">—</strong>
      </div>
      <hr class="dash">

      <p class="note">Daftar dimuat ulang tiap 15 detik lewat AJAX. Setelah UTS, aplikasi ini berjalan di lima server dengan load balancer: nama laman di atas berganti, tetapi isi papan tetap sama karena semua server membaca database terpusat yang sama.</p>

      <div class="legend">
        <span class="label muted">KETERANGAN</span>
        <p><i class="sw sw--on"></i>AKTIF</p>
        <p><i class="sw sw--off"></i>KEDALUWARSA / ARSIP</p>
      </div>

      <footer class="foot">
        <span class="credit">DIBANGUN OLEH <?= e(APP_KELOMPOK) ?> · <?= e(APP_PRODI) ?> — TUGAS 1 SISTEM TERDISTRIBUSI</span>
        <span class="legal">* HTML · CSS · JAVASCRIPT · PHP · MYSQL</span>
      </footer>
    </aside>
  </main>

  <script src="assets/app.js"></script>
  <script>
    // Pintasan keyboard khusus desktop
    document.addEventListener('keydown', (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') document.getElementById('form').requestSubmit();
      if (e.target.matches('input, textarea, select')) return;
      if (e.key === 'ArrowLeft')  document.getElementById('prev').click();
      if (e.key === 'ArrowRight') document.getElementById('next').click();
    });
  </script>
</body>
</html>
