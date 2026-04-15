<?php
$current = basename($_SERVER['PHP_SELF']);
$cart_count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0;
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">🍱</div>
        <h2>CateringKu</h2>
        <p>Pesan Makanan</p>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['nama'], 0, 1)) ?></div>
        <div class="user-info">
            <div class="name"><?= htmlspecialchars($_SESSION['nama']) ?></div>
            <div class="role">Pelanggan</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Menu</div>

        <a href="<?= BASE_URL ?>/pelanggan/dashboard.php"
           class="nav-link <?= $current === 'dashboard.php' ? 'active' : '' ?>">
            <span class="nav-icon">🏠</span> Beranda
        </a>

        <a href="<?= BASE_URL ?>/pelanggan/menu.php"
           class="nav-link <?= $current === 'menu.php' ? 'active' : '' ?>">
            <span class="nav-icon">🍽️</span> Lihat Menu
        </a>

        <a href="<?= BASE_URL ?>/pelanggan/pesan.php"
           class="nav-link <?= $current === 'pesan.php' ? 'active' : '' ?>">
            <span class="nav-icon">🛒</span> Keranjang
            <?php if ($cart_count > 0): ?>
            <span class="nav-badge"><?= $cart_count ?></span>
            <?php endif; ?>
        </a>

        <div class="nav-section-title">Akun Saya</div>

        <a href="<?= BASE_URL ?>/pelanggan/riwayat.php"
           class="nav-link <?= $current === 'riwayat.php' ? 'active' : '' ?>">
            <span class="nav-icon">📋</span> Riwayat Pesanan
        </a>

        <a href="<?= BASE_URL ?>/pelanggan/pembayaran.php"
           class="nav-link <?= $current === 'pembayaran.php' ? 'active' : '' ?>">
            <span class="nav-icon">💳</span> Pembayaran
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>/logout.php" class="logout-btn">
            <span>🚪</span> Keluar
        </a>
    </div>
</aside>