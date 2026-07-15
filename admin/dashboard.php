<?php
// Set page title
$page_title = 'Dashboard - Admin Kafetamin';

// Start session hanya jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

// Ambil pengaturan
$settings = [];
$stmt = $pdo->query("SELECT key_name, value FROM pengaturan");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key_name']] = $row['value'];
}
$namaToko = $settings['nama_toko'] ?? 'Kafetamin';
$tema = $settings['tema'] ?? 'ungu';

// Update page title
$page_title = 'Dashboard - ' . $namaToko;

// Cek login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Statistik
$totalMenu = $pdo->query("SELECT COUNT(*) as total FROM menu")->fetch()['total'] ?? 0;
$totalPesanan = $pdo->query("SELECT COUNT(*) as total FROM pesanan")->fetch()['total'] ?? 0;
$pendingPesanan = $pdo->query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'dikirim'")->fetch()['total'] ?? 0;
$omzetHari = $pdo->query("SELECT SUM(total_harga) as total FROM pesanan WHERE DATE(created_at) = CURDATE()")->fetch()['total'] ?? 0;
$omzetBulan = $pdo->query("SELECT SUM(total_harga) as total FROM pesanan WHERE MONTH(created_at) = MONTH(CURDATE())")->fetch()['total'] ?? 0;

// Pesanan Aktif
$pesananAktif = $pdo->query("
    SELECT p.*, GROUP_CONCAT(CONCAT(m.nama, ' (', d.jumlah, ' pcs)') SEPARATOR ', ') as detail
    FROM pesanan p
    LEFT JOIN detail_pesanan d ON p.id = d.pesanan_id
    LEFT JOIN menu m ON d.menu_id = m.id
    WHERE p.status != 'selesai'
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$statusLabels = [
    'dikirim' => ['label' => 'Dikirim', 'badge' => '#6C63FF', 'icon' => 'fa-paper-plane'],
    'dibayar' => ['label' => 'Dibayar', 'badge' => '#4ECDC4', 'icon' => 'fa-money-bill'],
    'dibuat' => ['label' => 'Dibuat', 'badge' => '#FFD93D', 'icon' => 'fa-utensils'],
    'selesai' => ['label' => 'Selesai', 'badge' => '#636E72', 'icon' => 'fa-check-circle']
];

// Warna tema
$themeColors = [
    'ungu' => ['primary' => '#6C63FF', 'light' => 'rgba(108, 99, 255, 0.12)'],
    'merah' => ['primary' => '#E74C3C', 'light' => 'rgba(231, 76, 60, 0.12)'],
    'hijau' => ['primary' => '#27AE60', 'light' => 'rgba(39, 174, 96, 0.12)'],
    'biru' => ['primary' => '#2980B9', 'light' => 'rgba(41, 128, 185, 0.12)'],
    'pelangi' => ['primary' => '#6C63FF', 'light' => 'rgba(108, 99, 255, 0.12)']
];
$color = $themeColors[$tema] ?? $themeColors['ungu'];
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
        body { min-height: 100vh; background: linear-gradient(135deg, #F8F9FA 0%, #E8ECF1 100%); }
        
        .stats-card {
            border: none;
            border-radius: 16px;
            transition: all 0.3s;
            background: white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            overflow: hidden;
            position: relative;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
        }
        .stats-card .icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }
        .stats-card .number {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 4px 0;
        }
        .stats-card .label {
            color: #636E72;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .stats-card .icon.bg-primary-custom {
            background: <?php echo $color['light']; ?>;
            color: <?php echo $color['primary']; ?>;
        }
        
        .status-badge {
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            color: white;
        }
        .btn-action {
            font-size: 0.7rem;
            padding: 4px 10px;
            border-radius: 8px;
        }
        .detail-item {
            display: flex; justify-content: space-between;
            padding: 8px 0; border-bottom: 1px solid #f0f0f0;
        }
        .detail-item:last-child { border-bottom: none; }
        
        .btn-primary-custom {
            background: <?php echo $color['primary']; ?> !important;
            border-color: <?php echo $color['primary']; ?> !important;
            color: white !important;
        }
        .btn-primary-custom:hover {
            background: <?php echo $color['primary']; ?>dd !important;
            border-color: <?php echo $color['primary']; ?>dd !important;
        }
        .border-primary-custom {
            border-color: <?php echo $color['primary']; ?> !important;
        }
        .text-primary-custom {
            color: <?php echo $color['primary']; ?> !important;
        }
        
        .reload-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(44, 62, 80, 0.9);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.75rem;
            z-index: 999;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
        }
        .reload-indicator i {
            margin-right: 6px;
            color: #4ECDC4;
        }
        .reload-indicator .countdown {
            font-weight: 700;
            color: #FFD93D;
        }
    </style>
</head>
<body class="theme-<?php echo $tema; ?>">
    <?php include 'sidebar.php'; ?>
    
    <div class="main-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0 fw-bold" style="color: #2D3436;">Dashboard</h4>
                <small class="text-muted">Selamat datang, <?php echo $_SESSION['admin_username']; ?></small>
            </div>
            <div>
                <span class="text-muted"><i class="fas fa-calendar-alt"></i> <?php echo date('d F Y'); ?></span>
                <span class="badge bg-success ms-2"><i class="fas fa-circle" style="font-size: 0.6rem;"></i> Online</span>
                <span class="badge bg-info ms-2">
                    <i class="fas fa-sync fa-spin"></i> Auto Refresh
                </span>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="stats-card p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number" style="color: <?php echo $color['primary']; ?>;"><?php echo $totalMenu; ?></div>
                            <div class="label">Total Menu</div>
                        </div>
                        <div class="icon bg-primary-custom"><i class="fas fa-utensils"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stats-card p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number" style="color: #4ECDC4;"><?php echo $totalPesanan; ?></div>
                            <div class="label">Total Pesanan</div>
                        </div>
                        <div class="icon" style="background: rgba(78, 205, 196, 0.12); color: #4ECDC4;"><i class="fas fa-shopping-cart"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stats-card p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number" style="color: #F9A825;">Rp <?php echo number_format($omzetHari, 0, ',', '.'); ?></div>
                            <div class="label">Omzet Hari Ini</div>
                        </div>
                        <div class="icon" style="background: rgba(255, 217, 61, 0.15); color: #F9A825;"><i class="fas fa-calendar-day"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stats-card p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number" style="color: #FF6B6B;">Rp <?php echo number_format($omzetBulan, 0, ',', '.'); ?></div>
                            <div class="label">Omzet Bulan Ini</div>
                        </div>
                        <div class="icon" style="background: rgba(255, 107, 107, 0.12); color: #FF6B6B;"><i class="fas fa-calendar-alt"></i></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pesanan Aktif -->
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-clock text-warning"></i> Pesanan Aktif</h6>
                <div>
                    <span class="text-muted small me-2" id="lastUpdate">Last update: now</span>
                    <a href="kelola_pesanan.php" class="btn btn-sm btn-primary-custom">
                        <i class="fas fa-arrow-right"></i> Lihat Semua
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>TRX ID</th><th>Pemesan</th><th>Tipe</th><th>Total</th><th>Status</th><th class="text-center">Aksi</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pesananAktif)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">✅ Semua pesanan sudah selesai</td></tr>
                            <?php else: foreach ($pesananAktif as $p): 
                                $statusInfo = $statusLabels[$p['status']] ?? $statusLabels['dikirim'];
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($p['trx_id']); ?></strong><br><small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($p['created_at'])); ?></small></td>
                                    <td><?php echo htmlspecialchars($p['nama_pemesan']); ?></td>
                                    <td><span class="badge bg-<?php echo $p['tipe'] == 'dine-in' ? 'info' : 'secondary'; ?>"><?php echo ucfirst(str_replace('-', ' ', $p['tipe'])); ?></span></td>
                                    <td><strong class="text-success">Rp <?php echo number_format($p['total_harga'], 0, ',', '.'); ?></strong></td>
                                    <td><span class="status-badge" style="background: <?php echo $statusInfo['badge']; ?>;"><i class="fas <?php echo $statusInfo['icon']; ?>"></i> <?php echo $statusInfo['label']; ?></span></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-info btn-action" onclick="showDetail(<?php echo $p['id']; ?>)"><i class="fas fa-eye"></i></button>
                                            <?php if ($p['status'] == 'dikirim'): ?>
                                                <button class="btn btn-success btn-action" onclick="updateStatus(<?php echo $p['id']; ?>, 'dibayar')"><i class="fas fa-money-bill"></i> Bayar</button>
                                            <?php elseif ($p['status'] == 'dibayar'): ?>
                                                <button class="btn btn-warning btn-action" onclick="updateStatus(<?php echo $p['id']; ?>, 'dibuat')"><i class="fas fa-utensils"></i> Buat</button>
                                            <?php elseif ($p['status'] == 'dibuat'): ?>
                                                <button class="btn btn-secondary btn-action" onclick="updateStatus(<?php echo $p['id']; ?>, 'selesai')"><i class="fas fa-check"></i> Selesai</button>
                                            <?php endif; ?>
                                            <button class="btn btn-danger btn-action" onclick="hapusPesanan(<?php echo $p['id']; ?>, '<?php echo $p['trx_id']; ?>')"><i class="fas fa-trash"></i></button>
                                            <button class="btn btn-secondary btn-action" onclick="printStruk('<?php echo $p['trx_id']; ?>')"><i class="fas fa-print"></i></button>
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
    
    <!-- Modal Detail -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-receipt text-primary"></i> Detail Pesanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailModalBody"></div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Auto Reload Indicator -->
    <div class="reload-indicator">
        <i class="fas fa-sync fa-spin"></i>
        Refresh <span class="countdown" id="countdown">20</span>s
    </div>
    
    <script>
    // ===== FUNGSI DASAR =====
    let countdown = 10;
    let countdownInterval = null;
    let isModalOpen = false;
    
    // ===== SHOW DETAIL - HENTIKAN COUNTDOWN =====
    function showDetail(id) {
        // Hentikan countdown
        isModalOpen = true;
        
        const modal = new bootstrap.Modal(document.getElementById('detailModal'));
        const body = document.getElementById('detailModalBody');
        body.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>';
        
        // Event listener untuk ketika modal ditutup
        document.getElementById('detailModal').addEventListener('hidden.bs.modal', function() {
            isModalOpen = false;
            // Reset countdown
            countdown = 10;
            const countdownEl = document.getElementById('countdown');
            if (countdownEl) {
                countdownEl.textContent = countdown;
            }
            // Update last check
            updateLastUpdate();
        });
        
        fetch('get_detail_pesanan.php?id=' + id)
            .then(r => r.json())
            .then(data => {
                let html = '';
                if (data.pesanan) {
                    const labels = { 'dikirim':'📨 Dikirim','dibayar':'💰 Dibayar','dibuat':'🍳 Dibuat','selesai':'✅ Selesai' };
                    html += `<div class="mb-3"><p><strong>TRX ID:</strong> ${data.pesanan.trx_id}</p>
                            <p><strong>Pemesan:</strong> ${data.pesanan.nama_pemesan}</p>
                            <p><strong>Tipe:</strong> ${data.pesanan.tipe.replace('-', ' ').toUpperCase()}</p>
                            <p><strong>Status:</strong> ${labels[data.pesanan.status] || data.pesanan.status}</p>
                            <p><strong>Waktu:</strong> ${new Date(data.pesanan.created_at).toLocaleString('id-ID')}</p>
                            </div><hr><h6>Detail Item:</h6>`;
                }
                if (data.items && data.items.length > 0) {
                    data.items.forEach(item => {
                        html += `<div class="detail-item"><span>${item.nama} <span class="text-muted">x${item.jumlah}</span></span><span>Rp ${new Intl.NumberFormat('id-ID').format(item.subtotal)}</span></div>`;
                    });
                    if (data.pesanan) {
                        html += `<hr><div class="detail-item fw-bold"><span>Total</span><span class="text-success">Rp ${new Intl.NumberFormat('id-ID').format(data.pesanan.total_harga)}</span></div>`;
                    }
                } else {
                    html += `<p class="text-muted">Tidak ada item</p>`;
                }
                body.innerHTML = html;
                modal.show();
            });
    }
    
    // ===== UPDATE STATUS - TANPA CONFIRM =====
    function updateStatus(id, status) {
        fetch('update_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id + '&status=' + status
        })
        .then(r => r.json())
        .then(data => { 
            if (data.success) {
                location.reload(); 
            } else {
                alert('Gagal update!'); 
            } 
        });
    }
    
    // ===== HAPUS PESANAN =====
    function hapusPesanan(id, trxId) {
        if (!confirm('Hapus pesanan ' + trxId + '?')) return;
        fetch('hapus_pesanan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id
        })
        .then(r => r.json())
        .then(data => { 
            if (data.success) { 
                alert('Pesanan dihapus!'); 
                location.reload(); 
            } else {
                alert('Gagal hapus!'); 
            } 
        });
    }
    
    function printStruk(trxId) {
        window.open('../print_struk.php?trx_id=' + trxId, '_blank', 'width=400,height=600');
    }

    // ===== AUTO RELOAD 10 DETIK =====
    const countdownEl = document.getElementById('countdown');
    const lastUpdateEl = document.getElementById('lastUpdate');
    
    function updateLastUpdate() {
        const now = new Date();
        const time = now.toLocaleTimeString('id-ID');
        if (lastUpdateEl) {
            lastUpdateEl.textContent = 'Last update: ' + time;
        }
    }
    
    function startCountdown() {
        // Hentikan interval lama jika ada
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }
        
        countdown = 10;
        if (countdownEl) {
            countdownEl.textContent = countdown;
        }
        
        countdownInterval = setInterval(function() {
            // Hanya jalankan jika modal tidak terbuka
            if (!isModalOpen) {
                countdown--;
                if (countdownEl) {
                    countdownEl.textContent = countdown;
                }
                
                if (countdown <= 0) {
                    countdown = 10;
                    location.reload();
                }
            } else {
                // Jika modal terbuka, reset countdown visual
                if (countdownEl) {
                    countdownEl.textContent = '⏸';
                }
            }
        }, 1000);
    }
    
    // Update waktu pertama kali
    updateLastUpdate();
    
    // Mulai countdown
    startCountdown();
    
    // ===== RESET COUNTDOWN SETELAH MODAL DITUTUP =====
    document.getElementById('detailModal').addEventListener('hidden.bs.modal', function() {
        isModalOpen = false;
        // Reset countdown
        countdown = 10;
        if (countdownEl) {
            countdownEl.textContent = countdown;
        }
        updateLastUpdate();
    });
</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>