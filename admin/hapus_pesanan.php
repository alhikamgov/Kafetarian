<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = $_POST['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit;
}

try {
    // Cek apakah pesanan ada
    $stmt = $pdo->prepare("SELECT status FROM pesanan WHERE id = ?");
    $stmt->execute([$id]);
    $pesanan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pesanan) {
        echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan']);
        exit;
    }
    
    // Hapus detail pesanan (otomatis karena foreign key cascade)
    // Hapus pesanan
    $stmt = $pdo->prepare("DELETE FROM pesanan WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>