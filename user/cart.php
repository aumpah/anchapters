<?php
/** @var mysqli $koneksi */
require_once '../config/functions.php';
cek_login_user();
$id_user = $_SESSION['id_user'];

// ---------- Update qty ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_qty'])) {
    $id_cart = (int) $_POST['id_cart'];
    $qty = max(1, (int) $_POST['qty']);

    $row = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT c.*, b.harga, b.stok FROM cart c JOIN buku b ON c.id_buku=b.id_buku WHERE c.id_cart=$id_cart AND c.id_user=$id_user"));
    if ($row) {
        if ($qty > $row['stok']) {
            $_SESSION['flash'] = ['type' => 'error', 'title' => 'Qty melebihi stok tersedia'];
        } else {
            $subtotal = $qty * $row['harga'];
            mysqli_query($koneksi, "UPDATE cart SET qty=$qty, subtotal=$subtotal WHERE id_cart=$id_cart");
            $_SESSION['flash'] = ['type' => 'success', 'title' => 'Keranjang diperbarui'];
        }
    }
    redirect('user/cart.php');
}

// ---------- Hapus item ----------
if (isset($_GET['hapus'])) {
    $id_cart = (int) $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM cart WHERE id_cart=$id_cart AND id_user=$id_user");
    $_SESSION['flash'] = ['type' => 'success', 'title' => 'Item dihapus dari keranjang'];
    redirect('user/cart.php');
}

$q = mysqli_query($koneksi, "SELECT c.*, b.judul, b.cover, b.harga, b.stok FROM cart c
                              JOIN buku b ON c.id_buku = b.id_buku
                              WHERE c.id_user = $id_user");
$total = 0;
$items = [];
while ($row = mysqli_fetch_assoc($q)) {
    $items[] = $row;
    $total += $row['subtotal'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Keranjang - An Chapters</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container my-5">
  <h3 class="fw-bold mb-4">Keranjang Belanja</h3>

  <?php if (empty($items)): ?>
    <div class="text-center py-5">
      <p class="text-muted">Keranjang Anda masih kosong.</p>
      <a href="home.php" class="btn btn-brand">Belanja Sekarang</a>
    </div>
  <?php else: ?>
    <div class="table-responsive bg-white rounded-3 shadow-sm p-3">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>Buku</th>
            <th>Harga</th>
            <th style="width:140px;">Qty</th>
            <th>Subtotal</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <img src="<?= BASE_URL ?>assets/images/cover/<?= htmlspecialchars($it['cover']) ?>" onerror="this.src='<?= BASE_URL ?>assets/images/cover/default.png'" style="width:50px;height:65px;object-fit:cover;border-radius:6px;">
                <span><?= htmlspecialchars($it['judul']) ?></span>
              </div>
            </td>
            <td><?= format_rupiah($it['harga']) ?></td>
            <td>
              <form method="POST" class="d-flex gap-1">
                <input type="hidden" name="id_cart" value="<?= $it['id_cart'] ?>">
                <input type="number" name="qty" value="<?= $it['qty'] ?>" min="1" max="<?= $it['stok'] ?>" class="form-control form-control-sm" style="width:70px;">
                <button type="submit" name="update_qty" class="btn btn-sm btn-outline-secondary">↻</button>
              </form>
            </td>
            <td class="harga"><?= format_rupiah($it['subtotal']) ?></td>
            <td><a href="?hapus=<?= $it['id_cart'] ?>" class="btn btn-sm btn-outline-danger btn-hapus">Hapus</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-content-end mt-4">
      <div class="card p-3" style="min-width:320px;">
        <div class="d-flex justify-content-between mb-2">
          <span>Total</span>
          <span class="harga fs-5"><?= format_rupiah($total) ?></span>
        </div>
        <a href="checkout.php" class="btn btn-brand w-100">Checkout</a>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
<script>
document.querySelectorAll('.btn-hapus').forEach(btn=>{
  btn.addEventListener('click', function(e){
    e.preventDefault();
    const href = this.href;
    Swal.fire({
      title:'Hapus item ini?', icon:'warning', showCancelButton:true,
      confirmButtonText:'Ya, hapus', confirmButtonColor:'#5b3a29'
    }).then(res=>{ if(res.isConfirmed) window.location.href = href; });
  });
});
</script>
