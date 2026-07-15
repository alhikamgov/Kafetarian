<?php
require_once 'config/database.php';

$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : 'semua';

try {
    if ($kategori === 'semua') {
        $stmt = $pdo->query("SELECT * FROM menu ORDER BY id");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM menu WHERE kategori = ? ORDER BY id");
        $stmt->execute([$kategori]);
    }
    
    $menu = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($menu);
} catch(PDOException $e) {
    echo json_encode([]);
}
?>