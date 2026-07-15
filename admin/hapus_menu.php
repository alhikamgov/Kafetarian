<?php
session_start();
require_once '../config/database.php';

// Cek login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? 0;

if ($id) {
    // Ambil thumbnail untuk dihapus
    $stmt = $pdo->prepare("SELECT thumbnail FROM menu WHERE id = ?");
    $stmt->execute([$id]);
    $menu = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Hapus file gambar
    if ($menu && $menu['thumbnail']) {
        $filePath = '../assets/images/' . $menu['thumbnail'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    // Hapus dari database
    $stmt = $pdo->prepare("DELETE FROM menu WHERE id = ?");
    $stmt->execute([$id]);
    
    // Set session message
    $_SESSION['message'] = 'Menu berhasil dihapus!';
    $_SESSION['message_type'] = 'success';
}

header('Location: dashboard.php');
exit;
?>