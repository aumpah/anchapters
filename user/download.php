<?php
require_once '../config/functions.php';
$id_buku = (int) ($_GET['id'] ?? 0);
$book = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT judul,file_digital,format_buku FROM buku WHERE id_buku=$id_buku LIMIT 1"));
if (!$book || $book['format_buku'] !== 'E-book' || empty($book['file_digital'])) {
    http_response_code(404); exit('File tidak tersedia.');
}

$verified = false;
if (is_login() && !is_admin()) {
    $id_user = (int) $_SESSION['id_user'];
    $purchase = mysqli_query($koneksi, "SELECT 1 FROM orders o JOIN order_detail od ON od.id_order=o.id_order WHERE o.id_user=$id_user AND od.id_buku=$id_buku AND o.status IN ('Dibayar','Diproses','Dikirim','Selesai') LIMIT 1");
    $verified = mysqli_num_rows($purchase) > 0;
} else {
    $kode = trim($_GET['kode'] ?? '');
    $email = trim($_GET['email'] ?? '');
    if ($kode !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = mysqli_prepare($koneksi, "SELECT 1 FROM orders o JOIN order_detail od ON od.id_order=o.id_order WHERE o.kode_pesanan=? AND o.email_penerima=? AND od.id_buku=? AND o.status IN ('Dibayar','Diproses','Dikirim','Selesai') LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'ssi', $kode, $email, $id_buku);
        mysqli_stmt_execute($stmt);
        $verified = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
    }
}
if (!$verified) {
    http_response_code(403); exit('E-book hanya tersedia bagi pembeli dengan pesanan yang sudah diverifikasi.');
}

$filename = basename($book['file_digital']);
$path = __DIR__ . '/../storage/private/ebooks/' . $filename;
if (!is_file($path)) { http_response_code(404); exit('File digital belum tersedia.'); }
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
header('Content-Type: ' . ($ext === 'epub' ? 'application/epub+zip' : 'application/pdf'));
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $book['judul']) . '.' . $ext . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
