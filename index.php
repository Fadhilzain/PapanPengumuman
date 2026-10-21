<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/pengumuman.php';

// Render awal di server (tetap tampil walau JavaScript mati); selanjutnya diperbarui lewat AJAX.
try {
    $hasil   = pengumuman_cari(['status' => 'aktif']);
    $dbGalat = null;
} catch (Throwable $ex) {
    error_log('[papan] ' . $ex->getMessage());
    $hasil   = ['data' => [], 'halaman' => 1, 'total_halaman' => 1, 'total' => 0, 'total_aktif' => 0, 'q' => ''];
    $dbGalat = 'Basis data tidak dapat dihubungi. Periksa config/db.php dan pastikan MySQL berjalan.';
}
$status = $_GET['status'] ?? '';
$pesan  = $_GET['pesan'] ?? '';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Papan Pengumuman · Kelompok 4</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

  <!-- Navigasi tetap + banner "Anda dilayani oleh" -->
  <header class="nav">
    <a href="#intro" class="nav__logo"><?= e(APP_KELOMPOK) ?></a>
    <?php include __DIR__ . '/partials/served_by.php'; ?>
    <nav class="nav__menu" aria-label="Menu utama">
      <a href="#intro" class="is-active">INTRO</a>
      <a href="#pengumuman">PENGUMUMAN</a>
      <a href="#tulis">TULIS</a>
      <a href="#server">SERVER</a>
      <a href="../index.php">PERSONAL</a>
    </nav>
  </header>

  <!-- Label vertikal -->
  <div class="side-label" aria-hidden="true"><?= e(APP_KELOMPOK) ?> · <?= e(APP_PRODI) ?> · <span data-served-route><?= e(strtoupper(MEMBER_ROUTE)) ?></span></div>

  <main>
    <!-- HERO -->
    <section id="intro" class="hero">
      <div class="hero__type">
        <p class="hero__tagline"><?= e(APP_KELOMPOK) ?> · <?= e(APP_PRODI) ?> — DITULIS SEKALI, DIBACA DI MANA SAJA.</p>
        <h1 class="hero__display">PAPAN<br>PENGUMUMAN</h1>
      </div>

      <!-- "Objek" tunggal di tengah ruang gelap -->
      <div class="hero__object" aria-hidden="true">
        <div class="note">
          <span class="note__pin"></span>
          <span class="note__tag">PENGUMUMAN</span>
          <span class="note__line"></span>
          <span class="note__line note__line--short"></span>
          <span class="note__line"></span>
          <span class="note__date">BERLAKU HINGGA —</span>
        </div>
      </div>

      <aside class="hero__card">
        <h2>INFORMASI S1 INFORMATIKA DALAM SATU PAPAN.</h2>
        <hr class="dash">
        <p>Pengumuman dengan judul, isi, kategori, dan tanggal kedaluwarsa. Yang sudah lewat masa berlakunya otomatis turun dari papan.</p>
      </aside>

      <aside class="hero__stat">
        <span class="label">PENGUMUMAN AKTIF</span>
        <strong id="stat-aktif"><?= e($hasil['total_aktif']) ?></strong>
      </aside>
    </section>

    <!-- DAFTAR PENGUMUMAN -->
    <section id="pengumuman" class="section">
      <div class="section__head">
        <h2 class="heading">BUKAN SEKADAR<br>PAPAN.</h2>
        <p class="body">Satu tulisan, terbaca dari server mana pun. Cari, saring berdasarkan kategori, atau lihat arsip yang sudah kedaluwarsa.</p>
      </div>

      <?php if ($status === 'ok'): ?>
        <p class="notice">PENGUMUMAN DITERBITKAN.</p>
      <?php elseif ($status === 'gagal'): ?>
        <p class="notice notice--err"><?= e(mb_strtoupper($pesan, 'UTF-8')) ?></p>
      <?php endif; ?>
      <?php if ($dbGalat): ?>
        <p class="notice notice--err"><?= e(mb_strtoupper($dbGalat, 'UTF-8')) ?></p>
      <?php endif; ?>

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
          <button type="button" class="ghost" data-status="kedaluwarsa">KEDALUWARSA</button>
          <button type="button" class="ghost" data-status="semua">SEMUA STATUS</button>
        </div>
      </div>

      <hr class="dash">
      <div id="daftar" class="list" aria-live="polite"><?php include __DIR__ . '/partials/daftar.php'; ?></div>

      <div class="pager">
        <button type="button" class="ghost" id="prev" disabled>← SEBELUMNYA</button>
        <span id="info-halaman" class="label">HALAMAN <?= e($hasil['halaman']) ?> / <?= e($hasil['total_halaman']) ?> · <?= e($hasil['total']) ?> ENTRI</span>
        <button type="button" class="ghost" id="next" <?= $hasil['total_halaman'] <= 1 ? 'disabled' : '' ?>>BERIKUTNYA →</button>
      </div>
    </section>

    <!-- FORM -->
    <section id="tulis" class="section section--form">
      <div class="section__head">
        <h2 class="heading">TULIS<br>PENGUMUMAN.</h2>
        <p class="body">Isi judul, isi, kategori, dan sampai kapan pengumuman berlaku. Setelah tanggal itu, pengumuman pindah ke arsip.</p>
      </div>

      <form id="form" class="form" action="api.php" method="post" novalidate>
        <input type="hidden" name="aksi" value="tambah">
        <label class="field">
          <span class="label">JUDUL</span>
          <input name="judul" maxlength="150" required placeholder="Contoh: Jadwal UTS diundur">
          <span class="field__err" data-err="judul"></span>
        </label>
        <label class="field">
          <span class="label">ISI</span>
          <textarea name="isi" rows="4" maxlength="2000" required placeholder="Tulis detail pengumuman…"></textarea>
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
      </form>
    </section>

    <!-- SERVER -->
    <section id="server" class="section section--server">
      <h2 class="heading">DILAYANI OLEH</h2>
      <p class="server-name" data-served-nama><?= e(MEMBER_NAMA) ?></p>
      <p class="label server-meta">ROUTE <span data-served-route><?= e(strtoupper(MEMBER_ROUTE)) ?></span> · NIM <span data-served-nim><?= e(MEMBER_NIM) ?></span></p>
      <hr class="dash">
      <p class="body">Halaman ini memuat ulang daftar setiap 15 detik lewat AJAX. Setelah UTS, aplikasi yang sama berjalan di lima server dengan load balancer: nama laman di atas akan berganti, namun isi papan tetap sama karena semua server membaca database terpusat yang sama.</p>
      <p class="label">RESPONS TERAKHIR: <span id="waktu-respons">—</span></p>
    </section>
  </main>

  <footer class="footer">
    <span class="credit">DIBANGUN OLEH <?= e(APP_KELOMPOK) ?> · <?= e(APP_PRODI) ?> — TUGAS 1 SISTEM TERDISTRIBUSI</span>
    <a href="desktop.php" class="label">VERSI DESKTOP →</a>
    <span class="legal">* HTML · CSS · JAVASCRIPT · PHP · MYSQL</span>
  </footer>

  <script src="assets/app.js"></script>
</body>
</html>
