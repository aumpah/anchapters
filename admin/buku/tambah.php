<?php
/** @var mysqli $koneksi */
require_once '../../config/functions.php';
cek_login_admin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul    = clean($koneksi, $_POST['judul']);
    $penulis  = clean($koneksi, $_POST['penulis']);
    $penerbit = clean($koneksi, $_POST['penerbit']);
    $tahun    = (int) $_POST['tahun'];
    $harga    = (float) $_POST['harga'];
    $stok     = (int) $_POST['stok'];
    $deskripsi = clean($koneksi, $_POST['deskripsi']);
    $id_kategori = (int) $_POST['id_kategori'];
    $isbn = clean($koneksi, $_POST['isbn'] ?? '');
    $jumlah_halaman = ($_POST['jumlah_halaman'] ?? '') !== '' ? max(1, (int) $_POST['jumlah_halaman']) : 'NULL';
    $tanggal_terbit = !empty($_POST['tanggal_terbit']) ? "'" . clean($koneksi, $_POST['tanggal_terbit']) . "'" : 'NULL';
    $bahasa = clean($koneksi, $_POST['bahasa'] ?? 'Indonesia');
    $format_buku = in_array($_POST['format_buku'] ?? 'Fisik', ['Fisik','E-book','Audiobook'], true) ? $_POST['format_buku'] : 'Fisik';
    $format_sql = clean($koneksi, $format_buku);
    $is_preorder = isset($_POST['is_preorder']) ? 1 : 0;
    $tanggal_rilis = !empty($_POST['tanggal_rilis']) ? "'" . clean($koneksi, $_POST['tanggal_rilis']) . "'" : 'NULL';

    if ($stok < 0) $errors[] = "Stok tidak boleh minus.";
    if ($harga < 0) $errors[] = "Harga tidak boleh minus.";
    if (empty($judul)) $errors[] = "Judul wajib diisi.";

    $cover = 'default.png';
    if (empty($errors)) {
        $uploadResult = upload_cover($_FILES['cover'] ?? null);
        if ($uploadResult === false) {
            $errors[] = "Upload cover gagal. Gunakan format jpg/jpeg/png/webp maksimal 2MB.";
        } elseif ($uploadResult !== 'nochange') {
            $cover = $uploadResult;
        }
    }

    $samplePdf = 'NULL';
    $ebookFile = 'NULL';
    if (empty($errors)) {
        $sampleUpload = upload_sample_pdf($_FILES['sample_pdf'] ?? null);
        if ($sampleUpload === false) $errors[] = 'Preview harus berupa PDF maksimal 8MB.';
        elseif ($sampleUpload !== 'nochange') $samplePdf = "'" . clean($koneksi, $sampleUpload) . "'";

        $ebookUpload = upload_ebook_file($_FILES['file_digital'] ?? null);
        if ($ebookUpload === false) $errors[] = 'File E-book harus PDF atau EPUB maksimal 30MB.';
        elseif ($ebookUpload !== 'nochange') $ebookFile = "'" . clean($koneksi, $ebookUpload) . "'";
    }

    if (empty($errors)) {
        $q = "INSERT INTO buku (judul, penulis, isbn, jumlah_halaman, penerbit, tahun, tanggal_terbit, bahasa, format_buku, harga, stok, cover, deskripsi, sample_pdf, file_digital, is_preorder, tanggal_rilis, id_kategori)
              VALUES ('$judul','$penulis','$isbn',$jumlah_halaman,'$penerbit',$tahun,$tanggal_terbit,'$bahasa','$format_sql',$harga,$stok,'$cover','$deskripsi',$samplePdf,$ebookFile,$is_preorder,$tanggal_rilis,$id_kategori)";
        mysqli_query($koneksi, $q);
        $_SESSION['flash'] = ['type'=>'success','title'=>'Buku berhasil ditambah'];
        redirect('admin/buku/index.php');
    }
}

$kategori = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori");
$page_title = 'Tambah Buku';
include '../includes/header.php';
?>

<div class="card p-4 shadow-sm" style="max-width:700px;">
  <h5 class="fw-bold mb-3">Tambah Buku</h5>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Judul</label>
        <input type="text" name="judul" class="form-control" required value="<?= htmlspecialchars($_POST['judul'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Penulis</label>
        <input type="text" name="penulis" class="form-control" required value="<?= htmlspecialchars($_POST['penulis'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Penerbit</label>
        <input type="text" name="penerbit" class="form-control" value="<?= htmlspecialchars($_POST['penerbit'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Tahun</label>
        <input type="number" name="tahun" class="form-control" min="1900" max="<?= date('Y') ?>" value="<?= htmlspecialchars($_POST['tahun'] ?? date('Y')) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Harga (Rp)</label>
        <input type="number" name="harga" class="form-control" min="0" step="100" required value="<?= htmlspecialchars($_POST['harga'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Stok</label>
        <input type="number" name="stok" class="form-control" min="0" required value="<?= htmlspecialchars($_POST['stok'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Kategori</label>
        <select name="id_kategori" class="form-select" required>
          <option value="">Pilih Kategori</option>
          <?php while ($k = mysqli_fetch_assoc($kategori)): ?>
            <option value="<?= $k['id_kategori'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-md-12">
        <label class="form-label">Cover Buku</label>
        <div class="d-flex align-items-center gap-3 mb-2">
          <img id="previewCover" src="<?= cover_url() ?>" style="width:90px;height:120px;object-fit:cover;border-radius:6px;border:1px solid #ddd;">
          <small class="text-muted">Jika tidak upload foto, buku akan memakai <strong>cover default</strong> seperti ini.</small>
        </div>
        <input type="file" name="cover" id="inputCover" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        <div class="form-text">Format jpg/jpeg/png/webp, maksimal 2MB.</div>
      </div>
      <div class="col-md-4">
        <label class="form-label">ISBN</label>
        <input type="text" name="isbn" class="form-control" value="<?= htmlspecialchars($_POST['isbn'] ?? '') ?>" maxlength="20">
      </div>
      <div class="col-md-4">
        <label class="form-label">Jumlah Halaman</label>
        <input type="number" name="jumlah_halaman" class="form-control" min="1" value="<?= htmlspecialchars($_POST['jumlah_halaman'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Bahasa</label>
        <input type="text" name="bahasa" class="form-control" value="<?= htmlspecialchars($_POST['bahasa'] ?? 'Indonesia') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Tanggal Terbit</label>
        <input type="date" name="tanggal_terbit" class="form-control" value="<?= htmlspecialchars($_POST['tanggal_terbit'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Format Buku</label>
        <select name="format_buku" class="form-select">
          <?php foreach (['Fisik','E-book','Audiobook'] as $f): ?>
            <option value="<?= $f ?>" <?= ($_POST['format_buku'] ?? 'Fisik')===$f?'selected':'' ?>><?= $f ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Tanggal Rilis (khusus PO)</label>
        <input type="date" name="tanggal_rilis" class="form-control" value="<?= htmlspecialchars($_POST['tanggal_rilis'] ?? '') ?>">
      </div>
      <div class="col-12">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="is_preorder" id="isPreorder" <?= isset($_POST['is_preorder'])?'checked':'' ?>>
          <label class="form-check-label" for="isPreorder">Tandai sebagai Pre-Order</label>
        </div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Preview PDF (opsional)</label>
        <input type="file" name="sample_pdf" class="form-control" accept="application/pdf,.pdf">
        <div class="form-text">Maksimal 8MB. Gunakan halaman contoh yang memang boleh dibagikan.</div>
      </div>
      <div class="col-md-6">
        <label class="form-label">File E-book (opsional)</label>
        <input type="file" name="file_digital" class="form-control" accept="application/pdf,.pdf,.epub">
        <div class="form-text">PDF/EPUB maksimal 30MB. File disimpan di folder privat.</div>
      </div>
      <div class="col-md-12">
        <label class="form-label">Deskripsi</label>
        <textarea name="deskripsi" class="form-control" rows="4"><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
      </div>
    </div>
    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-brand">Simpan</button>
      <a href="index.php" class="btn btn-outline-secondary">Batal</a>
    </div>
  </form>
</div>

<script>
(function(){
  const input = document.getElementById('inputCover');
  const prev  = document.getElementById('previewCover');
  const def   = prev.src;
  input.addEventListener('change', function(){
    if (this.files && this.files[0]) {
      prev.src = URL.createObjectURL(this.files[0]);
    } else {
      prev.src = def; // batal pilih file -> kembali ke cover default
    }
  });
})();
</script>

<?php include '../includes/footer.php'; ?>
