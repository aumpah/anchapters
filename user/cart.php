<?php
/** @var mysqli $koneksi */
require_once '../config/functions.php';
$isMember = is_login() && !is_admin();
$id_user = $isMember ? (int) $_SESSION['id_user'] : 0;
if (!$isMember && !isset($_SESSION['guest_cart'])) $_SESSION['guest_cart'] = [];

// ---------- Update qty ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_qty'])) {
    $itemId = (int) ($_POST['item_id'] ?? $_POST['id_cart'] ?? 0);
    $qty = max(1, min(99, (int) ($_POST['qty'] ?? 1)));
    if ($isMember) {
        $row = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT c.*, b.harga, b.stok, b.is_preorder FROM cart c JOIN buku b ON b.id_buku=c.id_buku WHERE c.id_cart=$itemId AND c.id_user=$id_user LIMIT 1"));
        if ($row && (empty($row['is_preorder']) ? $qty <= (int)$row['stok'] : true)) {
            $subtotal = $qty * $row['harga'];
            mysqli_query($koneksi, "UPDATE cart SET qty=$qty, subtotal=$subtotal WHERE id_cart=$itemId");
            $_SESSION['flash'] = ['type'=>'success','title'=>'Keranjang diperbarui'];
        } elseif ($row) {
            $_SESSION['flash'] = ['type'=>'error','title'=>'Qty melebihi stok tersedia'];
        }
    } else {
        $book = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT stok,is_preorder FROM buku WHERE id_buku=$itemId LIMIT 1"));
        if ($book && (empty($book['is_preorder']) ? $qty <= (int)$book['stok'] : true)) {
            $_SESSION['guest_cart'][$itemId] = $qty;
            $_SESSION['flash'] = ['type'=>'success','title'=>'Keranjang diperbarui'];
        } elseif ($book) {
            $_SESSION['flash'] = ['type'=>'error','title'=>'Qty melebihi stok tersedia'];
        }
    }
    redirect('user/cart.php');
}

// ---------- Hapus item ----------
if (isset($_GET['hapus'])) {
    $itemId = (int) $_GET['hapus'];
    if ($isMember) {
        mysqli_query($koneksi, "DELETE FROM cart WHERE id_cart=$itemId AND id_user=$id_user");
    } else {
        unset($_SESSION['guest_cart'][$itemId]);
    }
    $_SESSION['flash'] = ['type'=>'success','title'=>'Item dihapus dari keranjang'];
    redirect('user/cart.php');
}

$items = [];
$total = 0;
if ($isMember) {
    $q = mysqli_query($koneksi, "SELECT c.*, b.judul,b.cover,b.harga,b.stok,b.is_preorder FROM cart c JOIN buku b ON b.id_buku=c.id_buku WHERE c.id_user=$id_user");
    while ($row = mysqli_fetch_assoc($q)) $items[] = $row;
} else {
    $guestCart = $_SESSION['guest_cart'] ?? [];
    $ids = array_values(array_filter(array_map('intval', array_keys($guestCart))));
    if ($ids) {
        $idSql = implode(',', $ids);
        $q = mysqli_query($koneksi, "SELECT * FROM buku WHERE id_buku IN ($idSql)");
        while ($book = mysqli_fetch_assoc($q)) {
            $qty = max(1, (int) $guestCart[$book['id_buku']]);
            $items[] = [
                'id_cart' => $book['id_buku'], 'id_buku' => $book['id_buku'],
                'judul' => $book['judul'], 'cover' => $book['cover'], 'harga' => $book['harga'],
                'stok' => $book['stok'], 'is_preorder' => $book['is_preorder'], 'qty' => $qty,
                'subtotal' => $qty * $book['harga'],
            ];
        }
    }
}
foreach ($items as $row) $total += (float) $row['subtotal'];
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
  <h3 class="fw-bold mb-2">Keranjang Belanja</h3>
  <?php if (!$isMember): ?><p class="text-muted small mb-4">Anda berbelanja sebagai tamu. Detail penerima akan diminta saat checkout.</p><?php endif; ?>

  <?php if (empty($items)): ?>
    <div class="text-center py-5">
      <p class="text-muted">Keranjang Anda masih kosong.</p>
      <a href="home.php" class="btn btn-brand">Belanja Sekarang</a>
    </div>
  <?php else: ?>
    <div class="table-responsive bg-white rounded-3 shadow-sm p-3">
      <table class="table align-middle">
        <thead><tr><th>Buku</th><th>Harga</th><th style="width:160px;">Qty</th><th>Subtotal</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td><div class="d-flex align-items-center gap-2"><img src="<?= cover_url($it['cover']) ?>" onerror="this.onerror=null;this.src='<?= cover_url() ?>'" style="width:50px;height:65px;object-fit:cover;border-radius:6px;"><span><?= htmlspecialchars($it['judul']) ?><?php if (!empty($it['is_preorder'])): ?><span class="badge bg-warning-subtle text-dark ms-1">PO</span><?php endif; ?></span></div></td>
            <td><?= format_rupiah($it['harga']) ?></td>
            <td><form method="POST" class="d-flex gap-1"><input type="hidden" name="item_id" value="<?= $it['id_cart'] ?>"><input type="number" name="qty" value="<?= $it['qty'] ?>" min="1" max="<?= !empty($it['is_preorder']) ? 99 : max(1,(int)$it['stok']) ?>" class="form-control form-control-sm" style="width:70px;"><button type="submit" name="update_qty" class="btn btn-sm btn-outline-secondary">↻</button></form></td>
            <td class="harga"><?= format_rupiah($it['subtotal']) ?></td>
            <td><a href="?hapus=<?= $it['id_cart'] ?>" class="btn btn-sm btn-outline-danger btn-hapus">Hapus</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-content-end mt-4">
      <div class="card p-3" style="min-width:320px;">
        <div class="d-flex justify-content-between mb-2"><span>Subtotal Buku</span><span class="harga fs-5"><?= format_rupiah($total) ?></span></div>
        <a href="checkout.php" class="btn btn-brand w-100">Lanjut ke Checkout</a>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
<script>
document.querySelectorAll('.btn-hapus').forEach(btn => {
  btn.addEventListener('click', function (event) {
    event.preventDefault();
    const href = this.href;
    Swal.fire({ title: 'Hapus item ini?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, hapus', confirmButtonColor: '#5b3a29' })
      .then(result => { if (result.isConfirmed) window.location.href = href; });
  });
});
</script>
</body>
</html>
