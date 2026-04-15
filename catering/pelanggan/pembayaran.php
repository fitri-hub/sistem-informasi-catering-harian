<?php
require_once '../includes/config.php';
requirePelanggan();

$uid   = $_SESSION['user_id'];
$flash = getFlash();

// Handle payment upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'upload_bayar') {
    $id_pesanan = (int)$_POST['id_pesanan'];

    // Verify ownership
    $cek = $conn->query("SELECT * FROM pesanan WHERE id_pesanan=$id_pesanan AND id_user=$uid")->fetch_assoc();
    if (!$cek) {
        setFlash('danger', 'Pesanan tidak ditemukan.');
        redirect(BASE_URL . '/pelanggan/pembayaran.php');
    }

    $foto_path = null;
    if (!empty($_FILES['bukti']['name'])) {
        $ext     = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','pdf'];
        if (!in_array($ext, $allowed)) {
            setFlash('danger', 'Format file tidak didukung. Gunakan JPG/PNG/PDF.');
            redirect(BASE_URL . '/pelanggan/pembayaran.php?id_pesanan=' . $id_pesanan);
        }
        if (!is_dir(UPLOAD_PATH)) mkdir(UPLOAD_PATH, 0777, true);
        $fname = 'bukti_' . $id_pesanan . '_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['bukti']['tmp_name'], UPLOAD_PATH . $fname);
        $foto_path = $fname;
    }

    // Check if payment record exists
    $exist = $conn->query("SELECT * FROM pembayaran WHERE id_pesanan=$id_pesanan")->fetch_assoc();
    if ($exist) {
        if ($foto_path) {
            $conn->query("UPDATE pembayaran SET bukti_foto='$foto_path', status='pending' WHERE id_pesanan=$id_pesanan");
        }
    } else {
        $s = $conn->prepare("INSERT INTO pembayaran (id_pesanan, bukti_foto, status) VALUES (?,?,'pending')");
        $s->bind_param('is', $id_pesanan, $foto_path);
        $s->execute();
    }

    setFlash('success', '📤 Bukti pembayaran berhasil diupload! Menunggu verifikasi admin.');
    redirect(BASE_URL . '/pelanggan/pembayaran.php');
}

// Get specific pesanan for payment
$pesanan_detail = null;
if (isset($_GET['id_pesanan'])) {
    $pid = (int)$_GET['id_pesanan'];
    $pesanan_detail = $conn->query("
        SELECT ps.*, pb.status as status_bayar, pb.bukti_foto, pb.id_pembayaran
        FROM pesanan ps
        LEFT JOIN pembayaran pb ON ps.id_pesanan=pb.id_pesanan
        WHERE ps.id_pesanan=$pid AND ps.id_user=$uid
    ")->fetch_assoc();
}

// All payments
$payments = $conn->query("
    SELECT ps.id_pesanan, ps.tanggal_pesanan, ps.total_harga, ps.status as status_pesanan,
           pb.status as status_bayar, pb.bukti_foto, pb.tanggal_bayar, pb.id_pembayaran
    FROM pesanan ps
    LEFT JOIN pembayaran pb ON ps.id_pesanan=pb.id_pesanan
    WHERE ps.id_user=$uid
    ORDER BY ps.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran — CateringKu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout-wrapper">
    <?php include '../includes/sidebar_pelanggan.php'; ?>

    <main class="main-content">
        <div class="topbar">
            <div class="page-title">
                <h1>Pembayaran</h1>
                <p>Upload bukti dan pantau status pembayaran</p>
            </div>
        </div>

        <div class="content-area">
            <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
            <?php endif; ?>

            <?php if ($pesanan_detail): ?>
            <!-- Upload Form -->
            <div class="card mb-24">
                <div class="card-header">
                    <span class="card-title">💳 Upload Bukti Pembayaran</span>
                    <a href="pembayaran.php" class="btn btn-sm btn-secondary">← Kembali</a>
                </div>
                <div class="card-body">
                    <div class="order-info-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px;">
                        <div class="order-info-item">
                            <label>No. Pesanan</label>
                            <div class="value">#<?= $pesanan_detail['id_pesanan'] ?></div>
                        </div>
                        <div class="order-info-item">
                            <label>Tanggal</label>
                            <div class="value"><?= date('d M Y', strtotime($pesanan_detail['tanggal_pesanan'])) ?></div>
                        </div>
                        <div class="order-info-item">
                            <label>Total Bayar</label>
                            <div class="value" style="color:var(--primary);font-size:18px;"><?= formatRupiah($pesanan_detail['total_harga']) ?></div>
                        </div>
                    </div>

                    <?php if ($pesanan_detail['status_bayar'] === 'verified'): ?>
                    <div class="alert alert-success">✅ Pembayaran Anda telah terverifikasi!</div>
                    <?php elseif ($pesanan_detail['status_bayar'] === 'pending'): ?>
                    <div class="alert alert-warning">⏳ Bukti pembayaran sedang menunggu verifikasi admin.</div>
                    <?php elseif ($pesanan_detail['status_bayar'] === 'ditolak'): ?>
                    <div class="alert alert-danger">❌ Pembayaran ditolak. Silakan upload ulang bukti yang valid.</div>
                    <?php else: ?>
                    <!-- Info transfer -->
                    <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:20px;margin-bottom:20px;">
                        <h4 style="font-size:14px;margin-bottom:12px;color:var(--text-secondary);">💳 Transfer ke Rekening Berikut:</h4>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div><span style="font-size:12px;color:var(--text-muted);">Bank</span><div style="font-weight:700;">BCA</div></div>
                            <div><span style="font-size:12px;color:var(--text-muted);">No. Rekening</span><div style="font-weight:700;font-family:monospace;">1234-5678-90</div></div>
                            <div><span style="font-size:12px;color:var(--text-muted);">Atas Nama</span><div style="font-weight:700;">CateringKu</div></div>
                            <div><span style="font-size:12px;color:var(--text-muted);">Jumlah</span><div style="font-weight:700;color:var(--primary);"><?= formatRupiah($pesanan_detail['total_harga']) ?></div></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($pesanan_detail['status_bayar'] !== 'verified'): ?>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_bayar">
                        <input type="hidden" name="id_pesanan" value="<?= $pesanan_detail['id_pesanan'] ?>">
                        <div class="form-group">
                            <label class="form-label">📎 Upload Bukti Pembayaran *</label>
                            <input type="file" name="bukti" class="form-control" accept="image/*,.pdf" required>
                            <p class="form-hint">Format: JPG, PNG, atau PDF. Maksimal 5MB.</p>
                        </div>
                        <?php if ($pesanan_detail['bukti_foto']): ?>
                        <div style="margin-bottom:16px;">
                            <p style="font-size:12px;color:var(--text-muted);margin-bottom:8px;">Bukti sebelumnya:</p>
                            <img src="<?= UPLOAD_URL . htmlspecialchars($pesanan_detail['bukti_foto']) ?>"
                                 class="proof-img" alt="Bukti lama" style="max-width:200px;">
                        </div>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary">📤 Upload Bukti Pembayaran</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Payments History -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">💰 Riwayat Pembayaran</span>
                </div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>No. Pesanan</th>
                                <th>Tanggal</th>
                                <th>Total</th>
                                <th>Status Bayar</th>
                                <th>Status Pesanan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $rows = [];
                            while ($r = $payments->fetch_assoc()) $rows[] = $r;
                            if (empty($rows)):
                            ?>
                            <tr><td colspan="6"><div class="empty-state" style="padding:30px;"><div class="empty-icon">💳</div><p>Belum ada riwayat pembayaran</p></div></td></tr>
                            <?php else: foreach ($rows as $r): ?>
                            <tr>
                                <td style="font-weight:600;">#<?= $r['id_pesanan'] ?></td>
                                <td style="font-size:13px;"><?= date('d/m/Y', strtotime($r['tanggal_pesanan'])) ?></td>
                                <td style="font-weight:700;color:var(--primary);"><?= formatRupiah($r['total_harga']) ?></td>
                                <td>
                                    <?php if ($r['status_bayar']): ?>
                                    <span class="badge badge-<?= $r['status_bayar'] ?>"><?= $r['status_bayar'] ?></span>
                                    <?php else: ?>
                                    <span class="badge badge-pending">Belum Bayar</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-<?= $r['status_pesanan'] ?>"><?= $r['status_pesanan'] ?></span></td>
                                <td>
                                    <?php if (!$r['status_bayar'] || $r['status_bayar'] === 'ditolak'): ?>
                                    <a href="?id_pesanan=<?= $r['id_pesanan'] ?>"
                                       class="btn btn-sm btn-primary">📤 Upload</a>
                                    <?php else: ?>
                                    <a href="?id_pesanan=<?= $r['id_pesanan'] ?>"
                                       class="btn btn-sm btn-secondary">👁 Detail</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>