<?php
/** @var mysqli $koneksi */
require_once '../config/functions.php';
cek_login_user();
$id_user = $_SESSION['id_user'];

$user = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM users WHERE id_user=$id_user"));

$q = mysqli_query($koneksi, "SELECT c.*, b.judul, b.harga, b.stok FROM cart c JOIN buku b ON c.id_buku=b.id_buku WHERE c.id_user=$id_user");
$items = [];
$total = 0;
while ($row = mysqli_fetch_assoc($q)) {
    $items[] = $row;
    $total += $row['subtotal'];
}

if (empty($items)) {
    redirect('user/cart.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alamat_kirim = clean($koneksi, $_POST['alamat_kirim']);

    // Cek ulang stok sebelum proses (mencegah stok minus)
    $cukup = true;
    foreach ($items as $it) {
        if ($it['qty'] > $it['stok']) { $cukup = false; break; }
    }

    if (!$cukup) {
        $_SESSION['flash'] = ['type' => 'error', 'title' => 'Ada item melebihi stok, cek kembali keranjang Anda'];
        redirect('user/cart.php');
    }

    mysqli_begin_transaction($koneksi);
    try {
        mysqli_query($koneksi, "INSERT INTO orders (id_user, total, status, alamat_kirim) VALUES ($id_user, $total, 'Pending', '$alamat_kirim')");
        $id_order = mysqli_insert_id($koneksi);

        foreach ($items as $it) {
            mysqli_query($koneksi, "INSERT INTO order_detail (id_order, id_buku, qty, harga, subtotal)
                VALUES ($id_order, {$it['id_buku']}, {$it['qty']}, {$it['harga']}, {$it['subtotal']})");
            // kurangi stok, pastikan tidak minus
            mysqli_query($koneksi, "UPDATE buku SET stok = GREATEST(0, stok - {$it['qty']}) WHERE id_buku={$it['id_buku']}");
        }

        mysqli_query($koneksi, "DELETE FROM cart WHERE id_user=$id_user");
        mysqli_commit($koneksi);

        $_SESSION['flash'] = ['type' => 'success', 'title' => 'Pesanan berhasil dibuat', 'text' => 'Pembayaran dilakukan saat buku diterima (COD).'];
        redirect('user/history.php');
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $_SESSION['flash'] = ['type' => 'error', 'title' => 'Gagal memproses pesanan'];
        redirect('user/checkout.php');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Checkout - An Chapters</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container my-5">
  <h3 class="fw-bold mb-4">Checkout</h3>
  <div class="row g-4">
    <div class="col-md-7">
      <div class="card p-3 shadow-sm">
        <h6 class="fw-bold mb-3">Ringkasan Pesanan</h6>
        <?php foreach ($items as $it): ?>
          <div class="d-flex justify-content-between border-bottom py-2">
            <span><?= htmlspecialchars($it['judul']) ?> x<?= $it['qty'] ?></span>
            <span><?= format_rupiah($it['subtotal']) ?></span>
          </div>
        <?php endforeach; ?>
        <div class="d-flex justify-content-between pt-3">
          <strong>Total</strong>
          <strong class="harga"><?= format_rupiah($total) ?></strong>
        </div>
      </div>
    </div>
    <div class="col-md-5">
      <div class="card p-3 shadow-sm">
        <h6 class="fw-bold mb-3">Alamat & Pembayaran</h6>
        <form method="POST">
          <div class="mb-3">
            <label class="form-label">Alamat Pengiriman</label>
            <textarea name="alamat_kirim" class="form-control" rows="3" required><?= htmlspecialchars($user['alamat']) ?></textarea>
          </div>
          <div class="alert alert-info small">
            <strong>Payment at Delivery (COD)</strong><br>
            Pembayaran dilakukan tunai saat buku diterima oleh kurir.
          </div>
          <button type="submit" class="btn btn-brand w-100">Buat Pesanan</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
