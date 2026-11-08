<?php
/** @var mysqli $koneksi */
require_once '../config/functions.php';
cek_login_user();
$id_user = $_SESSION['id_user'];

$user = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM users WHERE id_user=$id_user"));

$q = mysqli_query($koneksi, "SELECT c.*, b.judul, b.harga, b.stok FROM cart c JOIN buku b ON c.id_buku=b.id_buku WHERE c.id_user=$id_user");
$items = [];
$total = 0;
while ($row = mysqli_fetch_assoc($q)) {
    $items[] = $row;
    $total += $row['subtotal'];
}

if (empty($items)) {
    redirect('user/cart.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alamat_kirim = clean($koneksi, $_POST['alamat_kirim']);

    // ---------- Validasi metode pembayaran ----------
    $metode = $_POST['metode'] ?? 'COD';
    if (!in_array($metode, ['COD', 'QRIS', 'Transfer Bank', 'Kartu'])) {
        $metode = 'COD';
    }

    $detail_bayar = null;   // mis. 'BCA' atau 'Visa **** 4242'
    $bukti_bayar  = null;   // nama file bukti (opsional)

    if ($metode === 'Transfer Bank') {
        $bank = $_POST['bank'] ?? '';
        if (!isset($GLOBALS['BANK_LIST'][$bank])) {
            $_SESSION['flash'] = ['type' => 'error', 'title' => 'Pilih bank tujuan transfer'];
            redirect('user/checkout.php');
        }
        $detail_bayar = $bank;
    }

    if ($metode === 'Kartu') {
        $brand = $_POST['kartu_brand'] ?? '';
        $last4 = $_POST['kartu_last4'] ?? '';
        if (!in_array($brand, $GLOBALS['KARTU_LIST']) || !preg_match('/^\d{4}$/', $last4)) {
            $_SESSION['flash'] = ['type' => 'error', 'title' => 'Data kartu tidak valid'];
            redirect('user/checkout.php');
        }
        // Nomor kartu lengkap & CVV TIDAK pernah dikirim/disimpan, hanya merek + 4 digit terakhir
        $detail_bayar = $brand . ' **** ' . $last4;
    }

    if ($metode === 'QRIS' || $metode === 'Transfer Bank') {
        $up = upload_bukti($_FILES['bukti'] ?? null);
        if ($up === false) {
            $_SESSION['flash'] = ['type' => 'error', 'title' => 'Upload bukti gagal', 'text' => 'Gunakan jpg/png/webp maksimal 3MB.'];
            redirect('user/checkout.php');
        }
        if ($up !== 'nochange') {
            $bukti_bayar = $up;
        }
    }

    // Status awal: COD = Pending. Kartu = Dibayar. QRIS/Transfer = Dibayar jika ada bukti, kalau tidak Pending
    $status = 'Pending';
    if ($metode === 'Kartu' || (($metode === 'QRIS' || $metode === 'Transfer Bank') && $bukti_bayar)) {
        $status = 'Dibayar';
    }

    // Cek ulang stok sebelum proses (mencegah stok minus)
    $cukup = true;
    foreach ($items as $it) {
        if ($it['qty'] > $it['stok']) { $cukup = false; break; }
    }

    if (!$cukup) {
        $_SESSION['flash'] = ['type' => 'error', 'title' => 'Ada item melebihi stok, cek kembali keranjang Anda'];
        redirect('user/cart.php');
    }

    $metode_sql  = clean($koneksi, $metode);
    $detail_sql  = $detail_bayar ? "'" . clean($koneksi, $detail_bayar) . "'" : 'NULL';
    $bukti_sql   = $bukti_bayar  ? "'" . clean($koneksi, $bukti_bayar)  . "'" : 'NULL';
    $tgl_sql     = $status === 'Dibayar' ? 'NOW()' : 'NULL';

    mysqli_begin_transaction($koneksi);
    try {
        mysqli_query($koneksi, "INSERT INTO orders (id_user, total, status, alamat_kirim, metode_bayar, detail_bayar, bukti_bayar, tgl_bayar)
            VALUES ($id_user, $total, '$status', '$alamat_kirim', '$metode_sql', $detail_sql, $bukti_sql, $tgl_sql)");
        $id_order = mysqli_insert_id($koneksi);

        foreach ($items as $it) {
            mysqli_query($koneksi, "INSERT INTO order_detail (id_order, id_buku, qty, harga, subtotal)
                VALUES ($id_order, {$it['id_buku']}, {$it['qty']}, {$it['harga']}, {$it['subtotal']})");
            // kurangi stok, pastikan tidak minus
            mysqli_query($koneksi, "UPDATE buku SET stok = GREATEST(0, stok - {$it['qty']}) WHERE id_buku={$it['id_buku']}");
        }

        mysqli_query($koneksi, "DELETE FROM cart WHERE id_user=$id_user");
        mysqli_commit($koneksi);

        // Ke halaman struk + pop up bukti pembayaran
        redirect('user/struk.php?id=' . $id_order . '&baru=1');
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $_SESSION['flash'] = ['type' => 'error', 'title' => 'Gagal memproses pesanan'];
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
          <div class="d-flex justify-content-between border-bottom py-2">
            <span><?= htmlspecialchars($it['judul']) ?> x<?= $it['qty'] ?></span>
            <span><?= format_rupiah($it['subtotal']) ?></span>
          </div>
        <?php endforeach; ?>
        <div class="d-flex justify-content-between pt-3">
          <strong>Total</strong>
          <strong class="harga"><?= format_rupiah($total) ?></strong>
        </div>
      </div>
    </div>
    <div class="col-md-5">
      <div class="card p-3 shadow-sm">
        <h6 class="fw-bold mb-3">Alamat & Pembayaran</h6>
        <form method="POST" id="formCheckout" enctype="multipart/form-data">
          <div class="mb-3">
            <label class="form-label">Alamat Pengiriman</label>
            <textarea name="alamat_kirim" class="form-control" rows="3" required><?= htmlspecialchars($user['alamat']) ?></textarea>
          </div>

          <label class="form-label">Metode Pembayaran</label>
          <div class="list-group mb-3" id="listMetode">
            <label class="list-group-item d-flex gap-2 align-items-start">
              <input class="form-check-input mt-1" type="radio" name="metode" value="COD" checked>
              <span><strong>COD</strong> <span class="text-muted small d-block">Bayar tunai saat buku diterima</span></span>
            </label>
            <label class="list-group-item d-flex gap-2 align-items-start">
              <input class="form-check-input mt-1" type="radio" name="metode" value="QRIS">
              <span><strong>QRIS</strong> <span class="text-muted small d-block">GoPay, OVO, DANA, ShopeePay, m-banking, dll</span></span>
            </label>
            <label class="list-group-item d-flex gap-2 align-items-start">
              <input class="form-check-input mt-1" type="radio" name="metode" value="Transfer Bank">
              <span><strong>Transfer Bank</strong> <span class="text-muted small d-block">BCA, Mandiri, BNI, BRI</span></span>
            </label>
            <label class="list-group-item d-flex gap-2 align-items-start">
              <input class="form-check-input mt-1" type="radio" name="metode" value="Kartu">
              <span><strong>Kartu Kredit / Debit</strong> <span class="text-muted small d-block">Visa, Mastercard</span></span>
            </label>
          </div>

          <!-- Panel: pilih bank -->
          <div id="panelBank" class="mb-3 d-none">
            <label class="form-label">Bank Tujuan</label>
            <select name="bank" id="selectBank" class="form-select">
              <option value="">Pilih bank</option>
              <?php foreach ($GLOBALS['BANK_LIST'] as $nama => $info): ?>
                <option value="<?= htmlspecialchars($nama) ?>"><?= htmlspecialchars($nama) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Panel: kartu (nomor lengkap & CVV TIDAK dikirim ke server) -->
          <div id="panelKartu" class="mb-3 d-none">
            <div class="mb-2">
              <label class="form-label">Nomor Kartu <span id="brandKartu" class="badge bg-secondary ms-1 d-none"></span></label>
              <input type="text" id="kartuNo" class="form-control" inputmode="numeric" maxlength="19" placeholder="1234 5678 9012 3456" autocomplete="off">
            </div>
            <div class="mb-2">
              <label class="form-label">Nama di Kartu</label>
              <input type="text" id="kartuNama" class="form-control" autocomplete="off">
            </div>
            <div class="row g-2">
              <div class="col-6">
                <label class="form-label">Berlaku s/d</label>
                <input type="text" id="kartuExp" class="form-control" maxlength="5" placeholder="MM/YY" autocomplete="off">
              </div>
              <div class="col-6">
                <label class="form-label">CVV</label>
                <input type="password" id="kartuCvv" class="form-control" inputmode="numeric" maxlength="4" autocomplete="off">
              </div>
            </div>
            <input type="hidden" name="kartu_brand" id="kartuBrand">
            <input type="hidden" name="kartu_last4" id="kartuLast4">
          </div>

          <button type="submit" class="btn btn-brand w-100" id="btnBuat">Buat Pesanan</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Pop up pembayaran QRIS / Transfer Bank -->
<div class="modal fade" id="modalBayar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="modalBayarJudul">Pembayaran</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <p class="mb-1 text-muted small">Total yang harus dibayar</p>
        <h4 class="harga mb-3"><?= format_rupiah($total) ?></h4>

        <!-- QRIS -->
        <div id="isiQris">
          <img src="<?= QRIS_IMAGE ?>" alt="QRIS An Chapters" class="img-fluid rounded border mb-2" style="max-height:340px;">
          <p class="small text-muted mb-3">Scan QRIS di atas menggunakan e-wallet atau mobile banking, lalu bayar sesuai total.</p>
        </div>

        <!-- Transfer bank -->
        <div id="isiBank" class="d-none">
          <div class="border rounded p-3 mb-3 text-start">
            <div class="small text-muted">Bank</div>
            <div class="fw-bold mb-2" id="bankNama"></div>
            <div class="small text-muted">Nomor Rekening</div>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fw-bold fs-5" id="bankNo"></span>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSalin">Salin</button>
            </div>
            <div class="small text-muted">Atas Nama</div>
            <div class="fw-bold" id="bankAn"></div>
          </div>
        </div>

        <div class="text-start">
          <label class="form-label small mb-1">Upload bukti pembayaran <span class="text-muted">(opsional)</span></label>
          <input type="file" name="bukti" form="formCheckout" id="inputBukti" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp">
          <div class="form-text">Jpg/png/webp maksimal 3MB. Pesanan dengan bukti langsung berstatus <em>Dibayar</em>, tanpa bukti berstatus <em>Pending</em> sampai dikonfirmasi admin.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-brand" id="btnKonfirmasi">Saya Sudah Bayar</button>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
const BANK_LIST = <?= json_encode($GLOBALS['BANK_LIST']) ?>;
const form = document.getElementById('formCheckout');
const modalEl = document.getElementById('modalBayar');
const modal = new bootstrap.Modal(modalEl);

const metodeVal = () => form.querySelector('input[name="metode"]:checked').value;

// tampilkan panel sesuai metode
function updatePanel() {
  const m = metodeVal();
  document.getElementById('panelBank').classList.toggle('d-none', m !== 'Transfer Bank');
  document.getElementById('panelKartu').classList.toggle('d-none', m !== 'Kartu');
}
form.querySelectorAll('input[name="metode"]').forEach(r => r.addEventListener('change', updatePanel));
updatePanel();

// ---------- Kartu: format & validasi (hanya di browser) ----------
const kartuNo = document.getElementById('kartuNo');
function brandDari(no) {
  if (/^4/.test(no)) return 'Visa';
  if (/^(5[1-5]|2(2[2-9][1-9]|2[3-9]|[3-6]|7[01]|720))/.test(no)) return 'Mastercard';
  return '';
}
function luhn(no) {
  let sum = 0, alt = false;
  for (let i = no.length - 1; i >= 0; i--) {
    let n = parseInt(no[i], 10);
    if (alt) { n *= 2; if (n > 9) n -= 9; }
    sum += n; alt = !alt;
  }
  return sum % 10 === 0;
}
kartuNo.addEventListener('input', function () {
  const digits = this.value.replace(/\D/g, '').slice(0, 16);
  this.value = digits.replace(/(.{4})/g, '$1 ').trim();
  const b = brandDari(digits), badge = document.getElementById('brandKartu');
  badge.textContent = b; badge.classList.toggle('d-none', !b);
});
document.getElementById('kartuExp').addEventListener('input', function () {
  let d = this.value.replace(/\D/g, '').slice(0, 4);
  if (d.length > 2) d = d.slice(0, 2) + '/' + d.slice(2);
  this.value = d;
});

function validasiKartu() {
  const no = kartuNo.value.replace(/\D/g, '');
  const brand = brandDari(no);
  const exp = document.getElementById('kartuExp').value;
  const cvv = document.getElementById('kartuCvv').value;
  let err = '';
  if (!brand) err = 'Hanya kartu Visa atau Mastercard yang diterima.';
  else if (no.length !== 16 || !luhn(no)) err = 'Nomor kartu tidak valid.';
  else if (!document.getElementById('kartuNama').value.trim()) err = 'Nama di kartu wajib diisi.';
  else if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(exp)) err = 'Masa berlaku harus berformat MM/YY.';
  else {
    const [mm, yy] = exp.split('/').map(Number);
    const akhir = new Date(2000 + yy, mm, 0, 23, 59, 59);
    if (akhir < new Date()) err = 'Kartu sudah kedaluwarsa.';
  }
  if (!err && !/^\d{3,4}$/.test(cvv)) err = 'CVV harus 3-4 digit.';
  if (err) { Swal.fire({icon: 'error', title: err, confirmButtonColor: '#5b3a29'}); return false; }
  document.getElementById('kartuBrand').value = brand;
  document.getElementById('kartuLast4').value = no.slice(-4);
  return true;
}

// ---------- Submit ----------
let sudahKonfirmasi = false;
form.addEventListener('submit', function (e) {
  const m = metodeVal();
  if (m === 'Kartu') {
    if (!validasiKartu()) e.preventDefault();
    return;
  }
  if ((m === 'QRIS' || m === 'Transfer Bank') && !sudahKonfirmasi) {
    e.preventDefault();
    if (m === 'Transfer Bank') {
      const bank = document.getElementById('selectBank').value;
      if (!bank) { Swal.fire({icon: 'warning', title: 'Pilih bank tujuan dulu', confirmButtonColor: '#5b3a29'}); return; }
      document.getElementById('modalBayarJudul').textContent = 'Transfer Bank ' + bank;
      document.getElementById('bankNama').textContent = bank;
      document.getElementById('bankNo').textContent = BANK_LIST[bank].no;
      document.getElementById('bankAn').textContent = BANK_LIST[bank].an;
      document.getElementById('isiQris').classList.add('d-none');
      document.getElementById('isiBank').classList.remove('d-none');
    } else {
      document.getElementById('modalBayarJudul').textContent = 'Bayar dengan QRIS';
      document.getElementById('isiQris').classList.remove('d-none');
      document.getElementById('isiBank').classList.add('d-none');
    }
    modal.show();
  }
});

document.getElementById('btnKonfirmasi').addEventListener('click', function () {
  sudahKonfirmasi = true;
  this.disabled = true;
  this.textContent = 'Memproses...';
  form.submit();
});

document.getElementById('btnSalin').addEventListener('click', function () {
  const no = document.getElementById('bankNo').textContent;
  if (navigator.clipboard) navigator.clipboard.writeText(no);
  this.textContent = 'Tersalin';
  setTimeout(() => this.textContent = 'Salin', 1500);
});
</script>
