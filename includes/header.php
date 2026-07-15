<?php
// Ambil pengaturan untuk title
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
$tema = $settings['tema'] ?? 'ungu';

// Set page title default jika belum di-set
if (!isset($page_title)) {
    $page_title = $namaToko . ' - Pemesanan Makanan & Minuman';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Themes CSS -->
    <link rel="stylesheet" href="assets/css/themes.css">
</head>
<body class="theme-<?php echo $tema; ?>">