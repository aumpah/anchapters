<?php
/** @var mysqli $koneksi */
require_once '../../config/functions.php';
cek_login_admin();

if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    $b = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT cover FROM buku WHERE id_buku=$id"));
    if ($b && $b['cover'] && $b['cover'] !== 'default.png') {
        @unlink(__DIR__ . '/../../assets/images/cover/' . basename($b['cover']));
    }
    mysqli_query($koneksi, "DELETE FROM buku WHERE id_buku=$id");
    $_SESSION['flash'] = ['type'=>'success','title'=>'Buku berhasil dihapus'];
    redirect('admin/buku/index.php');
}

$keyword = isset($_GET['q']) ? clean($koneksi, $_GET['q']) : '';
$where = $keyword ? "WHERE b.judul LIKE '%$keyword%' OR b.penulis LIKE '%$keyword%'" : '';

$list = mysqli_query($koneksi, "SELECT b.*, k.nama_kategori FROM buku b LEFT JOIN kategori k ON b.id_kategori=k.id_kategori $where ORDER BY b.id_buku DESC");

$page_title = 'Kelola Buku';
include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h5 class="fw-bold m-0">Daftar Buku</h5>
  <div class="d-flex gap-2">
    <form method="GET" class="d-flex gap-2">
      <input type="text" name="q" class="form-control" placeholder="Cari judul/penulis..." value="<?= htmlspecialchars($keyword) ?>">
      <button class="btn btn-outline-secondary">Cari</button>
    </form>
    <a href="tambah.php" class="btn btn-brand text-nowrap">+ Tambah Buku</a>
  </div>
</div>

<div class="card p-3 shadow-sm">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>Cover</th><th>Judul</th><th>Penulis</th><th>Kategori</th><th>Harga</th><th>Stok</th><th style="width:150px;">Aksi</th></tr></thead>
      <tbody>
      <?php while ($b = mysqli_fetch_assoc($list)): ?>
        <tr>
          <td><img src="<?= cover_url($b['cover']) ?>" onerror="this.onerror=null;this.src='<?= cover_url() ?>'" style="width:45px;height:60px;object-fit:cover;border-radius:4px;"></td>
          <td><?= htmlspecialchars($b['judul']) ?></td>
          <td><?= htmlspecialchars($b['penulis']) ?></td>
          <td><?= htmlspecialchars($b['nama_kategori'] ?? '-') ?></td>
          <td><?= format_rupiah($b['harga']) ?></td>
          <td>
            <?= $b['stok'] ?>
            <?php if ($b['stok'] <= 5): ?><span class="badge bg-danger ms-1">Menipis</span><?php endif; ?>
          </td>
          <td>
            <a href="edit.php?id=<?= $b['id_buku'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
            <a href="?hapus=<?= $b['id_buku'] ?>" class="btn btn-sm btn-outline-danger btn-hapus">Hapus</a>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.querySelectorAll('.btn-hapus').forEach(btn=>{
  btn.addEventListener('click', function(e){
    e.preventDefault();
    const href = this.href;
    Swal.fire({title:'Hapus buku ini?', icon:'warning', showCancelButton:true, confirmButtonText:'Ya, hapus', confirmButtonColor:'#5b3a29'})
      .then(res=>{ if(res.isConfirmed) window.location.href = href; });
  });
});
</script>

<?php include '../includes/footer.php'; ?>
