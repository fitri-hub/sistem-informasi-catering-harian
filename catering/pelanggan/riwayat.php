<?php
require_once '../includes/config.php';
requirePelanggan();

$uid   = $_SESSION['user_id'];
$flash = getFlash();

// Detail view
$detail = null;
$items  = null;
if (isset($_GET['id'])) {
    $pid    = (int)$_GET['id'];
    $detail = $conn->query("SELECT * FROM pesanan WHERE id_pesanan=$pid AND id_user=$uid")->fetch_assoc();
    if ($detail) {
        $items  = $conn->query("
            SELECT dp.*, m.nama_menu, m.harga
            FROM detail_pesanan dp
            JOIN menu m ON dp.id_menu=m.id_menu
            WHERE dp.id_pesanan=$pid
        ");
        $payment = $conn->query("SELECT * FROM pembayaran WHERE id_pesanan=$pid")->fetch_assoc();
    }
}

// Filter
$filter  = sanitize($_GET['status'] ?? '');
$where   = $filter ? "AND status='$filter'" : '';
$pesanan = $conn->query("SELECT * FROM pesanan WHERE id_user=$uid $where ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan — CateringKu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout-wrapper">
    <?php include '../includes/sidebar_pelanggan.php'; ?>

    <main class="main-content">
        <div class="topbar">
            <div class="page-title">
                <h1>Riwayat Pesanan</h1>
                <p>Seluruh riwayat pesanan Anda</p>
            </div>
        </div>

        <div class="content-area">
            <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
            <?php endif; ?>

            <!-- Filter -->
            <div class="card mb-24">
                <div class="card-body" style="padding:14px 24px;">
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <a href="riwayat.php" class="btn btn-sm <?= !$filter ? 'btn-primary' : 'btn-secondary' ?>">Semua</a>
                        <?php foreach(['pending','dikonfirmasi','diproses','selesai','dibatalkan'] as $s): ?>
                        <a href="?status=<?= $s ?>" class="btn btn-sm <?= $filter===$s ? 'btn-primary' : 'btn-secondary' ?>">
                            <?= ucfirst($s) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <?php if ($detail): ?>
            <!-- Detail -->
            <div class="card mb-24">
                <div class="card-header">
                    <span class="card-title">Detail Pesanan #<?= $detail['id_pesanan'] ?></span>
                    <div style="display:flex;gap:8px;">
                        <span class="badge badge-<?= $detail['status'] ?>"><?= $detail['status'] ?></span>
                        <a href="riwayat.php<?= $filter ? '?status='.$filter : '' ?>" class="btn btn-sm btn-secondary">✕ Tutup</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="order-info-grid">
                        <div class="order-info-item">
                            <label>Tanggal Pesanan</label>
                            <div class="value"><?= date('d F Y', strtotime($detail['tanggal_pesanan'])) ?></div>
                        </div>
                        <div class="order-info-item">
                            <label>Status</label>
                            <div class="value"><span class="badge badge-<?= $detail['status'] ?>"><?= $detail['status'] ?></span></div>
                        </div>
                        <div class="order-info-item">
                            <label>Total Harga</label>
                            <div class="value" style="color:var(--primary);"><?= formatRupiah($detail['total_harga']) ?></div>
                        </div>
                    </div>

                    <h4 style="font-size:14px;margin-bottom:12px;color:var(--text-secondary);">Item Pesanan</h4>
                    <div class="table-wrapper">
                        <table>
                            <thead><tr><th>Menu</th><th>Harga</th><th>Qty</th><th>Subtotal</th></tr></thead>
                            <tbody>
                                <?php while ($item = $items->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['nama_menu']) ?></td>
                                    <td><?= formatRupiah($item['harga']) ?></td>
                                    <td><?= $item['jumlah'] ?></td>
                                    <td style="font-weight:700;color:var(--primary);"><?= formatRupiah($item['subtotal']) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($detail['status'] === 'pending' && (!isset($payment) || !$payment)): ?>
                    <div style="margin-top:16px;">
                        <a href="pembayaran.php?id_pesanan=<?= $detail['id_pesanan'] ?>"
                           class="btn btn-primary">💳 Lakukan Pembayaran</a>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($payment) && $payment): ?>
                    <div class="divider"></div>
                    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                        <div>
                            <span style="font-size:12px;color:var(--text-muted);">Status Pembayaran: </span>
                            <span class="badge badge-<?= $payment['status'] ?>"><?= $payment['status'] ?></span>
                        </div>
                        <?php if ($payment['status'] === 'ditolak'): ?>
                        <a href="pembayaran.php?id_pesanan=<?= $detail['id_pesanan'] ?>"
                           class="btn btn-sm btn-primary">📤 Upload Ulang</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- List -->
            <div class="card">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
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
                            <tr><td colspan="5">
                                <div class="empty-state" style="padding:40px;">
                                    <div class="empty-icon">📋</div>
                                    <h3>Belum ada pesanan</h3>
                                    <a href="menu.php" class="btn btn-primary" style="margin-top:12px;">Mulai Pesan</a>
                                </div>
                            </td></tr>
                            <?php else: foreach ($rows as $r): ?>
                            <tr style="<?= isset($_GET['id']) && $_GET['id']==$r['id_pesanan'] ? 'background:var(--accent-soft);' : '' ?>">
                                <td style="font-weight:600;color:var(--text-muted);font-size:12px;">#<?= $r['id_pesanan'] ?></td>
                                <td><?= date('d M Y', strtotime($r['tanggal_pesanan'])) ?></td>
                                <td style="font-weight:700;color:var(--primary);"><?= formatRupiah($r['total_harga']) ?></td>
                                <td><span class="badge badge-<?= $r['status'] ?>"><?= $r['status'] ?></span></td>
                                <td>
                                    <a href="?id=<?= $r['id_pesanan'] ?><?= $filter ? '&status='.$filter : '' ?>"
                                       class="btn btn-sm btn-secondary">👁 Detail</a>
                                    <?php if ($r['status'] === 'pending'): ?>
                                    <a href="pembayaran.php?id_pesanan=<?= $r['id_pesanan'] ?>"
                                       class="btn btn-sm btn-primary">💳 Bayar</a>
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