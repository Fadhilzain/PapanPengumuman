-- =========================================================
-- Papan Pengumuman — Kelompok 4 · S1 Informatika
-- Skema MySQL / MariaDB (XAMPP/WAMP sekarang, MySQL 8 di GCP setelah UTS)
-- Impor lewat phpMyAdmin → tab Import → pilih file ini → Go
-- Aman diimpor ulang: tabel memakai IF NOT EXISTS, data contoh memakai INSERT IGNORE.
-- =========================================================

CREATE DATABASE IF NOT EXISTS kelompok_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kelompok_db;

-- Tabel wajib: daftar anggota dan route-nya
CREATE TABLE IF NOT EXISTS anggota (
  route_id VARCHAR(10)  PRIMARY KEY,          -- m0 (ketua), m1..m4
  nim      VARCHAR(15)  NOT NULL,
  nama     VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel aplikasi: papan pengumuman
CREATE TABLE IF NOT EXISTS pengumuman (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  route_id            VARCHAR(10)  NOT NULL,  -- server/laman yang menerima data (MEMBER_ROUTE)
  judul               VARCHAR(150) NOT NULL,
  isi                 TEXT         NOT NULL,
  kategori            VARCHAR(20)  NOT NULL DEFAULT 'Umum',
  tanggal_kedaluwarsa DATE         NOT NULL,
  created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_pengumuman_kategori CHECK (kategori IN ('Akademik', 'Kegiatan', 'Layanan', 'Umum')),
  CONSTRAINT fk_pengumuman_anggota FOREIGN KEY (route_id) REFERENCES anggota (route_id),
  INDEX idx_pengumuman_kedaluwarsa (tanggal_kedaluwarsa),
  INDEX idx_pengumuman_kategori (kategori)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data anggota Kelompok 4 — anggota lain mengisi baris m1..m4 lewat pull request
INSERT IGNORE INTO anggota (route_id, nim, nama) VALUES
  ('m0', '103012400329', 'Fadhil Zain'),
  ('m1', '00000000001', 'Nama Anggota 1'),
  ('m2', '00000000002', 'Nama Anggota 2'),
  ('m3', '00000000003', 'Nama Anggota 3'),
  ('m4', '00000000004', 'Nama Anggota 4');

-- Data contoh (id tetap agar tidak berlipat saat diimpor ulang)
INSERT IGNORE INTO pengumuman (id, route_id, judul, isi, kategori, tanggal_kedaluwarsa) VALUES
  (1, 'm0', 'Jadwal UTS Semester Ganjil',
   'UTS S1 Informatika dilaksanakan mulai pekan depan. Periksa ruang ujian masing-masing di portal akademik.',
   'Akademik', DATE_ADD(CURRENT_DATE, INTERVAL 14 DAY)),
  (2, 'm0', 'Pemeliharaan Server Kampus',
   'Layanan e-learning tidak dapat diakses Sabtu pukul 22.00-24.00 WIB.',
   'Layanan', DATE_ADD(CURRENT_DATE, INTERVAL 5 DAY)),
  (3, 'm0', 'Seminar Sistem Terdistribusi',
   'Seminar terbuka untuk mahasiswa S1 Informatika di Aula Lt. 3. Pendaftaran di sekretariat prodi.',
   'Kegiatan', DATE_ADD(CURRENT_DATE, INTERVAL 10 DAY));

-- User aplikasi dengan hak minimum (jangan memakai root di kode)
CREATE USER IF NOT EXISTS 'app_local'@'localhost' IDENTIFIED BY 'password_lokal';
CREATE USER IF NOT EXISTS 'app_local'@'127.0.0.1' IDENTIFIED BY 'password_lokal';
GRANT SELECT, INSERT, UPDATE, DELETE ON kelompok_db.* TO 'app_local'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON kelompok_db.* TO 'app_local'@'127.0.0.1';
FLUSH PRIVILEGES;
