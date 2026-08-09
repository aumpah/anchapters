-- =========================================
-- Database: an_chapters
-- Bookstore Website - Admin & User
-- =========================================

CREATE DATABASE IF NOT EXISTS an_chapters;
USE an_chapters;

-- ============ TABEL USERS ============
CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    alamat TEXT,
    telepon VARCHAR(20),
    role ENUM('admin','user') NOT NULL DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============ TABEL KATEGORI ============
CREATE TABLE kategori (
    id_kategori INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    deskripsi TEXT
) ENGINE=InnoDB;

-- ============ TABEL BUKU ============
CREATE TABLE buku (
    id_buku INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(150) NOT NULL,
    penulis VARCHAR(100) NOT NULL,
    penerbit VARCHAR(100),
    tahun YEAR,
    harga DECIMAL(12,2) NOT NULL DEFAULT 0,
    stok INT NOT NULL DEFAULT 0,
    cover VARCHAR(255) DEFAULT 'default.png',
    deskripsi TEXT,
    id_kategori INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============ TABEL CART ============
CREATE TABLE cart (
    id_cart INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_buku INT NOT NULL,
    qty INT NOT NULL DEFAULT 1,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
    FOREIGN KEY (id_buku) REFERENCES buku(id_buku) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============ TABEL ORDERS ============
CREATE TABLE orders (
    id_order INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('Pending','Dibayar','Diproses','Dikirim','Selesai') NOT NULL DEFAULT 'Pending',
    alamat_kirim TEXT,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============ TABEL ORDER_DETAIL ============
CREATE TABLE order_detail (
    id_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_order INT NOT NULL,
    id_buku INT NOT NULL,
    qty INT NOT NULL,
    harga DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (id_order) REFERENCES orders(id_order) ON DELETE CASCADE,
    FOREIGN KEY (id_buku) REFERENCES buku(id_buku)
) ENGINE=InnoDB;

-- ============ TABEL CONTACT ============
CREATE TABLE contact (
    id_contact INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    pesan TEXT NOT NULL,
    tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
    status_baca ENUM('Belum Dibaca','Sudah Dibaca') DEFAULT 'Belum Dibaca'
) ENGINE=InnoDB;

-- ============ DATA AWAL ============

-- Admin default (password: admin123)
INSERT INTO users (nama, email, password, alamat, telepon, role)
VALUES ('Administrator', 'admin@anchapters.com', '$2y$10$Fq1z7z4h1kQxJ8vJH0m1XeYQ0m0m2b8W1B1zXk0j9y4Rk3rV9m9AS', 'An Chapters HQ', '081234567890', 'admin');
-- NOTE: hash di atas placeholder, gunakan auth/register.php untuk membuat akun asli
-- karena password_hash() PHP menghasilkan salt acak setiap kali dijalankan.

-- Kategori contoh
INSERT INTO kategori (nama_kategori, deskripsi) VALUES
('Fiksi', 'Buku-buku cerita fiksi dan novel'),
('Non-Fiksi', 'Buku pengetahuan dan referensi'),
('Komik', 'Komik dan graphic novel'),
('Anak-anak', 'Buku bacaan anak'),
('Self Improvement', 'Buku pengembangan diri');

-- Buku contoh
INSERT INTO buku (judul, penulis, penerbit, tahun, harga, stok, cover, deskripsi, id_kategori) VALUES
('Laut Bercerita', 'Leila S. Chudori', 'KPG', 2017, 98000, 25, 'default.png', 'Novel tentang kisah kelam masa lalu.', 1),
('Atomic Habits', 'James Clear', 'Gramedia', 2019, 115000, 40, 'default.png', 'Membangun kebiasaan baik secara bertahap.', 5),
('Bumi Manusia', 'Pramoedya Ananta Toer', 'Lentera Dipantara', 1980, 85000, 15, 'default.png', 'Tetralogi Buru bagian pertama.', 1),
('Sapiens', 'Yuval Noah Harari', 'Pustaka Alvabet', 2017, 129000, 20, 'default.png', 'Sejarah singkat umat manusia.', 2);
