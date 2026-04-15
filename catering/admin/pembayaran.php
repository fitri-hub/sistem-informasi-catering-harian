<?php
require_once '../includes/config.php';
requireAdmin();

$flash = getFlash();

// Verify/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id_pembayaran = (int)$_POST['id_pembayaran'];
    $action = $_POST['action'];

    if ($action === 'verified') {
        $conn->query("UPDATE pembayaran SET status='verified' WHERE id_pembayaran=$id_pembayaran");
        // Update pesanan status
        $pb = $conn->query("SELECT id_pesanan FROM pembayaran WHERE id_pembayaran=$id_pembayaran")->fetch_assoc();
        if ($pb) {
            $conn->query("UPDATE pesanan SET status='dikonfirmasi' WHERE id_pesanan={$pb['id_pesanan']}");
        }
        setFlash('success', '✅ Pembayaran berhasil diverifikasi!');
    } elseif ($action === 'ditolak') {
        $conn->query("UPDATE pembayaran SET status='ditolak' WHERE id_pembayaran=$id_pembayaran");
        setFlash('danger', '❌ Pembayaran ditolak.');
    }
    redirect(BASE_URL . '/admin/pembayaran.php');
}

// Filter
$filter = sanitize($_GET['status'] ?? '');
$where  = $filter ? "WHERE pb.status='$filter'" : '';

$payments = $conn->query("
    SELECT pb.*, ps.total_harga, ps.id_pesanan, ps.tanggal_pesanan, u.nama, u.email
    FROM pembayaran pb
    JOIN pesanan ps ON pb.id_pesanan=ps.id_pesanan
    JOIN user u ON ps.id_user=u.id_user
    $where
    ORDER BY pb.tanggal_bayar DESC
");

// Detail
$detail = null;
if (isset($_GET['id'])) {
    $pid = (int)$_GET['id'];
    $detail = $conn->query("
        SELECT pb.*, ps.total_harga, ps.id_pesanan, ps.tanggal_pesanan, u.nama, u.email
        FROM pembayaran pb
        JOIN pesanan ps ON pb.id_pesanan=ps.id_pesanan
        JOIN user u ON ps.id_user=u.id_user
        WHERE pb.id_pembayaran=$pid
    ")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Pembayaran — CateringKu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout-wrapper">
    <?php include '../includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <div class="topbar">
            <div class="page-title">
                <h1>Verifikasi Pembayaran</h1>
                <p>Periksa dan verifikasi bukti pembayaran pelanggan</p>
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
                        <a href="pembayaran.php" class="btn btn-sm <?= !$filter ? 'btn-primary' : 'btn-secondary' ?>">Semua</a>
                        <a href="?status=pending" class="btn btn-sm <?= $filter==='pending' ? 'btn-primary' : 'btn-secondary' ?>">⏳ Pending</a>
                        <a href="?status=verified" class="btn btn-sm <?= $filter==='verified' ? 'btn-primary' : 'btn-secondary' ?>">✅ Terverifikasi</a>
                        <a href="?status=ditolak" class="btn btn-sm <?= $filter==='ditolak' ? 'btn-primary' : 'btn-secondary' ?>">❌ Ditolak</a>
                    </div>
                </div>
            </div>

            <div class="grid-2" style="gap:24px;align-items:start;">
                <!-- List -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">💳 Daftar Pembayaran</span>
                    </div>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Pelanggan</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $rows = [];
                                while ($r = $payments->fetch_assoc()) $rows[] = $r;
                                if (empty($rows)):
                                ?>
                                <tr>
                                    <td colspan="4">
                                        <div class="empty-state" style="padding:30px;">
                                            <div class="empty-icon">💳</div>
                                            <p>Tidak ada data pembayaran</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: foreach ($rows as $r): ?>
                                <tr style="<?= isset($_GET['id']) && $_GET['id']==$r['id_pembayaran'] ? 'background:var(--accent-soft);' : '' ?>">
                                    <td>
                                        <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($r['nama']) ?></div>
                                        <div style="font-size:11px;color:var(--text-muted);">Pesanan #<?= $r['id_pesanan'] ?></div>
                                    </td>
                                    <td style="font-weight:700;color:var(--primary);font-size:13px;"><?= formatRupiah($r['total_harga']) ?></td>
                                    <td><span class="badge badge-<?= $r['status'] ?>"><?= $r['status'] ?></span></td>
                                    <td>
                                        <a href="?id=<?= $r['id_pembayaran'] ?><?= $filter ? '&status='.$filter : '' ?>"
                                           class="btn btn-sm btn-secondary">👁 Lihat</a>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Detail -->
                <?php if ($detail): ?>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">🔍 Detail Pembayaran</span>
                        <span class="badge badge-<?= $detail['status'] ?>"><?= $detail['status'] ?></span>
                    </div>
                    <div class="card-body">
                        <div class="order-info-grid" style="grid-template-columns:1fr 1fr;">
                            <div class="order-info-item">
                                <label>Pelanggan</label>
                                <div class="value"><?= htmlspecialchars($detail['nama']) ?></div>
                            </div>
                            <div class="order-info-item">
                                <label>No. Pesanan</label>
                                <div class="value">#<?= $detail['id_pesanan'] ?></div>
                            </div>
                            <div class="order-info-item">
                                <label>Total Bayar</label>
                                <div class="value" style="color:var(--primary);"><?= formatRupiah($detail['total_harga']) ?></div>
                            </div>
                            <div class="order-info-item">
                                <label>Tanggal Bayar</label>
                                <div class="value"><?= date('d/m/Y H:i', strtotime($detail['tanggal_bayar'])) ?></div>
                            </div>
                        </div>

                        <?php if ($detail['bukti_foto']): ?>
                        <div style="margin:16px 0;">
                            <p style="font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;">Bukti Pembayaran</p>
                            <img src="<?= UPLOAD_URL . htmlspecialchars($detail['bukti_foto']) ?>"
                                 class="proof-img" alt="Bukti"
                                 style="max-width:100%;"
                                 onclick="window.open(this.src,'_blank')">
                            <p style="font-size:11px;color:var(--text-muted);margin-top:6px;">Klik untuk perbesar</p>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning" style="margin:16px 0;">⚠️ Belum ada bukti pembayaran diupload</div>
                        <?php endif; ?>

                        <?php if ($detail['status'] === 'pending'): ?>
                        <div style="display:flex;gap:12px;margin-top:16px;">
                            <form method="POST" style="flex:1;" onsubmit="return confirm('Verifikasi pembayaran ini?')">
                                <input type="hidden" name="action" value="verified">
                                <input type="hidden" name="id_pembayaran" value="<?= $detail['id_pembayaran'] ?>">
                                <button type="submit" class="btn btn-success w-full">✅ Verifikasi</button>
                            </form>
                            <form method="POST" style="flex:1;" onsubmit="return confirm('Tolak pembayaran ini?')">
                                <input type="hidden" name="action" value="ditolak">
                                <input type="hidden" name="id_pembayaran" value="<?= $detail['id_pembayaran'] ?>">
                                <button type="submit" class="btn btn-danger w-full">❌ Tolak</button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state">
                            <div class="empty-icon">👆</div>
                            <h3>Pilih Pembayaran</h3>
                            <p>Klik tombol "Lihat" untuk melihat detail pembayaran</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>