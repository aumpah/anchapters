<?php
/** @var mysqli $koneksi */
require_once '../../config/functions.php';
cek_login_admin();

$id = (int) ($_GET['id'] ?? 0);
$order = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT o.*, u.nama, u.email, u.telepon FROM orders o JOIN users u ON o.id_user=u.id_user WHERE o.id_order=$id"));
if (!$order) redirect('admin/order/index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $status = clean($koneksi, $_POST['status']);
    $allowed = ['Pending','Dibayar','Diproses','Dikirim','Selesai'];
    if (in_array($status, $allowed)) {
        mysqli_query($koneksi, "UPDATE orders SET status='$status' WHERE id_order=$id");
        $_SESSION['flash'] = ['type'=>'success','title'=>'Status pesanan diperbarui'];
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
      <h6 class="fw-bold mb-3">Order #<?= $order['id_order'] ?></h6>
      <?php while ($d = mysqli_fetch_assoc($detail)): ?>
        <div class="d-flex justify-content-between border-bottom py-2">
          <div class="d-flex align-items-center gap-2">
            <img src="<?= BASE_URL ?>assets/images/cover/<?= htmlspecialchars($d['cover']) ?>" onerror="this.src='<?= BASE_URL ?>assets/images/cover/default.png'" style="width:40px;height:55px;object-fit:cover;border-radius:4px;">
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
      <p class="mb-0 text-muted small">Alamat kirim: <?= htmlspecialchars($order['alamat_kirim']) ?></p>
    </div>

    <div class="card p-3 shadow-sm">
      <h6 class="fw-bold mb-2">Status Pesanan</h6>
      <p>Status saat ini: <?= badge_status($order['status']) ?></p>
      <form method="POST" class="d-flex gap-2">
        <select name="status" class="form-select">
          <?php foreach (['Pending','Dibayar','Diproses','Dikirim','Selesai'] as $s): ?>
            <option value="<?= $s ?>" <?= $order['status']==$s?'selected':'' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" name="update_status" class="btn btn-brand text-nowrap">Update</button>
      </form>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
