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

  <div class="card p-3 shadow-sm mb-3 contact-direct">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div>
        <div class="fw-semibold">Butuh jawaban lebih cepat?</div>
        <div class="small text-muted">Chat langsung dengan admin An Chapters.</div>
        <div class="small mt-1">WhatsApp: <strong>0857-7739-2970</strong></div>
      </div>
      <a class="btn btn-success px-3" href="<?= htmlspecialchars(whatsapp_url()) ?>" target="_blank" rel="noopener noreferrer">
        Chat WhatsApp <span aria-hidden="true">↗</span>
      </a>
    </div>
  </div>

  <div class="d-flex flex-wrap justify-content-center gap-3 small text-muted mb-4">
    <a href="mailto:<?= CONTACT_EMAIL ?>" class="text-decoration-none">✉ <?= CONTACT_EMAIL ?></a>
    <a href="https://www.instagram.com/<?= INSTAGRAM_HANDLE ?>/" target="_blank" rel="noopener noreferrer" class="text-decoration-none">Instagram @<?= INSTAGRAM_HANDLE ?></a>
  </div>


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
