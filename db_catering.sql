-- ================================================
-- DATABASE: Sistem Informasi Catering
-- ================================================

CREATE DATABASE IF NOT EXISTS db_catering CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_catering;

-- Tabel USER
CREATE TABLE IF NOT EXISTS user (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('pelanggan', 'admin') DEFAULT 'pelanggan',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel MENU
CREATE TABLE IF NOT EXISTS menu (
    id_menu INT AUTO_INCREMENT PRIMARY KEY,
    nama_menu VARCHAR(150) NOT NULL,
    harga DECIMAL(10,2) NOT NULL,
    foto VARCHAR(255),
    deskripsi TEXT,
    status ENUM('tersedia', 'habis') DEFAULT 'tersedia',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel PESANAN
CREATE TABLE IF NOT EXISTS pesanan (
    id_pesanan INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    tanggal_pesanan DATE NOT NULL,
    total_harga DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('pending', 'dikonfirmasi', 'diproses', 'selesai', 'dibatalkan') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES user(id_user) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabel DETAIL PESANAN
CREATE TABLE IF NOT EXISTS detail_pesanan (
    id_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_pesanan INT NOT NULL,
    id_menu INT NOT NULL,
    jumlah INT NOT NULL DEFAULT 1,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (id_pesanan) REFERENCES pesanan(id_pesanan) ON DELETE CASCADE,
    FOREIGN KEY (id_menu) REFERENCES menu(id_menu) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabel PEMBAYARAN
CREATE TABLE IF NOT EXISTS pembayaran (
    id_pembayaran INT AUTO_INCREMENT PRIMARY KEY,
    id_pesanan INT NOT NULL,
    tanggal_bayar TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    bukti_foto VARCHAR(255),
    status ENUM('pending', 'verified', 'ditolak') DEFAULT 'pending',
    FOREIGN KEY (id_pesanan) REFERENCES pesanan(id_pesanan) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ================================================
-- DATA AWAL (SEED)
-- ================================================

-- Admin default (password: admin123)
INSERT INTO user (nama, email, password, role) VALUES
('Administrator', 'admin@catering.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Pelanggan demo (password: user123)
INSERT INTO user (nama, email, password, role) VALUES
('Budi Santoso', 'budi@email.com', '$2y$10$TKh8H1.PyfcAz428xu6Xem4YTMsAnAliQEYYOz0GFGO1aqjPaqWi', 'pelanggan'),
('Siti Rahayu', 'siti@email.com', '$2y$10$TKh8H1.PyfcAz428xu6Xem4YTMsAnAliQEYYOz0GFGO1aqjPaqWi', 'pelanggan');

-- Menu Catering
INSERT INTO menu (nama_menu, harga, foto, deskripsi, status) VALUES
('Nasi Box Ayam Goreng', 25000, NULL, 'Nasi putih dengan ayam goreng crispy, lalapan, dan sambal', 'tersedia'),
('Nasi Box Rendang', 35000, NULL, 'Nasi putih dengan rendang sapi empuk bumbu khas Minang', 'tersedia'),
('Nasi Box Ikan Bakar', 30000, NULL, 'Nasi putih dengan ikan bakar bumbu kecap dan lalapan segar', 'tersedia'),
('Paket Prasmanan Bronze', 45000, NULL, 'Nasi, 2 lauk, sayur, dan buah per porsi (min. 50 porsi)', 'tersedia'),
('Paket Prasmanan Silver', 65000, NULL, 'Nasi, 3 lauk pilihan, 2 sayur, dessert per porsi (min. 50 porsi)', 'tersedia'),
('Paket Prasmanan Gold', 85000, NULL, 'Nasi, 4 lauk premium, 2 sayur, dessert, minuman per porsi', 'tersedia'),
('Snack Box Kue', 15000, NULL, 'Kotak berisi 5 jenis kue tradisional pilihan', 'tersedia'),
('Nasi Goreng Spesial', 20000, NULL, 'Nasi goreng dengan telur, ayam suwir, dan kerupuk', 'tersedia'),
('Ayam Bakar Madu', 32000, NULL, 'Ayam bakar dengan bumbu madu spesial dan nasi', 'tersedia'),
('Gado-Gado Spesial', 18000, NULL, 'Gado-gado lengkap dengan bumbu kacang segar', 'tersedia');