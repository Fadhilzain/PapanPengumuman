# Papan Pengumuman — Kelompok 4 · S1 Informatika

Aplikasi bersama **Tugas 1: Personal Page dan Aplikasi Bersama (Pra-UTS)**.
Pengunjung dapat menulis dan membaca pengumuman dengan **judul, isi, kategori, dan tanggal kedaluwarsa**.
Pengumuman yang sudah lewat tanggalnya otomatis pindah ke arsip.

- **Aplikasi yang dipilih:** Papan pengumuman (tabel utama `pengumuman`)
- **Konsep yang diuji setelah UTS:** data yang ditulis satu server langsung dibaca server lain
- **Lingkungan:** XAMPP/WAMP — Apache, PHP 8.x, MySQL/MariaDB; HTML, CSS, JavaScript di sisi klien

## Anggota dan route

| Route | Peran | Nama | NIM |
|---|---|---|---|
| m0 | Ketua | Fadhil Zain | 103012400329 |
| m1 | Anggota | _diisi anggota_ | _NIM_ |
| m2 | Anggota | _diisi anggota_ | _NIM_ |
| m3 | Anggota | _diisi anggota_ | _NIM_ |
| m4 | Anggota | _diisi anggota_ | _NIM_ |

Setiap anggota memperbarui barisnya di tabel ini **dan** di `sql/schema.sql` (`INSERT IGNORE INTO anggota`) lewat pull request.

## Fitur dan pemenuhan spesifikasi minimum

| Ketentuan | Implementasi |
|---|---|
| Fungsi tulis (form input) | Form "Tulis Pengumuman" → `api.php?aksi=tambah` |
| Fungsi baca (daftar terbaru) | Daftar pengumuman terbaru, diperbarui otomatis tiap 15 detik lewat AJAX |
| Fitur tambahan | **Paginasi** (5 per halaman) dan **pencarian** judul/isi, ditambah filter kategori dan aktif/kedaluwarsa |
| `route_id` | Setiap data menyimpan `route_id` dari `MEMBER_ROUTE` (`identity.php`) |
| Validasi klien | `assets/app.js` → fungsi `validasi()` |
| Validasi server | `lib/pengumuman.php` → fungsi `pengumuman_validasi()` |
| PDO + prepared statement | Semua query di `lib/pengumuman.php` |
| Escaping output | Semua data lewat `e()` = `htmlspecialchars()` (`lib/bootstrap.php`) |
| Tanpa kredensial di kode | Koneksi selalu membaca `../config/db.php` (`lib/db.php`) |
| Banner "Anda dilayani oleh" | `partials/served_by.php` |
| Sematan di personal page | `partials/ringkasan.php` (3 pengumuman aktif terbaru) |

## Struktur repo

Repo ini di-clone ke folder `app/` di dalam `htdocs/personal/`.

```
app/
├── index.php              halaman aplikasi bersama
├── desktop.php            tampilan khusus layar PC
├── api.php                API: daftar (GET), tambah (POST)
├── lib/
│   ├── bootstrap.php      memuat identity.php, konstanta, fungsi e()
│   ├── db.php             koneksi PDO (membaca ../config/db.php)
│   └── pengumuman.php     query: cari, validasi, tambah, terbaru
├── partials/
│   ├── served_by.php      banner "Anda dilayani oleh laman <nama> (<NIM>)"
│   ├── daftar.php         kartu pengumuman (halaman & AJAX)
│   └── ringkasan.php      ringkasan untuk personal page
├── sql/
│   └── schema.sql         skema database (impor lewat phpMyAdmin)
└── assets/                style.css, desktop.css, app.js
```

## Skema database

```
anggota    (route_id PK, nim, nama)
pengumuman (id PK, route_id FK → anggota.route_id, judul, isi, kategori,
            tanggal_kedaluwarsa, created_at)
```

`kategori` dibatasi `CHECK` ke Akademik, Kegiatan, Layanan, Umum.
User aplikasi `app_local` hanya memiliki hak `SELECT, INSERT, UPDATE, DELETE` pada `kelompok_db`.

## Cara instalasi

1. Instal **XAMPP** (PHP 8.x) dan **Git**. Start **Apache** dan **MySQL** di XAMPP Control Panel.
2. Buat folder personal page: `C:\xampp\htdocs\personal\` berisi `index.php`, `assets/`, `identity.php`, dan `config/`.
3. Clone repo ini sebagai folder `app`:
   ```
   cd C:\xampp\htdocs\personal
   git clone https://github.com/Fadhilzain/PapanPengumuman.git app
   ```
4. Buka `http://localhost/phpmyadmin` → tab **Import** → pilih `app/sql/schema.sql` → **Go**.
   Ini membuat database `kelompok_db`, tabel `anggota` dan `pengumuman`, serta user `app_local`.
5. Isi `personal/config/db.php`:
   ```php
   <?php
   return [
     'host' => '127.0.0.1',
     'name' => 'kelompok_db',
     'user' => 'app_local',
     'pass' => 'password_lokal',
   ];
   ```
6. Isi `personal/identity.php` dengan route, nama, dan NIM Anda.
7. Buka `http://localhost/personal/app/`.

## Alur kerja Git

Folder `app/` tidak boleh diubah langsung di laptop. Setiap anggota:

```
git checkout -b fitur-nama-fitur
# ... ubah kode ...
git add .
git commit -m "Menambah fitur ..."
git push -u origin fitur-nama-fitur
```

lalu membuat **pull request** di GitHub. Ketua menggabungkan setelah di-review minimal satu anggota lain,
kemudian semua anggota menjalankan `git pull` di folder `app/`.

## Uji keamanan

| Input | Hasil yang diharapkan |
|---|---|
| `<script>alert(1)</script>` sebagai judul | Tersimpan apa adanya, tampil sebagai teks biasa, tidak ada pop-up |
| `' OR '1'='1` sebagai isi | Tersimpan apa adanya, tampil sebagai teks biasa, tidak ada error SQL |
