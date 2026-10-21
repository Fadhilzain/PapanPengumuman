<?php
/**
 * Akses data tabel `pengumuman`. Semua query memakai PDO prepared statement.
 * SQL ditulis standar agar berjalan di MariaDB (XAMPP) maupun MySQL 8 (GCP).
 */

/**
 * Daftar pengumuman dengan filter, pencarian, dan paginasi.
 * $opsi: halaman, kategori, status (aktif|kedaluwarsa|semua), q (kata kunci).
 */
function pengumuman_cari(array $opsi): array
{
    $hariIni  = date('Y-m-d');
    $halaman  = max(1, (int)($opsi['halaman'] ?? 1));
    $kategori = (string)($opsi['kategori'] ?? '');
    $status   = (string)($opsi['status'] ?? 'aktif');
    $q        = trim((string)($opsi['q'] ?? ''));

    $where = [];
    $param = [];
    if (in_array($kategori, APP_KATEGORI, true)) {
        $where[] = 'p.kategori = :kategori';
        $param[':kategori'] = $kategori;
    }
    if ($status === 'aktif') {
        $where[] = 'p.tanggal_kedaluwarsa >= :hari';
        $param[':hari'] = $hariIni;
    } elseif ($status === 'kedaluwarsa') {
        $where[] = 'p.tanggal_kedaluwarsa < :hari';
        $param[':hari'] = $hariIni;
    } else {
        $status = 'semua';
    }
    if ($q !== '') {
        $q = mb_substr($q, 0, 100);
        $pola = '%' . addcslashes(mb_strtolower($q, 'UTF-8'), '%_\\') . '%';
        $where[] = '(LOWER(p.judul) LIKE :q1 OR LOWER(p.isi) LIKE :q2)';
        $param[':q1'] = $pola;
        $param[':q2'] = $pola;
    }
    $sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = db()->prepare("SELECT COUNT(*) FROM pengumuman p $sqlWhere");
    $stmt->execute($param);
    $total        = (int)$stmt->fetchColumn();
    $totalHalaman = max(1, (int)ceil($total / APP_PER_HALAMAN));
    $halaman      = min($halaman, $totalHalaman);

    $stmt = db()->prepare(
        "SELECT p.id, p.route_id, p.judul, p.isi, p.kategori, p.tanggal_kedaluwarsa, p.created_at,
                a.nama AS nama_anggota
         FROM pengumuman p
         LEFT JOIN anggota a ON a.route_id = p.route_id
         $sqlWhere
         ORDER BY p.created_at DESC, p.id DESC
         LIMIT :lim OFFSET :off"
    );
    foreach ($param as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':lim', APP_PER_HALAMAN, PDO::PARAM_INT);
    $stmt->bindValue(':off', ($halaman - 1) * APP_PER_HALAMAN, PDO::PARAM_INT);
    $stmt->execute();

    $data = [];
    foreach ($stmt->fetchAll() as $r) {
        $r['kedaluwarsa'] = $r['tanggal_kedaluwarsa'] < $hariIni;
        $data[] = $r;
    }

    $stmt = db()->prepare('SELECT COUNT(*) FROM pengumuman WHERE tanggal_kedaluwarsa >= :hari');
    $stmt->execute([':hari' => $hariIni]);

    return [
        'data'          => $data,
        'halaman'       => $halaman,
        'total_halaman' => $totalHalaman,
        'total'         => $total,
        'total_aktif'   => (int)$stmt->fetchColumn(),
        'kategori'      => $kategori,
        'status'        => $status,
        'q'             => $q,
    ];
}

/** Validasi sisi server (tetap wajib walaupun klien sudah memvalidasi). */
function pengumuman_validasi(array $in): array
{
    $galat   = [];
    $judul   = trim((string)($in['judul'] ?? ''));
    $isi     = trim((string)($in['isi'] ?? ''));
    $kat     = (string)($in['kategori'] ?? '');
    $tanggal = (string)($in['tanggal_kedaluwarsa'] ?? '');

    if ($judul === '' || mb_strlen($judul) > 150) {
        $galat['judul'] = 'Judul wajib diisi, maksimal 150 karakter.';
    }
    if ($isi === '' || mb_strlen($isi) > 2000) {
        $galat['isi'] = 'Isi wajib diisi, maksimal 2000 karakter.';
    }
    if (!in_array($kat, APP_KATEGORI, true)) {
        $galat['kategori'] = 'Kategori tidak valid.';
    }
    $tgl = DateTime::createFromFormat('Y-m-d', $tanggal);
    if (!$tgl || $tgl->format('Y-m-d') !== $tanggal) {
        $galat['tanggal_kedaluwarsa'] = 'Tanggal kedaluwarsa tidak valid.';
    } elseif ($tanggal < date('Y-m-d')) {
        $galat['tanggal_kedaluwarsa'] = 'Tanggal kedaluwarsa tidak boleh di masa lalu.';
    }

    return [$galat, compact('judul', 'isi', 'kat', 'tanggal')];
}

/** Menyimpan pengumuman baru dengan route_id = MEMBER_ROUTE (server penerima). */
function pengumuman_tambah(array $in): array
{
    [$galat, $v] = pengumuman_validasi($in);
    if ($galat) {
        return ['ok' => false, 'galat' => $galat];
    }

    try {
        $stmt = db()->prepare(
            'INSERT INTO pengumuman (route_id, judul, isi, kategori, tanggal_kedaluwarsa, created_at)
             VALUES (:route, :judul, :isi, :kategori, :tanggal, :dibuat)'
        );
        $stmt->execute([
            ':route'    => MEMBER_ROUTE,
            ':judul'    => $v['judul'],
            ':isi'      => $v['isi'],
            ':kategori' => $v['kat'],
            ':tanggal'  => $v['tanggal'],
            ':dibuat'   => date('Y-m-d H:i:s'),
        ]);
    } catch (PDOException $ex) {
        if (strpos((string)$ex->getCode(), '23') === 0) {   // pelanggaran constraint (FK/CHECK)
            return ['ok' => false, 'galat' => [
                'route' => 'Route laman "' . MEMBER_ROUTE . '" belum terdaftar di tabel anggota. Periksa identity.php.',
            ]];
        }
        throw $ex;
    }

    return ['ok' => true, 'id' => (int)db()->lastInsertId()];
}

/** Pengumuman aktif terbaru, untuk ringkasan di personal page. */
function pengumuman_terbaru(int $jumlah = 3): array
{
    $stmt = db()->prepare(
        'SELECT id, judul, kategori, tanggal_kedaluwarsa FROM pengumuman
         WHERE tanggal_kedaluwarsa >= :hari
         ORDER BY created_at DESC, id DESC
         LIMIT :lim'
    );
    $stmt->bindValue(':hari', date('Y-m-d'));
    $stmt->bindValue(':lim', $jumlah, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
