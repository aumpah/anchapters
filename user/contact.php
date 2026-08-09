<?php
/** @var mysqli $koneksi */
require_once '../config/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama  = clean($koneksi, $_POST['nama']);
    $email = clean($koneksi, $_POST['email']);
    $pesan = clean($koneksi, $_POST['pesan']);

    if ($nama && filter_var($email, FILTER_VALIDATE_EMAIL) && $pesan) {
        mysqli_query($koneksi, "INSERT INTO contact (nama, email, pesan) VALUES ('$nama', '$email', '$pesan')");
        $_SESSION['flash'] = ['type' => 'success', 'title' => 'Pesan terkirim', 'text' => 'Admin akan segera menghubungi Anda.'];
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'title' => 'Data tidak valid'];
    }
    redirect('user/contact.php');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Contact Us - An Chapters</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container my-5" style="max-width:600px;">
  <h3 class="fw-bold text-center mb-1">Hubungi Kami</h3>
  <p class="text-muted text-center mb-4">Ada pertanyaan? Kirim pesan ke Admin kami.</p>

  <form method="POST" class="card p-4 shadow-sm">
    <div class="mb-3">
      <label class="form-label">Nama</label>
      <input type="text" name="nama" class="form-control" required value="<?= isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : '' ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Pesan</label>
      <textarea name="pesan" class="form-control" rows="4" required></textarea>
    </div>
    <button type="submit" class="btn btn-brand w-100">Kirim Pesan</button>
  </form>
</div>

<?php include 'includes/footer.php'; ?>
