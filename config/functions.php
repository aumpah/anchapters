<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/koneksi.php';

// Base URL project, sesuaikan jika nama folder project berbeda
define('BASE_URL', '/anchapters/');

// ---------- Helper umum ----------
function format_rupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit;
}

function is_login() {
    return isset($_SESSION['id_user']);
}

function is_admin() {
    return is_login() && $_SESSION['role'] === 'admin';
}

// Panggil di paling atas halaman khusus user yang wajib login
function cek_login_user() {
    if (!is_login() || $_SESSION['role'] !== 'user') {
        redirect('auth/login.php');
    }
}

// Panggil di paling atas halaman admin
function cek_login_admin() {
    if (!is_login() || $_SESSION['role'] !== 'admin') {
        redirect('auth/login.php');
    }
}

function clean($koneksi, $data) {
    return mysqli_real_escape_string($koneksi, trim($data));
}

// ---------- Upload cover buku ----------
function upload_cover($file, $old_cover = null) {
    // return nama file baru, atau false jika gagal, atau 'nochange' jika tidak upload
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return 'nochange';
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        return false;
    }
    if ($file['size'] > 2 * 1024 * 1024) { // max 2MB
        return false;
    }

    $target_dir = __DIR__ . '/../assets/images/cover/';
    $new_name = 'buku_' . uniqid() . '.' . $ext;

    if (move_uploaded_file($file['tmp_name'], $target_dir . $new_name)) {
        // hapus cover lama jika ada dan bukan default
        if ($old_cover && $old_cover !== 'default.png' && file_exists($target_dir . $old_cover)) {
            @unlink($target_dir . $old_cover);
        }
        return $new_name;
    }
    return false;
}

// ---------- Upload gambar kategori ----------
function upload_gambar_kategori($file, $old_gambar = null) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return 'nochange';
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        return false;
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        return false;
    }

    $target_dir = __DIR__ . '/../assets/images/kategori/';
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $new_name = 'kategori_' . uniqid() . '.' . $ext;

    if (move_uploaded_file($file['tmp_name'], $target_dir . $new_name)) {
        if ($old_gambar && $old_gambar !== 'default.jpg' && file_exists($target_dir . $old_gambar)) {
            @unlink($target_dir . $old_gambar);
        }
        return $new_name;
    }
    return false;
}

// ---------- Badge status order (untuk tampilan) ----------
function badge_status($status) {
    $map = [
        'Pending'  => 'secondary',
        'Dibayar'  => 'info',
        'Diproses' => 'warning',
        'Dikirim'  => 'primary',
        'Selesai'  => 'success',
    ];
    $color = $map[$status] ?? 'secondary';
    return "<span class=\"badge bg-$color\">$status</span>";
}

// ---------- Hitung jumlah item di cart (untuk navbar) ----------
function jumlah_cart($koneksi, $id_user) {
    $q = mysqli_query($koneksi, "SELECT SUM(qty) as total FROM cart WHERE id_user = $id_user");
    $r = mysqli_fetch_assoc($q);
    return $r['total'] ?? 0;
}
