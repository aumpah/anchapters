<?php
/** @var mysqli $koneksi */
require_once '../config/functions.php';

if (!is_login()) {
    redirect('auth/login.php');
}

$id = (int) ($_GET['id'] ?? 0);
$id_user = (int) $_SESSION['id_user'];

// User hanya boleh melihat struk miliknya sendiri, admin boleh semua
$whereUser = is_admin() ? '' : "AND o.id_user = $id_user";
$order = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT o.*, u.nama, u.email, u.telepon
     FROM orders o JOIN users u ON o.id_user = u.id_user
     WHERE o.id_order = $id $whereUser"));

if (!$order) {
    redirect(is_admin() ? 'admin/order/index.php' : 'user/history.php');
}

$detail = mysqli_query($koneksi,
    "SELECT od.*, b.judul FROM order_detail od
     JOIN buku b ON od.id_buku = b.id_buku
     WHERE od.id_order = $id");

$no_struk  = 'ACH-' . date('Ymd', strtotime($order['tanggal'])) . '-' . str_pad($order['id_order'], 5, '0', STR_PAD_LEFT);
$metode    = $order['metode_bayar'] ?: 'COD';
$bayar_txt = $metode . ($order['detail_bayar'] ? ' - ' . $order['detail_bayar'] : '');

// Label status pembayaran di struk
if ($order['status'] === 'Pending') {
    $label_bayar = ($metode === 'COD') ? 'BAYAR DI TEMPAT (COD)' : 'MENUNGGU KONFIRMASI';
    $warna_bayar = '#b26a00';
} else {
    $label_bayar = 'LUNAS';
    $warna_bayar = '#1e7b34';
}
// COD yang masih Dibayar/Diproses/Dikirim = belum dibayar sebenarnya
if ($metode === 'COD' && $order['status'] !== 'Selesai') {
    $label_bayar = 'BAYAR DI TEMPAT (COD)';
    $warna_bayar = '#b26a00';
}

$bukti_url = $order['bukti_bayar']
    ? BASE_URL . 'assets/images/bukti/' . rawurlencode(basename($order['bukti_bayar']))
    : null;

$baru = isset($_GET['baru']) && !is_admin();
$balik = is_admin() ? BASE_URL . 'admin/order/detail.php?id=' . $order['id_order'] : BASE_URL . 'user/history.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk <?= $no_struk ?> - An Chapters</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
      .struk-wrap { max-width: 520px; margin: 0 auto; }
      #struk { background:#fff; padding:28px; border-radius:10px; font-size:.92rem; color:#222; }
      #struk .brand { color:#5b3a29; font-weight:700; letter-spacing:1px; font-size:1.4rem; }
      #struk .garis { border-top:2px dashed #bbb; margin:14px 0; }
      #struk table { width:100%; }
      #struk td { padding:3px 0; vertical-align:top; }
      #struk td.r { text-align:right; white-space:nowrap; padding-left:12px; }
      .stempel { display:inline-block; border:2px solid; padding:2px 12px; font-weight:700; letter-spacing:1px; border-radius:6px; }
    </style>
</head>
<body>
<?php if (!is_admin()) include 'includes/navbar.php'; ?>

<div class="container my-5">
  <div class="struk-wrap">

    <div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
      <a href="<?= $balik ?>" class="btn btn-outline-secondary btn-sm">&larr; Kembali</a>
      <div class="d-flex gap-2">
        <?php if ($bukti_url): ?>
          <button type="button" class="btn btn-outline-secondary btn-sm" id="btnBukti">Lihat Bukti Bayar</button>
        <?php endif; ?>
        <button type="button" class="btn btn-brand btn-sm" id="btnPdf">&#11015; Unduh Struk PDF</button>
      </div>
    </div>

    <!-- ===== ISI STRUK (yang dijadikan PDF) ===== -->
    <div id="struk">
      <div class="text-center">
        <div class="brand">AN CHAPTERS</div>
        <div class="small text-muted">Toko Buku Online</div>
        <div class="garis"></div>
        <div class="fw-bold">STRUK PEMBAYARAN</div>
      </div>

      <table class="mt-2">
        <tr><td>No. Struk</td><td class="r"><?= $no_struk ?></td></tr>
        <tr><td>No. Order</td><td class="r">#<?= $order['id_order'] ?></td></tr>
        <tr><td>Tanggal Order</td><td class="r"><?= date('d M Y, H:i', strtotime($order['tanggal'])) ?></td></tr>
        <?php if ($order['tgl_bayar']): ?>
          <tr><td>Tanggal Bayar</td><td class="r"><?= date('d M Y, H:i', strtotime($order['tgl_bayar'])) ?></td></tr>
        <?php endif; ?>
        <tr><td>Pelanggan</td><td class="r"><?= htmlspecialchars($order['nama']) ?></td></tr>
        <?php if ($order['telepon']): ?>
          <tr><td>Telepon</td><td class="r"><?= htmlspecialchars($order['telepon']) ?></td></tr>
        <?php endif; ?>
      </table>

      <div class="garis"></div>

      <table>
        <?php while ($d = mysqli_fetch_assoc($detail)): ?>
          <tr>
            <td colspan="2" class="fw-semibold"><?= htmlspecialchars($d['judul']) ?></td>
          </tr>
          <tr>
            <td class="text-muted"><?= $d['qty'] ?> x <?= format_rupiah($d['harga']) ?></td>
            <td class="r"><?= format_rupiah($d['subtotal']) ?></td>
          </tr>
        <?php endwhile; ?>
      </table>

      <div class="garis"></div>

      <table>
        <tr>
          <td class="fw-bold fs-6">TOTAL</td>
          <td class="r fw-bold fs-6"><?= format_rupiah($order['total']) ?></td>
        </tr>
        <tr><td>Metode Bayar</td><td class="r"><?= htmlspecialchars($bayar_txt) ?></td></tr>
      </table>

      <div class="text-center mt-3">
        <span class="stempel" style="color:<?= $warna_bayar ?>;border-color:<?= $warna_bayar ?>;"><?= $label_bayar ?></span>
      </div>

      <div class="garis"></div>

      <div class="small">
        <div class="fw-semibold">Dikirim ke:</div>
        <div class="text-muted"><?= nl2br(htmlspecialchars($order['alamat_kirim'])) ?></div>
      </div>

      <div class="garis"></div>
      <div class="text-center small text-muted">
        Terima kasih sudah berbelanja di An Chapters!<br>
        Simpan struk ini sebagai bukti transaksi.
      </div>
    </div>
    <!-- ===== END STRUK ===== -->

  </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
const NO_STRUK = <?= json_encode($no_struk) ?>;
const BUKTI_URL = <?= json_encode($bukti_url) ?>;

function unduhPdf() {
  const el = document.getElementById('struk');
  return html2pdf().set({
    margin: 8,
    filename: 'Struk-' + NO_STRUK + '.pdf',
    image: { type: 'jpeg', quality: 0.98 },
    html2canvas: { scale: 2, useCORS: true },
    jsPDF: { unit: 'mm', format: 'a5', orientation: 'portrait' }
  }).from(el).save();
}
document.getElementById('btnPdf').addEventListener('click', unduhPdf);

function popupBukti() {
  Swal.fire({
    title: 'Bukti Pembayaran',
    html: '<img src="' + BUKTI_URL + '" style="max-width:100%;max-height:60vh;border-radius:8px;">',
    confirmButtonText: 'Tutup',
    confirmButtonColor: '#5b3a29'
  });
}
const btnBukti = document.getElementById('btnBukti');
if (btnBukti) btnBukti.addEventListener('click', popupBukti);

<?php if ($baru): ?>
// ---------- Pop up setelah checkout berhasil ----------
(function () {
  const sudahBayar = <?= json_encode($order['status'] !== 'Pending') ?>;
  const baris = (k, v) =>
    '<div style="display:flex;justify-content:space-between;gap:12px;padding:4px 0;"><span style="color:#777">' + k + '</span><strong style="text-align:right">' + v + '</strong></div>';

  let html = '<div style="text-align:left;font-size:.95rem;">'
    + baris('No. Order', '#<?= $order['id_order'] ?>')
    + baris('Metode', <?= json_encode(htmlspecialchars($bayar_txt)) ?>)
    + baris('Total', <?= json_encode(format_rupiah($order['total'])) ?>)
    + baris('Status', <?= json_encode(strip_tags(badge_status($order['status']))) ?>)
    + '</div>';

  if (BUKTI_URL) {
    html += '<div style="margin-top:12px;font-size:.85rem;color:#777;">Bukti pembayaran yang kamu upload:</div>'
          + '<img src="' + BUKTI_URL + '" style="max-width:100%;max-height:180px;margin-top:6px;border-radius:8px;border:1px solid #ddd;">';
  }

  Swal.fire({
    icon: 'success',
    title: sudahBayar ? 'Pembayaran Berhasil!' : 'Pesanan Berhasil Dibuat!',
    html: html,
    showCancelButton: true,
    confirmButtonText: 'Unduh Struk PDF',
    cancelButtonText: 'Tutup',
    confirmButtonColor: '#5b3a29',
    allowOutsideClick: false
  }).then(res => {
    if (res.isConfirmed) unduhPdf();
  });
})();
<?php endif; ?>
</script>
</body>
</html>
