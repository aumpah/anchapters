<?php
$navCartCount = 0;
if (is_login() && !is_admin()) {
  $navCartCount = (int) jumlah_cart($koneksi, $_SESSION['id_user']);
} elseif (!is_admin() && !empty($_SESSION['guest_cart']) && is_array($_SESSION['guest_cart'])) {
  $navCartCount = array_sum(array_map('intval', $_SESSION['guest_cart']));
}

// Data dan nilai filter ditampilkan langsung di panel pencarian navbar.
$navKategoriList = mysqli_query($koneksi, "SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori");
$navQRaw = $_GET['q'] ?? '';
$navQ = is_scalar($navQRaw) ? (string)$navQRaw : '';
$navKategoriRaw = $_GET['kategori'] ?? 0;
$navKategori = is_scalar($navKategoriRaw) ? max(0, (int)$navKategoriRaw) : 0;
$navSortRaw = $_GET['sort'] ?? 'terbaru';
$navSort = is_scalar($navSortRaw) ? (string)$navSortRaw : 'terbaru';
$navFormatRaw = $_GET['format'] ?? '';
$navFormat = is_scalar($navFormatRaw) ? (string)$navFormatRaw : '';
$navHargaMinRaw = $_GET['harga_min'] ?? '';
$navHargaMaxRaw = $_GET['harga_max'] ?? '';
$navHargaMin = is_scalar($navHargaMinRaw) && is_numeric($navHargaMinRaw) ? (string)max(0, (float)$navHargaMinRaw) : '';
$navHargaMax = is_scalar($navHargaMaxRaw) && is_numeric($navHargaMaxRaw) ? (string)max(0, (float)$navHargaMaxRaw) : '';
$navRatingRaw = $_GET['rating'] ?? '0';
$navRating = is_scalar($navRatingRaw) ? (string)$navRatingRaw : '0';
$navStokRaw = $_GET['stok'] ?? '';
$navStok = is_scalar($navStokRaw) ? (string)$navStokRaw : '';

if (!in_array($navSort, ['terbaru', 'murah', 'mahal', 'bestseller', 'abjad'], true)) $navSort = 'terbaru';
if (!in_array($navFormat, ['', 'Fisik', 'E-book', 'Audiobook'], true)) $navFormat = '';
if (!in_array($navRating, ['0', '3', '4', '4.5'], true)) $navRating = '0';
if (!in_array($navStok, ['', 'tersedia', 'habis'], true)) $navStok = '';
$navActiveFilters = 0;
if ($navKategori > 0) $navActiveFilters++;
if ($navSort !== 'terbaru') $navActiveFilters++;
if ($navFormat !== '') $navActiveFilters++;
if ($navHargaMin !== '') $navActiveFilters++;
if ($navHargaMax !== '') $navActiveFilters++;
if ($navRating !== '0') $navActiveFilters++;
if ($navStok !== '') $navActiveFilters++;
?>
<style>
  .ac-navbar-container {
    display: flex;
    flex-wrap: wrap !important;
    align-items: center;
    gap: .5rem 1rem;
  }
  .ac-navbar-search {
    order: 3;
    flex: 0 0 100%;
    width: 100%;
    min-width: 0;
    margin: .25rem 0 .5rem;
    position: relative;
  }
  .ac-search-shell { position: relative; width: 100%; }
  .ac-search-row {
    display: flex;
    align-items: stretch;
    min-width: 0;
    border: 1px solid rgba(91, 58, 41, .18);
    border-radius: 999px;
    background: #fff;
    overflow: visible;
    box-shadow: 0 1px 2px rgba(36, 24, 16, .025);
  }
  .ac-search-main { position: relative; flex: 1 1 auto; min-width: 0; }
  .ac-navbar-search .form-control {
    min-width: 0;
    height: 42px;
    border: 0;
    box-shadow: none !important;
    background: transparent;
    border-radius: 999px 0 0 999px;
    padding-left: 1rem;
  }
  .ac-navbar-search .form-control:focus { outline: none; }
  .ac-search-submit,
  .ac-search-filter-toggle {
    border: 0;
    border-radius: 0;
    height: 42px;
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .4rem;
    white-space: nowrap;
  }
  .ac-search-submit { padding: 0 .9rem; border-radius: 0 999px 999px 0; }
  .ac-search-filter-toggle {
    color: var(--ac-brown, #5b3a29);
    background: #f8f4ef;
    border-left: 1px solid rgba(91, 58, 41, .12);
    padding: 0 .8rem;
  }
  .ac-search-filter-toggle:hover,
  .ac-search-filter-toggle[aria-expanded="true"] { background: #efe6dc; }
  .ac-filter-count {
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: var(--ac-brown, #5b3a29);
    color: #fff;
    font-size: .68rem;
    line-height: 1;
  }
  .ac-navbar-search .search-suggestions {
    top: calc(100% + 7px);
    left: 0;
    right: 0;
    border-radius: 12px;
  }
  .ac-filter-panel {
    position: absolute;
    z-index: 1060;
    top: calc(100% + 9px);
    right: 0;
    width: min(620px, calc(100vw - 28px));
    padding: 18px;
    border: 1px solid rgba(91, 58, 41, .12);
    border-radius: 15px;
    background: #fff;
    box-shadow: 0 14px 38px rgba(42, 29, 20, .16);
  }
  .ac-filter-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:14px; }
  .ac-filter-heading strong { color:#3d2b1f; font-size:.98rem; font-weight:650; }
  .ac-filter-heading small { display:block; margin-top:2px; color:#81756d; font-size:.78rem; }
  .ac-filter-close {
    border:0; background:transparent; color:#78695f; border-radius:8px;
    width:30px; height:30px; display:inline-flex; align-items:center; justify-content:center;
  }
  .ac-filter-close:hover { background:#f6f1eb; color:#3d2b1f; }
  .ac-filter-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; }
  .ac-filter-field { min-width:0; }
  .ac-filter-field label { display:block; color:#75665c; font-size:.76rem; margin-bottom:5px; }
  .ac-filter-field .form-select,
  .ac-filter-field .form-control { min-height:39px; font-size:.88rem; border-color:rgba(91,58,41,.16); }
  .ac-filter-actions { display:flex; align-items:center; justify-content:space-between; gap:10px; border-top:1px solid #f0ebe6; margin-top:15px; padding-top:13px; }
  .ac-filter-actions .btn { min-height:37px; font-size:.86rem; }
  .ac-filter-reset { color:#75665c; text-decoration:none; font-size:.86rem; }
  .ac-filter-reset:hover { color:#3d2b1f; text-decoration:underline; }
  .ac-navbar-toggler { order: 2; margin-left: auto; }
  .ac-navbar-menu { order: 4; flex-basis: 100%; }
  @media (min-width: 1200px) {
    .ac-navbar-container { flex-wrap: nowrap !important; gap: .65rem; }
    .ac-navbar-search {
      order: 1;
      flex: 0 1 320px;
      width: 320px;
      margin: 0 auto 0 .55rem;
    }
    .ac-navbar-toggler { display: none; }
    .ac-navbar-menu { order: 2; flex-basis: auto; }
    .ac-navbar-menu .navbar-nav { gap: 0 !important; }
    .ac-navbar-menu .nav-link { padding-left: .42rem; padding-right: .42rem; font-size: .9rem; }
  }
  @media (max-width: 575.98px) {
    .ac-filter-panel { left:0; right:auto; width:100%; max-height:calc(100vh - 130px); overflow-y:auto; }
    .ac-filter-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
    .ac-search-filter-toggle { padding:0 .65rem; }
    .ac-search-filter-toggle .ac-filter-label { display:none; }
    .ac-filter-panel { padding:14px; }
  }
</style>
<nav class="navbar navbar-expand-xl sticky-top py-3">
  <div class="container ac-navbar-container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>user/home.php">
      <img src="<?= BASE_URL ?>assets/images/logo.png" style="height:36px;" alt="Logo An Chapters"> An Chapters
    </a>

    <form method="GET" action="<?= BASE_URL ?>user/home.php" class="ac-navbar-search" role="search" aria-label="Cari dan filter buku">
      <input type="hidden" name="by" value="semua">
      <div class="ac-search-shell">
        <div class="ac-search-row">
          <div class="ac-search-main">
            <input
              type="search"
              id="searchBook"
              name="q"
              class="form-control"
              placeholder="Cari judul atau penulis..."
              value="<?= htmlspecialchars($navQ, ENT_QUOTES, 'UTF-8') ?>"
              autocomplete="off"
              aria-label="Cari judul atau penulis buku"
              aria-autocomplete="list"
              aria-controls="searchSuggestions"
            >
            <div id="searchSuggestions" class="search-suggestions d-none" role="listbox"></div>
          </div>
          <button class="btn btn-brand ac-search-submit" type="submit" aria-label="Cari buku">
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path>
            </svg>
          </button>
          <button
            class="ac-search-filter-toggle"
            type="button"
            id="filterToggle"
            aria-expanded="false"
            aria-controls="searchFilters"
            aria-label="Buka filter buku"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M4 7h9M17 7h3M4 17h3M11 17h9"></path><circle cx="15" cy="7" r="2"></circle><circle cx="9" cy="17" r="2"></circle>
            </svg>
            <span class="ac-filter-label">Filter</span>
            <?php if ($navActiveFilters > 0): ?><span class="ac-filter-count"><?= $navActiveFilters ?></span><?php endif; ?>
          </button>
        </div>

        <div id="searchFilters" class="ac-filter-panel d-none">
          <div class="ac-filter-heading">
            <div>
              <strong>Filter & Urutkan Buku</strong>
              <small>Atur hasil pencarian sesuai kebutuhanmu.</small>
            </div>
            <button type="button" class="ac-filter-close" id="filterClose" aria-label="Tutup filter">
              <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m18 6-12 12M6 6l12 12"></path></svg>
            </button>
          </div>

          <div class="ac-filter-grid">
            <div class="ac-filter-field">
              <label for="navKategori">Kategori</label>
              <select name="kategori" id="navKategori" class="form-select">
                <option value="0">Semua Kategori</option>
                <?php if ($navKategoriList): while ($kategori = mysqli_fetch_assoc($navKategoriList)): ?>
                  <option value="<?= (int)$kategori['id_kategori'] ?>" <?= $navKategori === (int)$kategori['id_kategori'] ? 'selected' : '' ?>><?= htmlspecialchars($kategori['nama_kategori']) ?></option>
                <?php endwhile; endif; ?>
              </select>
            </div>
            <div class="ac-filter-field">
              <label for="navSort">Urutkan</label>
              <select name="sort" id="navSort" class="form-select">
                <option value="terbaru" <?= $navSort === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
                <option value="murah" <?= $navSort === 'murah' ? 'selected' : '' ?>>Harga termurah</option>
                <option value="mahal" <?= $navSort === 'mahal' ? 'selected' : '' ?>>Harga termahal</option>
                <option value="bestseller" <?= $navSort === 'bestseller' ? 'selected' : '' ?>>Terlaris</option>
                <option value="abjad" <?= $navSort === 'abjad' ? 'selected' : '' ?>>Abjad A–Z</option>
              </select>
            </div>
            <div class="ac-filter-field">
              <label for="navFormat">Format</label>
              <select name="format" id="navFormat" class="form-select">
                <option value="">Semua Format</option>
                <?php foreach (['Fisik', 'E-book', 'Audiobook'] as $formatOption): ?>
                  <option value="<?= $formatOption ?>" <?= $navFormat === $formatOption ? 'selected' : '' ?>><?= $formatOption ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="ac-filter-field">
              <label for="navHargaMin">Harga minimum</label>
              <input type="number" min="0" name="harga_min" id="navHargaMin" class="form-control" placeholder="Rp 0" value="<?= htmlspecialchars($navHargaMin, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="ac-filter-field">
              <label for="navHargaMax">Harga maksimum</label>
              <input type="number" min="0" name="harga_max" id="navHargaMax" class="form-control" placeholder="Tanpa batas" value="<?= htmlspecialchars($navHargaMax, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="ac-filter-field">
              <label for="navRating">Rating minimum</label>
              <select name="rating" id="navRating" class="form-select">
                <option value="0" <?= $navRating === '0' ? 'selected' : '' ?>>Semua rating</option>
                <option value="3" <?= $navRating === '3' ? 'selected' : '' ?>>3+ bintang</option>
                <option value="4" <?= $navRating === '4' ? 'selected' : '' ?>>4+ bintang</option>
                <option value="4.5" <?= $navRating === '4.5' ? 'selected' : '' ?>>4.5+ bintang</option>
              </select>
            </div>
            <div class="ac-filter-field">
              <label for="navStok">Status stok</label>
              <select name="stok" id="navStok" class="form-select">
                <option value="" <?= $navStok === '' ? 'selected' : '' ?>>Semua</option>
                <option value="tersedia" <?= $navStok === 'tersedia' ? 'selected' : '' ?>>Tersedia / PO</option>
                <option value="habis" <?= $navStok === 'habis' ? 'selected' : '' ?>>Stok habis</option>
              </select>
            </div>
          </div>

          <div class="ac-filter-actions">
            <a class="ac-filter-reset" href="<?= BASE_URL ?>user/home.php<?= $navQ !== '' ? '?q=' . rawurlencode($navQ) : '' ?>">Reset filter</a>
            <button class="btn btn-brand px-4" type="submit">Terapkan filter</button>
          </div>
        </div>
      </div>
    </form>

    <button class="navbar-toggler ac-navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Buka menu navigasi">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse ac-navbar-menu" id="navMenu">
      <ul class="navbar-nav ms-xl-auto align-items-xl-center gap-1">
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>user/home.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>user/about.php">About Us</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>user/contact.php">Contact</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>user/track.php">Lacak Pesanan</a></li>
        <?php if (!is_admin()): ?>
          <li class="nav-item">
            <a class="nav-link position-relative" href="<?= BASE_URL ?>user/cart.php">
              🛒 Cart
              <?php if ($navCartCount > 0): ?>
                <span class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle" style="font-size:.6rem;"><?= $navCartCount ?></span>
              <?php endif; ?>
            </a>
          </li>
        <?php endif; ?>
        <?php if (is_login() && !is_admin()): ?>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>user/history.php">Riwayat</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>user/wishlist.php">Wishlist</a></li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Hai, <?= htmlspecialchars($_SESSION['nama']) ?></a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>auth/logout.php">Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>auth/login.php">Login</a></li>
          <li class="nav-item"><a class="btn btn-brand btn-sm ms-xl-2" href="<?= BASE_URL ?>auth/register.php">Daftar</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<script>
(() => {
  const input = document.getElementById('searchBook');
  const suggestions = document.getElementById('searchSuggestions');
  const filterToggle = document.getElementById('filterToggle');
  const filterPanel = document.getElementById('searchFilters');
  const filterClose = document.getElementById('filterClose');
  const searchShell = document.querySelector('.ac-search-shell');
  if (!searchShell) return;

  let timer = null;
  const closeSuggestions = () => {
    if (!suggestions) return;
    suggestions.classList.add('d-none');
    suggestions.innerHTML = '';
  };
  const closeFilters = () => {
    if (!filterPanel || !filterToggle) return;
    filterPanel.classList.add('d-none');
    filterToggle.setAttribute('aria-expanded', 'false');
  };
  const openFilters = () => {
    if (!filterPanel || !filterToggle) return;
    closeSuggestions();
    filterPanel.classList.remove('d-none');
    filterToggle.setAttribute('aria-expanded', 'true');
  };

  if (filterToggle) {
    filterToggle.addEventListener('click', () => {
      if (filterPanel.classList.contains('d-none')) openFilters();
      else closeFilters();
    });
  }
  if (filterClose) filterClose.addEventListener('click', closeFilters);

  if (input && suggestions) {
    input.addEventListener('input', () => {
      clearTimeout(timer);
      const term = input.value.trim();
      if (!term) { closeSuggestions(); return; }

      timer = setTimeout(async () => {
        try {
          const endpoint = '<?= BASE_URL ?>user/search_suggest.php?q=' + encodeURIComponent(term);
          const response = await fetch(endpoint, { headers: { 'Accept': 'application/json' } });
          if (!response.ok) throw new Error('Pencarian gagal');
          const items = await response.json();
          suggestions.innerHTML = '';

          if (!Array.isArray(items) || items.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'search-suggestion-empty';
            empty.textContent = 'Buku tidak ditemukan.';
            suggestions.appendChild(empty);
          } else {
            items.forEach(item => {
              const link = document.createElement('a');
              link.className = 'search-suggestion-item';
              link.href = '<?= BASE_URL ?>user/detail.php?id=' + encodeURIComponent(item.id);
              const title = document.createElement('span');
              title.className = 'fw-semibold';
              title.textContent = item.judul;
              const author = document.createElement('small');
              author.className = 'text-muted';
              author.textContent = item.penulis;
              link.append(title, author);
              suggestions.appendChild(link);
            });
          }
          suggestions.classList.remove('d-none');
        } catch (_) {
          closeSuggestions();
        }
      }, 180);
    });
    input.addEventListener('keydown', event => {
      if (event.key === 'Escape') closeSuggestions();
    });
  }

  document.addEventListener('click', event => {
    if (!searchShell.contains(event.target)) {
      closeSuggestions();
      closeFilters();
    }
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      closeSuggestions();
      closeFilters();
    }
  });
})();
</script>
