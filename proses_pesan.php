<?php
require_once 'config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || empty($data['nama']) || empty($data['tipe']) || empty($data['items'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

if (!is_array($data['items']) || count($data['items']) === 0) {
    echo json_encode(['success' => false, 'message' => 'Keranjang kosong']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $trx_id = 'TRX-' . date('Ymd') . '-' . rand(1000, 9999);
    
    $total = 0;
    foreach ($data['items'] as $item) {
        if (!isset($item['harga']) || !isset($item['jumlah'])) {
            throw new Exception('Data item tidak lengkap');
        }
        $total += $item['harga'] * $item['jumlah'];
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO pesanan (trx_id, nama_pemesan, tipe, total_harga, status) 
        VALUES (?, ?, ?, ?, 'dikirim')
    ");
    $stmt->execute([$trx_id, $data['nama'], $data['tipe'], $total]);
    $pesanan_id = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare("
        INSERT INTO detail_pesanan (pesanan_id, menu_id, jumlah, subtotal) 
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($data['items'] as $item) {
        $subtotal = $item['harga'] * $item['jumlah'];
        $stmt->execute([$pesanan_id, $item['id'], $item['jumlah'], $subtotal]);
    }
    
    $pdo->commit();
    
    // ===== SIMPAN TRX KE COOKIE =====
    // Ambil cookie yang sudah ada
    $existing = [];
    if (isset($_COOKIE['order_history'])) {
        $existing = explode(',', $_COOKIE['order_history']);
        $existing = array_filter($existing);
    }
    // Tambahkan TRX baru (max 50 untuk menghindari cookie terlalu besar)
    $existing[] = $trx_id;
    if (count($existing) > 50) {
        $existing = array_slice($existing, -50);
    }
    // Simpan ke cookie (expire 1 tahun)
    setcookie('order_history', implode(',', $existing), time() + (365 * 24 * 60 * 60), '/');
    
    echo json_encode([
        'success' => true,
        'trx_id' => $trx_id,
        'message' => 'Pesanan berhasil dibuat'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Error proses_pesan: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menyimpan pesanan: ' . $e->getMessage()
    ]);
}
?>