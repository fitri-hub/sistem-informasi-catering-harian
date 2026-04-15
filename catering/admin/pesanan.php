<?php
require_once '../includes/config.php';
requireAdmin();

$flash = getFlash();

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $id     = (int)$_POST['id_pesanan'];
        $status = $_POST['status'];
        $allowed = ['pending','dikonfirmasi','diproses','selesai','dibatalkan'];
        if (in_array($status, $allowed)) {
            $conn->query("UPDATE pesanan SET status='$status' WHERE id_pesanan=$id");
            setFlash('success', 'Status pesanan diperbarui.');
        }
    }
    redirect(BASE_URL . '/admin/pesanan.php' . (isset($_GET['id']) ? '?id='.(int)$_GET['id'] : ''));
}

// Detail view
$detail_pesanan = null;
$detail_items   = null;
if (isset($_GET['id'])) {
    $pid = (int)$_GET['id'];
    $detail_pesanan = $conn->query("
        SELECT ps.*, u.nama, u.email
        FROM pesanan ps
        JOIN user u ON ps.id_user=u.id_user
        WHERE ps.id_pesanan=$pid
    ")->fetch_assoc();
    $detail_items = $conn->query("
        SELECT dp.*, m.nama_menu, m.harga
        FROM detail_pesanan dp
        JOIN menu m ON dp.id_menu=m.id_menu
        WHERE dp.id_pesanan=$pid
    ");
    $detail_payment = $conn->query("
        SELECT * FROM pembayaran WHERE id_pesanan=$pid LIMIT 1
    ")->fetch_assoc();
}

// List pesanan
$filter = sanitize($_GET['status'] ?? '');
$where  = $filter ? "WHERE ps.status='$filter'" : '';
$pesanan = $conn->query("
    SELECT ps.*, u.nama
    FROM pesanan ps
    JOIN user u ON ps.id_user=u.id_user
    $where
    ORDER BY ps.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan — CateringKu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout-wrapper">
    <?php include '../includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <div class="topbar">
            <div class="page-title">
                <h1>Kelola Pesanan</h1>
                <p>Manajemen dan update status pesanan</p>
            </div>
        </div>

        <div class="content-area">
            <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
            <?php endif; ?>

            <?php if ($detail_pesanan): ?>
            <!-- DETAIL VIEW -->
            <div style="margin-bottom:16px;">
                <a href="pesanan.php" class="btn btn-secondary">← Kembali ke Daftar</a>
            </div>

            <div class="card mb-24">
                <div class="card-header">
                    <span class="card-title">📋 Detail Pesanan #<?= $detail_pesanan['id_pesanan'] ?></span>
                    <span class="badge badge-<?= $detail_pesanan['status'] ?>"><?= $detail_pesanan['status'] ?></span>
                </div>
                <div class="card-body">
                    <div class="order-info-grid">
                        <div class="order-info-item">
                            <label>Pelanggan</label>
                            <div class="value"><?= htmlspecialchars($detail_pesanan['nama']) ?></div>
                        </div>
                        <div class="order-info-item">
                            <label>Email</label>
                            <div class="value"><?= htmlspecialchars($detail_pesanan['email']) ?></div>
                        </div>
                        <div class="order-info-item">
                            <label>Tanggal Pesanan</label>
                            <div class="value"><?= date('d F Y', strtotime($detail_pesanan['tanggal_pesanan'])) ?></div>
                        </div>
                        <div class="order-info-item">
                            <label>Total Harga</label>
                            <div class="value" style="color:var(--primary);"><?= formatRupiah($detail_pesanan['total_harga']) ?></div>
                        </div>
                    </div>

                    <!-- Update status form -->
                    <form method="POST" style="display:flex;gap:12px;align-items:center;margin-bottom:24px;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="id_pesanan" value="<?= $detail_pesanan['id_pesanan'] ?>">
                        <label class="form-label" style="margin:0;white-space:nowrap;">Update Status:</label>
                        <select name="status" class="form-control" style="max-width:200px;">
                            <?php foreach(['pending','dikonfirmasi','diproses','selesai','dibatalkan'] as $s): ?>
                            <option value="<?= $s ?>" <?= $detail_pesanan['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">💾 Update</button>
                    </form>

                    <h4 style="font-size:15px;margin-bottom:12px;">Item Pesanan</h4>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Menu</th>
                                    <th>Harga Satuan</th>
                                    <th>Jumlah</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($item = $detail_items->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['nama_menu']) ?></td>
                                    <td><?= formatRupiah($item['harga']) ?></td>
                                    <td><?= $item['jumlah'] ?></td>
                                    <td style="font-weight:700;color:var(--primary);"><?= formatRupiah($item['subtotal']) ?></td>
                                </tr>
                                <?php endwhile; ?>
                                <tr style="background:var(--surface-2);">
                                    <td colspan="3" style="font-weight:700;text-align:right;">Total:</td>
                                    <td style="font-weight:700;font-size:16px;color:var(--primary);"><?= formatRupiah($detail_pesanan['total_harga']) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($detail_payment): ?>
                    <div class="divider"></div>
                    <h4 style="font-size:15px;margin-bottom:12px;">💳 Info Pembayaran</h4>
                    <div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap;">
                        <div>
                            <p><strong>Status:</strong> <span class="badge badge-<?= $detail_payment['status'] ?>"><?= $detail_payment['status'] ?></span></p>
                            <p style="margin-top:8px;"><strong>Tanggal Bayar:</strong> <?= date('d/m/Y H:i', strtotime($detail_payment['tanggal_bayar'])) ?></p>
                        </div>
                        <?php if ($detail_payment['bukti_foto']): ?>
                        <div>
                            <p style="margin-bottom:8px;font-size:12px;font-weight:600;color:var(--text-muted);">BUKTI PEMBAYARAN:</p>
                            <img src="<?= UPLOAD_URL . htmlspecialchars($detail_payment['bukti_foto']) ?>"
                                 class="proof-img" alt="Bukti Pembayaran"
                                 onclick="window.open(this.src,'_blank')">
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: ?>
            <!-- LIST VIEW -->

            <!-- Filter -->
            <div class="card mb-24">
                <div class="card-body" style="padding:14px 24px;">
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <a href="pesanan.php" class="btn btn-sm <?= !$filter ? 'btn-primary' : 'btn-secondary' ?>">Semua</a>
                        <?php foreach(['pending','dikonfirmasi','diproses','selesai','dibatalkan'] as $s): ?>
                        <a href="?status=<?= $s ?>" class="btn btn-sm <?= $filter===$s ? 'btn-primary' : 'btn-secondary' ?>">
                            <?= ucfirst($s) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Pelanggan</th>
                                <th>Tanggal</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $rows = [];
                            while ($r = $pesanan->fetch_assoc()) $rows[] = $r;
                            if (empty($rows)):
                            ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <div class="empty-icon">📋</div>
                                        <h3>Tidak ada pesanan</h3>
                                    </div>
                                </td>
                            </tr>
                            <?php else: foreach ($rows as $r): ?>
                            <tr>
                                <td style="font-size:12px;color:var(--text-muted);"><?= $r['id_pesanan'] ?></td>
                                <td style="font-weight:600;"><?= htmlspecialchars($r['nama']) ?></td>
                                <td style="font-size:13px;"><?= date('d/m/Y', strtotime($r['tanggal_pesanan'])) ?></td>
                                <td style="font-weight:700;color:var(--primary);"><?= formatRupiah($r['total_harga']) ?></td>
                                <td><span class="badge badge-<?= $r['status'] ?>"><?= $r['status'] ?></span></td>
                                <td>
                                    <a href="?id=<?= $r['id_pesanan'] ?>" class="btn btn-sm btn-secondary">👁 Detail</a>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>