<?php
// Cek pending pesanan untuk badge
$stmt = $pdo->query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'dikirim'");
$pendingPesanan = $stmt->fetch()['total'] ?? 0;

// Ambil nama toko dan tema
$settings = [];
$stmt = $pdo->query("SELECT key_name, value FROM pengaturan");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key_name']] = $row['value'];
}
$namaToko = $settings['nama_toko'] ?? 'Kafetamin';
$tema = $settings['tema'] ?? 'ungu';

// Tentukan halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);

// Warna tema untuk sidebar
$themeColors = [
    'ungu' => ['primary' => '#6C63FF', 'light' => 'rgba(108, 99, 255, 0.15)'],
    'merah' => ['primary' => '#E74C3C', 'light' => 'rgba(231, 76, 60, 0.15)'],
    'hijau' => ['primary' => '#27AE60', 'light' => 'rgba(39, 174, 96, 0.15)'],
    'biru' => ['primary' => '#2980B9', 'light' => 'rgba(41, 128, 185, 0.15)'],
    'pelangi' => ['primary' => '#F39C12', 'light' => 'rgba(243, 156, 18, 0.15)']
];
$color = $themeColors[$tema] ?? $themeColors['ungu'];
?>
<style>
    /* ===== SIDEBAR ===== */
    .sidebar-wrapper {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 250px;
        background: linear-gradient(180deg, #2D3436 0%, #1a1a2e 100%);
        z-index: 1000;
        overflow-y: auto;
        transition: transform 0.3s ease;
        border-right: 2px solid <?php echo $color['primary']; ?>33;
    }
    .sidebar-wrapper .brand {
        padding: 22px 20px;
        color: white;
        font-size: 1.3rem;
        font-weight: 700;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        display: flex;
        align-items: center;
        gap: 12px;
        background: <?php echo $color['light']; ?>;
    }
    .sidebar-wrapper .brand i {
        color: <?php echo $color['primary']; ?>;
        font-size: 1.5rem;
    }
    .sidebar-wrapper .nav-link {
        color: rgba(255,255,255,0.65);
        padding: 14px 20px;
        display: flex;
        align-items: center;
        gap: 14px;
        text-decoration: none;
        transition: all 0.3s;
        border-left: 3px solid transparent;
        margin: 2px 0;
        border-radius: 0 10px 10px 0;
        font-weight: 500;
    }
    .sidebar-wrapper .nav-link:hover {
        color: white;
        background: <?php echo $color['light']; ?>;
        border-left-color: <?php echo $color['primary']; ?>;
    }
    .sidebar-wrapper .nav-link.active {
        color: white;
        background: <?php echo $color['light']; ?>;
        border-left-color: <?php echo $color['primary']; ?>;
        box-shadow: inset 0 2px 10px <?php echo $color['primary']; ?>1A;
    }
    .sidebar-wrapper .nav-link i {
        width: 22px;
        text-align: center;
        font-size: 1.1rem;
    }
    .sidebar-wrapper .nav-link .badge {
        margin-left: auto;
        background: #FF6B6B !important;
        color: white;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
    }
    .sidebar-wrapper .nav-link.text-danger {
        color: rgba(255, 107, 107, 0.7) !important;
    }
    .sidebar-wrapper .nav-link.text-danger:hover {
        color: #FF6B6B !important;
        background: rgba(255, 107, 107, 0.1);
    }
    
    /* ===== SIDEBAR TOGGLE ===== */
    .sidebar-toggle {
        display: none;
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1001;
        background: #2D3436;
        color: white;
        border: none;
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 1.2rem;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    .sidebar-close {
        display: none;
        position: fixed;
        top: 15px;
        right: 15px;
        z-index: 1002;
        background: transparent;
        color: white;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
    }
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(4px);
        z-index: 999;
    }
    
    .main-wrapper {
        margin-left: 250px;
        padding: 24px 30px;
        min-height: 100vh;
        background: linear-gradient(135deg, #F8F9FA 0%, #E8ECF1 100%);
    }
    
    /* ===== THEME ADMIN BUTTONS ===== */
    .btn-primary-custom {
        background: <?php echo $color['primary']; ?> !important;
        border-color: <?php echo $color['primary']; ?> !important;
        color: white !important;
    }
    .btn-primary-custom:hover {
        background: <?php echo $color['primary']; ?>dd !important;
        border-color: <?php echo $color['primary']; ?>dd !important;
    }
    .bg-primary-custom {
        background: <?php echo $color['primary']; ?> !important;
    }
    .text-primary-custom {
        color: <?php echo $color['primary']; ?> !important;
    }
    .border-primary-custom {
        border-color: <?php echo $color['primary']; ?> !important;
    }
    
    /* ===== THEME CARDS ===== */
    .stats-card .icon.bg-purple {
        background: <?php echo $color['light']; ?>;
        color: <?php echo $color['primary']; ?>;
    }
    
    @media (max-width: 768px) {
        .sidebar-wrapper {
            transform: translateX(-100%);
            width: 280px;
        }
        .sidebar-wrapper.open {
            transform: translateX(0);
        }
        .sidebar-toggle {
            display: block;
        }
        .sidebar-close {
            display: block;
        }
        .sidebar-overlay.show {
            display: block;
        }
        .main-wrapper {
            margin-left: 0;
            padding-top: 80px;
            padding: 80px 15px 20px;
        }
    }
</style>

<!-- Sidebar Toggle -->
<button class="sidebar-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<div class="sidebar-wrapper" id="sidebar">
    <button class="sidebar-close" onclick="closeSidebar()">
        <i class="fas fa-times"></i>
    </button>
    
    <div class="brand">
        <i class="fas fa-coffee"></i> <?php echo htmlspecialchars($namaToko); ?>
    </div>
    
    <ul class="nav flex-column">
        <!-- 1. Dashboard -->
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
        </li>
        <!-- 2. POS Kasir -->
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'pos.php' ? 'active' : ''; ?>" href="pos.php">
                <i class="fas fa-cash-register"></i> POS Kasir
            </a>
        </li>
        <!-- 3. Pesanan -->
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'kelola_pesanan.php' ? 'active' : ''; ?>" href="kelola_pesanan.php">
                <i class="fas fa-receipt"></i> Pesanan
                <?php if ($pendingPesanan > 0): ?>
                    <span class="badge"><?php echo $pendingPesanan; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <!-- 4. Menu -->
        <li class="nav-item">
            <a class="nav-link <?php echo in_array($current_page, ['tambah_menu.php', 'edit_menu.php', 'hapus_menu.php']) ? 'active' : ''; ?>" href="tambah_menu.php">
                <i class="fas fa-utensils"></i> Menu
            </a>
        </li>
        <!-- 5. Laporan -->
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'laporan.php' ? 'active' : ''; ?>" href="laporan.php">
                <i class="fas fa-file-alt"></i> Laporan
            </a>
        </li>
        <!-- 6. Pengaturan -->
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'pengaturan.php' ? 'active' : ''; ?>" href="pengaturan.php">
                <i class="fas fa-cog"></i> Pengaturan
            </a>
        </li>
        <!-- Logout -->
        <li class="nav-item mt-3">
            <a class="nav-link text-danger" href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
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