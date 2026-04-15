#  CateringKu - Sistem Informasi Management Catering

CateringKu adalah aplikasi berbasis web yang dirancang untuk mendigitalisasi proses operasional bisnis catering. Mulai dari manajemen menu, pemesanan pelanggan, hingga verifikasi pembayaran, semuanya terintegrasi dalam satu sistem yang efisien dan elegan.

---

##  Fitur Utama

###  Panel Admin
- **Dashboard Statistik:** Monitoring pendapatan (revenue), jumlah pesanan, dan status pengguna secara real-time.
- **Manajemen Menu (CRUD):** Kelola daftar masakan lengkap dengan foto, harga, dan status ketersediaan.
- **Verifikasi Pembayaran:** Fitur validasi bukti transfer pelanggan untuk memastikan keamanan transaksi.
- **Manajemen Pesanan:** Update status pesanan (Pending, Diproses, Selesai) secara sistematis.

###  Panel Pelanggan
- **Katalog Menu:** Tampilan menu yang menarik dengan desain *Warm & Elegant*.
- **Shopping Cart:** Pengalaman pemesanan yang mudah dengan fitur keranjang belanja.
- **Konfirmasi Pembayaran:** Unggah bukti transfer langsung untuk mempercepat proses pesanan.
- **Riwayat Pesanan:** Pantau status makanan yang sedang dipesan kapan saja.

---

##  Teknologi yang Digunakan
- **Bahasa:** PHP (Native)
- **Database:** MySQL / MariaDB
- **Frontend:** HTML5, CSS3 (Custom Styles), JavaScript
- **Keamanan:** Password Hashing (BCRYPT) & Session Security

---

##  Struktur Folder
```text
catering/
├── admin/          # Fitur khusus Administrator
├── assets/         # File statis (CSS, Images, Uploads)
├── includes/       # Konfigurasi database & Sidebar
├── pelanggan/      # Fitur khusus Pelanggan
├── login.php       # Autentikasi User
└── register.php    # Pendaftaran Akun
