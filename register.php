<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    if (isAdmin()) redirect(BASE_URL . '/admin/dashboard.php');
    else redirect(BASE_URL . '/pelanggan/dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = sanitize($_POST['nama'] ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirm  = $_POST['konfirm_password'] ?? '';

    if (empty($nama) || empty($email) || empty($password)) {
        $error = 'Semua field wajib diisi.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        // Check email exists
        $chk = $conn->prepare("SELECT id_user FROM user WHERE email = ?");
        $chk->bind_param('s', $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = 'Email sudah terdaftar.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO user (nama, email, password, role) VALUES (?,?,?,'pelanggan')");
            $ins->bind_param('sss', $nama, $email, $hash);
            if ($ins->execute()) {
                setFlash('success', 'Akun berhasil dibuat! Silakan login.');
                redirect(BASE_URL . '/login.php');
            } else {
                $error = 'Registrasi gagal. Coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar — CateringKu</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="logo-icon">🍱</div>
            <h1>Buat Akun</h1>
            <p>Daftar sebagai pelanggan</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger">⚠️ <?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">👤 Nama Lengkap</label>
                <input type="text" name="nama" class="form-control"
                       placeholder="Nama lengkap Anda"
                       value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">📧 Email</label>
                <input type="email" name="email" class="form-control"
                       placeholder="email@contoh.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">🔒 Password</label>
                <input type="password" name="password" class="form-control"
                       placeholder="Min. 6 karakter" required>
            </div>
            <div class="form-group">
                <label class="form-label">🔒 Konfirmasi Password</label>
                <input type="password" name="konfirm_password" class="form-control"
                       placeholder="Ulangi password" required>
            </div>
            <button type="submit" class="auth-btn">Buat Akun</button>
        </form>

        <div class="auth-link">
            Sudah punya akun? <a href="login.php">Login di sini</a>
        </div>
    </div>
</div>
</body>
</html>