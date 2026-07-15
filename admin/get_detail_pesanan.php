<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$id = $_GET['id'] ?? 0;
if (!$id) {
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM pesanan WHERE id = ?");
$stmt->execute([$id]);
$pesanan = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT m.nama, d.jumlah, d.subtotal 
    FROM detail_pesanan d
    JOIN menu m ON d.menu_id = m.id
    WHERE d.pesanan_id = ?
");
$stmt->execute([$id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['pesanan' => $pesanan, 'items' => $items]);
?>