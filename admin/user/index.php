<?php
/** @var mysqli $koneksi */
require_once '../../config/functions.php';
cek_login_admin();

if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM users WHERE id_user=$id AND role='user'");
    $_SESSION['flash'] = ['type'=>'success','title'=>'User berhasil dihapus'];
    redirect('admin/user/index.php');
}

$list = mysqli_query($koneksi, "SELECT * FROM users WHERE role='user' ORDER BY created_at DESC");

$page_title = 'Data User';
include '../includes/header.php';
?>

<h5 class="fw-bold mb-3">Daftar User Terdaftar</h5>

<div class="card p-3 shadow-sm">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>#</th><th>Nama</th><th>Email</th><th>Telepon</th><th>Alamat</th><th>Terdaftar</th><th></th></tr></thead>
      <tbody>
      <?php $no=1; while ($u = mysqli_fetch_assoc($list)): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= htmlspecialchars($u['nama']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><?= htmlspecialchars($u['telepon']) ?></td>
          <td><?= htmlspecialchars($u['alamat']) ?></td>
          <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
          <td><a href="?hapus=<?= $u['id_user'] ?>" class="btn btn-sm btn-outline-danger btn-hapus">Hapus</a></td>
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
    Swal.fire({title:'Hapus user ini?', icon:'warning', showCancelButton:true, confirmButtonText:'Ya, hapus', confirmButtonColor:'#5b3a29'})
      .then(res=>{ if(res.isConfirmed) window.location.href = href; });
  });
});
</script>

<?php include '../includes/footer.php'; ?>
