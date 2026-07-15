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

// Ambil semua menu untuk POS
$allMenu = $pdo->query("SELECT * FROM menu ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);

// Ambil kategori menu
$stmt = $pdo->query("SELECT DISTINCT kategori FROM menu");
$kategori = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Statistik singkat
$totalPesananHari = $pdo->query("SELECT COUNT(*) as total FROM pesanan WHERE DATE(created_at) = CURDATE()")->fetch()['total'] ?? 0;
$omzetHari = $pdo->query("SELECT SUM(total_harga) as total FROM pesanan WHERE DATE(created_at) = CURDATE()")->fetch()['total'] ?? 0;

$page_title = 'POS Kasir - ' . ($settings['nama_toko'] ?? 'Kafetamin');

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
        
        /* ===== MAIN WRAPPER ===== */
        .main-wrapper {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }
        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; padding-top: 70px; }
        }
        
        /* ===== POS LAYOUT ===== */
        .pos-container {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .pos-menu {
            flex: 1 1 60%;
            min-width: 300px;
        }
        .pos-cart {
            flex: 1 1 35%;
            min-width: 280px;
            max-width: 400px;
        }
        @media (max-width: 992px) {
            .pos-menu { flex: 1 1 100%; }
            .pos-cart { flex: 1 1 100%; max-width: 100%; }
        }
        
        /* ===== STATS CARD ===== */
        .stats-card {
            border: none; border-radius: 12px; transition: transform 0.2s;
        }
        .stats-card:hover { transform: translateY(-3px); }
        .stats-card .number { font-size: 1.5rem; font-weight: bold; }
        
        /* ===== MENU GRID ===== */
        .menu-grid-pos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 10px;
            max-height: 450px;
            overflow-y: auto;
            padding: 5px;
        }
        .menu-item-pos {
            background: white;
            border-radius: 10px;
            padding: 10px 8px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.2s;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .menu-item-pos:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-color: #e67e22;
        }
        .menu-item-pos .thumb {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            margin: 0 auto 5px;
            background: #f0f0f0;
        }
        .menu-item-pos .name {
            font-size: 0.8rem;
            font-weight: 600;
            color: #2c3e50;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .menu-item-pos .price {
            font-size: 0.75rem;
            color: #e67e22;
            font-weight: bold;
        }
        .menu-item-pos .btn-add {
            margin-top: 4px;
            padding: 2px 10px;
            font-size: 0.65rem;
            border-radius: 20px;
        }
        
        /* ===== CART ===== */
        .cart-container {
            background: white;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            position: sticky;
            top: 80px;
            max-height: calc(100vh - 120px);
            display: flex;
            flex-direction: column;
        }
        .cart-container .cart-items {
            flex: 1;
            overflow-y: auto;
            max-height: 280px;
        }
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.85rem;
        }
        .cart-item .qty-control {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .cart-item .qty-control button {
            width: 22px;
            height: 22px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.65rem;
        }
        .cart-total {
            border-top: 2px solid #e67e22;
            padding-top: 10px;
            margin-top: 10px;
            font-weight: bold;
            font-size: 1.1rem;
        }
        .cart-empty {
            text-align: center;
            color: #999;
            padding: 20px 0;
        }
        
        /* ===== CATEGORY FILTER ===== */
        .cat-btn {
            padding: 4px 14px;
            border-radius: 20px;
            border: 1px solid #ddd;
            background: white;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .cat-btn:hover { background: #e9ecef; }
        .cat-btn.active { background: #2c3e50; color: white; border-color: #2c3e50; }
        
        /* ===== SEARCH ===== */
        .search-pos input {
            border-radius: 20px;
            padding-left: 15px;
            font-size: 0.9rem;
        }
        
        /* ===== TOAST NOTIFICATION ===== */
        .toast-pos {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            background: white;
            border-radius: 10px;
            padding: 15px 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            border-left: 4px solid #28a745;
            animation: slideIn 0.3s ease;
            max-width: 350px;
        }
        .toast-pos.error { border-left-color: #dc3545; }
        .toast-pos.warning { border-left-color: #ffc107; }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-wrapper">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0"><i class="fas fa-cash-register text-primary"></i> POS Kasir</h4>
                <small class="text-muted">Layani pembelian langsung di kasir</small>
            </div>
            <div>
                <span class="text-muted me-3">
                    <i class="fas fa-shopping-cart"></i> <?php echo $totalPesananHari; ?> transaksi hari ini
                </span>
                <span class="text-muted">
                    <i class="fas fa-money-bill"></i> Rp <?php echo number_format($omzetHari, 0, ',', '.'); ?>
                </span>
            </div>
        </div>
        
        <!-- POS: Menu + Cart -->
        <div class="pos-container">
            <!-- Menu -->
            <div class="pos-menu">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="mb-0"><i class="fas fa-utensils"></i> Pilih Menu</h6>
                        <div class="d-flex gap-1 flex-wrap">
                            <button class="cat-btn active" data-cat="all" onclick="filterKategori('all')">Semua</button>
                            <?php foreach ($kategori as $cat): ?>
                                <button class="cat-btn" data-cat="<?php echo $cat; ?>" onclick="filterKategori('<?php echo $cat; ?>')">
                                    <?php echo ucfirst($cat); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Search -->
                        <div class="search-pos mb-2">
                            <input type="text" class="form-control" id="searchMenuPos" placeholder="🔍 Cari menu..." onkeyup="filterMenuPos(this.value)">
                        </div>
                        <!-- Grid -->
                        <div class="menu-grid-pos" id="menuGridPos">
                            <?php foreach ($allMenu as $m): ?>
                            <div class="menu-item-pos" data-name="<?php echo strtolower($m['nama']); ?>" data-cat="<?php echo $m['kategori']; ?>" data-id="<?php echo $m['id']; ?>" onclick="tambahKeKeranjang(<?php echo $m['id']; ?>, '<?php echo addslashes($m['nama']); ?>', <?php echo $m['harga']; ?>)">
                                <?php if ($m['thumbnail']): ?>
                                    <img src="../assets/images/<?php echo $m['thumbnail']; ?>" class="thumb" alt="<?php echo $m['nama']; ?>">
                                <?php else: ?>
                                    <div class="thumb d-flex align-items-center justify-content-center bg-light"><i class="fas fa-utensils" style="font-size:1.8rem;color:#ccc;"></i></div>
                                <?php endif; ?>
                                <div class="name"><?php echo htmlspecialchars($m['nama']); ?></div>
                                <div class="price">Rp <?php echo number_format($m['harga'], 0, ',', '.'); ?></div>
                                <button class="btn btn-sm btn-success btn-add" onclick="event.stopPropagation(); tambahKeKeranjang(<?php echo $m['id']; ?>, '<?php echo addslashes($m['nama']); ?>', <?php echo $m['harga']; ?>)">
                                    <i class="fas fa-plus"></i> Tambah
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Keranjang -->
            <div class="pos-cart">
                <div class="cart-container">
                    <h6 class="mb-2"><i class="fas fa-shopping-cart text-primary"></i> Keranjang</h6>
                    <div class="cart-items" id="cartItems">
                        <div class="cart-empty"><i class="fas fa-cart-plus" style="font-size:2rem;display:block;margin-bottom:10px;"></i>Belum ada item</div>
                    </div>
                    <div class="cart-total" id="cartTotal">
                        Total: Rp 0
                    </div>
                    <hr>
                    <form id="formBayar">
                        <div class="mb-2">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;"><i class="fas fa-user"></i> Pemesan</label>
                            <input type="text" class="form-control form-control-sm" id="namaPelanggan" placeholder="Nama pemesan / No. Meja" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;"><i class="fas fa-utensils"></i> Tipe</label>
                            <select class="form-select form-select-sm" id="tipePesanan">
                                <option value="dine-in">🏠 Dine-In</option>
                                <option value="take-away">🛍️ Take Away</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-success w-100" onclick="prosesBayar()">
                            <i class="fas fa-money-bill"></i> Bayar Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // ===== SIDEBAR =====
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
        
        // ===== KERANJANG POS =====
        let cart = [];
        
        function tambahKeKeranjang(id, nama, harga) {
            const existing = cart.find(item => item.id === id);
            if (existing) {
                existing.qty++;
            } else {
                cart.push({ id, nama, harga, qty: 1 });
            }
            renderCart();
            showToast(nama + ' ditambahkan!', 'success');
        }
        
        function renderCart() {
            const container = document.getElementById('cartItems');
            const totalEl = document.getElementById('cartTotal');
            if (cart.length === 0) {
                container.innerHTML = '<div class="cart-empty"><i class="fas fa-cart-plus" style="font-size:2rem;display:block;margin-bottom:10px;"></i>Belum ada item</div>';
                totalEl.innerHTML = 'Total: Rp 0';
                return;
            }
            let html = '';
            let total = 0;
            cart.forEach((item, index) => {
                const subtotal = item.harga * item.qty;
                total += subtotal;
                html += `
                    <div class="cart-item">
                        <span>${item.nama} <span class="text-muted">x${item.qty}</span></span>
                        <span>
                            Rp ${formatRupiah(subtotal)}
                            <div class="qty-control d-inline-flex ms-2">
                                <button onclick="ubahQty(${index}, -1)">-</button>
                                <span class="mx-1">${item.qty}</span>
                                <button onclick="ubahQty(${index}, 1)">+</button>
                                <button class="text-danger border-0 bg-transparent" onclick="hapusItem(${index})"><i class="fas fa-trash"></i></button>
                            </div>
                        </span>
                    </div>
                `;
            });
            container.innerHTML = html;
            totalEl.innerHTML = `Total: Rp ${formatRupiah(total)}`;
        }
        
        function ubahQty(index, delta) {
            if (cart[index].qty + delta <= 0) {
                cart.splice(index, 1);
            } else {
                cart[index].qty += delta;
            }
            renderCart();
        }
        
        function hapusItem(index) {
            cart.splice(index, 1);
            renderCart();
        }
        
        function formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID').format(angka);
        }
        
        // ===== SEARCH & FILTER MENU =====
        function filterMenuPos(keyword) {
            keyword = keyword.toLowerCase();
            const items = document.querySelectorAll('.menu-item-pos');
            items.forEach(el => {
                const name = el.getAttribute('data-name');
                if (name.includes(keyword)) {
                    el.style.display = '';
                } else {
                    el.style.display = 'none';
                }
            });
        }
        
        let currentCategory = 'all';
        function filterKategori(category) {
            currentCategory = category;
            document.querySelectorAll('.cat-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.cat === category);
            });
            const items = document.querySelectorAll('.menu-item-pos');
            items.forEach(el => {
                const cat = el.getAttribute('data-cat');
                if (category === 'all' || cat === category) {
                    el.style.display = '';
                } else {
                    el.style.display = 'none';
                }
            });
        }
        
        // ===== TOAST =====
        function showToast(message, type = 'success') {
            const old = document.querySelector('.toast-pos');
            if (old) old.remove();
            const toast = document.createElement('div');
            toast.className = `toast-pos ${type}`;
            toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle text-success' : type === 'error' ? 'fa-exclamation-circle text-danger' : 'fa-exclamation-triangle text-warning'}"></i> ${message}`;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 2000);
        }
        
        // ===== PROSES BAYAR =====
        function prosesBayar() {
            if (cart.length === 0) {
                showToast('Keranjang kosong!', 'error');
                return;
            }
            const nama = document.getElementById('namaPelanggan').value.trim();
            if (!nama) {
                showToast('Masukkan nama pelanggan!', 'error');
                document.getElementById('namaPelanggan').focus();
                return;
            }
            const tipe = document.getElementById('tipePesanan').value;
            const data = {
                nama: nama,
                tipe: tipe,
                items: cart.map(item => ({ id: item.id, nama: item.nama, harga: item.harga, jumlah: item.qty }))
            };
            
            const btn = document.querySelector('#formBayar button');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            
            fetch('pos_bayar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    showToast('✅ Pembayaran berhasil! TRX: ' + result.trx_id, 'success');
                    cart = [];
                    renderCart();
                    document.getElementById('namaPelanggan').value = '';
                    window.open('../print_struk.php?trx_id=' + result.trx_id, '_blank', 'width=400,height=600');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('❌ Gagal: ' + result.message, 'error');
                }
            })
            .catch(err => {
                showToast('Terjadi kesalahan server!', 'error');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-money-bill"></i> Bayar Sekarang';
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>