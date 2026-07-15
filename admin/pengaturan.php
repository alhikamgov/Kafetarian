<?php
// Set page title
$page_title = 'Pengaturan - Admin Kafetamin';

// Start session hanya jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

// Ambil nama toko
$settings = [];
$stmt = $pdo->query("SELECT key_name, value FROM pengaturan");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key_name']] = $row['value'];
}
$namaToko = $settings['nama_toko'] ?? 'Kafetamin';
$tema = $settings['tema'] ?? 'ungu';

// Update page title
$page_title = 'Pengaturan - ' . $namaToko;

// Cek login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'pending'");
$pendingPesanan = $stmt->fetch()['total'] ?? 0;

$message = '';
$messageType = '';

// Fungsi untuk generate nama file unik
function generateFileName($extension) {
    $timestamp = time();
    $random = rand(100000, 999999);
    return 'banner_' . $timestamp . '_' . $random . '.' . $extension;
}

// ===== PROSES SIMPAN PENGATURAN =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // ===== UPDATE PENGATURAN =====
    if ($action === 'update_settings') {
        $data = $_POST;
        unset($data['submit']);
        unset($data['action']);
        
        // Proses upload banner
        if (isset($_FILES['banner']) && $_FILES['banner']['error'] === 0) {
            $targetDir = '../assets/images/banner/';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            $fileInfo = pathinfo($_FILES['banner']['name']);
            $extension = strtolower($fileInfo['extension']);
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($extension, $allowed)) {
                $fileName = generateFileName($extension);
                $targetFile = $targetDir . $fileName;
                
                if (move_uploaded_file($_FILES['banner']['tmp_name'], $targetFile)) {
                    if (!empty($settings['banner']) && file_exists($targetDir . $settings['banner'])) {
                        unlink($targetDir . $settings['banner']);
                    }
                    $data['banner'] = $fileName;
                }
            }
        }
        
        $stmt = $pdo->prepare("UPDATE pengaturan SET value = ? WHERE key_name = ?");
        $success = true;
        
        foreach ($data as $key => $value) {
            if (!$stmt->execute([$value, $key])) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            $message = 'Pengaturan berhasil disimpan!';
            $messageType = 'success';
            // Refresh data
            $settings = [];
            $stmt = $pdo->query("SELECT key_name, value FROM pengaturan");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['key_name']] = $row['value'];
            }
        } else {
            $message = 'Gagal menyimpan pengaturan!';
            $messageType = 'danger';
        }
    }
    
    // ===== GANTI PASSWORD =====
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        $admin_id = $_SESSION['admin_id'] ?? 0;
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $message = 'Semua field harus diisi!';
            $messageType = 'warning';
        } elseif ($new_password !== $confirm_password) {
            $message = 'Password baru dan konfirmasi tidak sama!';
            $messageType = 'warning';
        } elseif (strlen($new_password) < 6) {
            $message = 'Password minimal 6 karakter!';
            $messageType = 'warning';
        } else {
            // Ambil user dari database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$admin_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($current_password, $user['password_hash'])) {
                // Hash password baru
                $newHash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                if ($stmt->execute([$newHash, $admin_id])) {
                    $message = 'Password berhasil diubah!';
                    $messageType = 'success';
                } else {
                    $message = 'Gagal mengubah password!';
                    $messageType = 'danger';
                }
            } else {
                $message = 'Password saat ini salah!';
                $messageType = 'danger';
            }
        }
    }
}

// Daftar tema
$themes = [
    'ungu' => ['label' => 'Ungu', 'color' => '#6C63FF', 'icon' => 'fa-palette'],
    'merah' => ['label' => 'Merah', 'color' => '#E74C3C', 'icon' => 'fa-palette'],
    'hijau' => ['label' => 'Hijau', 'color' => '#27AE60', 'icon' => 'fa-palette'],
    'biru' => ['label' => 'Biru', 'color' => '#2980B9', 'icon' => 'fa-palette'],
    'pelangi' => ['label' => 'Pelangi', 'color' => 'linear-gradient(135deg, #E74C3C, #F39C12, #2ECC71, #3498DB, #9B59B6)', 'icon' => 'fa-rainbow']
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/themes.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; min-height: 100vh; }
        
        .settings-card {
            border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .settings-card .card-header {
            background: white; border-bottom: 1px solid #f0f0f0; border-radius: 12px 12px 0 0;
        }
        .banner-preview { max-height: 150px; border-radius: 8px; object-fit: cover; width: 100%; }
        
        /* Theme selector */
        .theme-option {
            cursor: pointer;
            padding: 15px;
            border-radius: 12px;
            border: 3px solid #e9ecef;
            transition: all 0.3s;
            text-align: center;
        }
        .theme-option:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .theme-option.active {
            border-color: #6C63FF;
            background: rgba(108, 99, 255, 0.05);
        }
        .theme-option .theme-preview {
            width: 100%;
            height: 40px;
            border-radius: 8px;
            margin-bottom: 8px;
        }
        .theme-option .theme-label {
            font-size: 0.85rem;
            font-weight: 600;
        }
        .theme-option .theme-check {
            display: none;
            color: #6C63FF;
            font-size: 1.2rem;
        }
        .theme-option.active .theme-check {
            display: block;
        }
        
        /* Password form */
        .password-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
        .password-section .form-control {
            border-radius: 8px;
        }
    </style>
</head>
<body class="theme-<?php echo $tema; ?>">
    <?php include 'sidebar.php'; ?>
    
    <div class="main-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0"><i class="fas fa-cog"></i> Pengaturan</h4>
                <small class="text-muted">Kelola pengaturan toko dan akun</small>
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
        
        <!-- ===== INFORMASI TOKO ===== -->
        <div class="card settings-card shadow-sm mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-store"></i> Informasi Toko</h6></div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <!-- Banner -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold"><i class="fas fa-image"></i> Banner Toko</label>
                        <?php if (!empty($settings['banner'])): ?>
                            <div class="mb-2"><img src="../assets/images/banner/<?php echo $settings['banner']; ?>" alt="Banner" class="banner-preview"></div>
                        <?php endif; ?>
                        <input type="file" class="form-control" name="banner" accept="image/*">
                        <small class="text-muted">Ukuran: 1200x400px (Maks 2MB, Format: JPG, PNG, GIF, WEBP)</small>
                    </div>
                    <hr>
                    
                    <!-- Pilihan Tema -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold"><i class="fas fa-palette"></i> Pilih Tema</label>
                        <div class="row g-3">
                            <?php foreach ($themes as $key => $theme): ?>
                                <div class="col-6 col-md-3">
                                    <div class="theme-option <?php echo ($settings['tema'] ?? 'ungu') == $key ? 'active' : ''; ?>" 
                                         onclick="selectTheme('<?php echo $key; ?>')">
                                        <div class="theme-preview" style="background: <?php echo $theme['color']; ?>;"></div>
                                        <div class="theme-label">
                                            <?php echo $theme['label']; ?>
                                            <span class="theme-check"><i class="fas fa-check-circle"></i></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="tema" id="selectedTheme" value="<?php echo $settings['tema'] ?? 'ungu'; ?>">
                    </div>
                    <hr>
                    
                    <!-- Informasi Toko -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold"><i class="fas fa-store-alt"></i> Nama Toko</label>
                                <input type="text" class="form-control" name="nama_toko" value="<?php echo htmlspecialchars($settings['nama_toko'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold"><i class="fas fa-phone"></i> Telepon</label>
                                <input type="text" class="form-control" name="telepon" value="<?php echo htmlspecialchars($settings['telepon'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><i class="fas fa-map-marker-alt"></i> Alamat</label>
                        <textarea class="form-control" name="alamat" rows="2"><?php echo htmlspecialchars($settings['alamat'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($settings['email'] ?? ''); ?>">
                    </div>
                    <hr>
                    <h6 class="mb-3"><i class="fas fa-clock"></i> Jam Operasional</h6>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Jam Buka</label>
                                <input type="time" class="form-control" name="jam_buka" value="<?php echo htmlspecialchars($settings['jam_buka'] ?? '08:00'); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Jam Tutup</label>
                                <input type="time" class="form-control" name="jam_tutup" value="<?php echo htmlspecialchars($settings['jam_tutup'] ?? '21:00'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Hari Buka</label>
                                <input type="text" class="form-control" name="hari_buka" value="<?php echo htmlspecialchars($settings['hari_buka'] ?? 'Senin - Jumat'); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hari Akhir Pekan</label>
                        <input type="text" class="form-control" name="hari_akhir_pekan" value="<?php echo htmlspecialchars($settings['hari_akhir_pekan'] ?? 'Sabtu - Minggu: 09:00 - 22:00'); ?>">
                    </div>
                    <hr>
                    <h6 class="mb-3"><i class="fas fa-edit"></i> Konten Lainnya</h6>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi Toko</label>
                        <textarea class="form-control" name="deskripsi" rows="2"><?php echo htmlspecialchars($settings['deskripsi'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Footer Text</label>
                        <input type="text" class="form-control" name="footer_text" value="<?php echo htmlspecialchars($settings['footer_text'] ?? '© 2024 Kafetamin. All rights reserved.'); ?>">
                    </div>
                    
                    <button type="submit" name="submit" class="btn btn-primary w-100 py-2"><i class="fas fa-save"></i> Simpan Pengaturan</button>
                </form>
            </div>
        </div>
        
        <!-- ===== GANTI PASSWORD ===== -->
        <div class="card settings-card shadow-sm mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-key"></i> Ganti Password</h6></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="password-section">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Password Saat Ini</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-lock text-muted"></i></span>
                                        <input type="password" class="form-control border-start-0" name="current_password" required placeholder="Masukkan password saat ini">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Password Baru</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-key text-muted"></i></span>
                                        <input type="password" class="form-control border-start-0" name="new_password" required placeholder="Minimal 6 karakter">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Konfirmasi Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-check text-muted"></i></span>
                                        <input type="password" class="form-control border-start-0" name="confirm_password" required placeholder="Ulangi password baru">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="fas fa-save"></i> Ganti Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- ===== PREVIEW ===== -->
        <div class="card settings-card shadow-sm">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-eye"></i> Preview</h6></div>
            <div class="card-body">
                <div class="bg-light p-3 rounded">
                    <?php if (!empty($settings['banner'])): ?>
                        <img src="../assets/images/banner/<?php echo $settings['banner']; ?>" alt="Banner Preview" class="img-fluid rounded mb-3" style="max-height: 200px; width: 100%; object-fit: cover;">
                    <?php endif; ?>
                    <h5><?php echo htmlspecialchars($settings['nama_toko'] ?? 'Kafetamin'); ?></h5>
                    <p class="mb-1"><i class="fas fa-map-marker-alt text-danger"></i> <?php echo htmlspecialchars($settings['alamat'] ?? 'Jl. Raya Contoh No. 123'); ?></p>
                    <p class="mb-1"><i class="fas fa-phone text-success"></i> <?php echo htmlspecialchars($settings['telepon'] ?? '0812-3456-7890'); ?></p>
                    <p class="mb-1"><i class="fas fa-envelope text-info"></i> <?php echo htmlspecialchars($settings['email'] ?? 'info@kafetamin.com'); ?></p>
                    <p class="mb-0"><i class="fas fa-clock text-warning"></i> <?php echo htmlspecialchars($settings['jam_buka'] ?? '08:00'); ?> - <?php echo htmlspecialchars($settings['jam_tutup'] ?? '21:00'); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function selectTheme(theme) {
            document.querySelectorAll('.theme-option').forEach(el => {
                el.classList.remove('active');
            });
            document.querySelector(`.theme-option[onclick="selectTheme('${theme}')"]`).classList.add('active');
            document.getElementById('selectedTheme').value = theme;
            
            // Preview tema
            const body = document.body;
            body.className = body.className.split(' ').filter(c => !c.startsWith('theme-')).join(' ');
            body.classList.add('theme-' + theme);
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>