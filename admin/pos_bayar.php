<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || empty($data['nama']) || empty($data['tipe']) || empty($data['items'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $trx_id = 'POS-' . date('Ymd') . '-' . rand(1000, 9999);
    $total = 0;
    foreach ($data['items'] as $item) {
        $total += $item['harga'] * $item['jumlah'];
    }
    
    // Status langsung 'selesai' karena pembayaran di kasir
    $stmt = $pdo->prepare("INSERT INTO pesanan (trx_id, nama_pemesan, tipe, total_harga, status) VALUES (?, ?, ?, ?, 'selesai')");
    $stmt->execute([$trx_id, $data['nama'], $data['tipe'], $total]);
    $pesanan_id = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare("INSERT INTO detail_pesanan (pesanan_id, menu_id, jumlah, subtotal) VALUES (?, ?, ?, ?)");
    foreach ($data['items'] as $item) {
        $subtotal = $item['harga'] * $item['jumlah'];
        $stmt->execute([$pesanan_id, $item['id'], $item['jumlah'], $subtotal]);
    }
    
    $pdo->commit();
    
    // ===== SIMPAN TRX KE COOKIE UNTUK HISTORI =====
    $existing = [];
    if (isset($_COOKIE['order_history'])) {
        $existing = explode(',', $_COOKIE['order_history']);
        $existing = array_filter($existing);
    }
    $existing[] = $trx_id;
    if (count($existing) > 50) {
        $existing = array_slice($existing, -50);
    }
    setcookie('order_history', implode(',', $existing), time() + (365 * 24 * 60 * 60), '/');
    
    echo json_encode([
        'success' => true,
        'trx_id' => $trx_id,
        'message' => 'Pembayaran berhasil'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>