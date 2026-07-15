<?php
require_once 'config/database.php';

$trx_id = $_GET['trx_id'] ?? '';

if (empty($trx_id)) {
    die('TRX ID tidak ditemukan');
}

// Ambil data pesanan
$stmt = $pdo->prepare("
    SELECT p.*, 
           GROUP_CONCAT(CONCAT(m.nama, ' (', d.jumlah, ' pcs @Rp ', FORMAT(m.harga, 0), ')') SEPARATOR '\n') as detail
    FROM pesanan p
    LEFT JOIN detail_pesanan d ON p.id = d.pesanan_id
    LEFT JOIN menu m ON d.menu_id = m.id
    WHERE p.trx_id = ?
    GROUP BY p.id
");
$stmt->execute([$trx_id]);
$pesanan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pesanan) {
    die('Pesanan tidak ditemukan');
}

// Ambil pengaturan untuk alamat toko
$settings = [];
$stmt = $pdo->query("SELECT key_name, value FROM pengaturan");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key_name']] = $row['value'];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pesanan - <?php echo htmlspecialchars($settings['nama_toko'] ?? 'Kafetamin'); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: #f5f5f5;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            flex-direction: column;
        }
        .struk-wrapper {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
        }
        .struk {
            max-width: 80mm;
            width: 100%;
            padding: 15px;
            background: white;
            margin: 0 auto;
        }
        .struk-title { text-align: center; font-size: 20px; font-weight: bold; }
        .struk-subtitle { text-align: center; font-size: 12px; color: #666; margin: 5px 0 15px 0; }
        .struk-divider { border-top: 1px dashed #000; margin: 10px 0; }
        .struk-row { display: flex; justify-content: space-between; padding: 3px 0; font-size: 14px; }
        .struk-row.total { font-weight: bold; font-size: 16px; border-top: 1px solid #000; padding-top: 10px; margin-top: 10px; }
        .struk-footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
        .struk-status { text-align: center; margin: 10px 0; font-weight: bold; }
        
        /* Tombol */
        .btn-group-print {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 10px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .btn-print {
            background: #2c3e50;
            color: white;
        }
        .btn-print:hover {
            background: #34495e;
            transform: translateY(-2px);
        }
        .btn-close {
            background: #dc3545;
            color: white;
        }
        .btn-close:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        .btn i { font-size: 14px; }
        
        @media print {
            body { background: white; padding: 0; }
            .struk-wrapper { box-shadow: none; padding: 0; }
            .btn-group-print { display: none !important; }
            .struk { padding: 10px; }
        }
    </style>
</head>
<body>
    <div class="struk-wrapper">
        <div class="struk" id="strukContent">
            <div class="struk-title">☕ <?php echo htmlspecialchars($settings['nama_toko'] ?? 'KAFETAMIN'); ?></div>
            <div class="struk-subtitle"><?php echo htmlspecialchars($settings['alamat'] ?? 'Jl. Contoh No. 123'); ?></div>
            
            <div class="struk-divider"></div>
            
            <div class="struk-row"><span>No. TRX</span><span><?php echo htmlspecialchars($pesanan['trx_id']); ?></span></div>
            <div class="struk-row"><span>Tanggal</span><span><?php echo date('d/m/Y H:i', strtotime($pesanan['created_at'])); ?></span></div>
            <div class="struk-row"><span>Pemesan</span><span><?php echo htmlspecialchars($pesanan['nama_pemesan']); ?></span></div>
            <div class="struk-row"><span>Tipe</span><span><?php echo ucfirst(str_replace('-', ' ', $pesanan['tipe'])); ?></span></div>
            
            <div class="struk-divider"></div>
            
            <div style="font-weight: bold; margin-bottom: 5px;">Detail Pesanan:</div>
            <?php
            $detailItems = explode("\n", $pesanan['detail']);
            foreach ($detailItems as $item) {
                if (!empty($item)) {
                    echo '<div class="struk-row"><span>' . htmlspecialchars($item) . '</span></div>';
                }
            }
            ?>
            
            <div class="struk-divider"></div>
            
            <div class="struk-row total">
                <span>Total</span>
                <span>Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?></span>
            </div>
            
            <div class="struk-divider"></div>
            
            <div class="struk-status">
                Status : Telah dibayar.
            </div>
            
            <div class="struk-divider"></div>
            
            <div class="struk-footer">
                Terima kasih atas kunjungan Anda!<br>
                Semoga hari Anda menyenangkan 😊
            </div>
        </div>
        
        <!-- Tombol Aksi -->
        <div class="btn-group-print">
            <button class="btn btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> Print Struk
            </button>
            <button class="btn btn-close" onclick="tutupHalaman()">
                <i class="fas fa-times"></i> Kembali
            </button>
        </div>
    </div>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <script>
        function tutupHalaman() {
            // Coba tutup window
            window.close();
            
            // Jika tidak bisa close (karena dibuka dari link biasa),
            // redirect ke halaman sebelumnya
            setTimeout(function() {
                window.history.back();
            }, 500);
        }
    </script>
</body>
</html>