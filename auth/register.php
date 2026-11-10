<?php
/** @var mysqli $koneksi */
require_once '../config/functions.php';

if (is_login()) {
    redirect(is_admin() ? 'admin/dashboard.php' : 'user/home.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama    = clean($koneksi, $_POST['nama']);
    $email   = clean($koneksi, $_POST['email']);
    $password = $_POST['password'];
    $alamat  = clean($koneksi, $_POST['alamat']);
    $telepon = clean($koneksi, $_POST['telepon']);

    // Validasi
    if (strlen($password) < 8) {
        $errors[] = "Password minimal 8 karakter.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    } else {
        $cek = mysqli_query($koneksi, "SELECT id_user FROM users WHERE email = '$email'");
        if (mysqli_num_rows($cek) > 0) {
            $errors[] = "Email sudah terdaftar, gunakan email lain.";
        }
    }
    if (empty($nama)) {
        $errors[] = "Nama wajib diisi.";
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $q = "INSERT INTO users (nama, email, password, alamat, telepon, role)
              VALUES ('$nama', '$email', '$hash', '$alamat', '$telepon', 'user')";
        if (mysqli_query($koneksi, $q)) {
            $newUserId = (int) mysqli_insert_id($koneksi);
            // Pesanan guest dengan email yang sama ikut muncul di riwayat akun baru.
            mysqli_query($koneksi, "UPDATE orders SET id_user=$newUserId WHERE id_user IS NULL AND email_penerima='$email'");
            $_SESSION['register_success'] = true;
            redirect('auth/login.php');
        } else {
            $errors[] = "Terjadi kesalahan sistem, coba lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Registrasi - An Chapters</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="container d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="card p-4 shadow-sm" style="max-width:480px;width:100%;border-radius:14px;">
        <h3 class="text-center mb-1" style="color:var(--ac-brown);font-weight:700;">An Chapters</h3>
        <p class="text-center text-muted mb-4">Buat akun baru</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e) echo "<li>$e</li>"; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" required value="<?= htmlspecialchars($_POST['nama'] ?? ($_GET['nama'] ?? '')) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? ($_GET['email'] ?? '')) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required minlength="8">
                <div class="form-text">Minimal 8 karakter.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Alamat</label>
                <textarea name="alamat" class="form-control" rows="2"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">No. Telepon</label>
                <input type="text" name="telepon" class="form-control">
            </div>
            <button type="submit" class="btn btn-brand w-100">Daftar</button>
        </form>
        <p class="text-center mt-3 mb-0">Sudah punya akun? <a href="login.php">Login di sini</a></p>
    </div>
</div>
</body>
</html>
