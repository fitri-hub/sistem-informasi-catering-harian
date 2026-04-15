<?php
require_once '../includes/config.php';
requireAdmin();

$flash = getFlash();

// Stats
$total_menu     = $conn->query("SELECT COUNT(*) as c FROM menu")->fetch_assoc()['c'];
$total_pesanan  = $conn->query("SELECT COUNT(*) as c FROM pesanan")->fetch_assoc()['c'];
$pending_pesanan = $conn->query("SELECT COUNT(*) as c FROM pesanan WHERE status='pending'")->fetch_assoc()['c'];
$pending_bayar  = $conn->query("SELECT COUNT(*) as c FROM pembayaran WHERE status='pending'")->fetch_assoc()['c'];
$total_revenue  = $conn->query("SELECT COALESCE(SUM(p.total_harga),0) as r FROM pesanan p JOIN pembayaran pb ON p.id_pesanan=pb.id_pesanan WHERE pb.status='verified'")->fetch_assoc()['r'];
$total_user     = $conn->query("SELECT COUNT(*) as c FROM user WHERE role='pelanggan'")->fetch_assoc()['c'];

// Recent orders
$recent = $conn->query("
    SELECT ps.*, u.nama, u.email
    FROM pesanan ps
    JOIN user u ON ps.id_user = u.id_user
    ORDER BY ps.created_at DESC LIMIT 8
");

// Recent payments
$payments = $conn->query("
    SELECT pb.*, ps.total_harga, ps.id_pesanan, u.nama
    FROM pembayaran pb
    JOIN pesanan ps ON pb.id_pesanan = ps.id_pesanan
    JOIN user u ON ps.id_user = u.id_user
    WHERE pb.status='pending'
    ORDER BY pb.tanggal_bayar DESC LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin — CateringKu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout-wrapper">
    <?php include '../includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <div class="topbar">
            <div class="page-title">
                <h1>Dashboard</h1>
                <p>Selamat datang kembali, <?= htmlspecialchars($_SESSION['nama']) ?>!</p>
            </div>
            <div class="topbar-actions">
                <span style="font-size:13px;color:var(--text-muted);">
                    📅 <?= date('d M Y') ?>
                </span>
            </div>
        </div>

        <div class="content-area">
            <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
            <?php endif; ?>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon orange">🍽️</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $total_menu ?></div>
                        <div class="stat-label">Total Menu</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue">📋</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $total_pesanan ?></div>
                        <div class="stat-label">Total Pesanan</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon yellow">⏳</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $pending_pesanan ?></div>
                        <div class="stat-label">Pesanan Pending</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">💰</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $pending_bayar ?></div>
                        <div class="stat-label">Verifikasi Pending</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">💵</div>
                    <div class="stat-info">
                        <div class="stat-value" style="font-size:18px;"><?= formatRupiah($total_revenue) ?></div>
                        <div class="stat-label">Pendapatan</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue">👥</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $total_user ?></div>
                        <div class="stat-label">Pelanggan</div>
                    </div>
                </div>
            </div>

            <div class="grid-2" style="gap:24px;">
                <!-- Recent Orders -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">📋 Pesanan Terbaru</span>
                        <a href="pesanan.php" class="btn btn-sm btn-secondary">Lihat Semua</a>
                    </div>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Pelanggan</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $recent->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($row['nama']) ?></div>
                                        <div style="font-size:11px;color:var(--text-muted);">#<?= $row['id_pesanan'] ?></div>
                                    </td>
                                    <td style="font-weight:700;color:var(--primary);font-size:13px;"><?= formatRupiah($row['total_harga']) ?></td>
                                    <td><span class="badge badge-<?= $row['status'] ?>"><?= $row['status'] ?></span></td>
                                    <td style="font-size:12px;color:var(--text-muted);"><?= date('d/m/Y', strtotime($row['tanggal_pesanan'])) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pending Payments -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">💰 Pembayaran Perlu Diverifikasi</span>
                        <a href="pembayaran.php" class="btn btn-sm btn-secondary">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <?php
                        $rows = [];
                        while ($r = $payments->fetch_assoc()) $rows[] = $r;
                        if (empty($rows)):
                        ?>
                        <div class="empty-state" style="padding:30px;">
                            <div class="empty-icon">✅</div>
                            <p>Tidak ada pembayaran pending</p>
                        </div>
                        <?php else: foreach ($rows as $r): ?>
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border-light);">
                            <div>
                                <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($r['nama']) ?></div>
                                <div style="font-size:11px;color:var(--text-muted);">Pesanan #<?= $r['id_pesanan'] ?> • <?= formatRupiah($r['total_harga']) ?></div>
                            </div>
                            <a href="pembayaran.php?id=<?= $r['id_pembayaran'] ?>"
                               class="btn btn-sm btn-primary">Verifikasi</a>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>