<nav class="navbar navbar-expand-lg sticky-top py-3">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>user/home.php">
  <img src="<?= BASE_URL ?>assets/images/logo.png" style="height:36px;"> An Chapters
</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>user/home.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>user/about.php">About Us</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>user/contact.php">Contact</a></li>
        <?php if (is_login() && !is_admin()): ?>
          <li class="nav-item">
            <a class="nav-link position-relative" href="<?= BASE_URL ?>user/cart.php">
              🛒 Cart
              <?php $jc = jumlah_cart($koneksi, $_SESSION['id_user']); if ($jc > 0): ?>
                <span class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle" style="font-size:.6rem;"><?= $jc ?></span>
              <?php endif; ?>
            </a>
          </li>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>user/history.php">Riwayat</a></li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Hai, <?= htmlspecialchars($_SESSION['nama']) ?></a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>auth/logout.php">Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>auth/login.php">Login</a></li>
          <li class="nav-item"><a class="btn btn-brand btn-sm ms-lg-2" href="<?= BASE_URL ?>auth/register.php">Daftar</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
