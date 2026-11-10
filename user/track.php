<?php
require_once '../config/functions.php';
require_once '../config/order_helpers.php';
$order = null;
$error = '';
$kode = trim($_POST['kode_pesanan'] ?? $_GET['kode'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_received'])) {
    $kodeKonfirmasi = trim($_POST['kode_pesanan'] ?? '');
    $emailKonfirmasi = trim($_POST['email'] ?? '');
    $stmtOwned = mysqli_prepare(
        $koneksi,
        "SELECT o.id_order
         FROM orders o
         LEFT JOIN users u ON u.id_user = o.id_user
         WHERE o.kode_pesanan = ?
           AND (o.email_penerima = ? OR (o.email_penerima IS NULL AND u.email = ?))
           AND o.status = 'Dikirim'
         LIMIT 1"
    );
    $ownedOrderId = 0;
    if ($stmtOwned) {
        mysqli_stmt_bind_param($stmtOwned, 'sss', $kodeKonfirmasi, $emailKonfirmasi, $emailKonfirmasi);
        mysqli_stmt_execute($stmtOwned);
        $ownedResult = mysqli_stmt_get_result($stmtOwned);
        $ownedOrder = $ownedResult ? mysqli_fetch_assoc($ownedResult) : null;
        $ownedOrderId = (int) ($ownedOrder['id_order'] ?? 0);
        mysqli_stmt_close($stmtOwned);
    }

    if ($ownedOrderId > 0 && konfirmasi_pesanan_diterima($koneksi, $ownedOrderId)) {
        $_SESSION['flash'] = ['type'=>'success','title'=>'Pesanan berhasil diselesaikan','text'=>'Terima kasih sudah mengonfirmasi bahwa buku telah diterima.'];
    } else {
        $_SESSION['flash'] = ['type'=>'error','title'=>'Pesanan belum dapat diselesaikan','text'=>'Pastikan kode dan email benar serta status pesanan sudah Dikirim.'];
    }
    redirect('user/track.php?kode=' . urlencode($kodeKonfirmasi) . '&email=' . urlencode($emailKonfirmasi));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_layanan'])) {
    $kodeLayanan = trim($_POST['kode_pesanan'] ?? '');
    $emailLayanan = trim($_POST['email'] ?? '');
    $jenisLayanan = in_array($_POST['jenis'] ?? '', ['Komplain','Retur'], true) ? $_POST['jenis'] : 'Komplain';
    $pesanLayanan = clean($koneksi, $_POST['pesan_layanan'] ?? '');
    if ($kodeLayanan !== '' && filter_var($emailLayanan, FILTER_VALIDATE_EMAIL) && $pesanLayanan !== '') {
        $stmtOrder = mysqli_prepare($koneksi, "SELECT o.id_order,o.id_user FROM orders o LEFT JOIN users u ON u.id_user=o.id_user WHERE o.kode_pesanan=? AND COALESCE(o.email_penerima,u.email)=? LIMIT 1");
        mysqli_stmt_bind_param($stmtOrder, 'ss', $kodeLayanan, $emailLayanan);
        mysqli_stmt_execute($stmtOrder);
        $ownedOrder = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtOrder));
        if ($ownedOrder) {
            $idOrderLayanan = (int)$ownedOrder['id_order'];
            $idUserLayanan = $ownedOrder['id_user'] !== null ? (int)$ownedOrder['id_user'] : 'NULL';
            $jenisSql = clean($koneksi, $jenisLayanan);
            mysqli_query($koneksi, "INSERT INTO layanan_pesanan (id_order,id_user,jenis,pesan) VALUES ($idOrderLayanan,$idUserLayanan,'$jenisSql','$pesanLayanan')");
            $_SESSION['flash'] = ['type'=>'success','title'=>'Pengajuan berhasil dikirim','text'=>'Admin akan meninjau pengajuan Anda.'];
            redirect('user/track.php?kode=' . urlencode($kodeLayanan) . '&email=' . urlencode($emailLayanan));
        }
    }
    $error = 'Data pengajuan tidak valid. Pastikan kode pesanan dan email sesuai.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' || ($kode !== '' && isset($_GET['email']))) {
    if ($kode === '' || !filter_var($email ?: ($_GET['email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
        $error = 'Masukkan kode pesanan dan email yang digunakan saat checkout.';
    } else {
        $email = $email ?: trim($_GET['email']);
        $stmt = mysqli_prepare($koneksi, "SELECT o.*, COALESCE(o.nama_penerima,u.nama) AS nama, COALESCE(o.email_penerima,u.email) AS email_order FROM orders o LEFT JOIN users u ON u.id_user=o.id_user WHERE o.kode_pesanan=? AND (o.email_penerima=? OR (o.email_penerima IS NULL AND u.email=?)) LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'sss', $kode, $email, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $order = mysqli_fetch_assoc($result);
        if (!$order) $error = 'Pesanan tidak ditemukan. Periksa kembali kode pesanan dan email Anda.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Lacak Pesanan - An Chapters</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container my-5" style="max-width:760px;">
  <h3 class="fw-bold mb-1">Lacak Pesanan</h3>
  <p class="text-muted mb-4">Masukkan kode pesanan dan email checkout untuk melihat status terbaru.</p>
  <form method="POST" class="card p-4 shadow-sm mb-4">
    <div class="mb-3"><label class="form-label">Kode Pesanan</label><input class="form-control" name="kode_pesanan" required value="<?= htmlspecialchars($kode) ?>" placeholder="Contoh: TBJ101126001"></div>
    <div class="mb-3"><label class="form-label">Email Checkout</label><input class="form-control" type="email" name="email" required value="<?= htmlspecialchars($email) ?>" placeholder="email@contoh.com"></div>
    <button class="btn btn-brand">Cek Status Pesanan</button>
    <?php if ($error): ?><div class="alert alert-warning mt-3 mb-0"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  </form>

  <?php if ($order): ?>
    <div class="card p-4 shadow-sm">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div><div class="small text-muted">Kode Pesanan</div><h5 class="fw-bold mb-1"><?= htmlspecialchars($order['kode_pesanan']) ?></h5><div class="small text-muted"><?= date('d M Y, H:i', strtotime($order['tanggal'])) ?></div></div>
        <div><?= badge_status($order['status']) ?></div>
      </div>
      <hr>
      <div class="row g-3 small">
        <div class="col-sm-6"><div class="text-muted">Penerima</div><strong><?= htmlspecialchars($order['nama']) ?></strong></div>
        <div class="col-sm-6"><div class="text-muted">Metode Pembayaran</div><strong><?= htmlspecialchars($order['metode_bayar']) ?></strong></div>
        <div class="col-sm-6"><div class="text-muted">Kurir</div><strong><?= htmlspecialchars($order['kurir'] ?: 'Belum ditentukan') ?></strong></div>
        <div class="col-sm-6"><div class="text-muted">Nomor Resi</div><strong><?= htmlspecialchars($order['nomor_resi'] ?: 'Belum tersedia') ?></strong></div>
        <div class="col-12"><div class="text-muted">Alamat Pengiriman</div><strong><?= nl2br(htmlspecialchars($order['alamat_kirim'])) ?></strong></div>
      </div>
      <hr>
      <div class="d-flex justify-content-between small"><span>Subtotal buku</span><span><?= format_rupiah($order['subtotal'] ?? $order['total']) ?></span></div>
      <?php if ((float)($order['ongkir'] ?? 0) > 0): ?><div class="d-flex justify-content-between small"><span>Ongkir (pesanan lama)</span><span><?= format_rupiah($order['ongkir']) ?></span></div><?php endif; ?>
      <?php if (!empty($order['potongan_poin'])): ?><div class="d-flex justify-content-between small"><span>Potongan poin</span><span>-<?= format_rupiah($order['potongan_poin']) ?></span></div><?php endif; ?>
      <div class="d-flex justify-content-between mt-2"><strong>Total Pesanan</strong><strong class="harga"><?= format_rupiah($order['total']) ?></strong></div>
      <?php if ($order['status'] === 'Pending'): ?><p class="small text-muted mt-2 mb-0">Pesanan menunggu verifikasi pembayaran oleh admin.</p><?php endif; ?>
      <?php
        $digitalItems = mysqli_query($koneksi, "SELECT b.id_buku,b.judul,b.file_digital FROM order_detail od JOIN buku b ON b.id_buku=od.id_buku WHERE od.id_order=" . (int)$order['id_order'] . " AND b.format_buku='E-book' AND b.file_digital IS NOT NULL");
      ?>
      <?php if ($order['status'] !== 'Pending' && mysqli_num_rows($digitalItems) > 0): ?>
        <div class="border-top mt-3 pt-3"><div class="fw-semibold small mb-2">E-book pesanan Anda</div>
          <?php while ($digital = mysqli_fetch_assoc($digitalItems)): ?>
            <a class="btn btn-sm btn-outline-secondary me-1 mb-1" href="download.php?id=<?= $digital['id_buku'] ?>&kode=<?= urlencode($order['kode_pesanan']) ?>&email=<?= urlencode($order['email_order']) ?>">Unduh <?= htmlspecialchars($digital['judul']) ?></a>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>
      <?php if ($order['nomor_resi']): ?>
        <p class="small text-muted mt-3 mb-0">Nomor resi sudah dicatat admin. Status perjalanan kurir dapat dicek melalui layanan resmi ekspedisi.</p>
      <?php else: ?>
        <p class="small text-muted mt-3 mb-0">Nomor resi akan muncul di halaman ini setelah admin memperbarui data pengiriman.</p>
      <?php endif; ?>
      <?php if ($order['status'] === 'Dikirim'): ?>
        <form method="POST" class="border-top mt-3 pt-3" onsubmit="return confirm('Konfirmasi bahwa paket sudah kamu terima?')">
          <input type="hidden" name="kode_pesanan" value="<?= htmlspecialchars($order['kode_pesanan']) ?>">
          <input type="hidden" name="email" value="<?= htmlspecialchars($order['email_order']) ?>">
          <p class="small mb-2">Jika buku sudah sampai dan kamu telah menerimanya, konfirmasikan pesanan di bawah ini.</p>
          <button type="submit" name="confirm_received" class="btn btn-success btn-sm">Konfirmasi Pesanan Diterima</button>
        </form>
      <?php elseif ($order['status'] === 'Selesai'): ?>
        <div class="alert alert-success small mt-3 mb-0">Pesanan ini sudah kamu konfirmasi diterima. Terima kasih sudah berbelanja di An Chapters!</div>
      <?php endif; ?>
      <details class="border-top mt-3 pt-3">
        <summary class="small fw-semibold">Ajukan komplain atau retur</summary>
        <form method="POST" class="mt-3">
          <input type="hidden" name="kode_pesanan" value="<?= htmlspecialchars($order['kode_pesanan']) ?>">
          <input type="hidden" name="email" value="<?= htmlspecialchars($order['email_order']) ?>">
          <div class="mb-2"><select name="jenis" class="form-select form-select-sm"><option value="Komplain">Komplain</option><option value="Retur">Retur</option></select></div>
          <textarea name="pesan_layanan" class="form-control form-control-sm" rows="3" maxlength="1000" placeholder="Jelaskan kendala atau alasan retur..." required></textarea>
          <button class="btn btn-brand btn-sm mt-2" name="submit_layanan">Kirim Pengajuan</button>
        </form>
      </details>
      <div class="d-flex flex-wrap gap-2 mt-3">
        <a class="btn btn-outline-secondary btn-sm" target="_blank" href="struk.php?kode=<?= urlencode($order['kode_pesanan']) ?>&email=<?= urlencode($order['email_order']) ?>">Invoice PDF</a>
        <a class="btn btn-outline-success btn-sm" target="_blank" rel="noopener noreferrer" href="<?= htmlspecialchars(whatsapp_url('Halo An Chapters, saya ingin menanyakan pesanan ' . $order['kode_pesanan'])) ?>">Tanyakan via WhatsApp</a>
        <?php if (!is_login()): ?><a class="btn btn-outline-secondary btn-sm" href="<?= BASE_URL ?>auth/register.php?email=<?= urlencode($order['email_order']) ?>">Buat akun untuk belanja berikutnya</a><?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>
