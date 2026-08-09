<?php
/** @var mysqli $koneksi */
require_once '../config/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$q = mysqli_query($koneksi, "SELECT b.*, k.nama_kategori FROM buku b LEFT JOIN kategori k ON b.id_kategori = k.id_kategori WHERE b.id_buku = $id");
$buku = mysqli_fetch_assoc($q);

if (!$buku) {
    redirect('user/home.php');
}

// ---------- Proses Add to Cart ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_cart'])) {
    cek_login_user();
    $qty = max(1, (int) $_POST['qty']);

    if ($qty > $buku['stok']) {
        $_SESSION['flash'] = ['type' => 'error', 'title' => 'Stok tidak mencukupi'];
    } else {
        $id_user = $_SESSION['id_user'];
        $cek = mysqli_query($koneksi, "SELECT * FROM cart WHERE id_user=$id_user AND id_buku=$id");
        $subtotal = $qty * $buku['harga'];

        if ($row = mysqli_fetch_assoc($cek)) {
            $newQty = $row['qty'] + $qty;
            $newSub = $newQty * $buku['harga'];
            mysqli_query($koneksi, "UPDATE cart SET qty=$newQty, subtotal=$newSub WHERE id_cart={$row['id_cart']}");
        } else {
            mysqli_query($koneksi, "INSERT INTO cart (id_user, id_buku, qty, subtotal) VALUES ($id_user, $id, $qty, $subtotal)");
        }
        $_SESSION['flash'] = ['type' => 'success', 'title' => 'Berhasil ditambahkan ke keranjang'];
    }
    redirect('user/detail.php?id=' . $id);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($buku['judul']) ?> - An Chapters</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container my-5">
  <div class="row g-4">
    <div class="col-md-4">
      <img src="<?= BASE_URL ?>assets/images/cover/<?= htmlspecialchars($buku['cover']) ?>" onerror="this.src='<?= BASE_URL ?>assets/images/cover/default.png'" class="img-fluid rounded-3 shadow-sm w-100" style="object-fit:cover;max-height:480px;">
    </div>
    <div class="col-md-8">
      <span class="badge bg-light text-dark border mb-2"><?= htmlspecialchars($buku['nama_kategori'] ?? '-') ?></span>
      <h2 class="fw-bold"><?= htmlspecialchars($buku['judul']) ?></h2>
      <p class="text-muted mb-1">Penulis: <strong><?= htmlspecialchars($buku['penulis']) ?></strong></p>
      <p class="text-muted mb-1">Penerbit: <?= htmlspecialchars($buku['penerbit']) ?> (<?= htmlspecialchars($buku['tahun']) ?>)</p>
      <p class="text-muted mb-3">Stok: <?= $buku['stok'] ?> unit</p>
      <h3 class="harga mb-2"><?= format_rupiah($buku['harga']) ?></h3>

      <?php if ($buku['stok'] > 0): ?>
        <p class="text-success small mb-3">● Stok tersedia (<?= $buku['stok'] ?> unit)</p>
      <?php else: ?>
        <p class="text-danger small mb-3">● Stok habis</p>
      <?php endif; ?>

      <?php if ($buku['stok'] > 0): ?>
        <form method="POST" class="d-flex align-items-center gap-2 mt-1 mb-4">
          <div class="input-group" style="width:130px;">
            <button type="button" class="btn btn-outline-secondary" id="btnMin">-</button>
            <input type="number" name="qty" id="qtyInput" value="1" min="1" max="<?= $buku['stok'] ?>" class="form-control text-center">
            <button type="button" class="btn btn-outline-secondary" id="btnPlus">+</button>
          </div>
          <button type="submit" name="add_cart" class="btn btn-brand px-4">🛒 Add to Cart</button>
        </form>
      <?php else: ?>
        <button class="btn btn-secondary mb-4" disabled>Stok Habis</button>
      <?php endif; ?>

      <!-- Tabs Deskripsi / Detail -->
      <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabDeskripsi">Deskripsi</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabDetail">Detail</button></li>
      </ul>
      <div class="tab-content border border-top-0 p-3 mb-4">
        <div class="tab-pane fade show active" id="tabDeskripsi">
          <?= nl2br(htmlspecialchars($buku['deskripsi'])) ?>
        </div>
        <div class="tab-pane fade" id="tabDetail">
          <table class="table table-sm mb-0">
            <tr><td class="text-muted" style="width:140px;">Penulis</td><td><?= htmlspecialchars($buku['penulis']) ?></td></tr>
            <tr><td class="text-muted">Penerbit</td><td><?= htmlspecialchars($buku['penerbit']) ?></td></tr>
            <tr><td class="text-muted">Tahun Terbit</td><td><?= htmlspecialchars($buku['tahun']) ?></td></tr>
            <tr><td class="text-muted">Kategori</td><td><?= htmlspecialchars($buku['nama_kategori'] ?? '-') ?></td></tr>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Buku Lainnya -->
  <?php
     $related = mysqli_query($koneksi, "SELECT * FROM buku WHERE id_kategori={$buku['id_kategori']} AND id_buku != {$buku['id_buku']} LIMIT 4");

    // fallback: kalau tidak ada buku lain di kategori yang sama, ambil buku random dari kategori lain
    if (mysqli_num_rows($related) === 0) {
        $related = mysqli_query($koneksi, "SELECT * FROM buku WHERE id_buku != {$buku['id_buku']} ORDER BY RAND() LIMIT 4");
    }

    if (mysqli_num_rows($related) > 0):
  ?>
  <h5 class="fw-bold mt-5 mb-3">Buku Lainnya</h5>
  <div class="row g-4">
    <?php while ($r = mysqli_fetch_assoc($related)): ?>
      <div class="col-6 col-md-3">
        <a href="detail.php?id=<?= $r['id_buku'] ?>" class="text-decoration-none text-dark">
          <div class="card card-buku h-100">
            <img src="<?= BASE_URL ?>assets/images/cover/<?= htmlspecialchars($r['cover']) ?>" onerror="this.src='<?= BASE_URL ?>assets/images/cover/default.png'" class="card-img-top" style="height:200px;">
            <div class="card-body">
              <h6 class="card-title mb-1"><?= htmlspecialchars($r['judul']) ?></h6>
              <p class="harga mb-0"><?= format_rupiah($r['harga']) ?></p>
            </div>
          </div>
        </a>
      </div>
    <?php endwhile; ?>
  </div>
  <?php endif; ?>
</div>

<script>
document.getElementById('btnPlus')?.addEventListener('click', () => {
  const input = document.getElementById('qtyInput');
  if (parseInt(input.value) < parseInt(input.max)) input.value = parseInt(input.value) + 1;
});
document.getElementById('btnMin')?.addEventListener('click', () => {
  const input = document.getElementById('qtyInput');
  if (parseInt(input.value) > 1) input.value = parseInt(input.value) - 1;
});
</script>

<?php include 'includes/footer.php'; ?>
