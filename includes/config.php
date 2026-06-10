<?php
// ================================================
// KONFIGURASI DATABASE
// ================================================
define('DB_HOST', 'sql110.infinityfree.com');
define('DB_USER', 'if0_42146810');
define('DB_PASS', 'fitriani5678');
define('DB_NAME', 'if0_42146810_db_catering');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

// ================================================
// SESSION & HELPER FUNCTIONS
// ================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isPelanggan() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'pelanggan';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /pelanggan/dashboard.php');
        exit;
    }
}

function requirePelanggan() {
    requireLogin();
    if (!isPelanggan()) {
        header('Location: /admin/dashboard.php');
        exit;
    }
}

function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Base URL helper
define('BASE_URL', '');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', BASE_URL . '/assets/uploads/');
?>