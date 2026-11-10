<?php
date_default_timezone_set('Asia/Jakarta');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/koneksi.php';

// Base URL project, sesuaikan jika nama folder project berbeda
define('BASE_URL', '/anchapters/');

// Kontak brand. Email dan Instagram di bawah adalah kandidat placeholder,
// bukan akun yang sudah dibuat atau diverifikasi.
define('WA_ADMIN', '6285777392970');
define('CONTACT_EMAIL', 'anchapters.bookstore@gmail.com');
define('INSTAGRAM_HANDLE', 'anchapters.bookstore');

function whatsapp_url($message = 'Halo An Chapters, saya ingin bertanya tentang koleksi buku.') {
    return 'https://wa.me/' . WA_ADMIN . '?text=' . rawurlencode($message);
}

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

// ---------- Cover buku: pakai cover default jika belum ada foto ----------
// Dipakai di semua tempat yang menampilkan cover buku.
// Jika kolom cover kosong / NULL / 'default.png' / file-nya tidak ada di folder,
// otomatis yang tampil adalah assets/images/cover/default.png
function cover_url($cover = null) {
    $dir = __DIR__ . '/../assets/images/cover/';
    $nama = $cover ? basename($cover) : '';
    if ($nama !== '' && is_file($dir . $nama)) {
        return BASE_URL . 'assets/images/cover/' . rawurlencode($nama);
    }
    return BASE_URL . 'assets/images/cover/default.png';
}

// ---------- Konfigurasi pembayaran ----------
function qris_url() {
    foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
        if (is_file(__DIR__ . '/../assets/images/qris.' . $ext)) {
            return BASE_URL . 'assets/images/qris.' . $ext;
        }
    }
    return BASE_URL . 'assets/images/qris.png';
}
define('QRIS_IMAGE', qris_url());

// Isi rekening asli toko di sini sebelum mengaktifkan Transfer Bank.
// Sengaja kosong agar nomor rekening contoh tidak dianggap sebagai rekening pembayaran.
$GLOBALS['BANK_LIST'] = [
    'BCA' => [
        'no' => '123456789',
        'an' => 'Aufa Atthallah'
    ],

    'BRI' => [
        'no' => '123456789',
        'an' => 'Aufa Atthallah'
    ],

    'BNI' => [
        'no' => '123456789',
        'an' => 'Aufa Atthallah'
    ],

    'Mandiri' => [
        'no' => '123456789',
        'an' => 'Aufa Atthallah'
    ],

    'BSI' =>[
        'no' => '123456789',
        'an' => 'Aufa Atthallah'
    ]
];

// ---------- Upload bukti pembayaran (opsional) ----------
function upload_bukti($file) {
    // return nama file, 'nochange' jika tidak upload, false jika gagal
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return 'nochange';
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
        return false;
    }
    if ($file['size'] > 3 * 1024 * 1024) { // max 3MB
        return false;
    }
    $target_dir = __DIR__ . '/../assets/images/bukti/';
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $new_name = 'bukti_' . uniqid() . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $target_dir . $new_name)) {
        return $new_name;
    }
    return false;
}

// ---------- Upload file PDF (sample baca publik) ----------
function upload_sample_pdf($file, $old_file = null) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) return 'nochange';
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 8 * 1024 * 1024) return false;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mime = function_exists('finfo_open')
        ? (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name'])
        : 'application/pdf';
    if ($ext !== 'pdf' || $mime !== 'application/pdf') return false;

    $target_dir = __DIR__ . '/../assets/files/samples/';
    if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
    $new_name = 'sample_' . bin2hex(random_bytes(8)) . '.pdf';
    if (!move_uploaded_file($file['tmp_name'], $target_dir . $new_name)) return false;

    if ($old_file) {
        $old_path = $target_dir . basename($old_file);
        if (is_file($old_path)) @unlink($old_path);
    }
    return $new_name;
}

// ---------- Upload file digital secara privat ----------
function upload_ebook_file($file, $old_file = null) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) return 'nochange';
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 30 * 1024 * 1024) return false;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'epub'], true)) return false;
    if (function_exists('finfo_open')) {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $validMime = $ext === 'pdf'
            ? $mime === 'application/pdf'
            : in_array($mime, ['application/epub+zip', 'application/zip', 'application/octet-stream'], true);
        if (!$validMime) return false;
    }

    $target_dir = __DIR__ . '/../storage/private/ebooks/';
    if (!is_dir($target_dir)) mkdir($target_dir, 0750, true);
    $new_name = 'ebook_' . bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $target_dir . $new_name)) return false;

    if ($old_file) {
        $old_path = $target_dir . basename($old_file);
        if (is_file($old_path)) @unlink($old_path);
    }
    return $new_name;
}
