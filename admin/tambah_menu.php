<?php
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

$stmt = $pdo->query("SELECT * FROM menu ORDER BY id DESC");
$allMenu = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$messageType = '';

function generateFileName($extension) {
    $timestamp = time();
    $random = rand(100000, 999999);
    return $timestamp . '_' . $random . '.' . $extension;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'];
    $kategori = $_POST['kategori'];
    $harga = $_POST['harga'];
    $thumbnail = '';
    
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === 0) {
        $targetDir = '../assets/images/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $fileInfo = pathinfo($_FILES['thumbnail']['name']);
        $extension = strtolower($fileInfo['extension']);
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($extension, $allowed)) {
            $fileName = generateFileName($extension);
            $targetFile = $targetDir . $fileName;
            
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $targetFile)) {
                $thumbnail = $fileName;
            }
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO menu (nama, kategori, harga, thumbnail) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$nama, $kategori, $harga, $thumbnail])) {
        $message = 'Menu berhasil ditambahkan!';
        $messageType = 'success';
        $stmt = $pdo->query("SELECT * FROM menu ORDER BY id DESC");
        $allMenu = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $message = 'Gagal menambahkan menu!';
        $messageType = 'danger';
    }
}

$page_title = 'Kelola Pesanan - ' . ($settings['nama_toko'] ?? 'Kafetamin');

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
        
        .form-card, .menu-list-card {
            border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .menu-thumbnail-small {
            width: 50px; height: 50px; object-fit: cover; border-radius: 8px;
        }
    </style>
</head>
<body>
    
    <?php include 'sidebar.php'; ?>
    
    <div class="main-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0"><i class="fas fa-utensils"></i> Menu</h4>
                <small class="text-muted">Kelola menu makanan & minuman</small>
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
        
        <!-- Form Tambah Menu -->
        <div class="card form-card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-plus-circle"></i> Tambah Menu Baru</h6>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nama" class="form-label fw-semibold"><i class="fas fa-tag"></i> Nama Menu</label>
                                <input type="text" class="form-control" id="nama" name="nama" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="kategori" class="form-label fw-semibold"><i class="fas fa-folder"></i> Kategori</label>
                                <select class="form-select" id="kategori" name="kategori" required>
                                    <option value="makanan">🍽️ Makanan</option>
                                    <option value="minuman">🥤 Minuman</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="harga" class="form-label fw-semibold"><i class="fas fa-money-bill"></i> Harga (Rp)</label>
                                <input type="number" class="form-control" id="harga" name="harga" required min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="thumbnail" class="form-label fw-semibold"><i class="fas fa-image"></i> Foto</label>
                                <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/*">
                                <small class="text-muted">Format: JPG, PNG, WEBP (Max 2MB)</small>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100 py-2"><i class="fas fa-save"></i> Simpan Menu</button>
                </form>
            </div>
        </div>
        
        <!-- Daftar Menu -->
        <div class="card menu-list-card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-list"></i> Daftar Menu (<?php echo count($allMenu); ?>)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>#</th><th>Foto</th><th>Nama</th><th>Kategori</th><th>Harga</th><th class="text-center">Aksi</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allMenu)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada menu</td></tr>
                            <?php else: $no = 1; foreach ($allMenu as $menu): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <?php if ($menu['thumbnail']): ?>
                                            <img src="../assets/images/<?php echo $menu['thumbnail']; ?>" alt="<?php echo $menu['nama']; ?>" class="menu-thumbnail-small">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; border-radius: 8px;"><i class="fas fa-utensils text-muted"></i></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($menu['nama']); ?></td>
                                    <td><span class="badge bg-<?php echo $menu['kategori'] == 'makanan' ? 'primary' : 'info'; ?>"><?php echo ucfirst($menu['kategori']); ?></span></td>
                                    <td>Rp <?php echo number_format($menu['harga'], 0, ',', '.'); ?></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="edit_menu.php?id=<?php echo $menu['id']; ?>" class="btn btn-outline-primary"><i class="fas fa-edit"></i></a>
                                            <a href="hapus_menu.php?id=<?php echo $menu['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Yakin ingin menghapus menu ini?')"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>