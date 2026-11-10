<?php
/** @var mysqli $koneksi */
require_once '../../config/functions.php';
cek_login_admin();

$statusFilter = $_GET['status'] ?? '';
$where = $statusFilter ? "WHERE o.status = '" . clean($koneksi, $statusFilter) . "'" : '';

$list = mysqli_query($koneksi, "SELECT o.*, COALESCE(o.nama_penerima,u.nama) AS nama, COALESCE(o.email_penerima,u.email) AS email FROM orders o LEFT JOIN users u ON o.id_user=u.id_user $where ORDER BY o.tanggal DESC");

$page_title = 'Kelola Pesanan';
include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h5 class="fw-bold m-0">Daftar Pesanan</h5>
  <form method="GET" class="d-flex gap-2">
    <select name="status" class="form-select" onchange="this.form.submit()">
      <option value="">Semua Status</option>
      <?php foreach (['Pending','Dibayar','Diproses','Dikirim','Selesai'] as $s): ?>
        <option value="<?= $s ?>" <?= $statusFilter==$s?'selected':'' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<div class="card p-3 shadow-sm">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>#</th><th>User</th><th>Tanggal</th><th>Total</th><th>Status</th><th style="width:120px;"></th></tr></thead>
      <tbody>
      <?php while ($o = mysqli_fetch_assoc($list)): ?>
        <tr>
          <td><strong><?= htmlspecialchars($o['kode_pesanan']) ?></strong><br><span class="small text-muted">#<?= $o['id_order'] ?></span></td>
          <td><?= htmlspecialchars($o['nama']) ?><br><span class="text-muted small"><?= htmlspecialchars($o['email']) ?></span></td>
          <td><?= date('d M Y, H:i', strtotime($o['tanggal'])) ?></td>
          <td><?= format_rupiah($o['total']) ?></td>
          <td><?= badge_status($o['status']) ?></td>
          <td><a href="detail.php?id=<?= $o['id_order'] ?>" class="btn btn-sm btn-brand">Detail</a></td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
