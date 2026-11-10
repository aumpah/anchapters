<?php
/** @var mysqli $koneksi */
require_once '../config/functions.php';
require_once '../config/order_helpers.php';
cek_login_user();
$id_user = (int) $_SESSION['id_user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_received'])) {
    $id_order = (int) ($_POST['id_order'] ?? 0);
    $stmtOwned = mysqli_prepare(
        $koneksi,
        "SELECT id_order FROM orders WHERE id_order = ? AND id_user = ? AND status = 'Dikirim' LIMIT 1"
    );
    $owned = false;
    if ($stmtOwned) {
        mysqli_stmt_bind_param($stmtOwned, 'ii', $id_order, $id_user);
        mysqli_stmt_execute($stmtOwned);
        $owned = mysqli_stmt_get_result($stmtOwned);
        $owned = $owned && mysqli_num_rows($owned) === 1;
        mysqli_stmt_close($stmtOwned);
    }

    if ($owned && konfirmasi_pesanan_diterima($koneksi, $id_order)) {
        $_SESSION['flash'] = ['type'=>'success','title'=>'Pesanan berhasil diselesaikan','text'=>'Terima kasih sudah mengonfirmasi bahwa buku telah diterima.'];
    } else {
        $_SESSION['flash'] = ['type'=>'error','title'=>'Pesanan belum dapat diselesaikan','text'=>'Pastikan status pesanan sudah Dikirim dan pesanan tersebut milik akun Anda.'];
    }
    redirect('user/history.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_layanan'])) {
    $id_order = (int) ($_POST['id_order'] ?? 0);
    $jenis = in_array($_POST['jenis'] ?? '', ['Komplain','Retur'], true) ? $_POST['jenis'] : 'Komplain';
    $pesan = clean($koneksi, $_POST['pesan_layanan'] ?? '');
    $owned = mysqli_query($koneksi, "SELECT id_order FROM orders WHERE id_order=$id_order AND id_user=$id_user LIMIT 1");
    if (mysqli_num_rows($owned) && $pesan !== '') {
        $jenisSql = clean($koneksi, $jenis);
        mysqli_query($koneksi, "INSERT INTO layanan_pesanan (id_order,id_user,jenis,pesan) VALUES ($id_order,$id_user,'$jenisSql','$pesan')");
        $_SESSION['flash'] = ['type'=>'success','title'=>'Pengajuan berhasil dikirim','text'=>'Admin akan meninjau pengajuan Anda.'];
    } else {
        $_SESSION['flash'] = ['type'=>'error','title'=>'Pengajuan tidak valid'];
    }
    redirect('user/history.php');
}

$userInfo = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT poin FROM users WHERE id_user=$id_user"));
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
  <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-4">
    <div><h3 class="fw-bold mb-1">Riwayat Pesanan</h3><p class="text-muted small mb-0">Poin pembaca: <strong><?= (int)($userInfo['poin'] ?? 0) ?></strong></p></div>
    <a href="track.php" class="btn btn-outline-secondary btn-sm">Lacak Pesanan</a>
  </div>

  <?php if (mysqli_num_rows($orders) === 0): ?>
    <p class="text-muted text-center py-5">Belum ada pesanan.</p>
  <?php endif; ?>

  <?php while ($o = mysqli_fetch_assoc($orders)): ?>
    <div class="card mb-3 shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
          <div>
            <strong><?= htmlspecialchars($o['kode_pesanan']) ?></strong>
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
        <?php if (!empty($o['nomor_resi'])): ?><div class="small text-muted mt-2">Pengiriman: <?= htmlspecialchars($o['kurir']) ?> · Resi <?= htmlspecialchars($o['nomor_resi']) ?></div><?php endif; ?>
        <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
          <span class="small text-muted">
            Bayar: <?= htmlspecialchars($o['metode_bayar'] ?: 'QRIS') ?><?= $o['detail_bayar'] ? ' - ' . htmlspecialchars($o['detail_bayar']) : '' ?>
          </span>
          <div class="d-flex flex-wrap gap-2">
            <a href="struk.php?id=<?= $o['id_order'] ?>" class="btn btn-sm btn-outline-secondary">Invoice / PDF</a>
            <a href="track.php?kode=<?= urlencode($o['kode_pesanan']) ?>&email=<?= urlencode($_SESSION['email'] ?? '') ?>" class="btn btn-sm btn-outline-secondary">Lacak</a>
            <?php if ($o['status'] === 'Dikirim'): ?>
              <form method="POST" onsubmit="return confirm('Konfirmasi bahwa pesanan ini sudah kamu terima?')">
                <input type="hidden" name="id_order" value="<?= (int)$o['id_order'] ?>">
                <button type="submit" name="confirm_received" class="btn btn-sm btn-success">Pesanan Diterima</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
        <details class="mt-3 border-top pt-3">
          <summary class="small fw-semibold">Ajukan komplain atau retur</summary>
          <form method="POST" class="mt-3">
            <input type="hidden" name="id_order" value="<?= $o['id_order'] ?>">
            <div class="row g-2">
              <div class="col-md-3"><select name="jenis" class="form-select form-select-sm"><option value="Komplain">Komplain</option><option value="Retur">Retur</option></select></div>
              <div class="col-md-9"><textarea name="pesan_layanan" class="form-control form-control-sm" rows="2" maxlength="1000" placeholder="Ceritakan kendala atau alasan pengajuan..." required></textarea></div>
            </div>
            <button class="btn btn-brand btn-sm mt-2" name="submit_layanan">Kirim Pengajuan</button>
          </form>
        </details>
      </div>
    </div>
  <?php endwhile; ?>
</div>

<?php include 'includes/footer.php'; ?>
