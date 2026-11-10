-- ==========================================================
-- MIGRASI FITUR TAMBAHAN AN CHAPTERS
-- Jalankan SATU KALI melalui phpMyAdmin > database an_chapters > SQL.
-- Backup database terlebih dahulu sebelum menjalankan migrasi.
-- ==========================================================
USE an_chapters;

ALTER TABLE buku
  ADD COLUMN isbn VARCHAR(20) NULL AFTER penulis,
  ADD COLUMN jumlah_halaman SMALLINT UNSIGNED NULL AFTER isbn,
  ADD COLUMN tanggal_terbit DATE NULL AFTER tahun,
  ADD COLUMN bahasa VARCHAR(60) NOT NULL DEFAULT 'Indonesia' AFTER tanggal_terbit,
  ADD COLUMN format_buku ENUM('Fisik','E-book','Audiobook') NOT NULL DEFAULT 'Fisik' AFTER bahasa,
  ADD COLUMN sample_pdf VARCHAR(255) NULL AFTER deskripsi,
  ADD COLUMN file_digital VARCHAR(255) NULL AFTER sample_pdf,
  ADD COLUMN is_preorder TINYINT(1) NOT NULL DEFAULT 0 AFTER file_digital,
  ADD COLUMN tanggal_rilis DATE NULL AFTER is_preorder;

ALTER TABLE users
  ADD COLUMN poin INT UNSIGNED NOT NULL DEFAULT 0 AFTER telepon;

ALTER TABLE orders
  ADD COLUMN kode_pesanan VARCHAR(40) NULL AFTER id_order,
  ADD COLUMN subtotal DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER total,
  ADD COLUMN ongkir DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER subtotal,
  ADD COLUMN poin_digunakan INT UNSIGNED NOT NULL DEFAULT 0 AFTER ongkir,
  ADD COLUMN potongan_poin DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER poin_digunakan,
  ADD COLUMN nama_penerima VARCHAR(100) NULL AFTER alamat_kirim,
  ADD COLUMN email_penerima VARCHAR(100) NULL AFTER nama_penerima,
  ADD COLUMN telepon_penerima VARCHAR(20) NULL AFTER email_penerima,
  ADD COLUMN kurir VARCHAR(50) NULL AFTER telepon_penerima,
  ADD COLUMN nomor_resi VARCHAR(100) NULL AFTER kurir,
  ADD COLUMN gift_wrap TINYINT(1) NOT NULL DEFAULT 0 AFTER nomor_resi,
  ADD COLUMN gift_message VARCHAR(500) NULL AFTER gift_wrap,
  ADD COLUMN poin_diberikan TINYINT(1) NOT NULL DEFAULT 0 AFTER gift_message;

-- Guest checkout: pesanan dapat disimpan tanpa akun terdaftar.
-- Nama constraint ini mengikuti nama otomatis MySQL dari database.sql.
ALTER TABLE orders DROP FOREIGN KEY orders_ibfk_1;
ALTER TABLE orders MODIFY id_user INT NULL;
ALTER TABLE orders ADD CONSTRAINT fk_orders_user FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE SET NULL;

UPDATE orders
SET kode_pesanan = CONCAT('ACH-', DATE_FORMAT(tanggal, '%Y%m%d'), '-', LPAD(id_order, 5, '0'))
WHERE kode_pesanan IS NULL OR kode_pesanan = '';

UPDATE orders SET subtotal = total WHERE subtotal = 0;

ALTER TABLE orders
  MODIFY kode_pesanan VARCHAR(40) NOT NULL,
  ADD UNIQUE KEY uq_orders_kode_pesanan (kode_pesanan);

CREATE TABLE IF NOT EXISTS wishlist (
  id_wishlist INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT NOT NULL,
  id_buku INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_wishlist_user_book (id_user, id_buku),
  CONSTRAINT fk_wishlist_user FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
  CONSTRAINT fk_wishlist_book FOREIGN KEY (id_buku) REFERENCES buku(id_buku) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ulasan_buku (
  id_ulasan INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT NOT NULL,
  id_buku INT NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  ulasan TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_ulasan_user_buku (id_user, id_buku),
  CONSTRAINT chk_ulasan_rating CHECK (rating BETWEEN 1 AND 5),
  CONSTRAINT fk_ulasan_user FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
  CONSTRAINT fk_ulasan_book FOREIGN KEY (id_buku) REFERENCES buku(id_buku) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS layanan_pesanan (
  id_layanan INT AUTO_INCREMENT PRIMARY KEY,
  id_order INT NOT NULL,
  id_user INT NULL,
  jenis ENUM('Komplain','Retur') NOT NULL,
  pesan TEXT NOT NULL,
  status ENUM('Menunggu','Diproses','Selesai','Ditolak') NOT NULL DEFAULT 'Menunggu',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_layanan_order FOREIGN KEY (id_order) REFERENCES orders(id_order) ON DELETE CASCADE,
  CONSTRAINT fk_layanan_user FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE SET NULL
) ENGINE=InnoDB;
