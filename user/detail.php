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
    $qty = max(1, min(99, (int) ($_POST['qty'] ?? 1)));
    $isMember = is_login() && !is_admin();
    $existingQty = 0;
    if ($isMember) {
        $id_user = (int) $_SESSION['id_user'];
        $cek = mysqli_query($koneksi, "SELECT * FROM cart WHERE id_user=$id_user AND id_buku=$id LIMIT 1");
        $existing = mysqli_fetch_assoc($cek);
        $existingQty = $existing ? (int)$existing['qty'] : 0;
    } else {
        if (!isset($_SESSION['guest_cart']) || !is_array($_SESSION['guest_cart'])) $_SESSION['guest_cart'] = [];
        $existingQty = (int) ($_SESSION['guest_cart'][$id] ?? 0);
    }
    $newQty = $existingQty + $qty;

    if (empty($buku['is_preorder']) && $newQty > (int)$buku['stok']) {
        $_SESSION['flash'] = ['type' => 'error', 'title' => 'Stok tidak mencukupi'];
    } else {
        if ($isMember) {
            $subtotal = $newQty * $buku['harga'];
            if ($existing) {
                mysqli_query($koneksi, "UPDATE cart SET qty=$newQty, subtotal=$subtotal WHERE id_cart={$existing['id_cart']}");
            } else {
                mysqli_query($koneksi, "INSERT INTO cart (id_user, id_buku, qty, subtotal) VALUES ($id_user, $id, $newQty, $subtotal)");
            }
        } else {
            $_SESSION['guest_cart'][$id] = $newQty;
        }
        $_SESSION['flash'] = ['type' => 'success', 'title' => 'Berhasil ditambahkan ke keranjang'];
    }
    redirect('user/detail.php?id=' . $id);
}

// ---------- Wishlist ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_wishlist'])) {
    cek_login_user();
    $id_user = (int) $_SESSION['id_user'];
    $saved = mysqli_query($koneksi, "SELECT id_wishlist FROM wishlist WHERE id_user=$id_user AND id_buku=$id LIMIT 1");
    if (mysqli_num_rows($saved)) {
        mysqli_query($koneksi, "DELETE FROM wishlist WHERE id_user=$id_user AND id_buku=$id");
        $_SESSION['flash'] = ['type'=>'success', 'title'=>'Buku dihapus dari wishlist'];
    } else {
        mysqli_query($koneksi, "INSERT IGNORE INTO wishlist (id_user, id_buku) VALUES ($id_user, $id)");
        $_SESSION['flash'] = ['type'=>'success', 'title'=>'Buku disimpan ke wishlist'];
    }
    redirect('user/detail.php?id=' . $id);
}

// ---------- Ulasan hanya untuk pembeli terverifikasi ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    cek_login_user();
    $id_user = (int) $_SESSION['id_user'];
    $rating = min(5, max(1, (int) ($_POST['rating'] ?? 5)));
    $ulasan = clean($koneksi, $_POST['ulasan'] ?? '');
    $bought = mysqli_query($koneksi, "SELECT 1 FROM orders o JOIN order_detail od ON od.id_order=o.id_order WHERE o.id_user=$id_user AND od.id_buku=$id AND o.status IN ('Dibayar','Diproses','Dikirim','Selesai') LIMIT 1");
    if (mysqli_num_rows($bought) && $ulasan !== '') {
        mysqli_query($koneksi, "INSERT INTO ulasan_buku (id_user,id_buku,rating,ulasan) VALUES ($id_user,$id,$rating,'$ulasan') ON DUPLICATE KEY UPDATE rating=VALUES(rating), ulasan=VALUES(ulasan), created_at=NOW()");
        $_SESSION['flash'] = ['type'=>'success', 'title'=>'Ulasan berhasil disimpan'];
    } else {
        $_SESSION['flash'] = ['type'=>'error', 'title'=>'Ulasan hanya tersedia untuk pembeli terverifikasi'];
    }
    redirect('user/detail.php?id=' . $id . '#ulasan');
}

$isAvailable = ((int)$buku['stok'] > 0) || !empty($buku['is_preorder']);
$avgRatingQ = mysqli_query($koneksi, "SELECT AVG(rating) AS rata, COUNT(*) AS jumlah FROM ulasan_buku WHERE id_buku=$id");
$ratingData = mysqli_fetch_assoc($avgRatingQ);
$reviews = mysqli_query($koneksi, "SELECT r.*, u.nama FROM ulasan_buku r JOIN users u ON u.id_user=r.id_user WHERE r.id_buku=$id ORDER BY r.created_at DESC LIMIT 10");
$isWishlisted = false;
$canReview = false;
$hasPurchased = false;
if (is_login() && !is_admin()) {
    $currentUser = (int) $_SESSION['id_user'];
    $saved = mysqli_query($koneksi, "SELECT 1 FROM wishlist WHERE id_user=$currentUser AND id_buku=$id LIMIT 1");
    $isWishlisted = mysqli_num_rows($saved) > 0;
    $purchase = mysqli_query($koneksi, "SELECT 1 FROM orders o JOIN order_detail od ON od.id_order=o.id_order WHERE o.id_user=$currentUser AND od.id_buku=$id AND o.status IN ('Dibayar','Diproses','Dikirim','Selesai') LIMIT 1");
    $hasPurchased = mysqli_num_rows($purchase) > 0;
    $canReview = $hasPurchased;
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
      <img src="<?= cover_url($buku['cover']) ?>" onerror="this.onerror=null;this.src='<?= cover_url() ?>'" class="img-fluid rounded-3 shadow-sm w-100" style="object-fit:cover;max-height:480px;">
    </div>
    <div class="col-md-8">
      <span class="badge bg-light text-dark border mb-2"><?= htmlspecialchars($buku['nama_kategori'] ?? '-') ?></span>
      <h2 class="fw-bold"><?= htmlspecialchars($buku['judul']) ?></h2>
      <p class="text-muted mb-1">Penulis: <strong><?= htmlspecialchars($buku['penulis']) ?></strong></p>
      <p class="text-muted mb-1">Penerbit: <?= htmlspecialchars($buku['penerbit'] ?? '-') ?> (<?= htmlspecialchars($buku['tahun'] ?? '-') ?>)</p>
      <div class="d-flex align-items-center gap-2 small mb-3">
        <span class="text-warning">★</span>
        <strong><?= $ratingData['rata'] ? number_format((float)$ratingData['rata'], 1) : 'Belum ada' ?></strong>
        <span class="text-muted">(<?= (int)$ratingData['jumlah'] ?> ulasan)</span>
      </div>
      <p class="text-muted mb-2">Format: <?= htmlspecialchars($buku['format_buku']) ?></p>
      <h3 class="harga mb-2"><?= format_rupiah($buku['harga']) ?></h3>

      <?php if (!empty($buku['is_preorder'])): ?>
        <p class="text-warning-emphasis small mb-3">● Pre-order<?= !empty($buku['tanggal_rilis']) ? ' · Estimasi rilis ' . date('d M Y', strtotime($buku['tanggal_rilis'])) : '' ?></p>
      <?php elseif ($buku['stok'] > 0): ?>
        <p class="text-success small mb-3">● Stok tersedia (<?= $buku['stok'] ?> unit)</p>
      <?php else: ?>
        <p class="text-danger small mb-3">● Stok habis</p>
      <?php endif; ?>

      <?php if ($isAvailable): ?>
        <form method="POST" class="d-flex align-items-center gap-2 mt-1 mb-4">
          <div class="input-group" style="width:130px;">
            <button type="button" class="btn btn-outline-secondary" id="btnMin">-</button>
            <input type="number" name="qty" id="qtyInput" value="1" min="1" max="<?= !empty($buku['is_preorder']) ? 99 : (int)$buku['stok'] ?>" class="form-control text-center">
            <button type="button" class="btn btn-outline-secondary" id="btnPlus">+</button>
          </div>
          <button type="submit" name="add_cart" class="btn btn-brand px-4">🛒 Add to Cart</button>
        </form>
      <?php else: ?>
        <button class="btn btn-secondary mb-4" disabled>Stok Habis</button>
      <?php endif; ?>

      <div class="d-flex flex-wrap gap-2 mb-4">
        <?php if (!empty($buku['sample_pdf'])): ?>
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#sampleModal">Lihat Preview Buku</button>
        <?php endif; ?>
        <?php if (is_login() && !is_admin()): ?>
          <form method="POST">
            <button type="submit" name="toggle_wishlist" class="btn btn-outline-secondary btn-sm"><?= $isWishlisted ? '♥ Tersimpan di Wishlist' : '♡ Simpan ke Wishlist' ?></button>
          </form>
        <?php else: ?>
          <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-outline-secondary btn-sm">♡ Simpan ke Wishlist</a>
        <?php endif; ?>
      </div>

      <?php if ($hasPurchased && $buku['format_buku'] === 'E-book' && !empty($buku['file_digital'])): ?>
        <a class="btn btn-brand btn-sm mb-4" href="download.php?id=<?= $id ?>">Buka / Unduh E-book</a>
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
            <tr><td class="text-muted">Tahun Terbit</td><td><?= htmlspecialchars($buku['tahun'] ?? '-') ?></td></tr>
            <tr><td class="text-muted">Tanggal Terbit</td><td><?= !empty($buku['tanggal_terbit']) ? date('d M Y', strtotime($buku['tanggal_terbit'])) : '-' ?></td></tr>
            <tr><td class="text-muted">ISBN</td><td><?= htmlspecialchars($buku['isbn'] ?: '-') ?></td></tr>
            <tr><td class="text-muted">Jumlah Halaman</td><td><?= $buku['jumlah_halaman'] ? (int)$buku['jumlah_halaman'] . ' halaman' : '-' ?></td></tr>
            <tr><td class="text-muted">Bahasa</td><td><?= htmlspecialchars($buku['bahasa'] ?: '-') ?></td></tr>
            <tr><td class="text-muted">Format</td><td><?= htmlspecialchars($buku['format_buku']) ?></td></tr>
            <tr><td class="text-muted">Kategori</td><td><?= htmlspecialchars($buku['nama_kategori'] ?? '-') ?></td></tr>
          </table>
        </div>
      </div>
    </div>
  </div>

  <section id="ulasan" class="mt-5">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
      <h5 class="fw-bold mb-0">Ulasan Pembaca</h5>
      <span class="small text-muted">Hanya pembeli terverifikasi yang dapat mengulas.</span>
    </div>
    <?php if ($canReview): ?>
      <form method="POST" class="card p-3 mb-3 shadow-sm">
        <label class="form-label fw-semibold">Bagikan pengalaman membaca</label>
        <div class="row g-2">
          <div class="col-md-3">
            <select name="rating" class="form-select" required>
              <?php for ($star=5; $star>=1; $star--): ?><option value="<?= $star ?>"><?= $star ?> / 5 bintang</option><?php endfor; ?>
            </select>
          </div>
          <div class="col-md-9"><textarea name="ulasan" class="form-control" rows="2" placeholder="Tulis ulasan singkat..." required></textarea></div>
        </div>
        <div class="text-end mt-2"><button class="btn btn-brand btn-sm" name="submit_review">Kirim Ulasan</button></div>
      </form>
    <?php endif; ?>
    <?php if (mysqli_num_rows($reviews) === 0): ?>
      <p class="text-muted small">Belum ada ulasan untuk buku ini.</p>
    <?php else: ?>
      <div class="row g-3">
        <?php while ($review = mysqli_fetch_assoc($reviews)): ?>
          <div class="col-md-6"><article class="card p-3 h-100 shadow-sm">
            <div class="d-flex justify-content-between gap-2"><strong><?= htmlspecialchars($review['nama']) ?></strong><span class="text-warning"><?= str_repeat('★', (int)$review['rating']) ?></span></div>
            <p class="small text-muted mb-2">Pembeli terverifikasi · <?= date('d M Y', strtotime($review['created_at'])) ?></p>
            <p class="mb-0"><?= nl2br(htmlspecialchars($review['ulasan'])) ?></p>
          </article></div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- Buku Lainnya -->
  <?php
     $relatedTitle = 'Sering dibeli bersama';
     $related = mysqli_query($koneksi, "SELECT b.*, COUNT(*) AS frekuensi FROM order_detail mine
       JOIN orders o ON o.id_order=mine.id_order AND o.status IN ('Dibayar','Diproses','Dikirim','Selesai')
       JOIN order_detail together ON together.id_order=mine.id_order AND together.id_buku <> mine.id_buku
       JOIN buku b ON b.id_buku=together.id_buku
       WHERE mine.id_buku={$buku['id_buku']} GROUP BY b.id_buku ORDER BY frekuensi DESC LIMIT 4");

    // fallback ke kategori serupa jika belum ada data pembelian bersama
    if (mysqli_num_rows($related) === 0) {
        $relatedTitle = 'Pembaca buku ini juga menyukai';
        $kategoriSaatIni = (int)($buku['id_kategori'] ?? 0);
        $related = mysqli_query($koneksi, "SELECT * FROM buku WHERE id_buku != {$buku['id_buku']} AND id_kategori = $kategoriSaatIni ORDER BY created_at DESC LIMIT 4");
        if (mysqli_num_rows($related) === 0) {
            $related = mysqli_query($koneksi, "SELECT * FROM buku WHERE id_buku != {$buku['id_buku']} ORDER BY created_at DESC LIMIT 4");
        }
    }

    if (mysqli_num_rows($related) > 0):
  ?>
  <h5 class="fw-bold mt-5 mb-3"><?= htmlspecialchars($relatedTitle) ?></h5>
  <div class="row g-4">
    <?php while ($r = mysqli_fetch_assoc($related)): ?>
      <div class="col-6 col-md-3">
        <a href="detail.php?id=<?= $r['id_buku'] ?>" class="text-decoration-none text-dark">
          <div class="card card-buku h-100">
            <img src="<?= cover_url($r['cover']) ?>" onerror="this.onerror=null;this.src='<?= cover_url() ?>'" class="card-img-top" style="height:200px;">
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

<?php if (!empty($buku['sample_pdf'])): ?>
<div class="modal fade" id="sampleModal" tabindex="-1" aria-labelledby="sampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="sampleModalLabel">Preview: <?= htmlspecialchars($buku['judul']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
      <div class="modal-body p-0"><iframe src="<?= BASE_URL ?>assets/files/samples/<?= rawurlencode(basename($buku['sample_pdf'])) ?>" title="Preview buku" style="width:100%;height:72vh;border:0;"></iframe></div>
    </div>
  </div>
</div>
<?php endif; ?>

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
