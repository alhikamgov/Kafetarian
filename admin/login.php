<?php
// Start session hanya jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

// Cek jika sudah login
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Cari user di database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $loginSuccess = false;
        
        // Cek password dengan priority:
        // 1. Cek password_hash (bcrypt) dulu
        if (!empty($user['password_hash'])) {
            $loginSuccess = password_verify($password, $user['password_hash']);
        }
        
        // 2. Jika gagal, cek MD5 (untuk kompatibilitas ke belakang)
        if (!$loginSuccess && !empty($user['password'])) {
            if (md5($password) === $user['password']) {
                $loginSuccess = true;
                // Upgrade ke password_hash otomatis
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, password = '' WHERE id = ?");
                $stmt->execute([$newHash, $user['id']]);
            }
        }
        
        if ($loginSuccess) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_role'] = $user['role'] ?? 'admin';
            $_SESSION['admin_id'] = $user['id'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Username atau password salah!';
        }
    } else {
        $error = 'Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .login-card .logo { text-align: center; font-size: 3.5rem; color: #f39c12; margin-bottom: 10px; }
        .login-card h4 { text-align: center; color: #2c3e50; margin-bottom: 30px; }
        .login-card .btn-login {
            background: #2c3e50; color: white; border: none;
            padding: 12px; border-radius: 10px; font-weight: 600;
            width: 100%; transition: all 0.3s;
        }
        .login-card .btn-login:hover { background: #34495e; transform: translateY(-2px); }
        .login-card .error {
            background: #fde8e8; color: #c62828;
            padding: 10px; border-radius: 10px;
            margin-bottom: 20px; text-align: center;
        }
        .login-card .info {
            background: #e3f2fd; color: #0d47a1;
            padding: 10px; border-radius: 10px;
            margin-bottom: 20px; text-align: center;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo"><i class="fas fa-coffee"></i></div>
        <h4>Admin & Kasir</h4>
        
        <?php if ($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-user text-muted"></i></span>
                    <input type="text" class="form-control border-start-0" name="username" required placeholder="Username" autofocus>
                </div>
            </div>
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" class="form-control border-start-0" name="password" required placeholder="Password">
                </div>
            </div>
            <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> Login</button>
        </form>
        
    </div>
</body>
</html>