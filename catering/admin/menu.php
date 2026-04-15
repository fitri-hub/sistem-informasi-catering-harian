<?php
require_once '../includes/config.php';
requireAdmin();

$flash = getFlash();
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah' || $action === 'edit') {
        $nama_menu  = sanitize($_POST['nama_menu'] ?? '');
        $harga      = (float)($_POST['harga'] ?? 0);
        $deskripsi  = sanitize($_POST['deskripsi'] ?? '');
        $status     = $_POST['status'] ?? 'tersedia';
        $foto_path  = null;

        if (!empty($_FILES['foto']['name'])) {
            $ext   = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if (in_array($ext, $allowed)) {
                if (!is_dir(UPLOAD_PATH)) mkdir(UPLOAD_PATH, 0777, true);
                $fname = 'menu_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                move_uploaded_file($_FILES['foto']['tmp_name'], UPLOAD_PATH . $fname);
                $foto_path = $fname;
            }
        }

        if (empty($nama_menu) || $harga <= 0) {
            $error = 'Nama menu dan harga wajib diisi.';
        } else {
            if ($action === 'tambah') {
                $s = $conn->prepare("INSERT INTO menu (nama_menu, harga, foto, deskripsi, status) VALUES (?,?,?,?,?)");
                $s->bind_param('sdsss', $nama_menu, $harga, $foto_path, $deskripsi, $status);
                $s->execute();
                setFlash('success', 'Menu berhasil ditambahkan!');
            } else {
                $id = (int)$_POST['id_menu'];
                if ($foto_path) {
                    $s = $conn->prepare("UPDATE menu SET nama_menu=?,harga=?,foto=?,deskripsi=?,status=? WHERE id_menu=?");
                    $s->bind_param('sdsssi', $nama_menu, $harga, $foto_path, $deskripsi, $status, $id);
                } else {
                    $s = $conn->prepare("UPDATE menu SET nama_menu=?,harga=?,deskripsi=?,status=? WHERE id_menu=?");
                    $s->bind_param('sdssi', $nama_menu, $harga, $deskripsi, $status, $id);
                }
                $s->execute();
                setFlash('success', 'Menu berhasil diperbarui!');
            }
            redirect(BASE_URL . '/admin/menu.php');
        }
    } elseif ($action === 'hapus') {
        $id = (int)$_POST['id_menu'];
        $conn->query("DELETE FROM menu WHERE id_menu=$id");
        setFlash('success', 'Menu berhasil dihapus.');
        redirect(BASE_URL . '/admin/menu.php');
    }
}

// Fetch menus
$search = sanitize($_GET['q'] ?? '');
$where  = $search ? "WHERE nama_menu LIKE '%$search%'" : '';
$menus  = $conn->query("SELECT * FROM menu $where ORDER BY created_at DESC");

// Edit prefetch
$edit_menu = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $edit_menu = $conn->query("SELECT * FROM menu WHERE id_menu=$eid")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Menu — CateringKu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout-wrapper">
    <?php include '../includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <div class="topbar">
            <div class="page-title">
                <h1>Kelola Menu</h1>
                <p>Tambah, edit, dan hapus menu catering</p>
            </div>
            <button class="btn btn-primary" onclick="openModal('modal-tambah')">
                ➕ Tambah Menu
            </button>
        </div>

        <div class="content-area">
            <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-danger">⚠️ <?= $error ?></div>
            <?php endif; ?>

            <!-- Search -->
            <div class="card mb-24">
                <div class="card-body" style="padding:16px 24px;">
                    <form method="GET" style="display:flex;gap:12px;align-items:center;">
                        <input type="text" name="q" class="form-control"
                               placeholder="🔍 Cari nama menu..."
                               value="<?= htmlspecialchars($search) ?>"
                               style="max-width:320px;">
                        <button type="submit" class="btn btn-secondary">Cari</button>
                        <?php if ($search): ?>
                        <a href="menu.php" class="btn btn-secondary">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Table -->
            <div class="card">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Foto</th>
                                <th>Nama Menu</th>
                                <th>Harga</th>
                                <th>Deskripsi</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; while ($m = $menus->fetch_assoc()): ?>
                            <tr>
                                <td style="color:var(--text-muted);font-size:12px;"><?= $no++ ?></td>
                                <td>
                                    <?php if ($m['foto']): ?>
                                    <img src="<?= UPLOAD_URL . htmlspecialchars($m['foto']) ?>"
                                         style="width:52px;height:42px;object-fit:cover;border-radius:8px;border:1px solid var(--border);">
                                    <?php else: ?>
                                    <div style="width:52px;height:42px;background:linear-gradient(135deg,#FFF0E8,#FFE4CC);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:20px;">🍽️</div>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight:600;"><?= htmlspecialchars($m['nama_menu']) ?></td>
                                <td style="font-weight:700;color:var(--primary);"><?= formatRupiah($m['harga']) ?></td>
                                <td style="font-size:12px;color:var(--text-muted);max-width:200px;">
                                    <?= mb_strimwidth(htmlspecialchars($m['deskripsi']), 0, 60, '...') ?>
                                </td>
                                <td><span class="badge badge-<?= $m['status'] ?>"><?= $m['status'] ?></span></td>
                                <td>
                                    <div style="display:flex;gap:6px;">
                                        <a href="?edit=<?= $m['id_menu'] ?>"
                                           class="btn btn-sm btn-secondary">✏️ Edit</a>
                                        <form method="POST" style="display:inline;"
                                              onsubmit="return confirm('Hapus menu ini?')">
                                            <input type="hidden" name="action" value="hapus">
                                            <input type="hidden" name="id_menu" value="<?= $m['id_menu'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal Tambah -->
<div class="modal-overlay" id="modal-tambah">
    <div class="modal-box">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 class="modal-title">➕ Tambah Menu Baru</h3>
            <button onclick="closeModal('modal-tambah')"
                    style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--text-muted);">✕</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="tambah">
            <div class="form-group">
                <label class="form-label">Nama Menu *</label>
                <input type="text" name="nama_menu" class="form-control" required
                       placeholder="Contoh: Nasi Box Ayam Goreng">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label class="form-label">Harga (Rp) *</label>
                    <input type="number" name="harga" class="form-control" min="0" required
                           placeholder="25000">
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="tersedia">Tersedia</option>
                        <option value="habis">Habis</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <textarea name="deskripsi" class="form-control" rows="2"
                          placeholder="Deskripsi singkat menu..."></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Foto Menu</label>
                <input type="file" name="foto" class="form-control" accept="image/*">
            </div>
            <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:8px;">
                <button type="button" onclick="closeModal('modal-tambah')"
                        class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary">💾 Simpan Menu</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<?php if ($edit_menu): ?>
<div class="modal-overlay show" id="modal-edit">
    <div class="modal-box">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 class="modal-title">✏️ Edit Menu</h3>
            <a href="menu.php" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--text-muted);text-decoration:none;">✕</a>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_menu" value="<?= $edit_menu['id_menu'] ?>">
            <div class="form-group">
                <label class="form-label">Nama Menu *</label>
                <input type="text" name="nama_menu" class="form-control" required
                       value="<?= htmlspecialchars($edit_menu['nama_menu']) ?>">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label class="form-label">Harga (Rp) *</label>
                    <input type="number" name="harga" class="form-control" min="0" required
                           value="<?= $edit_menu['harga'] ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="tersedia" <?= $edit_menu['status']==='tersedia'?'selected':'' ?>>Tersedia</option>
                        <option value="habis" <?= $edit_menu['status']==='habis'?'selected':'' ?>>Habis</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <textarea name="deskripsi" class="form-control" rows="2"><?= htmlspecialchars($edit_menu['deskripsi']) ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Foto Baru (opsional)</label>
                <input type="file" name="foto" class="form-control" accept="image/*">
                <?php if ($edit_menu['foto']): ?>
                <p class="form-hint">Foto saat ini: <?= $edit_menu['foto'] ?></p>
                <?php endif; ?>
            </div>
            <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:8px;">
                <a href="menu.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('show');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('show');
    });
});
</script>
</body>
</html>