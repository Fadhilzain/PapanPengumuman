<?php
/**
 * Daftar kartu pengumuman. Dipakai saat halaman pertama dimuat dan oleh api.php (AJAX).
 * Membutuhkan variabel $hasil dari pengumuman_cari(). Semua data lewat e() = htmlspecialchars().
 */
if (empty($hasil['data'])): ?>
  <p class="empty"><?= $hasil['q'] !== '' ? 'TIDAK ADA PENGUMUMAN YANG COCOK DENGAN &ldquo;' . e(mb_strtoupper($hasil['q'], 'UTF-8')) . '&rdquo;.' : 'BELUM ADA PENGUMUMAN UNTUK FILTER INI.' ?></p>
<?php else: foreach ($hasil['data'] as $p): ?>
  <article class="item<?= $p['kedaluwarsa'] ? ' is-expired' : '' ?>">
    <div class="item__meta">
      <span class="item__tag"><?= e(mb_strtoupper($p['kategori'], 'UTF-8')) ?></span>
      <span class="item__date">DIBUAT <?= e(tgl_id($p['created_at'])) ?></span>
      <span class="item__date"><?= $p['kedaluwarsa'] ? 'KEDALUWARSA' : 'BERLAKU S/D' ?> <?= e(tgl_id($p['tanggal_kedaluwarsa'])) ?></span>
    </div>
    <div>
      <h3 class="item__title"><?= e($p['judul']) ?></h3>
      <p class="item__text"><?= e($p['isi']) ?></p>
      <div class="item__foot">
        <span>DITULIS VIA LAMAN <?= e(mb_strtoupper($p['nama_anggota'] ?? '-', 'UTF-8')) ?> (<?= e(strtoupper($p['route_id'])) ?>)</span>
        <span>#<?= e($p['id']) ?></span>
      </div>
    </div>
  </article>
<?php endforeach; endif;
