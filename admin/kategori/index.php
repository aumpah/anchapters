<?php
/** @var mysqli $koneksi */
require_once '../../config/functions.php';
cek_login_admin();

// ---------- Tambah ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $nama = clean($koneksi, $_POST['nama_kategori']);
    $desk = clean($koneksi, $_POST['deskripsi']);

    $gambar = 'default.jpg';
    $uploadResult = upload_gambar_kategori($_FILES['gambar'] ?? null);
    if ($uploadResult !== false && $uploadResult !== 'nochange') {
        $gambar = $uploadResult;
    }

    mysqli_query($koneksi, "INSERT INTO kategori (nama_kategori, deskripsi, gambar) VALUES ('$nama', '$desk', '$gambar')");
    $_SESSION['flash'] = ['type'=>'success','title'=>'Kategori berhasil ditambah'];
    redirect('admin/kategori/index.php');
}

// ---------- Edit ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $id = (int) $_POST['id_kategori'];
    $nama = clean($koneksi, $_POST['nama_kategori']);
    $desk = clean($koneksi, $_POST['deskripsi']);

    $old = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT gambar FROM kategori WHERE id_kategori=$id"));
    $gambar = $old['gambar'] ?? 'default.jpg';

    $uploadResult = upload_gambar_kategori($_FILES['gambar'] ?? null, $gambar);
    if ($uploadResult !== false && $uploadResult !== 'nochange') {
        $gambar = $uploadResult;
    }

    mysqli_query($koneksi, "UPDATE kategori SET nama_kategori='$nama', deskripsi='$desk', gambar='$gambar' WHERE id_kategori=$id");
    $_SESSION['flash'] = ['type'=>'success','title'=>'Kategori berhasil diupdate'];
    redirect('admin/kategori/index.php');
}

// ---------- Hapus ----------
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM kategori WHERE id_kategori=$id");
    $_SESSION['flash'] = ['type'=>'success','title'=>'Kategori berhasil dihapus'];
    redirect('admin/kategori/index.php');
}

$list = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY id_kategori DESC");

$page_title = 'Kelola Kategori';
include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold m-0">Daftar Kategori</h5>
  <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#modalTambah">+ Tambah Kategori</button>
</div>

<div class="card p-3 shadow-sm">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>Gambar</th><th>Nama Kategori</th><th>Deskripsi</th><th style="width:150px;">Aksi</th></tr></thead>
      <tbody>
      <?php while ($k = mysqli_fetch_assoc($list)): ?>
        <tr>
          <td>
            <img src="<?= BASE_URL ?>assets/images/kategori/<?= htmlspecialchars($k['gambar'] ?? 'default.jpg') ?>"
                 onerror="this.src='<?= BASE_URL ?>assets/images/kategori/default.jpg'"
                 style="width:60px;height:45px;object-fit:cover;border-radius:6px;">
          </td>
          <td><?= htmlspecialchars($k['nama_kategori']) ?></td>
          <td><?= htmlspecialchars($k['deskripsi']) ?></td>
          <td>
            <button class="btn btn-sm btn-outline-primary"
              data-bs-toggle="modal" data-bs-target="#modalEdit"
              data-id="<?= $k['id_kategori'] ?>"
              data-nama="<?= htmlspecialchars($k['nama_kategori']) ?>"
              data-desk="<?= htmlspecialchars($k['deskripsi']) ?>">Edit</button>
            <a href="?hapus=<?= $k['id_kategori'] ?>" class="btn btn-sm btn-outline-danger btn-hapus">Hapus</a>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content" enctype="multipart/form-data">
      <div class="modal-header"><h6 class="modal-title fw-bold">Tambah Kategori</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">Nama Kategori</label><input type="text" name="nama_kategori" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control" rows="3"></textarea></div>
        <div class="mb-3">
          <label class="form-label">Gambar Kategori</label>
          <input type="file" name="gambar" class="form-control" accept=".jpg,.jpeg,.png,.webp">
          <div class="form-text">Format jpg/jpeg/png/webp, maksimal 2MB. Gambar ini yang muncul di section "Jelajahi Kategori".</div>
        </div>
      </div>
      <div class="modal-footer"><button type="submit" name="tambah" class="btn btn-brand">Simpan</button></div>
    </form>
  </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content" enctype="multipart/form-data">
      <div class="modal-header"><h6 class="modal-title fw-bold">Edit Kategori</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="id_kategori" id="edit_id">
        <div class="mb-3"><label class="form-label">Nama Kategori</label><input type="text" name="nama_kategori" id="edit_nama" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="deskripsi" id="edit_desk" class="form-control" rows="3"></textarea></div>
        <div class="mb-3">
          <label class="form-label">Ganti Gambar Kategori (opsional)</label>
          <input type="file" name="gambar" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        </div>
      </div>
      <div class="modal-footer"><button type="submit" name="edit" class="btn btn-brand">Update</button></div>
    </form>
  </div>
</div>

<script>
document.getElementById('modalEdit').addEventListener('show.bs.modal', function (e) {
  const btn = e.relatedTarget;
  document.getElementById('edit_id').value = btn.dataset.id;
  document.getElementById('edit_nama').value = btn.dataset.nama;
  document.getElementById('edit_desk').value = btn.dataset.desk;
});
document.querySelectorAll('.btn-hapus').forEach(btn=>{
  btn.addEventListener('click', function(e){
    e.preventDefault();
    const href = this.href;
    Swal.fire({title:'Hapus kategori ini?', icon:'warning', showCancelButton:true, confirmButtonText:'Ya, hapus', confirmButtonColor:'#5b3a29'})
      .then(res=>{ if(res.isConfirmed) window.location.href = href; });
  });
});
</script>

<?php include '../includes/footer.php'; ?>
