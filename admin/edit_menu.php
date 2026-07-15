<?php
$page_title = 'Edit Menu - Admin Kafetamin';
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Ambil pengaturan
$settings = [];
$stmt = $pdo->query("SELECT key_name, value FROM pengaturan");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key_name']] = $row['value'];
}

$stmt = $pdo->query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'pending'");
$pendingPesanan = $stmt->fetch()['total'] ?? 0;

$id = $_GET['id'] ?? 0;
$message = '';
$messageType = '';

$stmt = $pdo->prepare("SELECT * FROM menu WHERE id = ?");
$stmt->execute([$id]);
$menu = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$menu) {
    header('Location: dashboard.php');
    exit;
}

// Fungsi untuk generate nama file unik
function generateFileName($extension) {
    $timestamp = time();
    $random = rand(100000, 999999);
    return $timestamp . '_' . $random . '.' . $extension;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'];
    $kategori = $_POST['kategori'];
    $harga = $_POST['harga'];
    $thumbnail = $menu['thumbnail'];
    
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === 0) {
        $targetDir = '../assets/images/';
        $fileInfo = pathinfo($_FILES['thumbnail']['name']);
        $extension = strtolower($fileInfo['extension']);
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($extension, $allowed)) {
            $fileName = generateFileName($extension);
            $targetFile = $targetDir . $fileName;
            
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $targetFile)) {
                // Hapus gambar lama
                if ($thumbnail && file_exists($targetDir . $thumbnail)) {
                    unlink($targetDir . $thumbnail);
                }
                $thumbnail = $fileName;
            }
        }
    }
    
    $stmt = $pdo->prepare("UPDATE menu SET nama = ?, kategori = ?, harga = ?, thumbnail = ? WHERE id = ?");
    if ($stmt->execute([$nama, $kategori, $harga, $thumbnail, $id])) {
        $message = 'Menu berhasil diupdate!';
        $messageType = 'success';
        $stmt = $pdo->prepare("SELECT * FROM menu WHERE id = ?");
        $stmt->execute([$id]);
        $menu = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $message = 'Gagal mengupdate menu!';
        $messageType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; min-height: 100vh; }
        
        .sidebar-wrapper {
            position: fixed; top: 0; left: 0; height: 100vh; width: 250px;
            background: #2c3e50; z-index: 1000; overflow-y: auto;
            transition: transform 0.3s ease;
        }
        .sidebar-wrapper .brand {
            padding: 20px; color: white; font-size: 1.3rem; font-weight: bold;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex; align-items: center; gap: 10px;
        }
        .sidebar-wrapper .brand i { color: #f39c12; }
        .sidebar-wrapper .nav-link {
            color: rgba(255,255,255,0.7); padding: 12px 20px;
            display: flex; align-items: center; gap: 12px;
            text-decoration: none; transition: all 0.3s;
        }
        .sidebar-wrapper .nav-link:hover,
        .sidebar-wrapper .nav-link.active {
            color: white; background: rgba(255,255,255,0.1);
        }
        .sidebar-wrapper .nav-link i { width: 20px; text-align: center; }
        .sidebar-wrapper .nav-link .badge { margin-left: auto; }
        
        .main-wrapper { margin-left: 250px; padding: 20px; min-height: 100vh; }
        .form-card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        
        .sidebar-toggle {
            display: none; position: fixed; top: 15px; left: 15px;
            z-index: 1001; background: #2c3e50; color: white;
            border: none; padding: 10px 15px; border-radius: 8px;
            font-size: 1.2rem; cursor: pointer;
        }
        .sidebar-close {
            display: none; position: fixed; top: 15px; right: 15px;
            z-index: 1002; background: transparent; color: white;
            border: none; font-size: 1.5rem; cursor: pointer;
        }
        .sidebar-overlay {
            display: none; position: fixed; top: 0; left: 0;
            width: 100%; height: 100%; background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        @media (max-width: 768px) {
            .sidebar-wrapper { transform: translateX(-100%); }
            .sidebar-wrapper.open { transform: translateX(0); }
            .sidebar-toggle { display: block; }
            .sidebar-close { display: block; }
            .sidebar-overlay.show { display: block; }
            .main-wrapper { margin-left: 0; padding-top: 70px; }
        }
    </style>
</head>
<body>
    <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
    
    <?php include 'sidebar.php'; ?>
    
    <div class="main-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="tambah_menu.php" class="btn btn-outline-secondary btn-sm mb-2"><i class="fas fa-arrow-left"></i> Kembali</a>
                <h4 class="mb-0"><i class="fas fa-edit"></i> Edit Menu</h4>
                <small class="text-muted">Edit data menu yang sudah ada</small>
            </div>
            <div><span class="text-muted"><i class="fas fa-calendar-alt"></i> <?php echo date('d F Y'); ?></span></div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="card form-card shadow-sm">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nama" class="form-label fw-semibold"><i class="fas fa-tag"></i> Nama Menu</label>
                                <input type="text" class="form-control" id="nama" name="nama" value="<?php echo htmlspecialchars($menu['nama']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="kategori" class="form-label fw-semibold"><i class="fas fa-folder"></i> Kategori</label>
                                <select class="form-select" id="kategori" name="kategori" required>
                                    <option value="makanan" <?php echo $menu['kategori'] == 'makanan' ? 'selected' : ''; ?>>🍽️ Makanan</option>
                                    <option value="minuman" <?php echo $menu['kategori'] == 'minuman' ? 'selected' : ''; ?>>🥤 Minuman</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="harga" class="form-label fw-semibold"><i class="fas fa-money-bill"></i> Harga (Rp)</label>
                                <input type="number" class="form-control" id="harga" name="harga" value="<?php echo $menu['harga']; ?>" required min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold"><i class="fas fa-image"></i> Foto Menu</label>
                                <?php if ($menu['thumbnail']): ?>
                                    <div class="mb-2"><img src="../assets/images/<?php echo $menu['thumbnail']; ?>" alt="Current" style="max-height: 100px; border-radius: 8px;"></div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/*">
                                <small class="text-muted">Kosongkan jika tidak ingin mengganti gambar</small>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2"><i class="fas fa-save"></i> Update Menu</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        }
        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebarOverlay').classList.remove('show');
        }
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) closeSidebar();
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>