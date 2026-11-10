<?php
require_once '../config/functions.php';
header('Content-Type: application/json; charset=utf-8');

$term = trim($_GET['q'] ?? '');
if ($term === '') {
    echo json_encode([]);
    exit;
}

$prefix = $term . '%';
$stmt = mysqli_prepare($koneksi, "SELECT id_buku, judul, penulis FROM buku WHERE judul LIKE ? OR penulis LIKE ? ORDER BY CASE WHEN judul LIKE ? THEN 0 ELSE 1 END, judul ASC LIMIT 8");
mysqli_stmt_bind_param($stmt, 'sss', $prefix, $prefix, $prefix);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $items[] = [
        'id' => (int) $row['id_buku'],
        'judul' => $row['judul'],
        'penulis' => $row['penulis'],
    ];
}
echo json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
