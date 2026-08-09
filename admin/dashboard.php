<?php
/** @var mysqli $koneksi */
require_once '../config/functions.php';
cek_login_admin();

$totalBuku = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) c FROM buku"))['c'];
$totalUser = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) c FROM users WHERE role='user'"))['c'];
$totalOrder = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) c FROM orders"))['c'];
$pendapatan = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(total) t FROM orders WHERE status='Selesai'"))['t'] ?? 0;

$page_title = 'Dashboard';
include 'includes/header.php';
?>

<div class="row g-4 mb-4">
  <div class="col-md-3">
    <div class="dashboard-card bg-brand1">
      <div class="small opacity-75">Total Buku</div>
      <div class="fs-2 fw-bold"><?= $totalBuku ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="dashboard-card bg-brand2">
      <div class="small opacity-75">Total User</div>
      <div class="fs-2 fw-bold"><?= $totalUser ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="dashboard-card bg-brand3">
      <div class="small opacity-75">Total Order</div>
      <div class="fs-2 fw-bold"><?= $totalOrder ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="dashboard-card bg-brand4">
      <div class="small opacity-75">Pendapatan</div>
      <div class="fs-4 fw-bold"><?= format_rupiah($pendapatan) ?></div>
    </div>
  </div>
</div>

<div class="card p-4 shadow-sm">
  <h6 class="fw-bold mb-3">Pesanan Terbaru</h6>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr><th>#</th><th>User</th><th>Tanggal</th><th>Total</th><th>Status</th></tr>
      </thead>
      <tbody>
      <?php
      $q = mysqli_query($koneksi, "SELECT o.*, u.nama FROM orders o JOIN users u ON o.id_user=u.id_user ORDER BY o.tanggal DESC LIMIT 5");
      while ($o = mysqli_fetch_assoc($q)):
      ?>
        <tr>
          <td>#<?= $o['id_order'] ?></td>
          <td><?= htmlspecialchars($o['nama']) ?></td>
          <td><?= date('d M Y', strtotime($o['tanggal'])) ?></td>
          <td><?= format_rupiah($o['total']) ?></td>
          <td><?= badge_status($o['status']) ?></td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
