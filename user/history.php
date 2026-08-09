<?php
/** @var mysqli $koneksi */
require_once '../config/functions.php';
cek_login_user();
$id_user = $_SESSION['id_user'];

$orders = mysqli_query($koneksi, "SELECT * FROM orders WHERE id_user=$id_user ORDER BY tanggal DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Pesanan - An Chapters</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container my-5">
  <h3 class="fw-bold mb-4">Riwayat Pesanan</h3>

  <?php if (mysqli_num_rows($orders) === 0): ?>
    <p class="text-muted text-center py-5">Belum ada pesanan.</p>
  <?php endif; ?>

  <?php while ($o = mysqli_fetch_assoc($orders)): ?>
    <div class="card mb-3 shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
          <div>
            <strong>Order #<?= $o['id_order'] ?></strong>
            <span class="text-muted small ms-2"><?= date('d M Y, H:i', strtotime($o['tanggal'])) ?></span>
          </div>
          <?= badge_status($o['status']) ?>
        </div>
        <?php
          $det = mysqli_query($koneksi, "SELECT od.*, b.judul FROM order_detail od JOIN buku b ON od.id_buku=b.id_buku WHERE od.id_order={$o['id_order']}");
          while ($d = mysqli_fetch_assoc($det)):
        ?>
          <div class="d-flex justify-content-between small text-muted">
            <span><?= htmlspecialchars($d['judul']) ?> x<?= $d['qty'] ?></span>
            <span><?= format_rupiah($d['subtotal']) ?></span>
          </div>
        <?php endwhile; ?>
        <hr>
        <div class="d-flex justify-content-between">
          <strong>Total</strong>
          <strong class="harga"><?= format_rupiah($o['total']) ?></strong>
        </div>
      </div>
    </div>
  <?php endwhile; ?>
</div>

<?php include 'includes/footer.php'; ?>
