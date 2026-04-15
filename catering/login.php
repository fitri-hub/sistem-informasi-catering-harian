<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) redirect(BASE_URL . '/admin/dashboard.php');
    else redirect(BASE_URL . '/pelanggan/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM user WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];

            setFlash('success', 'Selamat datang, ' . $user['nama'] . '!');

            if ($user['role'] === 'admin') {
                redirect(BASE_URL . '/admin/dashboard.php');
            } else {
                redirect(BASE_URL . '/pelanggan/dashboard.php');
            }
        } else {
            $error = 'Email atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Sistem Informasi Catering</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .demo-info {
            background: rgba(245,200,66,0.12);
            border: 1px solid rgba(245,200,66,0.3);
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 24px;
            color: rgba(255,255,255,0.75);
            font-size: 12px;
            line-height: 1.7;
        }
        .demo-info strong { color: var(--accent); }
    </style>
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="logo-icon">🍱</div>
            <h1>CateringKu</h1>
            <p>Sistem Informasi Catering</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger">⚠️ <?= $error ?></div>
        <?php endif; ?>

        <div class="demo-info">
            <strong>Demo Login:</strong><br>
            Admin: admin@catering.com / <strong>admin123</strong><br>
            Pelanggan: budi@email.com / <strong>user123</strong>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">📧 Alamat Email</label>
                <input type="email" name="email" class="form-control"
                       placeholder="masukkan@email.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">🔒 Password</label>
                <input type="password" name="password" class="form-control"
                       placeholder="••••••••" required>
            </div>

            <button type="submit" class="auth-btn">Masuk ke Sistem</button>
        </form>

        <div class="auth-link">
            Belum punya akun? <a href="register.php">Daftar sekarang</a>
        </div>
    </div>
</div>
</body>
</html>