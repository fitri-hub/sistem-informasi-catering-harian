<?php
// Admin Sidebar
$current = basename($_SERVER['PHP_SELF']);

// Count pending items for badges
$pending_pesanan = $conn->query("SELECT COUNT(*) as c FROM pesanan WHERE status='pending'")->fetch_assoc()['c'];
$pending_bayar   = $conn->query("SELECT COUNT(*) as c FROM pembayaran WHERE status='pending'")->fetch_assoc()['c'];
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">🍱</div>
        <h2>CateringKu</h2>
        <p>Panel Admin</p>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['nama'], 0, 1)) ?></div>
        <div class="user-info">
            <div class="name"><?= htmlspecialchars($_SESSION['nama']) ?></div>
            <div class="role">Administrator</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Menu Utama</div>

        <a href="<?= BASE_URL ?>/admin/dashboard.php"
           class="nav-link <?= $current === 'dashboard.php' ? 'active' : '' ?>">
            <span class="nav-icon">📊</span> Dashboard
        </a>

        <div class="nav-section-title">Kelola Data</div>

        <a href="<?= BASE_URL ?>/admin/menu.php"
           class="nav-link <?= $current === 'menu.php' ? 'active' : '' ?>">
            <span class="nav-icon">🍽️</span> Kelola Menu
        </a>

        <a href="<?= BASE_URL ?>/admin/pesanan.php"
           class="nav-link <?= $current === 'pesanan.php' ? 'active' : '' ?>">
            <span class="nav-icon">📋</span> Kelola Pesanan
            <?php if ($pending_pesanan > 0): ?>
            <span class="nav-badge"><?= $pending_pesanan ?></span>
            <?php endif; ?>
        </a>

        <a href="<?= BASE_URL ?>/admin/pembayaran.php"
           class="nav-link <?= $current === 'pembayaran.php' ? 'active' : '' ?>">
            <span class="nav-icon">💰</span> Verifikasi Pembayaran
            <?php if ($pending_bayar > 0): ?>
            <span class="nav-badge"><?= $pending_bayar ?></span>
            <?php endif; ?>
        </a>

        <div class="nav-section-title">Lainnya</div>

        <a href="<?= BASE_URL ?>/admin/pengguna.php"
           class="nav-link <?= $current === 'pengguna.php' ? 'active' : '' ?>">
            <span class="nav-icon">👥</span> Data Pengguna
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>/logout.php" class="logout-btn">
            <span>🚪</span> Keluar
        </a>
    </div>
</aside>