<?php
/** @var mysqli $koneksi */
require_once '../config/functions.php';

// ---------- Ambil parameter search & filter ----------
$keyword     = isset($_GET['q']) ? clean($koneksi, $_GET['q']) : '';
$searchBy    = isset($_GET['by']) ? $_GET['by'] : 'judul'; // judul | penulis | kategori
$kategoriId  = isset($_GET['kategori']) ? (int) $_GET['kategori'] : 0;
$sort        = isset($_GET['sort']) ? $_GET['sort'] : 'terbaru'; // terbaru | murah | mahal
$page        = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$limit       = 10;
$offset      = ($page - 1) * $limit;

// ---------- Bangun query WHERE ----------
$where = "WHERE 1=1";

if ($keyword !== '') {
    if ($searchBy === 'penulis') {
        $where .= " AND b.penulis LIKE '%$keyword%'";
    } elseif ($searchBy === 'kategori') {
        $where .= " AND k.nama_kategori LIKE '%$keyword%'";
    } else {
        $where .= " AND b.judul LIKE '%$keyword%'";
    }
}
if ($kategoriId > 0) {
    $where .= " AND b.id_kategori = $kategoriId";
}

$orderBy = "b.created_at DESC";
if ($sort === 'murah') $orderBy = "b.harga ASC";
if ($sort === 'mahal') $orderBy = "b.harga DESC";

// ---------- Hitung total data untuk pagination ----------
$totalQ = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM buku b LEFT JOIN kategori k ON b.id_kategori = k.id_kategori $where");
$totalRow = mysqli_fetch_assoc($totalQ);
$totalData = $totalRow['total'];
$totalPage = max(1, ceil($totalData / $limit));

// ---------- Ambil data buku ----------
$q = "SELECT b.*, k.nama_kategori FROM buku b LEFT JOIN kategori k ON b.id_kategori = k.id_kategori
      $where ORDER BY $orderBy LIMIT $limit OFFSET $offset";
$bukuList = mysqli_query($koneksi, $q);

// ---------- Ambil semua kategori untuk dropdown filter ----------
$kategoriList = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>An Chapters - Toko Buku Online</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<!-- Hero -->
<div class="py-5" style="background:#fdf6ec;">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <span class="badge bg-warning-subtle text-dark border mb-3 px-3 py-2">✨ Koleksi Terbaru Tersedia</span>
        <h1 class="fw-bold display-5" style="color:#3d2b1f;">
          Temukan Bacaan <span style="color:var(--ac-gold);">Favoritmu</span>
        </h1>
        <p class="text-muted mt-3 mb-4">
          Koleksi buku pilihan dari berbagai genre. Dari fiksi hingga pengembangan diri,
          temukan buku yang cocok untukmu.
        </p>
        <div class="d-flex gap-2">
          <a href="#daftar-buku" class="btn btn-brand px-4">Jelajahi Koleksi →</a>
          <a href="?sort=terbaru" class="btn btn-outline-dark px-4">Lihat Terbaru</a>
        </div>
      </div>
      <div class="col-lg-6">
        <img src="<?= BASE_URL ?>assets/images/hero.jpg" class="img-fluid rounded-4 shadow" style="width:100%;object-fit:cover;max-height:420px;">
      </div>
    </div>
  </div>
</div>

<div class="container my-4">
   <!-- Kategori Pilihan -->
  <h5 class="fw-bold mb-3">Jelajahi Kategori</h5>
  <div class="row g-3 mb-5">
    <?php
      $kategoriHome = mysqli_query($koneksi, "
        SELECT k.*, COUNT(b.id_buku) as jumlah
        FROM kategori k LEFT JOIN buku b ON k.id_kategori = b.id_kategori
        GROUP BY k.id_kategori ORDER BY jumlah DESC LIMIT 4");
      while ($kh = mysqli_fetch_assoc($kategoriHome)):
    ?>
<div class="col-6 col-md-3">
        <a href="?kategori=<?= $kh['id_kategori'] ?>" class="text-decoration-none">
          <div class="card card-kategori overflow-hidden border-0 shadow-sm position-relative" style="height:160px;">
            <img src="<?= BASE_URL ?>assets/images/kategori/<?= htmlspecialchars($kh['gambar'] ?? 'default.jpg') ?>"
                 onerror="this.src='<?= BASE_URL ?>assets/images/kategori/default.png'"
                 class="w-100 h-100" style="object-fit:cover;">
            <div class="position-absolute bottom-0 start-0 w-100 p-3"
                 style="background:linear-gradient(to top, rgba(0,0,0,.75), transparent);">
              <h6 class="text-white mb-0"><?= htmlspecialchars($kh['nama_kategori']) ?></h6>
              <small class="text-white-50"><?= $kh['jumlah'] ?> buku</small>
            </div>
          </div>
        </a>
      </div>
    <?php endwhile; ?>
  </div>
  <!-- Search & Filter -->
  <form method="GET" class="row g-2 align-items-end bg-white p-3 rounded-3 shadow-sm mb-4">
    <div class="col-md-4">
      <label class="form-label small text-muted mb-1">Cari Buku</label>
      <input type="text" name="q" class="form-control" placeholder="Kata kunci..." value="<?= htmlspecialchars($keyword) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label small text-muted mb-1">Berdasarkan</label>
      <select name="by" class="form-select">
        <option value="judul" <?= $searchBy=='judul'?'selected':'' ?>>Judul</option>
        <option value="penulis" <?= $searchBy=='penulis'?'selected':'' ?>>Penulis</option>
        <option value="kategori" <?= $searchBy=='kategori'?'selected':'' ?>>Kategori</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small text-muted mb-1">Filter Kategori</label>
      <select name="kategori" class="form-select">
        <option value="0">Semua Kategori</option>
        <?php while ($k = mysqli_fetch_assoc($kategoriList)): ?>
          <option value="<?= $k['id_kategori'] ?>" <?= $kategoriId==$k['id_kategori']?'selected':'' ?>>
            <?= htmlspecialchars($k['nama_kategori']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small text-muted mb-1">Urutkan</label>
      <select name="sort" class="form-select">
        <option value="terbaru" <?= $sort=='terbaru'?'selected':'' ?>>Terbaru</option>
        <option value="murah" <?= $sort=='murah'?'selected':'' ?>>Harga Termurah</option>
        <option value="mahal" <?= $sort=='mahal'?'selected':'' ?>>Harga Termahal</option>
      </select>
    </div>
    <div class="col-md-1">
      <button class="btn btn-brand w-100" type="submit">Cari</button>
    </div>
  </form>

  <p class="text-muted"><?= $totalData ?> buku ditemukan</p>

  <!-- Grid Buku -->
  <div id="daftar-buku" class="row g-4">
    <?php if (mysqli_num_rows($bukuList) === 0): ?>
      <div class="col-12 text-center text-muted py-5">Buku tidak ditemukan.</div>
    <?php endif; ?>
    <?php while ($b = mysqli_fetch_assoc($bukuList)): ?>
      <div class="col-6 col-md-4 col-lg-3">
        <a href="detail.php?id=<?= $b['id_buku'] ?>" class="text-decoration-none text-dark">
          <div class="card card-buku h-100">
            <img src="<?= cover_url($b['cover']) ?>" onerror="this.onerror=null;this.src='<?= cover_url() ?>'" class="card-img-top" alt="<?= htmlspecialchars($b['judul']) ?>">
            <div class="card-body">
              <span class="badge bg-light text-dark border mb-1"><?= htmlspecialchars($b['nama_kategori'] ?? '-') ?></span>
              <h6 class="card-title mb-1"><?= htmlspecialchars($b['judul']) ?></h6>
              <p class="text-muted small mb-1"><?= htmlspecialchars($b['penulis']) ?></p>
              <p class="harga mb-0"><?= format_rupiah($b['harga']) ?></p>
              <?php if ($b['stok'] <= 0): ?>
                <span class="badge bg-danger mt-1">Stok Habis</span>
              <?php endif; ?>
            </div>
          </div>
        </a>
      </div>
    <?php endwhile; ?>
  </div>

  <!-- Pagination -->
  <?php if ($totalPage > 1): ?>
  <nav class="mt-4">
    <ul class="pagination justify-content-center">
      <?php for ($i = 1; $i <= $totalPage; $i++): ?>
        <?php
          $query = $_GET;
          $query['page'] = $i;
          $link = '?' . http_build_query($query);
        ?>
        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
          <a class="page-link" href="<?= $link ?>" style="<?= $i==$page ? 'background-color:var(--ac-brown);border-color:var(--ac-brown);' : 'color:var(--ac-brown);' ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
