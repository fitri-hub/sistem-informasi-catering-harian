<?php
// ================================================
// KONFIGURASI DATABASE
// ================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_catering');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Koneksi database gagal: ' . $conn->connect_error]));
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
        header('Location: /catering/login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /catering/pelanggan/dashboard.php');
        exit;
    }
}

function requirePelanggan() {
    requireLogin();
    if (!isPelanggan()) {
        header('Location: /catering/admin/dashboard.php');
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
define('BASE_URL', '/catering');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', BASE_URL . '/assets/uploads/');
?>