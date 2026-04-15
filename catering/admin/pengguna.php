<?php
require_once '../includes/config.php';
requireAdmin();

$flash = getFlash();

$users = $conn->query("
    SELECT u.*,
           COUNT(DISTINCT p.id_pesanan) as total_pesanan
    FROM user u
    LEFT JOIN pesanan p ON u.id_user=p.id_user
    WHERE u.role='pelanggan'
    GROUP BY u.id_user
    ORDER BY u.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pengguna — CateringKu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout-wrapper">
    <?php include '../includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <div class="topbar">
            <div class="page-title">
                <h1>Data Pengguna</h1>
                <p>Daftar seluruh pelanggan terdaftar</p>
            </div>
        </div>

        <div class="content-area">
            <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Total Pesanan</th>
                                <th>Bergabung</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no=1; while ($u = $users->fetch_assoc()): ?>
                            <tr>
                                <td style="color:var(--text-muted);font-size:12px;"><?= $no++ ?></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div style="width:34px;height:34px;background:linear-gradient(135deg,var(--primary),var(--primary-light));border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;flex-shrink:0;">
                                            <?= strtoupper(substr($u['nama'],0,1)) ?>
                                        </div>
                                        <span style="font-weight:600;"><?= htmlspecialchars($u['nama']) ?></span>
                                    </div>
                                </td>
                                <td style="color:var(--text-muted);"><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <span style="background:var(--info-bg);color:var(--info);padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;">
                                        <?= $u['total_pesanan'] ?> pesanan
                                    </span>
                                </td>
                                <td style="font-size:12px;color:var(--text-muted);"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>