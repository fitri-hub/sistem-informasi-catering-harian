<?php
require_once '../includes/config.php';
requirePelanggan();

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

$uid   = $_SESSION['user_id'];
$flash = getFlash();

// Handle cart updates and order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_cart') {
        $id_menu = (int)$_POST['id_menu'];
        $qty     = (int)$_POST['qty'];
        if ($qty <= 0) {
            unset($_SESSION['cart'][$id_menu]);
        } else {
            if (isset($_SESSION['cart'][$id_menu])) {
                $_SESSION['cart'][$id_menu]['qty'] = $qty;
            }
        }
        redirect(BASE_URL . '/pelanggan/pesan.php');
    }

    if ($action === 'hapus_item') {
        $id_menu = (int)$_POST['id_menu'];
        unset($_SESSION['cart'][$id_menu]);
        redirect(BASE_URL . '/pelanggan/pesan.php');
    }

    if ($action === 'kosongkan') {
        $_SESSION['cart'] = [];
        redirect(BASE_URL . '/pelanggan/pesan.php');
    }

    if ($action === 'konfirmasi_pesanan') {
        if (empty($_SESSION['cart'])) {
            setFlash('danger', 'Keranjang kosong!');
            redirect(BASE_URL . '/pelanggan/pesan.php');
        }

        $tanggal = date('Y-m-d');
        $total   = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['harga'] * $item['qty'];
        }

        $conn->begin_transaction();
        try {
            $ins = $conn->prepare("INSERT INTO pesanan (id_user, tanggal_pesanan, total_harga, status) VALUES (?,?,?,'pending')");
            $ins->bind_param('isd', $uid, $tanggal, $total);
            $ins->execute();
            $id_pesanan = $conn->insert_id;

            foreach ($_SESSION['cart'] as $item) {
                $subtotal = $item['harga'] * $item['qty'];
                $d = $conn->prepare("INSERT INTO detail_pesanan (id_pesanan, id_menu, jumlah, subtotal) VALUES (?,?,?,?)");
                $d->bind_param('iiid', $id_pesanan, $item['id_menu'], $item['qty'], $subtotal);
                $d->execute();
            }

            $conn->commit();
            $_SESSION['cart'] = [];
            setFlash('success', '✅ Pesanan berhasil dibuat! Silakan lakukan pembayaran.');
            redirect(BASE_URL . '/pelanggan/pembayaran.php?id_pesanan=' . $id_pesanan);
        } catch (Exception $e) {
            $conn->rollback();
            setFlash('danger', 'Pesanan gagal dibuat. Coba lagi.');
            redirect(BASE_URL . '/pelanggan/pesan.php');
        }
    }
}

// Calculate totals
$cart  = $_SESSION['cart'];
$total = 0;
foreach ($cart as $item) {
    $total += $item['harga'] * $item['qty'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Pesanan — CateringKu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout-wrapper">
    <?php include '../includes/sidebar_pelanggan.php'; ?>

    <main class="main-content">
        <div class="topbar">
            <div class="page-title">
                <h1>Keranjang Pesanan</h1>
                <p>Konfirmasi item yang ingin dipesan</p>
            </div>
            <a href="menu.php" class="btn btn-secondary">← Tambah Menu</a>
        </div>

        <div class="content-area">
            <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
            <?php endif; ?>

            <?php if (empty($cart)): ?>
            <div class="card">
                <div class="card-body">
                    <div class="empty-state" style="padding:60px;">
                        <div class="empty-icon">🛒</div>
                        <h3>Keranjang Kosong</h3>
                        <p>Tambahkan menu dari daftar menu catering</p>
                        <a href="menu.php" class="btn btn-primary" style="margin-top:16px;">🍽️ Lihat Menu</a>
                    </div>
                </div>
            </div>
            <?php else: ?>

            <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;">
                <!-- Cart Items -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">🛒 Item Pesanan</span>
                        <form method="POST" onsubmit="return confirm('Kosongkan keranjang?')">
                            <input type="hidden" name="action" value="kosongkan">
                            <button type="submit" class="btn btn-sm btn-danger">🗑️ Kosongkan</button>
                        </form>
                    </div>
                    <div class="card-body">
                        <?php foreach ($cart as $item): ?>
                        <div class="cart-item">
                            <div style="width:48px;height:48px;background:linear-gradient(135deg,#FFF0E8,#FFE4CC);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;">🍽️</div>
                            <div class="cart-item-info">
                                <div class="cart-item-name"><?= htmlspecialchars($item['nama_menu']) ?></div>
                                <div class="cart-item-price"><?= formatRupiah($item['harga']) ?> / porsi</div>
                            </div>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <form method="POST" style="display:flex;align-items:center;gap:8px;">
                                    <input type="hidden" name="action" value="update_cart">
                                    <input type="hidden" name="id_menu" value="<?= $item['id_menu'] ?>">
                                    <div class="qty-control">
                                        <button type="submit" name="qty" value="<?= $item['qty']-1 ?>" class="qty-btn">−</button>
                                        <input type="number" name="qty" value="<?= $item['qty'] ?>" min="0"
                                               class="qty-input" onchange="this.form.submit()">
                                        <button type="submit" name="qty" value="<?= $item['qty']+1 ?>" class="qty-btn">+</button>
                                    </div>
                                </form>
                                <div class="cart-item-subtotal"><?= formatRupiah($item['harga'] * $item['qty']) ?></div>
                                <form method="POST">
                                    <input type="hidden" name="action" value="hapus_item">
                                    <input type="hidden" name="id_menu" value="<?= $item['id_menu'] ?>">
                                    <button type="submit" style="background:none;border:none;cursor:pointer;font-size:18px;color:var(--danger);" title="Hapus">✕</button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="card" style="position:sticky;top:80px;">
                    <div class="card-header">
                        <span class="card-title">📋 Ringkasan Pesanan</span>
                    </div>
                    <div class="card-body">
                        <?php foreach ($cart as $item): ?>
                        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px;">
                            <span><?= htmlspecialchars($item['nama_menu']) ?> ×<?= $item['qty'] ?></span>
                            <span><?= formatRupiah($item['harga'] * $item['qty']) ?></span>
                        </div>
                        <?php endforeach; ?>
                        <div class="divider"></div>
                        <div class="order-total">
                            <span>Total</span>
                            <span class="amount"><?= formatRupiah($total) ?></span>
                        </div>

                        <div style="margin-top:6px;padding:12px;background:var(--accent-soft);border-radius:var(--radius-sm);">
                            <p style="font-size:12px;color:var(--text-secondary);line-height:1.6;">
                                📌 Setelah konfirmasi, Anda akan diminta untuk mengunggah bukti pembayaran.
                            </p>
                        </div>

                        <form method="POST" onsubmit="return confirm('Konfirmasi pesanan ini?')" style="margin-top:16px;">
                            <input type="hidden" name="action" value="konfirmasi_pesanan">
                            <button type="submit" class="btn btn-primary btn-lg w-full">
                                ✅ Konfirmasi Pesanan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>