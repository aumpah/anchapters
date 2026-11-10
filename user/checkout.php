<?php
/** @var mysqli $koneksi */
require_once '../config/functions.php';
if (is_admin()) redirect('admin/dashboard.php');

$isMember = is_login() && !is_admin();
$id_user = $isMember ? (int) $_SESSION['id_user'] : null;
$user = $isMember ? mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM users WHERE id_user=$id_user")) : null;
$items = [];
$total = 0;

if ($isMember) {
    $q = mysqli_query($koneksi, "SELECT c.*,b.judul,b.harga,b.stok,b.is_preorder FROM cart c JOIN buku b ON b.id_buku=c.id_buku WHERE c.id_user=$id_user");
    while ($row = mysqli_fetch_assoc($q)) {
        $row['subtotal'] = (float)$row['harga'] * (int)$row['qty'];
        $items[] = $row;
        $total += $row['subtotal'];
    }
} else {
    $guestCart = $_SESSION['guest_cart'] ?? [];
    $ids = array_values(array_filter(array_map('intval', array_keys($guestCart))));
    if ($ids) {
        $idSql = implode(',', $ids);
        $q = mysqli_query($koneksi, "SELECT * FROM buku WHERE id_buku IN ($idSql)");
        while ($book = mysqli_fetch_assoc($q)) {
            $qty = max(1, min(99, (int)$guestCart[$book['id_buku']]));
            $row = $book;
            $row['qty'] = $qty;
            $row['subtotal'] = (float)$book['harga'] * $qty;
            $items[] = $row;
            $total += $row['subtotal'];
        }
    }
}

if (empty($items)) redirect('user/cart.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_penerima = clean($koneksi, $isMember ? $user['nama'] : ($_POST['nama_penerima'] ?? ''));
    $email_penerima = clean($koneksi, $isMember ? $user['email'] : ($_POST['email_penerima'] ?? ''));
    $telepon_penerima = clean($koneksi, $isMember ? ($user['telepon'] ?? '') : ($_POST['telepon_penerima'] ?? ''));
    $alamat_kirim = clean($koneksi, $_POST['alamat_kirim'] ?? '');

    if ($nama_penerima === '' || !filter_var($email_penerima, FILTER_VALIDATE_EMAIL) || $telepon_penerima === '' || $alamat_kirim === '') {
        $_SESSION['flash'] = ['type'=>'error','title'=>'Lengkapi data penerima dan alamat dengan benar'];
        redirect('user/checkout.php');
    }
    if ($isMember && isset($_POST['simpan_alamat'])) {
        mysqli_query($koneksi, "UPDATE users SET alamat='$alamat_kirim' WHERE id_user=$id_user");
    }

    $gift_wrap = isset($_POST['gift_wrap']) ? 1 : 0;
    $gift_message = $gift_wrap ? clean($koneksi, substr($_POST['gift_message'] ?? '', 0, 500)) : '';
    $poinTersedia = $isMember ? (int)($user['poin'] ?? 0) : 0;
    $maksPoin = min($poinTersedia, (int)floor(($total * 0.5) / 100));
    $poin_digunakan = $isMember ? min($maksPoin, max(0, (int)($_POST['poin_digunakan'] ?? 0))) : 0;
    $potongan_poin = $poin_digunakan * 100;
    $totalSetelahPoin = max(0, $total - $potongan_poin);
   $metode = $_POST['metode'] ?? 'QRIS';
if (!in_array($metode, ['QRIS','Transfer Bank'], true)) $metode = 'QRIS';
    $detail_bayar = null;
    $bukti_bayar = null;

    if ($metode === 'Transfer Bank') {
        $bank = $_POST['bank'] ?? '';
        if (!isset($GLOBALS['BANK_LIST'][$bank])) {
            $_SESSION['flash'] = ['type'=>'error','title'=>'Pilih bank tujuan transfer'];
            redirect('user/checkout.php');
        }
        $detail_bayar = $bank;
    }
    if ($metode === 'QRIS' || $metode === 'Transfer Bank') {
        $upload = upload_bukti($_FILES['bukti'] ?? null);
        if ($upload === false) {
            $_SESSION['flash'] = ['type'=>'error','title'=>'Upload bukti gagal','text'=>'Gunakan jpg/png/webp maksimal 3MB.'];
            redirect('user/checkout.php');
        }
        if ($upload !== 'nochange') $bukti_bayar = $upload;
    }

    // Validasi stok terakhir sebelum membuat transaksi. PO tidak mengunci stok fisik.
    foreach ($items as $it) {
        if (empty($it['is_preorder']) && (int)$it['qty'] > (int)$it['stok']) {
            $_SESSION['flash'] = ['type'=>'error','title'=>'Ada item melebihi stok, periksa keranjang Anda'];
            redirect('user/cart.php');
        }
    }

    $id_user_sql = $isMember ? (string)$id_user : 'NULL';
    $metode_sql = clean($koneksi, $metode);
    $detail_sql = $detail_bayar ? "'" . clean($koneksi, $detail_bayar) . "'" : 'NULL';
    $bukti_sql = $bukti_bayar ? "'" . clean($koneksi, $bukti_bayar) . "'" : 'NULL';
    $gift_message_sql = $gift_message !== '' ? "'" . clean($koneksi, $gift_message) . "'" : 'NULL';
    $kode_pesanan = 'TMP-' . strtoupper(bin2hex(random_bytes(8)));  
    $nama_sql = clean($koneksi, $nama_penerima);
    $email_sql = clean($koneksi, $email_penerima);
    $telepon_sql = clean($koneksi, $telepon_penerima);

    mysqli_begin_transaction($koneksi);
    try {
        mysqli_query($koneksi, "INSERT INTO orders (id_user,kode_pesanan,total,subtotal,ongkir,poin_digunakan,potongan_poin,status,alamat_kirim,nama_penerima,email_penerima,telepon_penerima,gift_wrap,gift_message,metode_bayar,detail_bayar,bukti_bayar,tgl_bayar)
          VALUES ($id_user_sql,'$kode_pesanan',$totalSetelahPoin,$total,0,$poin_digunakan,$potongan_poin,'Pending','$alamat_kirim','$nama_sql','$email_sql','$telepon_sql',$gift_wrap,$gift_message_sql,'$metode_sql',$detail_sql,$bukti_sql,NULL)");
        if ($isMember && $poin_digunakan > 0) {
            mysqli_query($koneksi, "UPDATE users SET poin=poin-$poin_digunakan WHERE id_user=$id_user AND poin >= $poin_digunakan");
            if (mysqli_affected_rows($koneksi) !== 1) {
                throw new RuntimeException('Saldo poin berubah. Silakan ulangi checkout.');
            }
        }
        $id_order = mysqli_insert_id($koneksi);
        // Menghitung jumlah transaksi berdasarkan email pelanggan
$stmtUrutan = mysqli_prepare(
    $koneksi,
    "SELECT COUNT(*)
     FROM orders o
     LEFT JOIN users u ON u.id_user = o.id_user
     WHERE LOWER(COALESCE(NULLIF(o.email_penerima, ''), u.email)) = LOWER(?)"
);

mysqli_stmt_bind_param($stmtUrutan, 's', $email_penerima);
mysqli_stmt_execute($stmtUrutan);

mysqli_stmt_bind_result($stmtUrutan, $jumlahTransaksi);
mysqli_stmt_fetch($stmtUrutan);

mysqli_stmt_close($stmtUrutan);

// Format tanggal DDMMYY
$tanggalPesanan = date('dmy');

// Membuat kode TBJ
$kode_pesanan = 'TBJ' . $tanggalPesanan .
    str_pad((string)$jumlahTransaksi, 3, '0', STR_PAD_LEFT);

// Memperbarui kode pesanan di database
$stmtUpdate = mysqli_prepare(
    $koneksi,
    "UPDATE orders SET kode_pesanan = ? WHERE id_order = ?"
);

mysqli_stmt_bind_param(
    $stmtUpdate,
    'si',
    $kode_pesanan,
    $id_order
);

if (!mysqli_stmt_execute($stmtUpdate)) {
    throw new RuntimeException('Gagal memperbarui kode pesanan.');
}

mysqli_stmt_close($stmtUpdate);


        foreach ($items as $it) {
            $id_buku = (int)$it['id_buku'];
            $qty = (int)$it['qty'];
            $harga = (float)$it['harga'];
            $subtotal = $harga * $qty;
            mysqli_query($koneksi, "INSERT INTO order_detail (id_order,id_buku,qty,harga,subtotal) VALUES ($id_order,$id_buku,$qty,$harga,$subtotal)");
            if (empty($it['is_preorder'])) {
                mysqli_query($koneksi, "UPDATE buku SET stok=stok-$qty WHERE id_buku=$id_buku AND stok >= $qty");
                if (mysqli_affected_rows($koneksi) !== 1) {
                    throw new RuntimeException('Stok buku berubah. Silakan periksa kembali keranjang.');
                }
            }
        }

        if ($isMember) mysqli_query($koneksi, "DELETE FROM cart WHERE id_user=$id_user");
        else unset($_SESSION['guest_cart']);
        mysqli_commit($koneksi);

        if ($isMember) redirect('user/struk.php?id=' . $id_order . '&baru=1');
        redirect('user/track.php?kode=' . urlencode($kode_pesanan) . '&email=' . urlencode($email_penerima));
    } catch (Throwable $e) {
        mysqli_rollback($koneksi);
        $_SESSION['flash'] = ['type'=>'error','title'=>'Gagal memproses pesanan','text'=>'Silakan coba lagi atau hubungi admin.'];
        redirect('user/checkout.php');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Checkout - An Chapters</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container my-5">
  <h3 class="fw-bold mb-4">Checkout</h3>
  <div class="row g-4">
    <div class="col-md-7">
      <div class="card p-3 shadow-sm">
        <h6 class="fw-bold mb-3">Ringkasan Pesanan</h6>
        <?php foreach ($items as $it): ?>
          <div class="d-flex justify-content-between border-bottom py-2"><span><?= htmlspecialchars($it['judul']) ?> x<?= $it['qty'] ?><?= !empty($it['is_preorder']) ? ' · PO' : '' ?></span><span><?= format_rupiah($it['subtotal']) ?></span></div>
        <?php endforeach; ?>
        <div class="d-flex justify-content-between pt-3"><strong>Subtotal Buku</strong><strong class="harga"><?= format_rupiah($total) ?></strong></div>
        <div class="small text-muted mt-2">Ongkos kirim akan dikonfirmasi admin. Total pada tahap ini belum termasuk ongkir; tunggu konfirmasi total akhir sebelum menyelesaikan pembayaran.</div>
      </div>
    </div>
    <div class="col-md-5">
      <div class="card p-3 shadow-sm">
        <h6 class="fw-bold mb-3">Alamat & Pembayaran</h6>
        <form method="POST" id="formCheckout" enctype="multipart/form-data">
          <?php if (!$isMember): ?>
            <div class="alert alert-light border small">Checkout sebagai tamu. Anda tidak perlu membuat akun untuk menyelesaikan pesanan.</div>
            <div class="mb-2"><label class="form-label">Nama Penerima</label><input type="text" name="nama_penerima" class="form-control" required maxlength="100"></div>
            <div class="mb-2"><label class="form-label">Email</label><input type="email" name="email_penerima" class="form-control" required maxlength="100"></div>
            <div class="mb-2"><label class="form-label">Nomor WhatsApp</label><input type="tel" name="telepon_penerima" class="form-control" required maxlength="20"></div>
          <?php else: ?>
            <div class="mb-2"><label class="form-label">Nama Penerima</label><input type="text" class="form-control" value="<?= htmlspecialchars($user['nama']) ?>" readonly></div>
            <div class="mb-2"><label class="form-label">Email</label><input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly></div>
            <div class="mb-2"><label class="form-label">Nomor WhatsApp</label><input type="text" class="form-control" value="<?= htmlspecialchars($user['telepon'] ?? '') ?>" readonly></div>
          <?php endif; ?>
          <div class="mb-3">
            <label class="form-label">Alamat Pengiriman</label>
            <textarea name="alamat_kirim" class="form-control" rows="3" required><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
            <?php if ($isMember): ?><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="simpan_alamat" id="simpanAlamat" checked><label class="form-check-label small" for="simpanAlamat">Simpan perubahan alamat untuk checkout berikutnya</label></div><?php endif; ?>
          </div>
          <?php if ($isMember && (int)($user['poin'] ?? 0) >= 100): ?>
            <div class="border-top pt-3 mb-3">
              <label class="form-label">Tukar poin pembaca</label>
              <div class="input-group"><input type="number" class="form-control" name="poin_digunakan" id="poinDigunakan" min="0" max="<?= min((int)$user['poin'], (int)floor(($total * .5) / 100)) ?>" step="100" value="0"><span class="input-group-text">poin</span></div>
              <div class="form-text">Saldo <?= (int)$user['poin'] ?> poin · 100 poin = Rp10.000 · Maksimal 50% subtotal.</div>
            </div>
          <?php endif; ?>
          <div class="border-top pt-3 mb-3">
            <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="gift_wrap" id="giftWrap"><label class="form-check-label" for="giftWrap">Tambahkan gift wrap dan greeting card</label></div>
            <textarea name="gift_message" id="giftMessage" class="form-control d-none" rows="2" maxlength="500" placeholder="Tulis pesan singkat untuk kartu ucapan..."></textarea>
            <div class="form-text">Biaya dan ketersediaan bungkus hadiah akan dikonfirmasi admin.</div>
          </div>
          <label class="form-label">Metode Pembayaran</label>
          <div class="small text-muted mb-2">Bukti pembayaran diperiksa admin secara manual. Status pesanan tetap Pending sampai pembayaran diverifikasi.</div>
          <div class="list-group mb-3" id="listMetode">
            <label class="list-group-item d-flex gap-2 align-items-start"><input class="form-check-input mt-1" type="radio" name="metode" value="COD" checked><span><strong>COD</strong><span class="text-muted small d-block">Bayar tunai saat buku diterima</span></span></label>
            <label class="list-group-item d-flex gap-2 align-items-start"><input class="form-check-input mt-1" type="radio" name="metode" value="QRIS"><span><strong>QRIS</strong><span class="text-muted small d-block">E-wallet dan mobile banking</span></span></label>
            <label class="list-group-item d-flex gap-2 align-items-start <?= empty($GLOBALS['BANK_LIST']) ? 'text-muted' : '' ?>"><input class="form-check-input mt-1" type="radio" name="metode" value="Transfer Bank" <?= empty($GLOBALS['BANK_LIST']) ? 'disabled' : '' ?>><span><strong>Transfer Bank</strong><span class="text-muted small d-block"><?= empty($GLOBALS['BANK_LIST']) ? 'Belum tersedia — admin perlu mengatur rekening tujuan.' : htmlspecialchars(implode(', ', array_keys($GLOBALS['BANK_LIST']))) ?></span></span></label>
          </div>
          <div id="panelBank" class="mb-3 d-none">
            <label class="form-label">Bank Tujuan</label>
            <select name="bank" id="selectBank" class="form-select"><option value="">Pilih bank</option><?php foreach ($GLOBALS['BANK_LIST'] as $nama => $info): ?><option value="<?= htmlspecialchars($nama) ?>"><?= htmlspecialchars($nama) ?></option><?php endforeach; ?></select>
          </div>
          <button type="submit" class="btn btn-brand w-100" id="btnBuat">Buat Pesanan</button>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalBayar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title fw-bold" id="modalBayarJudul">Pembayaran</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body text-center">
      <p class="mb-1 text-muted small">Perkiraan total sebelum ongkir</p><h4 class="harga mb-3" id="totalPembayaran" data-total="<?= (float)$total ?>"><?= format_rupiah($total) ?></h4>
      <div id="isiQris"><img src="<?= QRIS_IMAGE ?>" alt="QRIS An Chapters" class="img-fluid rounded border mb-2" style="max-height:340px;"><p class="small text-muted mb-3">Scan QRIS menggunakan e-wallet atau mobile banking. Pastikan QRIS yang dipasang adalah milik toko.</p></div>
      <div id="isiBank" class="d-none"><div class="border rounded p-3 mb-3 text-start"><div class="small text-muted">Bank</div><div class="fw-bold mb-2" id="bankNama"></div><div class="small text-muted">Nomor Rekening</div><div class="d-flex justify-content-between align-items-center mb-2"><span class="fw-bold fs-5" id="bankNo"></span><button type="button" class="btn btn-sm btn-outline-secondary" id="btnSalin">Salin</button></div><div class="small text-muted">Atas Nama</div><div class="fw-bold" id="bankAn"></div></div></div>
      <div class="text-start"><label class="form-label small mb-1">Upload bukti pembayaran <span class="text-muted">(opsional)</span></label><input type="file" name="bukti" form="formCheckout" id="inputBukti" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp"><div class="form-text">Jpg/png/webp maksimal 3MB. Bukti akan diperiksa admin; pesanan belum dianggap lunas sebelum diverifikasi.</div></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-brand" id="btnKonfirmasi">Lanjut Buat Pesanan</button></div>
  </div></div>
</div>

<?php include 'includes/footer.php'; ?>
<script>
const BANK_LIST = <?= json_encode($GLOBALS['BANK_LIST']) ?>;
const form = document.getElementById('formCheckout');
const modal = new bootstrap.Modal(document.getElementById('modalBayar'));
const metodeVal = () => form.querySelector('input[name="metode"]:checked').value;
function updatePanel() {
  const method = metodeVal();
  document.getElementById('panelBank').classList.toggle('d-none', method !== 'Transfer Bank');
}
form.querySelectorAll('input[name="metode"]').forEach(radio => radio.addEventListener('change', updatePanel));
updatePanel();
const poinInput = document.getElementById('poinDigunakan');
const totalPembayaran = document.getElementById('totalPembayaran');
if (poinInput && totalPembayaran) {
  const formatIDR = amount => new Intl.NumberFormat('id-ID', {style:'currency',currency:'IDR',maximumFractionDigits:0}).format(amount);
  poinInput.addEventListener('input', () => {
    const base = Number(totalPembayaran.dataset.total || 0);
    const points = Math.max(0, Number(poinInput.value || 0));
    totalPembayaran.textContent = formatIDR(Math.max(0, base - points * 100));
  });
}

document.getElementById('giftWrap')?.addEventListener('change', event => {
  document.getElementById('giftMessage').classList.toggle('d-none', !event.target.checked);
});
let sudahKonfirmasi = false;
form.addEventListener('submit', event => {
  const method = metodeVal();
  if ((method === 'QRIS' || method === 'Transfer Bank') && !sudahKonfirmasi) {
    event.preventDefault();
    if (method === 'Transfer Bank') {
      const bank = document.getElementById('selectBank').value;
      if (!bank) { Swal.fire({icon:'warning',title:'Pilih bank tujuan dulu',confirmButtonColor:'#5b3a29'}); return; }
      document.getElementById('modalBayarJudul').textContent = 'Transfer Bank ' + bank;
      document.getElementById('bankNama').textContent = bank;
      document.getElementById('bankNo').textContent = BANK_LIST[bank].no;
      document.getElementById('bankAn').textContent = BANK_LIST[bank].an;
      document.getElementById('isiBank').classList.remove('d-none');
      document.getElementById('isiQris').classList.add('d-none');
    } else {
      document.getElementById('modalBayarJudul').textContent = 'Pembayaran QRIS';
      document.getElementById('isiQris').classList.remove('d-none');
      document.getElementById('isiBank').classList.add('d-none');
    }
    modal.show();
  }
});
document.getElementById('btnKonfirmasi').addEventListener('click', () => {
  sudahKonfirmasi = true;
  form.requestSubmit();
});
document.getElementById('btnSalin').addEventListener('click', async event => {
  const no = document.getElementById('bankNo').textContent;
  try { await navigator.clipboard.writeText(no); event.currentTarget.textContent = 'Tersalin'; }
  catch (_) { event.currentTarget.textContent = 'Salin manual'; }
});
</script>
</body>
</html>
