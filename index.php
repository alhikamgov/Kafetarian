<?php
require_once 'config/database.php';

// Ambil pengaturan
$settings = [];
try {
    $stmt = $pdo->query("SELECT key_name, value FROM pengaturan");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['key_name']] = $row['value'];
    }
} catch(PDOException $e) {
    $settings = [];
}

$namaToko = $settings['nama_toko'] ?? 'Kafetamin';

// Set page title
$page_title = $namaToko . ' - Pemesanan Makanan & Minuman';
?>
<?php include 'includes/header.php'; ?>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-coffee"></i> <?php echo htmlspecialchars($settings['nama_toko']); ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link active" href="index.php"><i class="fas fa-home"></i> Beranda</a></li>
                <li class="nav-item"><a class="nav-link" href="pesanan.php"><i class="fas fa-receipt"></i> Pesanan</a></li>
                <li class="nav-item"><button class="cart-btn" onclick="openCart()"><i class="fas fa-shopping-cart"></i><span class="cart-badge" id="cartCount">0</span></button></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="container main-content py-4">
    <!-- Banner -->
    <div class="banner banner-height mb-4">
        <?php if (!empty($settings['banner']) && file_exists('assets/images/banner/' . $settings['banner'])): ?>
            <img src="assets/images/banner/<?php echo $settings['banner']; ?>" alt="Banner">
        <?php else: ?>
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); width: 100%; height: 100%; position: absolute;"></div>
        <?php endif; ?>
        <div class="banner-overlay"></div>
        <div class="banner-content text-center p-4">
            <h1 class="display-4 fw-bold">☕ <?php echo htmlspecialchars($settings['nama_toko']); ?></h1>
            <p class="lead"><?php echo htmlspecialchars($settings['deskripsi']); ?></p>
        </div>
    </div>

    <!-- Search -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-6 col-lg-5">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" class="form-control border-start-0" id="searchMenu" placeholder="Cari menu..." onkeyup="searchMenu(this.value)">
            </div>
        </div>
    </div>

    <!-- Kategori -->
    <div class="text-center mb-4">
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <button class="kategori-btn active" data-kategori="semua" onclick="loadMenu('semua')"><i class="fas fa-th-large"></i> Semua</button>
            <button class="kategori-btn" data-kategori="makanan" onclick="loadMenu('makanan')"><i class="fas fa-utensils"></i> Makanan</button>
            <button class="kategori-btn" data-kategori="minuman" onclick="loadMenu('minuman')"><i class="fas fa-coffee"></i> Minuman</button>
        </div>
    </div>

    <!-- Menu Grid -->
    <div class="row row-cols-2 row-cols-md-4 g-3" id="menuGrid"></div>
</div>

<!-- Floating Cart -->
<div class="floating-cart" id="floatingCart" onclick="openCart()" style="display: none;">
    <i class="fas fa-shopping-cart"></i>
    <span class="cart-badge-floating" id="floatingCartCount">0</span>
</div>

<!-- Cart Modal -->
<div class="cart-modal" id="cartModal">
    <div class="cart-content">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h5 class="mb-0"><i class="fas fa-shopping-cart text-primary"></i> Keranjang</h5>
            <button class="btn-close" onclick="closeCart()"></button>
        </div>
        <div id="cartItems"></div>
        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
            <strong>Total:</strong>
            <strong class="text-success" id="cartTotal">Rp 0</strong>
        </div>
        <form onsubmit="submitOrder(event)" class="mt-3" id="orderForm">
            <div class="mb-3">
                <label for="nama" class="form-label fw-semibold"><i class="fas fa-user"></i> Pemesan</label>
                <input type="text" class="form-control" id="nama" name="nama" required placeholder="Nama Pemesan / No. Meja">
            </div>
            <div class="mb-3">
                <label for="tipe" class="form-label fw-semibold"><i class="fas fa-utensils"></i> Tipe Pesanan</label>
                <select class="form-select" id="tipe" name="tipe" required>
                    <option value="dine-in">🏠 Dine-In</option>
                    <option value="take-away">🛍️ Take Away</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success w-100 py-2 btn-pesan">
                <i class="fas fa-check-circle"></i> Pesan Sekarang
            </button>
        </form>
    </div>
</div>

<!-- Footer -->
<footer class="footer-custom mt-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h5><i class="fas fa-coffee text-warning"></i> <?php echo htmlspecialchars($settings['nama_toko']); ?></h5>
                <p class="text-muted small"><?php echo htmlspecialchars($settings['deskripsi']); ?></p>
            </div>
            <div class="col-md-4">
                <h5><i class="fas fa-map-marker-alt text-danger"></i> Alamat</h5>
                <p class="text-muted small mb-0"><?php echo htmlspecialchars($settings['alamat']); ?></p>
                <p class="text-muted small"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($settings['telepon']); ?></p>
                <p class="text-muted small"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($settings['email']); ?></p>
            </div>
            <div class="col-md-4">
                <h5><i class="fas fa-clock text-info"></i> Jam Operasional</h5>
                <p class="text-muted small mb-0"><?php echo htmlspecialchars($settings['hari_buka']); ?>: <?php echo htmlspecialchars($settings['jam_buka']); ?> - <?php echo htmlspecialchars($settings['jam_tutup']); ?></p>
                <p class="text-muted small"><?php echo htmlspecialchars($settings['hari_akhir_pekan']); ?></p>
            </div>
        </div>
        <hr class="mt-3">
        <div class="text-center"><p class="text-muted small mb-0"><?php echo htmlspecialchars($settings['footer_text']); ?></p></div>
    </div>
</footer>

<?php include 'includes/footer.php'; ?>