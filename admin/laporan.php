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

// Filter
$filter = $_GET['filter'] ?? 'hari_ini';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Tentukan tanggal berdasarkan filter
$now = new DateTime();
$today = $now->format('Y-m-d');

switch ($filter) {
    case 'hari_ini':
        $start_date = $today;
        $end_date = $today;
        $label = 'Hari Ini';
        break;
    case '7_hari':
        $start_date = $now->modify('-7 days')->format('Y-m-d');
        $end_date = date('Y-m-d');
        $label = '7 Hari Terakhir';
        break;
    case '14_hari':
        $start_date = $now->modify('-14 days')->format('Y-m-d');
        $end_date = date('Y-m-d');
        $label = '14 Hari Terakhir';
        break;
    case '30_hari':
        $start_date = $now->modify('-30 days')->format('Y-m-d');
        $end_date = date('Y-m-d');
        $label = '30 Hari Terakhir';
        break;
    case 'bulan_ini':
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        $label = 'Bulan Ini';
        break;
    case 'bulan_kemarin':
        $firstDay = date('Y-m-01', strtotime('first day of previous month'));
        $lastDay = date('Y-m-t', strtotime('last day of previous month'));
        $start_date = $firstDay;
        $end_date = $lastDay;
        $label = 'Bulan Kemarin';
        break;
    case 'custom':
        $label = 'Custom (' . $start_date . ' s/d ' . $end_date . ')';
        break;
    default:
        $start_date = $today;
        $end_date = $today;
        $label = 'Hari Ini';
}

// Query data
$query = "
    SELECT 
        p.*,
        GROUP_CONCAT(CONCAT(m.nama, ' (', d.jumlah, ' pcs)') SEPARATOR ', ') as detail
    FROM pesanan p
    LEFT JOIN detail_pesanan d ON p.id = d.pesanan_id
    LEFT JOIN menu m ON d.menu_id = m.id
    WHERE DATE(p.created_at) BETWEEN ? AND ?
    GROUP BY p.id
    ORDER BY p.created_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute([$start_date, $end_date]);
$pesanan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistik
$totalPesanan = count($pesanan);
$totalPendapatan = 0;
$totalPending = 0;
$totalSelesai = 0;

foreach ($pesanan as $p) {
    $totalPendapatan += $p['total_harga'];
    if ($p['status'] == 'pending') $totalPending++;
    if ($p['status'] == 'selesai') $totalSelesai++;
}

// Rata-rata per hari
$daysDiff = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;
$rataRata = $daysDiff > 0 ? round($totalPendapatan / $daysDiff, 0) : 0;

$page_title = 'Laporan - ' . ($settings['nama_toko'] ?? 'Kafetamin');

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
        
        .stats-card {
            border: none; border-radius: 12px; transition: transform 0.2s;
        }
        .stats-card:hover { transform: translateY(-5px); }
        .stats-card .icon {
            width: 50px; height: 50px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
        }
        .stats-card .number { font-size: 1.8rem; font-weight: bold; margin: 5px 0; }
        .stats-card .label { color: #6c757d; font-size: 0.9rem; }
        .bg-soft-primary { background: #e3f2fd; color: #1976d2; }
        .bg-soft-success { background: #e8f5e9; color: #388e3c; }
        .bg-soft-warning { background: #fff3e0; color: #f57c00; }
        .bg-soft-info { background: #e0f7fa; color: #00838f; }
        
        .filter-card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .filter-btn {
            padding: 8px 16px; border-radius: 8px; border: 2px solid #dee2e6;
            background: white; color: #6c757d; transition: all 0.3s;
            cursor: pointer; font-weight: 500; text-decoration: none;
            display: inline-block;
        }
        .filter-btn:hover { background: #e9ecef; }
        .filter-btn.active {
            background: #2c3e50; color: white; border-color: #2c3e50;
        }
        
        .sidebar-toggle {
            display: none; position: fixed; top: 15px; left: 15px;
            z-index: 1001; background: #2c3e50; color: white;
            border: none; padding: 10px 15px; border-radius: 8px;
            font-size: 1.2rem; cursor: pointer;
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
            .sidebar-overlay.show { display: block; }
            .main-wrapper { margin-left: 0; padding-top: 70px; }
            .stats-card .number { font-size: 1.4rem; }
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
                <h4 class="mb-0"><i class="fas fa-file-alt"></i> Laporan</h4>
                <small class="text-muted">Periode: <?php echo $label; ?></small>
            </div>
            <div><span class="text-muted"><i class="fas fa-calendar-alt"></i> <?php echo date('d F Y'); ?></span></div>
        </div>
        
        <!-- Filter -->
        <div class="card filter-card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-12">
                        <label class="form-label fw-semibold mb-2"><i class="fas fa-filter"></i> Filter Periode:</label>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="?filter=hari_ini" class="filter-btn <?php echo $filter == 'hari_ini' ? 'active' : ''; ?>">📅 Hari Ini</a>
                            <a href="?filter=7_hari" class="filter-btn <?php echo $filter == '7_hari' ? 'active' : ''; ?>">📊 7 Hari</a>
                            <a href="?filter=14_hari" class="filter-btn <?php echo $filter == '14_hari' ? 'active' : ''; ?>">📊 14 Hari</a>
                            <a href="?filter=30_hari" class="filter-btn <?php echo $filter == '30_hari' ? 'active' : ''; ?>">📊 30 Hari</a>
                            <a href="?filter=bulan_ini" class="filter-btn <?php echo $filter == 'bulan_ini' ? 'active' : ''; ?>">📆 Bulan Ini</a>
                            <a href="?filter=bulan_kemarin" class="filter-btn <?php echo $filter == 'bulan_kemarin' ? 'active' : ''; ?>">📆 Bulan Kemarin</a>
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>" placeholder="Tgl Mulai">
                            </div>
                            <div class="col-md-4">
                                <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>" placeholder="Tgl Akhir">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" name="filter" value="custom" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Terapkan
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Statistik -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card stats-card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><div class="number"><?php echo $totalPesanan; ?></div><div class="label">Total Pesanan</div></div>
                            <div class="icon bg-soft-primary"><i class="fas fa-shopping-cart"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><div class="number text-success">Rp <?php echo number_format($totalPendapatan, 0, ',', '.'); ?></div><div class="label">Total Pendapatan</div></div>
                            <div class="icon bg-soft-success"><i class="fas fa-money-bill"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><div class="number text-warning"><?php echo $totalPending; ?></div><div class="label">Pending</div></div>
                            <div class="icon bg-soft-warning"><i class="fas fa-clock"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><div class="number text-info">Rp <?php echo number_format($rataRata, 0, ',', '.'); ?></div><div class="label">Rata-rata / Hari</div></div>
                            <div class="icon bg-soft-info"><i class="fas fa-chart-line"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Daftar Pesanan -->
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-list"></i> Daftar Pesanan</h6>
                <span class="text-muted small"><?php echo $totalPesanan; ?> pesanan ditemukan</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>TRX ID</th>
                                <th>Pemesan</th>
                                <th>Tipe</th>
                                <th>Waktu</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pesanan)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada pesanan</td></tr>
                            <?php else: foreach ($pesanan as $p): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($p['trx_id']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($p['nama_pemesan']); ?></td>
                                    <td><span class="badge bg-<?php echo $p['tipe'] == 'dine-in' ? 'info' : 'secondary'; ?>"><?php echo ucfirst(str_replace('-', ' ', $p['tipe'])); ?></span></td>
                                    <td><small><?php echo date('d/m/Y H:i', strtotime($p['created_at'])); ?></small></td>
                                    <td><strong class="text-success">Rp <?php echo number_format($p['total_harga'], 0, ',', '.'); ?></strong></td>
                                    <td><span class="badge bg-<?php echo $p['status'] == 'selesai' ? 'success' : 'warning'; ?>"><?php echo ucfirst($p['status']); ?></span></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
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