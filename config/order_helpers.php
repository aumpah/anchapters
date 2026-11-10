<?php
/** Helper untuk aksi konfirmasi penerimaan pesanan oleh user. */

// Konfirmasi penerimaan hanya mengubah pesanan yang masih berstatus Dikirim.
// Kepemilikan pesanan harus diverifikasi terlebih dahulu oleh halaman pemanggil.
// Poin pelanggan diberikan satu kali setelah pesanan berhasil diselesaikan.
function konfirmasi_pesanan_diterima($koneksi, $id_order) {
    $id_order = (int) $id_order;
    if ($id_order <= 0) return false;

    mysqli_begin_transaction($koneksi);
    try {
        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id_user, subtotal, poin_diberikan
             FROM orders
             WHERE id_order = ? AND status = 'Dikirim'
             FOR UPDATE"
        );
        if (!$stmt) throw new RuntimeException('Gagal menyiapkan pemeriksaan pesanan.');
        mysqli_stmt_bind_param($stmt, 'i', $id_order);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $order = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        if (!$order) {
            mysqli_rollback($koneksi);
            return false;
        }

        $stmtUpdate = mysqli_prepare(
            $koneksi,
            "UPDATE orders SET status = 'Selesai'
             WHERE id_order = ? AND status = 'Dikirim'"
        );
        if (!$stmtUpdate) throw new RuntimeException('Gagal menyiapkan pembaruan status.');
        mysqli_stmt_bind_param($stmtUpdate, 'i', $id_order);
        mysqli_stmt_execute($stmtUpdate);
        $berhasil = mysqli_stmt_affected_rows($stmtUpdate) === 1;
        mysqli_stmt_close($stmtUpdate);
        if (!$berhasil) throw new RuntimeException('Status pesanan sudah berubah.');

        $id_user_order = (int) ($order['id_user'] ?? 0);
        if ($id_user_order > 0 && (int) ($order['poin_diberikan'] ?? 0) === 0) {
            $poin = (int) floor(((float) $order['subtotal']) / 10000);
            if ($poin > 0) {
                $stmtPoin = mysqli_prepare($koneksi, 'UPDATE users SET poin = poin + ? WHERE id_user = ?');
                if (!$stmtPoin) throw new RuntimeException('Gagal menyiapkan pemberian poin.');
                mysqli_stmt_bind_param($stmtPoin, 'ii', $poin, $id_user_order);
                mysqli_stmt_execute($stmtPoin);
                mysqli_stmt_close($stmtPoin);
            }

            $stmtTandai = mysqli_prepare($koneksi, 'UPDATE orders SET poin_diberikan = 1 WHERE id_order = ? AND poin_diberikan = 0');
            if (!$stmtTandai) throw new RuntimeException('Gagal menandai poin pesanan.');
            mysqli_stmt_bind_param($stmtTandai, 'i', $id_order);
            mysqli_stmt_execute($stmtTandai);
            mysqli_stmt_close($stmtTandai);
        }

        mysqli_commit($koneksi);
        return true;
    } catch (Throwable $e) {
        mysqli_rollback($koneksi);
        error_log('Konfirmasi pesanan diterima gagal: ' . $e->getMessage());
        return false;
    }
}
