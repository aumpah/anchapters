<?php
/** @var mysqli $koneksi */
require_once '../../config/functions.php';
cek_login_admin();

if (isset($_GET['baca'])) {
    $id = (int) $_GET['baca'];
    mysqli_query($koneksi, "UPDATE contact SET status_baca='Sudah Dibaca' WHERE id_contact=$id");
    redirect('admin/contact/index.php');
}
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM contact WHERE id_contact=$id");
    $_SESSION['flash'] = ['type'=>'success','title'=>'Pesan dihapus'];
    redirect('admin/contact/index.php');
}

$list = mysqli_query($koneksi, "SELECT * FROM contact ORDER BY tanggal DESC");

$page_title = 'Pesan Masuk';
include '../includes/header.php';
?>

<h5 class="fw-bold mb-3">Pesan dari User</h5>

<div class="row g-3">
<?php while ($c = mysqli_fetch_assoc($list)): ?>
  <div class="col-md-6">
    <div class="card p-3 shadow-sm h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <strong><?= htmlspecialchars($c['nama']) ?></strong>
          <div class="text-muted small"><?= htmlspecialchars($c['email']) ?></div>
        </div>
        <span class="badge <?= $c['status_baca']=='Belum Dibaca' ? 'bg-warning text-dark' : 'bg-success' ?>"><?= $c['status_baca'] ?></span>
      </div>
      <p class="mt-2 mb-2"><?= nl2br(htmlspecialchars($c['pesan'])) ?></p>
      <div class="d-flex justify-content-between align-items-center">
        <span class="text-muted small"><?= date('d M Y, H:i', strtotime($c['tanggal'])) ?></span>
        <div class="d-flex gap-1">
          <?php if ($c['status_baca'] == 'Belum Dibaca'): ?>
            <a href="?baca=<?= $c['id_contact'] ?>" class="btn btn-sm btn-outline-primary">Tandai Dibaca</a>
          <?php endif; ?>
          <a href="mailto:<?= htmlspecialchars($c['email']) ?>" class="btn btn-sm btn-brand">Balas via Email</a>
          <a href="?hapus=<?= $c['id_contact'] ?>" class="btn btn-sm btn-outline-danger btn-hapus">Hapus</a>
        </div>
      </div>
    </div>
  </div>
<?php endwhile; ?>
</div>

<script>
document.querySelectorAll('.btn-hapus').forEach(btn=>{
  btn.addEventListener('click', function(e){
    e.preventDefault();
    const href = this.href;
    Swal.fire({title:'Hapus pesan ini?', icon:'warning', showCancelButton:true, confirmButtonText:'Ya, hapus', confirmButtonColor:'#5b3a29'})
      .then(res=>{ if(res.isConfirmed) window.location.href = href; });
  });
});
</script>

<?php include '../includes/footer.php'; ?>
