<?php
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

// Update page title
$page_title = 'Kelola Pesanan - ' . $namaToko;

// Cek login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$filter = $_GET['filter'] ?? 'semua';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Query untuk menghitung total data
$countQuery = "
    SELECT COUNT(DISTINCT p.id) as total
    FROM pesanan p
    LEFT JOIN detail_pesanan d ON p.id = d.pesanan_id
    LEFT JOIN menu m ON d.menu_id = m.id
";

$countConditions = [];
$countParams = [];

if ($filter !== 'semua') {
    $countConditions[] = "p.status = ?";
    $countParams[] = $filter;
}

if ($search) {
    $countConditions[] = "(p.trx_id LIKE ? OR p.nama_pemesan LIKE ?)";
    $countParams[] = "%$search%";
    $countParams[] = "%$search%";
}

if (!empty($countConditions)) {
    $countQuery .= " WHERE " . implode(" AND ", $countConditions);
}

$stmt = $pdo->prepare($countQuery);
$stmt->execute($countParams);
$totalData = $stmt->fetch()['total'];
$totalPages = ceil($totalData / $limit);

// Query untuk mengambil data dengan limit
$query = "
    SELECT p.*, 
           GROUP_CONCAT(CONCAT(m.nama, ' (', d.jumlah, ' pcs)') SEPARATOR ', ') as detail
    FROM pesanan p
    LEFT JOIN detail_pesanan d ON p.id = d.pesanan_id
    LEFT JOIN menu m ON d.menu_id = m.id
";

$conditions = [];
$params = [];

if ($filter !== 'semua') {
    $conditions[] = "p.status = ?";
    $params[] = $filter;
}

if ($search) {
    $conditions[] = "(p.trx_id LIKE ? OR p.nama_pemesan LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " GROUP BY p.id ORDER BY p.created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$pesanan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistik per status
$totalPesanan = $pdo->query("SELECT COUNT(*) as total FROM pesanan")->fetch()['total'] ?? 0;
$statusCount = [];
$statuses = ['dikirim', 'dibayar', 'dibuat', 'selesai'];
foreach ($statuses as $s) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pesanan WHERE status = ?");
    $stmt->execute([$s]);
    $statusCount[$s] = $stmt->fetch()['total'] ?? 0;
}

// Label status untuk tampilan
$statusLabels = [
    'dikirim' => ['label' => 'Dikirim', 'badge' => 'info', 'icon' => 'fa-paper-plane'],
    'dibayar' => ['label' => 'Dibayar', 'badge' => 'success', 'icon' => 'fa-money-bill'],
    'dibuat' => ['label' => 'Dibuat', 'badge' => 'warning', 'icon' => 'fa-utensils'],
    'selesai' => ['label' => 'Selesai', 'badge' => 'secondary', 'icon' => 'fa-check-circle']
];

// Fungsi untuk generate pagination
function generatePagination($currentPage, $totalPages, $filter, $search) {
    $html = '<ul class="pagination pagination-sm justify-content-center mb-0">';
    
    // Tombol Previous
    if ($currentPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="?page=' . ($currentPage - 1) . '&filter=' . $filter . '&search=' . urlencode($search) . '">&laquo; Prev</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">&laquo; Prev</span></li>';
    }
    
    // Nomor halaman
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    if ($startPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="?page=1&filter=' . $filter . '&search=' . urlencode($search) . '">1</a></li>';
        if ($startPage > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i == $currentPage) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="?page=' . $i . '&filter=' . $filter . '&search=' . urlencode($search) . '">' . $i . '</a></li>';
        }
    }
    
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '&filter=' . $filter . '&search=' . urlencode($search) . '">' . $totalPages . '</a></li>';
    }
    
    // Tombol Next
    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item"><a class="page-link" href="?page=' . ($currentPage + 1) . '&filter=' . $filter . '&search=' . urlencode($search) . '">Next &raquo;</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Next &raquo;</span></li>';
    }
    
    $html .= '</ul>';
    return $html;
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
        
        .stats-card {
            border: none; border-radius: 12px; transition: transform 0.2s;
            cursor: default;
        }
        .stats-card:hover { transform: translateY(-5px); }
        .stats-card .number { font-size: 1.8rem; font-weight: bold; margin: 5px 0; }
        .stats-card .label { font-size: 0.9rem; opacity: 0.9; }
        
        .detail-item {
            display: flex; justify-content: space-between;
            padding: 8px 0; border-bottom: 1px solid #f0f0f0;
        }
        .detail-item:last-child { border-bottom: none; }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .btn-action {
            font-size: 0.7rem;
            padding: 3px 8px;
        }
        
        .pagination .page-link {
            color: #6C63FF;
            border-radius: 8px;
            margin: 0 3px;
            border: 1px solid #e9ecef;
        }
        .pagination .page-link:hover {
            background: #6C63FF;
            color: white;
            border-color: #6C63FF;
        }
        .pagination .page-item.active .page-link {
            background: #6C63FF;
            border-color: #6C63FF;
            color: white;
        }
        .pagination .page-item.disabled .page-link {
            color: #adb5bd;
        }
        .info-text {
            font-size: 0.85rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0"><i class="fas fa-receipt"></i> Kelola Pesanan</h4>
                <small class="text-muted">Manage semua pesanan pelanggan</small>
            </div>
            <div><span class="text-muted"><i class="fas fa-calendar-alt"></i> <?php echo date('d F Y'); ?></span></div>
        </div>
        
        <!-- Statistik Status -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card stats-card shadow-sm bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><div class="number"><?php echo $statusCount['dikirim'] ?? 0; ?></div><div class="label">📨 Dikirim</div></div>
                            <i class="fas fa-paper-plane" style="font-size: 2rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card shadow-sm bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><div class="number"><?php echo $statusCount['dibayar'] ?? 0; ?></div><div class="label">💰 Dibayar</div></div>
                            <i class="fas fa-money-bill" style="font-size: 2rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card shadow-sm bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><div class="number"><?php echo $statusCount['dibuat'] ?? 0; ?></div><div class="label">🍳 Dibuat</div></div>
                            <i class="fas fa-utensils" style="font-size: 2rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card shadow-sm bg-secondary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><div class="number"><?php echo $statusCount['selesai'] ?? 0; ?></div><div class="label">✅ Selesai</div></div>
                            <i class="fas fa-check-circle" style="font-size: 2rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter & Search -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Filter Status</label>
                        <select name="filter" class="form-select" onchange="this.form.submit()">
                            <option value="semua" <?php echo $filter == 'semua' ? 'selected' : ''; ?>>📋 Semua</option>
                            <option value="dikirim" <?php echo $filter == 'dikirim' ? 'selected' : ''; ?>>📨 Dikirim</option>
                            <option value="dibayar" <?php echo $filter == 'dibayar' ? 'selected' : ''; ?>>💰 Dibayar</option>
                            <option value="dibuat" <?php echo $filter == 'dibuat' ? 'selected' : ''; ?>>🍳 Dibuat</option>
                            <option value="selesai" <?php echo $filter == 'selesai' ? 'selected' : ''; ?>>✅ Selesai</option>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label fw-semibold">Cari</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-start-0" 
                                   placeholder="Cari TRX atau nama..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Cari
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Daftar Pesanan -->
        <?php if (empty($pesanan)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox" style="font-size: 4rem; color: #ccc;"></i>
                <p class="text-muted mt-3">Tidak ada pesanan</p>
            </div>
        <?php else: ?>
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>TRX ID</th>
                                    <th>Pemesan</th>
                                    <th>Tipe</th>
                                    <th>Detail</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pesanan as $p): 
                                    $statusInfo = $statusLabels[$p['status']] ?? $statusLabels['dikirim'];
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($p['trx_id']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-clock"></i> 
                                                <?php echo date('d/m/Y H:i', strtotime($p['created_at'])); ?>
                                            </small>
                                        </td>
                                        <td><?php echo htmlspecialchars($p['nama_pemesan']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $p['tipe'] == 'dine-in' ? 'info' : 'secondary'; ?>">
                                                <?php echo ucfirst(str_replace('-', ' ', $p['tipe'])); ?>
                                            </span>
                                        </td>
                                        <td><small><?php echo htmlspecialchars($p['detail']); ?></small></td>
                                        <td>
                                            <strong class="text-success">
                                                Rp <?php echo number_format($p['total_harga'], 0, ',', '.'); ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <span class="status-badge bg-<?php echo $statusInfo['badge']; ?> text-white">
                                                <i class="fas <?php echo $statusInfo['icon']; ?>"></i>
                                                <?php echo $statusInfo['label']; ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <!-- Tombol Detail -->
                                                <button class="btn btn-info btn-action" 
                                                        onclick="showDetail(<?php echo $p['id']; ?>)"
                                                        title="Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <!-- Tombol Aksi Status -->
                                                <?php if ($p['status'] == 'dikirim'): ?>
                                                    <button class="btn btn-success btn-action" 
                                                            onclick="updateStatus(<?php echo $p['id']; ?>, 'dibayar')"
                                                            title="Bayar">
                                                        <i class="fas fa-money-bill"></i> Bayar
                                                    </button>
                                                <?php elseif ($p['status'] == 'dibayar'): ?>
                                                    <button class="btn btn-warning btn-action" 
                                                            onclick="updateStatus(<?php echo $p['id']; ?>, 'dibuat')"
                                                            title="Buat">
                                                        <i class="fas fa-utensils"></i> Buat
                                                    </button>
                                                <?php elseif ($p['status'] == 'dibuat'): ?>
                                                    <button class="btn btn-secondary btn-action" 
                                                            onclick="updateStatus(<?php echo $p['id']; ?>, 'selesai')"
                                                            title="Selesai">
                                                        <i class="fas fa-check"></i> Selesai
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <!-- Tombol Hapus -->
                                                <button class="btn btn-danger btn-action" 
                                                        onclick="hapusPesanan(<?php echo $p['id']; ?>, '<?php echo $p['trx_id']; ?>')"
                                                        title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                
                                                <!-- Tombol Print -->
                                                <button class="btn btn-secondary btn-action" 
                                                        onclick="printStruk('<?php echo $p['trx_id']; ?>')"
                                                        title="Print">
                                                    <i class="fas fa-print"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="info-text">
                            Menampilkan <?php echo (($page - 1) * $limit) + 1; ?> - 
                            <?php echo min($page * $limit, $totalData); ?> 
                            dari <?php echo $totalData; ?> data
                        </div>
                        <?php echo generatePagination($page, $totalPages, $filter, $search); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal Detail -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-receipt text-primary"></i> Detail Pesanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailModalBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function showDetail(id) {
            const modal = new bootstrap.Modal(document.getElementById('detailModal'));
            const body = document.getElementById('detailModalBody');
            body.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>';
            
            fetch('get_detail_pesanan.php?id=' + id)
                .then(r => r.json())
                .then(data => {
                    let html = '';
                    if (data.pesanan) {
                        const statusLabels = {
                            'dikirim': '📨 Dikirim',
                            'dibayar': '💰 Dibayar',
                            'dibuat': '🍳 Dibuat',
                            'selesai': '✅ Selesai'
                        };
                        html += `<div class="mb-3">
                                <p><strong>TRX ID:</strong> ${data.pesanan.trx_id}</p>
                                <p><strong>Pemesan:</strong> ${data.pesanan.nama_pemesan}</p>
                                <p><strong>Tipe:</strong> ${data.pesanan.tipe.replace('-', ' ').toUpperCase()}</p>
                                <p><strong>Status:</strong> ${statusLabels[data.pesanan.status] || data.pesanan.status}</p>
                                <p><strong>Waktu:</strong> ${new Date(data.pesanan.created_at).toLocaleString('id-ID')}</p>
                                </div><hr><h6 class="mb-3">Detail Item:</h6>`;
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
                })
                .catch(error => {
                    body.innerHTML = '<p class="text-danger">Gagal memuat detail</p>';
                });
        }
        
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
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>