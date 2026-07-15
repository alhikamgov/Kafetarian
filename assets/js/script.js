// ===== STATE =====
let cart = [];
let currentCategory = 'semua';
let menuData = [];

// ===== LOAD MENU =====
async function loadMenu(kategori = 'semua') {
    currentCategory = kategori;
    
    document.querySelectorAll('.kategori-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.kategori === kategori);
    });
    
    showSkeleton();
    
    try {
        const response = await fetch(`get_menu.php?kategori=${kategori}`);
        menuData = await response.json();
        renderMenu(menuData);
    } catch (error) {
        showNotification('Gagal memuat menu', 'error');
    }
}

function renderMenu(menu) {
    const grid = document.getElementById('menuGrid');
    grid.innerHTML = '';
    
    if (menu.length === 0) {
        grid.innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="fas fa-utensils" style="font-size: 3rem; color: #ccc;"></i>
                <p class="text-muted mt-3">Tidak ada menu</p>
            </div>
        `;
        return;
    }
    
    menu.forEach(item => {
        const col = document.createElement('div');
        col.className = 'col';
        col.innerHTML = `
            <div class="menu-item" onclick="addToCart(${item.id})">
                <div class="menu-img">
                    ${item.thumbnail ? 
                        `<img src="assets/images/${item.thumbnail}" alt="${item.nama}" class="menu-img">` :
                        `<div class="menu-img d-flex align-items-center justify-content-center bg-light">
                            <i class="fas fa-utensils" style="font-size: 2.5rem; color: #ccc;"></i>
                        </div>`
                    }
                </div>
                <div class="menu-body">
                    <h6>${item.nama}</h6>
                    <div class="harga">Rp ${formatRupiah(item.harga)}</div>
                </div>
            </div>
        `;
        grid.appendChild(col);
    });
}

function showSkeleton() {
    const grid = document.getElementById('menuGrid');
    grid.innerHTML = Array(4).fill(0).map(() => `
        <div class="col">
            <div class="skeleton-item">
                <div class="skeleton-img"></div>
                <div class="skeleton-body">
                    <div class="skeleton-line medium"></div>
                    <div class="skeleton-line short"></div>
                </div>
            </div>
        </div>
    `).join('');
}

// ===== CART =====
function addToCart(menuId) {
    const item = menuData.find(m => m.id === menuId);
    if (!item) return;
    
    const existing = cart.find(c => c.id === menuId);
    if (existing) {
        existing.jumlah++;
    } else {
        cart.push({ id: item.id, nama: item.nama, harga: item.harga, jumlah: 1 });
    }
    
    updateCartBadge();
    showFloatingCart();
    showNotification(`${item.nama} ditambahkan!`, 'success');
}

function updateCartBadge() {
    const total = cart.reduce((sum, item) => sum + item.jumlah, 0);
    const badge = document.getElementById('cartCount');
    const floatingBadge = document.getElementById('floatingCartCount');
    if (badge) badge.textContent = total;
    if (floatingBadge) floatingBadge.textContent = total;
}

function showFloatingCart() {
    const el = document.getElementById('floatingCart');
    if (!el) return;
    el.style.display = cart.length > 0 ? 'flex' : 'none';
}

function openCart() {
    document.getElementById('cartModal').classList.add('show');
    renderCartItems();
}

function closeCart() {
    document.getElementById('cartModal').classList.remove('show');
}

function renderCartItems() {
    const container = document.getElementById('cartItems');
    const totalEl = document.getElementById('cartTotal');
    
    if (cart.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-shopping-cart" style="font-size: 3rem; color: #ccc;"></i>
                <p class="text-muted mt-2">Keranjang kosong</p>
            </div>
        `;
        totalEl.textContent = 'Rp 0';
        return;
    }
    
    let html = '', total = 0;
    cart.forEach((item, index) => {
        const subtotal = item.harga * item.jumlah;
        total += subtotal;
        html += `
            <div class="cart-item">
                <div class="cart-item-info">
                    <strong>${item.nama}</strong>
                    <br>
                    <small class="text-muted">Rp ${formatRupiah(item.harga)}</small>
                </div>
                <div class="cart-item-controls">
                    <button onclick="updateCartItem(${index}, -1)">-</button>
                    <span>${item.jumlah}</span>
                    <button onclick="updateCartItem(${index}, 1)">+</button>
                    <button onclick="removeCartItem(${index})" class="text-danger border-0 bg-transparent">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    totalEl.textContent = `Rp ${formatRupiah(total)}`;
}

function updateCartItem(index, change) {
    if (cart[index].jumlah + change <= 0) {
        cart.splice(index, 1);
    } else {
        cart[index].jumlah += change;
    }
    renderCartItems();
    updateCartBadge();
    showFloatingCart();
}

function removeCartItem(index) {
    cart.splice(index, 1);
    renderCartItems();
    updateCartBadge();
    showFloatingCart();
}

// ===== SEARCH =====
function searchMenu(keyword) {
    const filtered = menuData.filter(item => 
        item.nama.toLowerCase().includes(keyword.toLowerCase())
    );
    renderMenu(filtered);
}

// ===== ORDER =====
async function submitOrder(event) {
    event.preventDefault();
    
    if (cart.length === 0) {
        showNotification('Keranjang kosong!', 'warning');
        return;
    }
    
    const formData = new FormData(event.target);
    const nama = formData.get('nama');
    const tipe = formData.get('tipe');
    
    if (!nama || !tipe) {
        showNotification('Nama dan tipe pesanan harus diisi!', 'error');
        return;
    }
    
    const data = {
        nama: nama,
        tipe: tipe,
        items: cart
    };
    
    const btn = event.target.querySelector('.btn-pesan');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
    }
    
    try {
        const response = await fetch('proses_pesan.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Pesanan berhasil! 🎉', 'success');
            cart = [];
            updateCartBadge();
            showFloatingCart();
            closeCart();
            
            // Redirect ke halaman konfirmasi
            setTimeout(() => {
                window.location.href = `konfirmasi.php?trx=${result.trx_id}`;
            }, 1000);
        } else {
            showNotification('Gagal: ' + (result.message || 'Terjadi kesalahan'), 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Pesan Sekarang';
            }
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan server', 'error');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle"></i> Pesan Sekarang';
        }
    }
}

// ===== HELPERS =====
function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID').format(angka);
}

function showNotification(message, type = 'success') {
    const old = document.querySelector('.notification');
    if (old) old.remove();
    
    const icons = { 
        success: 'fa-check-circle', 
        error: 'fa-exclamation-circle', 
        warning: 'fa-exclamation-triangle' 
    };
    
    const notif = document.createElement('div');
    notif.className = `notification ${type}`;
    notif.innerHTML = `<i class="fas ${icons[type] || icons.success}"></i> <span>${message}</span>`;
    document.body.appendChild(notif);
    
    setTimeout(() => {
        notif.style.animation = 'slideOutRight 0.5s ease';
        setTimeout(() => notif.remove(), 500);
    }, 3000);
}

function printStruk(trxId) {
    window.open(`print_struk.php?trx_id=${trxId}`, '_blank', 'width=400,height=600');
}

// ===== INIT =====
document.addEventListener('DOMContentLoaded', () => {
    loadMenu('semua');
    const floatingCart = document.getElementById('floatingCart');
    if (floatingCart) {
        floatingCart.style.display = 'none';
    }
});

// Close modal on outside click
document.getElementById('cartModal').addEventListener('click', function(e) {
    if (e.target === this) closeCart();
});