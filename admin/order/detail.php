<?php
/** @var mysqli $koneksi */
require_once '../../config/functions.php';
cek_login_admin();

$id = (int) ($_GET['id'] ?? 0);
$order = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT o.*, COALESCE(o.nama_penerima,u.nama) AS nama, COALESCE(o.email_penerima,u.email) AS email, COALESCE(o.telepon_penerima,u.telepon) AS telepon FROM orders o LEFT JOIN users u ON o.id_user=u.id_user WHERE o.id_order=$id"));
if (!$order) redirect('admin/order/index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    // Admin hanya mengelola pesanan sampai tahap Dikirim.
    // Status Selesai dikonfirmasi oleh pelanggan setelah paket diterima.
    $status = $_POST['status'] ?? '';
    $allowed = ['Pending','Dibayar','Diproses','Dikirim'];
    $kurir = trim($_POST['kurir'] ?? '');
    $resi = trim($_POST['nomor_resi'] ?? '');

    if (in_array($status, $allowed, true) && $order['status'] !== 'Selesai') {
        $stmtUpdate = mysqli_prepare(
            $koneksi,
            "UPDATE orders
             SET status = ?, kurir = ?, nomor_resi = ?,
                 tgl_bayar = IF(? = 'Dibayar' AND tgl_bayar IS NULL, NOW(), tgl_bayar)
             WHERE id_order = ? AND status <> 'Selesai'"
        );
        if ($stmtUpdate) {
            mysqli_stmt_bind_param($stmtUpdate, 'ssssi', $status, $kurir, $resi, $status, $id);
            if (mysqli_stmt_execute($stmtUpdate) && mysqli_stmt_affected_rows($stmtUpdate) >= 0) {
                $_SESSION['flash'] = ['type'=>'success','title'=>'Status dan data pengiriman diperbarui'];
            } else {
                $_SESSION['flash'] = ['type'=>'error','title'=>'Gagal memperbarui pesanan'];
            }
            mysqli_stmt_close($stmtUpdate);
        } else {
            $_SESSION['flash'] = ['type'=>'error','title'=>'Gagal menyiapkan pembaruan pesanan'];
        }
    } else {
        $_SESSION['flash'] = ['type'=>'error','title'=>'Status tidak dapat diubah dari halaman admin'];
    }
    redirect('admin/order/detail.php?id=' . $id);
}

$detail = mysqli_query($koneksi, "SELECT od.*, b.judul, b.cover FROM order_detail od JOIN buku b ON od.id_buku=b.id_buku WHERE od.id_order=$id");

$page_title = 'Detail Pesanan';
include '../includes/header.php';
?>

<a href="index.php" class="text-decoration-none">&larr; Kembali</a>

<div class="row g-4 mt-1">
  <div class="col-md-7">
    <div class="card p-3 shadow-sm">
      <h6 class="fw-bold mb-1">Pesanan <?= htmlspecialchars($order['kode_pesanan']) ?></h6>
      <div class="small text-muted mb-3">ID internal #<?= $order['id_order'] ?></div>
      <?php while ($d = mysqli_fetch_assoc($detail)): ?>
        <div class="d-flex justify-content-between border-bottom py-2">
          <div class="d-flex align-items-center gap-2">
            <img src="<?= cover_url($d['cover']) ?>" onerror="this.onerror=null;this.src='<?= cover_url() ?>'" style="width:40px;height:55px;object-fit:cover;border-radius:4px;">
            <span><?= htmlspecialchars($d['judul']) ?> x<?= $d['qty'] ?></span>
          </div>
          <span><?= format_rupiah($d['subtotal']) ?></span>
        </div>
      <?php endwhile; ?>
      <div class="d-flex justify-content-between pt-3">
        <strong>Total</strong>
        <strong class="harga"><?= format_rupiah($order['total']) ?></strong>
      </div>
    </div>
  </div>

  <div class="col-md-5">
    <div class="card p-3 shadow-sm mb-3">
      <h6 class="fw-bold mb-2">Data Pemesan</h6>
      <p class="mb-1"><?= htmlspecialchars($order['nama']) ?></p>
      <p class="mb-1 text-muted small"><?= htmlspecialchars($order['email']) ?> | <?= htmlspecialchars($order['telepon']) ?></p>
      <p class="mb-0 text-muted small">Alamat kirim: <?= nl2br(htmlspecialchars($order['alamat_kirim'])) ?></p>
      <?php if (!empty($order['gift_wrap'])): ?><p class="small mt-2 mb-0"><strong>Gift wrap:</strong> Ya<br><strong>Pesan kartu:</strong> <?= nl2br(htmlspecialchars($order['gift_message'] ?? '')) ?></p><?php endif; ?>
    </div>

    <div class="card p-3 shadow-sm mb-3">
      <h6 class="fw-bold mb-2">Pembayaran</h6>
      <p class="mb-1">Metode: <strong><?= htmlspecialchars($order['metode_bayar'] ?: 'QRIS') ?></strong><?= $order['detail_bayar'] ? ' (' . htmlspecialchars($order['detail_bayar']) . ')' : '' ?></p>
      <?php if ($order['tgl_bayar']): ?>
        <p class="mb-1 text-muted small">Dibayar: <?= date('d M Y, H:i', strtotime($order['tgl_bayar'])) ?></p>
      <?php endif; ?>
      <?php if (!empty($order['bukti_bayar'])): ?>
        <?php $bukti = BASE_URL . 'assets/images/bukti/' . rawurlencode(basename($order['bukti_bayar'])); ?>
        <p class="mb-1 small text-muted">Bukti pembayaran:</p>
        <a href="<?= $bukti ?>" target="_blank"><img src="<?= $bukti ?>" style="max-width:100%;max-height:220px;border-radius:6px;border:1px solid #ddd;"></a>
      <?php else: ?>
        <p class="mb-1 text-muted small">Belum ada bukti pembayaran.</p>
      <?php endif; ?>
      <a href="<?= BASE_URL ?>user/struk.php?id=<?= $order['id_order'] ?>" class="btn btn-sm btn-outline-secondary mt-2" target="_blank">Lihat Invoice / PDF</a>
      <a href="<?= BASE_URL ?>user/track.php?kode=<?= urlencode($order['kode_pesanan']) ?>&email=<?= urlencode($order['email']) ?>" class="btn btn-sm btn-outline-secondary mt-2" target="_blank">Lihat Tracking</a>
      <?php
        $waPhone = preg_replace('/\D+/', '', (string)$order['telepon']);
        if (substr($waPhone, 0, 1) === '0') $waPhone = '62' . substr($waPhone, 1);
        $waText = 'Halo ' . $order['nama'] . ', update pesanan ' . $order['kode_pesanan'] . ': status ' . $order['status'] . '. Kurir: ' . ($order['kurir'] ?: '-') . ', resi: ' . ($order['nomor_resi'] ?: 'belum tersedia') . '.';
      ?>
      <?php if ($waPhone): ?><a href="https://wa.me/<?= htmlspecialchars($waPhone) ?>?text=<?= rawurlencode($waText) ?>" class="btn btn-sm btn-outline-success mt-2" target="_blank" rel="noopener noreferrer">Kirim Update WhatsApp</a><?php endif; ?>
      <a href="mailto:<?= htmlspecialchars($order['email']) ?>?subject=Update%20Pesanan%20<?= rawurlencode($order['kode_pesanan']) ?>" class="btn btn-sm btn-outline-secondary mt-2">Kirim Email</a>
    </div>

    <div class="card p-3 shadow-sm">
      <h6 class="fw-bold mb-2">Status Pesanan</h6>
      <p>Status saat ini: <?= badge_status($order['status']) ?></p>
      <?php if ($order['status'] === 'Selesai'): ?>
        <div class="alert alert-success small mb-0">Pelanggan sudah mengonfirmasi bahwa pesanan diterima. Status selesai tidak dapat diubah dari halaman admin.</div>
      <?php else: ?>
        <form method="POST">
          <label class="form-label small">Status Pesanan</label>
          <select name="status" class="form-select mb-2">
            <?php foreach (['Pending','Dibayar','Diproses','Dikirim'] as $s): ?>
              <option value="<?= $s ?>" <?= $order['status']==$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
          <label class="form-label small">Kurir / Ekspedisi</label>
          <input type="text" name="kurir" class="form-control mb-2" placeholder="Contoh: JNE / J&T" value="<?= htmlspecialchars($order['kurir'] ?? '') ?>">
          <label class="form-label small">Nomor Resi</label>
          <input type="text" name="nomor_resi" class="form-control mb-3" value="<?= htmlspecialchars($order['nomor_resi'] ?? '') ?>">
          <button type="submit" name="update_status" class="btn btn-brand w-100">Simpan Status & Pengiriman</button>
          <p class="form-text mt-2 mb-0">Admin memperbarui status dan nomor resi. Setelah paket diterima, pelanggan mengonfirmasi pesanan dari akun atau halaman tracking.</p>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
