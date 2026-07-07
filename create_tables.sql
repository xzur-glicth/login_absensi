-- Run this SQL in phpMyAdmin or mysql client to create required database and tables
CREATE DATABASE IF NOT EXISTS absensi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE absensi;

CREATE TABLE IF NOT EXISTS siswa (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nis VARCHAR(50) NOT NULL UNIQUE,
  nama VARCHAR(255) NOT NULL,
  kelas VARCHAR(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS absen (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nis VARCHAR(50) NOT NULL,
  tanggal DATE NOT NULL,
  waktu_masuk TIME DEFAULT NULL,
  waktu_keluar TIME DEFAULT NULL,
  jenis_absen TINYINT NOT NULL DEFAULT 1,
  INDEX (nis)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional: sample siswa
INSERT IGNORE INTO siswa (nis, nama, kelas) VALUES
('1001', 'Siswa Satu', '10A'),
('1002', 'Siswa Dua', '10B');
