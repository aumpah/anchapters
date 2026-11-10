<?php
require_once '../../config/functions.php';
cek_login_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_layanan'])) {
    $id_layanan = (int) ($_POST['id_layanan'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['Menunggu','Diproses','Selesai','Ditolak'], true) ? $_POST['status'] : 'Menunggu';
    $statusSql = clean($koneksi, $status);
    mysqli_query($koneksi, "UPDATE layanan_pesanan SET status='$statusSql' WHERE id_layanan=$id_layanan");
    $_SESSION['flash'] = ['type'=>'success','title'=>'Status pengajuan diperbarui'];
    redirect('admin/complaints/index.php');
}

$list = mysqli_query($koneksi, "SELECT lp.*, o.kode_pesanan, COALESCE(o.nama_penerima,u.nama) AS nama, COALESCE(o.email_penerima,u.email) AS email FROM layanan_pesanan lp JOIN orders o ON o.id_order=lp.id_order LEFT JOIN users u ON u.id_user=lp.id_user ORDER BY lp.created_at DESC");
$page_title = 'Komplain & Retur';
include '../includes/header.php';
?>
<div class="card p-3 shadow-sm">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>Tanggal</th><th>Pesanan</th><th>Pelanggan</th><th>Jenis</th><th>Pesan</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php if (mysqli_num_rows($list) === 0): ?><tr><td colspan="7" class="text-center text-muted py-4">Belum ada pengajuan.</td></tr><?php endif; ?>
      <?php while ($row = mysqli_fetch_assoc($list)): ?>
        <tr>
          <td class="small text-muted"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
          <td><a href="<?= BASE_URL ?>admin/order/detail.php?id=<?= $row['id_order'] ?>"><?= htmlspecialchars($row['kode_pesanan']) ?></a></td>
          <td><?= htmlspecialchars($row['nama']) ?><div class="small text-muted"><?= htmlspecialchars($row['email']) ?></div></td>
          <td><?= htmlspecialchars($row['jenis']) ?></td>
          <td style="min-width:220px;white-space:normal;"><?= nl2br(htmlspecialchars($row['pesan'])) ?></td>
          <td><span class="badge bg-secondary"><?= htmlspecialchars($row['status']) ?></span></td>
          <td><form method="POST" class="d-flex gap-1"><input type="hidden" name="id_layanan" value="<?= $row['id_layanan'] ?>"><select name="status" class="form-select form-select-sm"><option <?= $row['status']==='Menunggu'?'selected':'' ?>>Menunggu</option><option <?= $row['status']==='Diproses'?'selected':'' ?>>Diproses</option><option <?= $row['status']==='Selesai'?'selected':'' ?>>Selesai</option><option <?= $row['status']==='Ditolak'?'selected':'' ?>>Ditolak</option></select><button class="btn btn-sm btn-brand" name="update_layanan">Simpan</button></form></td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
