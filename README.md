# An Chapters — Website Bookstore 
## 📁 Struktur Folder

```
anchapters/
├── assets/
│   ├── css/style.css
│   ├── js/
│   └── images/cover/        <- tempat cover buku (upload_cover() menyimpan di sini)
├── config/
│   ├── koneksi.php          <- koneksi database (host, user, pass, dbname)
│   └── functions.php        <- helper: session, redirect, proteksi login, upload cover, format rupiah
├── admin/
│   ├── dashboard.php
│   ├── includes/ (sidebar, header, footer)
│   ├── buku/     (index, tambah, edit — hapus lewat query GET di index)
│   ├── kategori/ (index — tambah/edit pakai modal, hapus lewat GET)
│   ├── user/     (index — list + hapus)
│   ├── order/    (index, detail — update status)
│   └── contact/  (index — list pesan masuk, tandai dibaca, hapus)
├── user/
│   ├── home.php      (search + filter kategori + sort harga + pagination)
│   ├── detail.php    (detail buku + add to cart)
│   ├── cart.php       (keranjang: update qty, hapus)
│   ├── checkout.php  (checkout + Payment at Delivery/COD)
│   ├── history.php   (riwayat pesanan + badge status)
│   ├── about.php
│   ├── contact.php   (kirim pesan ke admin)
│   └── includes/ (navbar, footer)
├── auth/
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── database.sql       <- import ke phpMyAdmin/MySQL

http://localhost/anchapters/`

## ✅ Fitur yang Sudah Diimplementasikan

**Admin**
- Add / Update / Delete Kategori Buku (modal + SweetAlert konfirmasi hapus)
- Add / Update / Delete Data Buku (upload cover, validasi stok/harga tidak minus)
- List User terdaftar
- List & Detail pesanan + update status (Pending → Dibayar → Diproses → Dikirim → Selesai)
- Dashboard dengan card: Total Buku, Total User, Total Order, Pendapatan
- Kelola pesan masuk dari Contact Us

**User**
- Registrasi (validasi email unik, password ≥ 8 karakter)
- Login / Logout
- Halaman About Us
- Contact to Admin (tersimpan ke tabel `contact`, muncul di admin)
- Pencarian buku (judul / penulis / kategori) + filter kategori + filter harga (murah/mahal) + pagination 10/halaman
- Add to Cart, update qty, hapus item
- Checkout dengan Payment at Delivery (COD)
- Riwayat pesanan dengan badge status

## 🔐 Keamanan Dasar yang Diterapkan
- Password di-hash dengan `password_hash()` / `password_verify()`.
- Query menggunakan `mysqli_real_escape_string()` melalui fungsi `clean()` (untuk level pemula;
  jika ingin lebih aman, bisa ditingkatkan ke prepared statement / PDO).
- Proteksi halaman dengan `cek_login_user()` dan `cek_login_admin()`.
- Validasi upload cover: hanya jpg/jpeg/png/webp, maksimal 2MB.
- Stok tidak bisa minus (`GREATEST(0, stok-qty)` saat checkout + validasi form admin).
