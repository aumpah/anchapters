<?php
require_once '../config/functions.php';
cek_login_user();
$id_user = (int) $_SESSION['id_user'];

if (isset($_GET['hapus'])) {
    $id_buku = (int) $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM wishlist WHERE id_user=$id_user AND id_buku=$id_buku");
    $_SESSION['flash'] = ['type'=>'success','title'=>'Buku dihapus dari wishlist'];
    redirect('user/wishlist.php');
}
$list = mysqli_query($koneksi, "SELECT b.*, w.created_at AS disimpan_pada FROM wishlist w JOIN buku b ON b.id_buku=w.id_buku WHERE w.id_user=$id_user ORDER BY w.created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"><title>Wishlist - An Chapters</title><meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container my-5">
  <h3 class="fw-bold mb-1">Wishlist</h3><p class="text-muted mb-4">Buku yang Anda simpan untuk dibaca nanti.</p>
  <div class="row g-3">
    <?php if (mysqli_num_rows($list) === 0): ?><div class="col-12"><p class="text-muted py-4">Belum ada buku tersimpan.</p></div><?php endif; ?>
    <?php while ($b = mysqli_fetch_assoc($list)): ?>
      <div class="col-6 col-md-3"><div class="card card-buku h-100">
        <a href="detail.php?id=<?= $b['id_buku'] ?>" class="text-decoration-none text-dark"><img src="<?= cover_url($b['cover']) ?>" class="card-img-top" alt="<?= htmlspecialchars($b['judul']) ?>"><div class="card-body"><h6><?= htmlspecialchars($b['judul']) ?></h6><p class="harga mb-2"><?= format_rupiah($b['harga']) ?></p></div></a>
        <div class="px-3 pb-3"><a class="btn btn-outline-danger btn-sm" href="?hapus=<?= $b['id_buku'] ?>">Hapus</a></div>
      </div></div>
    <?php endwhile; ?>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>
