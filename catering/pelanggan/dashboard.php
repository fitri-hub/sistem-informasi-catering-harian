<?php
require_once '../includes/config.php';
requirePelanggan();

$flash = getFlash();
$uid = $_SESSION['user_id'];

$total_pesanan  = $conn->query("SELECT COUNT(*) as c FROM pesanan WHERE id_user=$uid")->fetch_assoc()['c'];
$pesanan_aktif  = $conn->query("SELECT COUNT(*) as c FROM pesanan WHERE id_user=$uid AND status NOT IN ('selesai','dibatalkan')")->fetch_assoc()['c'];
$total_spent    = $conn->query("SELECT COALESCE(SUM(p.total_harga),0) as t FROM pesanan p JOIN pembayaran pb ON p.id_pesanan=pb.id_pesanan WHERE p.id_user=$uid AND pb.status='verified'")->fetch_assoc()['t'];

$recent_pesanan = $conn->query("
    SELECT ps.*, pb.status as status_bayar
    FROM pesanan ps
    LEFT JOIN pembayaran pb ON ps.id_pesanan=pb.id_pesanan
    WHERE ps.id_user=$uid
    ORDER BY ps.created_at DESC LIMIT 5
");

$menu_sample = $conn->query("SELECT * FROM menu WHERE status='tersedia' ORDER BY RAND() LIMIT 4");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda — CateringKu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout-wrapper">
    <?php include '../includes/sidebar_pelanggan.php'; ?>

    <main class="main-content">
        <div class="topbar">
            <div class="page-title">
                <h1>Halo, <?= htmlspecialchars(explode(' ', $_SESSION['nama'])[0]) ?>! 👋</h1>
                <p>Mau pesan catering hari ini?</p>
            </div>
            <a href="menu.php" class="btn btn-primary">🛒 Pesan Sekarang</a>
        </div>

        <div class="content-area">
            <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);">
                <div class="stat-card">
                    <div class="stat-icon blue">📋</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $total_pesanan ?></div>
                        <div class="stat-label">Total Pesanan</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">⏳</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $pesanan_aktif ?></div>
                        <div class="stat-label">Pesanan Aktif</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">💵</div>
                    <div class="stat-info">
                        <div class="stat-value" style="font-size:16px;"><?= formatRupiah($total_spent) ?></div>
                        <div class="stat-label">Total Belanja</div>
                    </div>
                </div>
            </div>

            <!-- Quick action banner -->
            <div style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));border-radius:var(--radius-lg);padding:28px 32px;margin-bottom:28px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
                <div>
                    <h2 style="color:#fff;font-size:20px;margin-bottom:6px;">Lapar? Yuk Pesan Sekarang!</h2>
                    <p style="color:rgba(255,255,255,0.75);font-size:14px;">Tersedia <?= $conn->query("SELECT COUNT(*) as c FROM menu WHERE status='tersedia'")->fetch_assoc()['c'] ?> menu catering lezat untuk Anda</p>
                </div>
                <a href="menu.php" class="btn btn-lg" style="background:#fff;color:var(--primary);font-weight:700;">
                    🍽️ Lihat Menu
                </a>
            </div>

            <div class="grid-2" style="gap:24px;">
                <!-- Recent orders -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">📋 Pesanan Terakhir</span>
                        <a href="riwayat.php" class="btn btn-sm btn-secondary">Lihat Semua</a>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php
                        $rows = [];
                        while ($r = $recent_pesanan->fetch_assoc()) $rows[] = $r;
                        if (empty($rows)):
                        ?>
                        <div class="empty-state"><div class="empty-icon">🍽️</div><p>Belum ada pesanan</p></div>
                        <?php else: foreach ($rows as $r): ?>
                        <div style="padding:16px 24px;border-bottom:1px solid var(--border-light);display:flex;justify-content:space-between;align-items:center;gap:12px;">
                            <div>
                                <div style="font-weight:600;font-size:14px;">Pesanan #<?= $r['id_pesanan'] ?></div>
                                <div style="font-size:12px;color:var(--text-muted);"><?= date('d M Y', strtotime($r['tanggal_pesanan'])) ?></div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-weight:700;color:var(--primary);"><?= formatRupiah($r['total_harga']) ?></div>
                                <span class="badge badge-<?= $r['status'] ?>"><?= $r['status'] ?></span>
                            </div>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>

                <!-- Menu highlights -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">✨ Menu Pilihan</span>
                        <a href="menu.php" class="btn btn-sm btn-secondary">Semua Menu</a>
                    </div>
                    <div class="card-body">
                        <div style="display:flex;flex-direction:column;gap:12px;">
                            <?php while ($m = $menu_sample->fetch_assoc()): ?>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div style="width:44px;height:44px;background:linear-gradient(135deg,#FFF0E8,#FFE4CC);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">🍽️</div>
                                <div style="flex:1;">
                                    <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($m['nama_menu']) ?></div>
                                    <div style="font-size:12px;color:var(--text-muted);"><?= mb_strimwidth($m['deskripsi'],0,40,'...') ?></div>
                                </div>
                                <div style="font-weight:700;color:var(--primary);font-size:13px;white-space:nowrap;"><?= formatRupiah($m['harga']) ?></div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <div style="margin-top:16px;">
                            <a href="menu.php" class="btn btn-outline w-full">🛒 Mulai Pesan</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>