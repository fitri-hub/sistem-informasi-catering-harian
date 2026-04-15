<?php
require_once '../includes/config.php';
requirePelanggan();

// Initialize cart
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_cart') {
        $id_menu = (int)$_POST['id_menu'];
        $qty     = max(1, (int)$_POST['qty']);
        $menu    = $conn->query("SELECT * FROM menu WHERE id_menu=$id_menu AND status='tersedia'")->fetch_assoc();
        if ($menu) {
            if (isset($_SESSION['cart'][$id_menu])) {
                $_SESSION['cart'][$id_menu]['qty'] += $qty;
            } else {
                $_SESSION['cart'][$id_menu] = [
                    'id_menu'   => $id_menu,
                    'nama_menu' => $menu['nama_menu'],
                    'harga'     => $menu['harga'],
                    'qty'       => $qty,
                ];
            }
            setFlash('success', '✅ ' . $menu['nama_menu'] . ' ditambahkan ke keranjang!');
        }
    }
    redirect(BASE_URL . '/pelanggan/menu.php');
}

$flash = getFlash();
$menus = $conn->query("SELECT * FROM menu ORDER BY status='tersedia' DESC, nama_menu ASC");
$cart_count = array_sum(array_column($_SESSION['cart'], 'qty'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Catering — CateringKu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout-wrapper">
    <?php include '../includes/sidebar_pelanggan.php'; ?>

    <main class="main-content">
        <div class="topbar">
            <div class="page-title">
                <h1>Menu Catering</h1>
                <p>Pilih menu favorit Anda</p>
            </div>
            <div class="topbar-actions">
                <?php if ($cart_count > 0): ?>
                <a href="pesan.php" class="btn btn-primary">
                    🛒 Keranjang (<?= $cart_count ?>)
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="content-area">
            <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
            <?php endif; ?>

            <div class="menu-grid">
                <?php while ($m = $menus->fetch_assoc()): ?>
                <div class="menu-card">
                    <?php if ($m['foto']): ?>
                    <img src="<?= UPLOAD_URL . htmlspecialchars($m['foto']) ?>"
                         class="menu-card-img" style="display:block;" alt="<?= htmlspecialchars($m['nama_menu']) ?>">
                    <?php else: ?>
                    <div class="menu-card-img">🍽️</div>
                    <?php endif; ?>

                    <div class="menu-card-body">
                        <div class="menu-card-name"><?= htmlspecialchars($m['nama_menu']) ?></div>
                        <div class="menu-card-desc"><?= htmlspecialchars($m['deskripsi'] ?: 'Menu catering lezat pilihan') ?></div>

                        <div class="menu-card-footer">
                            <div class="menu-price"><?= formatRupiah($m['harga']) ?></div>
                            <?php if ($m['status'] === 'tersedia'): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="add_cart">
                                <input type="hidden" name="id_menu" value="<?= $m['id_menu'] ?>">
                                <input type="hidden" name="qty" value="1">
                                <button type="submit" class="btn btn-primary btn-sm">+ Keranjang</button>
                            </form>
                            <?php else: ?>
                            <span class="badge badge-habis">Habis</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>