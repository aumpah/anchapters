# An Chapters — Website Bookstore (PHP Native + MySQL)

Website toko buku dengan 2 aktor: **Admin** dan **User**, dibangun dengan PHP native (mysqli),
Bootstrap 5, dan SweetAlert2.

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
└── index.php           <- redirect ke user/home.php
```

## 🔧 Cara Menjalankan (XAMPP / Laragon)

1. **Copy folder** `anchapters/` ke dalam `htdocs` (XAMPP) atau `www` (Laragon).
2. **Import database**:
   - Buka phpMyAdmin → buat database baru bernama `an_chapters` (atau biarkan, karena
     `database.sql` sudah punya `CREATE DATABASE IF NOT EXISTS`).
   - Import file `database.sql`.
3. **Buat akun admin** (karena hash password contoh di SQL hanya placeholder):
   - Daftar dulu lewat halaman Register sebagai user biasa.
   - Lalu di phpMyAdmin, ubah kolom `role` pada tabel `users` untuk akun tersebut dari
     `user` menjadi `admin`. Setelah itu login ulang → otomatis masuk ke dashboard admin.
4. **Cek `config/koneksi.php`** — sesuaikan `$user`, `$pass`, `$dbname` jika berbeda dari default XAMPP (`root`, password kosong).
5. **Cek `BASE_URL` di `config/functions.php`** — sesuaikan dengan nama folder project kamu.
   Contoh: jika folder project bernama `bookstore`, ubah jadi:
   ```php
   define('BASE_URL', '/bookstore/');
   ```
6. Buka browser: `http://localhost/anchapters/`

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

## 🚀 Kemungkinan Pengembangan Lanjutan
- Ganti query mysqli manual menjadi PDO + prepared statement penuh.
- Tambah fitur reset password via email.
- Tambah role-based middleware terpusat (saat ini masih per-file `cek_login_admin()`).
- Integrasi payment gateway asli jika ingin selain COD.
