-- =========================================
-- MIGRASI: fitur metode pembayaran + bukti bayar
-- Jalankan SEKALI di phpMyAdmin (tab SQL) pada database an_chapters
-- =========================================
USE an_chapters;

ALTER TABLE orders
    ADD COLUMN metode_bayar  VARCHAR(30)  NOT NULL DEFAULT 'COD' AFTER alamat_kirim,
    ADD COLUMN detail_bayar  VARCHAR(100) NULL AFTER metode_bayar,
    ADD COLUMN bukti_bayar   VARCHAR(255) NULL AFTER detail_bayar,
    ADD COLUMN tgl_bayar     DATETIME     NULL AFTER bukti_bayar;
