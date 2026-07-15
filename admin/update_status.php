<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = $_POST['id'] ?? 0;
$status = $_POST['status'] ?? '';

// Status yang valid
$validStatus = ['dikirim', 'dibayar', 'dibuat', 'selesai'];

if ($id && in_array($status, $validStatus)) {
    try {
        $stmt = $pdo->prepare("UPDATE pesanan SET status = ? WHERE id = ?");
        $success = $stmt->execute([$status, $id]);
        
        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal update']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
?>