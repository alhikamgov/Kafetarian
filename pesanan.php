<?php
$page_title = 'Daftar Pesanan';
require_once 'config/database.php';

$settings = [];
$stmt = $pdo->query("SELECT key_name, value FROM pengaturan");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key_name']] = $row['value'];
}

// Ambil daftar TRX ID dari cookie
$trx_list = [];
if (isset($_COOKIE['order_history'])) {
    $trx_list = explode(',', $_COOKIE['order_history']);
    $trx_list = array_filter($trx_list); // hapus kosong
}

// Jika ada TRX di cookie, ambil data pesanan tersebut
$pesanan = [];
if (!empty($trx_list)) {
    // Escape untuk keamanan
    $placeholders = implode(',', array_fill(0, count($trx_list), '?'));
    $query = "
        SELECT p.*, 
               GROUP_CONCAT(CONCAT(m.nama, ' (', d.jumlah, ' pcs)') SEPARATOR ', ') as detail
        FROM pesanan p
        LEFT JOIN detail_pesanan d ON p.id = d.pesanan_id
        LEFT JOIN menu m ON d.menu_id = m.id
        WHERE p.trx_id IN ($placeholders)
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($trx_list);
    $pesanan = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$statusLabels = [
    'dikirim' => ['label' => 'Dikirim', 'badge' => 'info', 'icon' => 'fa-paper-plane'],
    'dibayar' => ['label' => 'Dibayar', 'badge' => 'success', 'icon' => 'fa-money-bill'],
    'dibuat' => ['label' => 'Dibuat', 'badge' => 'warning', 'icon' => 'fa-utensils'],
    'selesai' => ['label' => 'Selesai', 'badge' => 'secondary', 'icon' => 'fa-check-circle']
];
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
                <li class="nav-item"><a class="nav-link active" href="pesanan.php"><i class="fas fa-receipt"></i> Pesanan</a></li>
                <li class="nav-item"><button class="cart-btn" onclick="openCart()"><i class="fas fa-shopping-cart"></i><span class="cart-badge" id="cartCount">0</span></button></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container main-content py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-receipt"></i> Daftar Pesanan Saya</h4>
        <a href="index.php" class="btn btn-primary"><i class="fas fa-plus"></i> Pesan Lagi</a>
    </div>
    
    <?php if (empty($pesanan)): ?>
        <div class="text-center py-5">
            <i class="fas fa-inbox" style="font-size: 4rem; color: #ccc;"></i>
            <p class="text-muted mt-3">Belum ada pesanan</p>
            <a href="index.php" class="btn btn-primary">Mulai Pesan</a>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($pesanan as $p): 
                $statusInfo = $statusLabels[$p['status']] ?? $statusLabels['dikirim'];
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <strong class="text-primary"><?php echo htmlspecialchars($p['trx_id']); ?></strong>
                            <span class="badge bg-<?php echo $statusInfo['badge']; ?>">
                                <i class="fas <?php echo $statusInfo['icon']; ?>"></i>
                                <?php echo $statusInfo['label']; ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><i class="fas fa-user"></i> <?php echo htmlspecialchars($p['nama_pemesan']); ?></p>
                            <p class="mb-1"><i class="fas fa-utensils"></i> <?php echo ucfirst(str_replace('-', ' ', $p['tipe'])); ?></p>
                            <p class="mb-1"><i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($p['created_at'])); ?></p>
                            <p class="mb-2"><i class="fas fa-list"></i> <?php echo htmlspecialchars($p['detail']); ?></p>
                            <hr>
                            <h5 class="text-success">Rp <?php echo number_format($p['total_harga'], 0, ',', '.'); ?></h5>
                        </div>
                        <div class="card-footer bg-white">
                            <button class="btn btn-sm btn-secondary" onclick="printStruk('<?php echo $p['trx_id']; ?>')">
                                <i class="fas fa-print"></i> Print
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    function printStruk(trxId) {
        window.open(`print_struk.php?trx_id=${trxId}`, '_blank', 'width=400,height=600');
    }
</script>

<?php include 'includes/footer.php'; ?>