<?php
$page_title = 'Konfirmasi Pesanan';
require_once 'config/database.php';

// Ambil parameter TRX
$trx_id = $_GET['trx'] ?? '';

if (empty($trx_id)) {
    header('Location: index.php');
    exit;
}

// Ambil data pesanan
$stmt = $pdo->prepare("
    SELECT p.*, 
           GROUP_CONCAT(CONCAT(m.nama, ' (', d.jumlah, ' pcs)') SEPARATOR ', ') as detail
    FROM pesanan p
    LEFT JOIN detail_pesanan d ON p.id = d.pesanan_id
    LEFT JOIN menu m ON d.menu_id = m.id
    WHERE p.trx_id = ?
    GROUP BY p.id
");
$stmt->execute([$trx_id]);
$pesanan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pesanan) {
    header('Location: index.php');
    exit;
}

// Ambil detail items
$stmt = $pdo->prepare("
    SELECT m.nama, d.jumlah, d.subtotal 
    FROM detail_pesanan d
    JOIN menu m ON d.menu_id = m.id
    WHERE d.pesanan_id = ?
");
$stmt->execute([$pesanan['id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$settings = [];
$stmt = $pdo->query("SELECT key_name, value FROM pengaturan");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key_name']] = $row['value'];
}

// Status pesanan
$currentStatus = $pesanan['status'];
$statuses = [
    'dikirim' => [
        'label' => 'Menunggu Pembayaran',
        'icon' => 'fa-clock',
        'color' => 'warning',
        'desc' => 'Silakan lanjutkan ke kasir untuk melakukan pembayaran.'
    ],
    'dibayar' => [
        'label' => 'Sudah Dibayar',
        'icon' => 'fa-money-bill',
        'color' => 'primary',
        'desc' => 'Pembayaran telah diterima. Pesanan sedang diproses oleh dapur.'
    ],
    'dibuat' => [
        'label' => 'Sedang Dibuat',
        'icon' => 'fa-utensils',
        'color' => 'warning',
        'desc' => 'Pesanan Anda sedang diproses oleh dapur. Mohon tunggu.'
    ],
    'selesai' => [
        'label' => 'Selesai',
        'icon' => 'fa-check-circle',
        'color' => 'success',
        'desc' => $pesanan['tipe'] == 'dine-in' 
            ? 'Pesanan sudah siap! Silakan tunggu, pesanan akan diantar ke meja Anda.' 
            : 'Pesanan sudah siap! Silakan ambil di kasir.'
    ]
];

$statusInfo = $statuses[$currentStatus] ?? $statuses['dikirim'];

// Urutan tahapan (sesuai keinginan)
$steps = [
    'dikirim' => ['label' => 'Menunggu Pembayaran', 'icon' => 'fa-clock'],
    'dibayar' => ['label' => 'Sudah Dibayar', 'icon' => 'fa-money-bill'],
    'dibuat' => ['label' => 'Sedang Dibuat', 'icon' => 'fa-utensils'],
    'selesai' => ['label' => 'Selesai', 'icon' => 'fa-check-circle']
];

// Tentukan step aktif
$activeStep = array_search($currentStatus, array_keys($steps));
$totalSteps = count($steps);
?>
<?php include 'includes/header.php'; ?>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-coffee"></i> <?php echo htmlspecialchars($settings['nama_toko'] ?? 'Kafetamin'); ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home"></i> Beranda</a></li>
                <li class="nav-item"><a class="nav-link" href="pesanan.php"><i class="fas fa-receipt"></i> Pesanan</a></li>
            </ul>
        </div>
    </div>
</nav>

<style>
    /* ===== TIMELINE HORIZONTAL ===== */
    .timeline-horizontal {
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        padding: 20px 0;
        margin: 0 10px;
    }
    
    .timeline-horizontal::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 5%;
        right: 5%;
        height: 4px;
        background: #dee2e6;
        transform: translateY(-50%);
        z-index: 0;
        border-radius: 2px;
    }
    
    .timeline-horizontal .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 1;
        flex: 1;
    }
    
    .timeline-horizontal .step .step-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: white;
        transition: all 0.5s ease;
        margin-bottom: 8px;
        position: relative;
    }
    
    /* Status Selesai = Abu-abu */
    .timeline-horizontal .step .step-icon.completed {
        background: #6c757d !important;
    }
    
    /* Status Berjalan = Hijau */
    .timeline-horizontal .step .step-icon.active {
        background: #28a745 !important;
        transform: scale(1.15);
        box-shadow: 0 0 25px rgba(40, 167, 69, 0.4);
        animation: pulse-green 1.5s ease-in-out infinite;
    }
    
    /* Status Belum = Abu-abu muda */
    .timeline-horizontal .step .step-icon.pending {
        background: #e9ecef !important;
        color: #adb5bd;
    }
    
    .timeline-horizontal .step .step-label {
        font-size: 0.85rem;
        font-weight: 600;
        text-align: center;
        transition: all 0.3s ease;
        max-width: 120px;
    }
    
    .timeline-horizontal .step .step-label.completed {
        color: #6c757d;
    }
    
    .timeline-horizontal .step .step-label.active {
        color: #28a745;
    }
    
    .timeline-horizontal .step .step-label.pending {
        color: #adb5bd;
    }
    
    .timeline-horizontal .step .step-sub {
        font-size: 0.7rem;
        color: #adb5bd;
        text-align: center;
    }
    
    .timeline-horizontal .step .step-sub.active {
        color: #28a745;
        font-weight: 500;
    }
    
    .timeline-horizontal .step .step-sub.completed {
        color: #6c757d;
    }
    
    @keyframes pulse-green {
        0%, 100% { transform: scale(1.15); box-shadow: 0 0 25px rgba(40, 167, 69, 0.4); }
        50% { transform: scale(1.25); box-shadow: 0 0 35px rgba(40, 167, 69, 0.6); }
    }
    
    /* ===== STATUS CARD ===== */
    .status-card {
        border-radius: 15px;
        padding: 25px;
        text-align: center;
        border: none;
        transition: all 0.3s ease;
    }
    .status-card .status-icon {
        font-size: 3.5rem;
        margin-bottom: 10px;
    }
    .status-card .status-label {
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 5px;
    }
    .status-card .status-desc {
        font-size: 0.95rem;
        opacity: 0.9;
        margin-bottom: 0;
    }
    
    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
        .timeline-horizontal {
            flex-direction: column;
            align-items: stretch;
            padding: 10px 0;
            gap: 15px;
        }
        
        .timeline-horizontal::before {
            display: none;
        }
        
        .timeline-horizontal .step {
            flex-direction: row;
            gap: 15px;
            padding: 10px 15px;
            border-left: 4px solid #dee2e6;
            position: relative;
        }
        
        .timeline-horizontal .step:last-child {
            border-left-color: transparent;
        }
        
        .timeline-horizontal .step .step-icon {
            width: 40px;
            height: 40px;
            font-size: 1rem;
            flex-shrink: 0;
            margin-bottom: 0;
        }
        
        .timeline-horizontal .step .step-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .timeline-horizontal .step .step-label {
            text-align: left;
            font-size: 0.9rem;
            max-width: none;
        }
        
        .timeline-horizontal .step .step-sub {
            text-align: left;
            font-size: 0.75rem;
        }
        
        .status-card .status-icon { font-size: 2.5rem; }
        .status-card .status-label { font-size: 1.2rem; }
    }
</style>

<div class="container main-content py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            
            <!-- Status Card -->
            <div class="card status-card shadow-sm border-<?php echo $statusInfo['color']; ?> mb-4">
                <div class="card-body">
                    <div class="status-icon text-<?php echo $statusInfo['color']; ?>">
                        <i class="fas <?php echo $statusInfo['icon']; ?>"></i>
                    </div>
                    <div class="status-label text-<?php echo $statusInfo['color']; ?>">
                        <?php echo $statusInfo['label']; ?>
                    </div>
                    <p class="status-desc text-muted"><?php echo $statusInfo['desc']; ?></p>
                    <div class="mt-2">
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-clock"></i> 
                            <?php echo date('d/m/Y H:i', strtotime($pesanan['created_at'])); ?>
                        </span>
                        <span class="badge bg-light text-dark ms-2">
                            <i class="fas fa-tag"></i> 
                            <?php echo htmlspecialchars($pesanan['trx_id']); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Timeline Horizontal -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-chart-line"></i> Status Pesanan</h6>
                </div>
                <div class="card-body">
                    <div class="timeline-horizontal">
                        <?php 
                        $stepIndex = 0;
                        foreach ($steps as $key => $step): 
                            $isActive = $stepIndex <= $activeStep;
                            $isCompleted = $stepIndex < $activeStep;
                            $isCurrent = $stepIndex == $activeStep;
                            
                            // Status class
                            if ($isCompleted) {
                                $statusClass = 'completed';
                            } elseif ($isCurrent) {
                                $statusClass = 'active';
                            } else {
                                $statusClass = 'pending';
                            }
                            
                            $icon = $isCompleted ? 'fa-check' : $step['icon'];
                        ?>
                            <div class="step">
                                <div class="step-icon <?php echo $statusClass; ?>">
                                    <i class="fas <?php echo $icon; ?>"></i>
                                </div>
                                <div class="step-info">
                                    <div class="step-label <?php echo $statusClass; ?>">
                                        <?php echo $step['label']; ?>
                                    </div>
                                    <div class="step-sub <?php echo $statusClass; ?>">
                                        <?php 
                                        if ($isCompleted) echo '✅ Selesai';
                                        elseif ($isCurrent) echo '⏳ Proses...';
                                        else echo '⏰ Menunggu';
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            $stepIndex++;
                        endforeach; 
                        ?>
                    </div>
                </div>
            </div>

            <!-- Info Tambahan -->
            <div class="alert alert-info mb-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle fa-2x me-3"></i>
                    <div>
                        <h6 class="mb-1">Informasi Pesanan</h6>
                        <p class="mb-0 small">
                            <strong>Tipe:</strong> <?php echo ucfirst(str_replace('-', ' ', $pesanan['tipe'])); ?> | 
                            <strong>Nama:</strong> <?php echo htmlspecialchars($pesanan['nama_pemesan']); ?> | 
                            <strong>Total:</strong> Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Detail Pesanan -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-receipt"></i> Detail Pesanan</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Menu</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-end">Harga</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['nama']); ?></td>
                                    <td class="text-center"><?php echo $item['jumlah']; ?> pcs</td>
                                    <td class="text-end">Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-success fw-bold">
                                    <td colspan="2" class="text-end">Total</td>
                                    <td class="text-end">Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="d-flex gap-2 flex-wrap justify-content-center">
                <a href="print_struk.php?trx_id=<?php echo $pesanan['trx_id']; ?>" target="_blank" class="btn btn-secondary">
                    <i class="fas fa-print"></i> Cetak Struk
                </a>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Kembali ke Beranda
                </a>
                <a href="pesanan.php" class="btn btn-outline-primary">
                    <i class="fas fa-receipt"></i> Lihat Pesanan
                </a>
            </div>

            <!-- Refresh otomatis -->
            <div class="text-center mt-4">
                <small class="text-muted">
                    <i class="fas fa-sync fa-spin"></i> 
                    Halaman akan diperbarui otomatis setiap 15 detik
                </small>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto refresh setiap 30 detik untuk update status
    setTimeout(function() {
        location.reload();
    }, 10000);
</script>

<?php include 'includes/footer.php'; ?>