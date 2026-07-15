<?php
// ===== KONFIGURASI DATABASE =====

$host = 'localhost';
$dbname = 'kafetarian';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

// ===== CEK DAN UPDATE TABEL USERS =====
try {
    // Cek apakah kolom password_hash sudah ada
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'password_hash'");
    if ($stmt->rowCount() == 0) {
        // Tambahkan kolom password_hash
        $pdo->exec("ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) AFTER password");
        echo "Kolom password_hash berhasil ditambahkan!<br>";
    }
    
    // Cek apakah masih ada user dengan password MD5 yang belum di-upgrade
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE password_hash IS NULL OR password_hash = ''");
    $needUpgrade = $stmt->fetch()['total'];
    
    if ($needUpgrade > 0) {
        echo "Ada $needUpgrade user yang perlu di-upgrade password-nya.<br>";
        echo "Jalankan admin/upgrade_password.php untuk migrasi.<br>";
    }
    
} catch(PDOException $e) {
    // Error handling
}
?>