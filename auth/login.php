<?php
/** @var mysqli $koneksi */
require_once '../config/functions.php';

if (is_login()) {
    redirect(is_admin() ? 'admin/dashboard.php' : 'user/home.php');
}

$error = null;
$success = isset($_SESSION['register_success']);
unset($_SESSION['register_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean($koneksi, $_POST['email']);
    $password = $_POST['password'];

    $q = mysqli_query($koneksi, "SELECT * FROM users WHERE email = '$email' LIMIT 1");
    $user = mysqli_fetch_assoc($q);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['nama']    = $user['nama'];
        $_SESSION['role']    = $user['role'];

        redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'user/home.php');
    } else {
        $error = "Email atau password salah.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - An Chapters</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="container d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="card p-4 shadow-sm" style="max-width:420px;width:100%;border-radius:14px;">
        <h3 class="text-center mb-1" style="color:var(--ac-brown);font-weight:700;">An Chapters</h3>
        <p class="text-center text-muted mb-4">Masuk ke akun Anda</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-brand w-100">Login</button>
        </form>
        <p class="text-center mt-3 mb-0">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
    </div>
</div>

<?php if ($success): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
Swal.fire({icon:'success', title:'Registrasi berhasil', text:'Silakan login dengan akun baru Anda.'});
</script>
<?php endif; ?>
</body>
</html>
