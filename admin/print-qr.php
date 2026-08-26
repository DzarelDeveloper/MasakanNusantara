<?php
require_once __DIR__ . '/../includes/session.php';
startSecureSession();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/qr.php';
require_once __DIR__ . '/includes/auth.php';

requireStaffLogin();
$pdo = db();

function ownerBaseUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $root = dirname($scriptDir);
    $root = ($root === '/' || $root === '\\') ? '' : $root;
    return $scheme . '://' . $_SERVER['HTTP_HOST'] . $root;
}

$tableId = isset($_GET['table']) ? (int) $_GET['table'] : null;

if ($tableId) {
    $stmt = $pdo->prepare('SELECT * FROM dining_tables WHERE id = ?');
    $stmt->execute([$tableId]);
    $tables = array_filter([$stmt->fetch()]);
} else {
    $tables = $pdo->query('SELECT * FROM dining_tables ORDER BY table_number ASC')->fetchAll();
}

$baseUrl = ownerBaseUrl();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cetak QR Meja</title>
<link rel="icon" href="../assets/images/favicon.svg" type="image/svg+xml">
<style>
    * { box-sizing: border-box; }
    body { font-family: sans-serif; background: #e5e7eb; margin: 0; padding: 24px; }
    .toolbar { text-align: center; margin-bottom: 20px; }
    .toolbar button {
        padding: 10px 20px; font-weight: bold; font-size: 14px;
        background: #264653; color: #fff; border: none; border-radius: 8px; cursor: pointer;
    }
    .sheet {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 16px;
        max-width: 1100px;
        margin: 0 auto;
    }
    .qr-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,.08);
        break-inside: avoid;
    }
    .qr-card img { width: 100%; max-width: 180px; height: auto; }
    .qr-card .table-label { font-size: 1.1rem; font-weight: 800; margin-top: 10px; }
    .qr-card .capacity-label { font-size: 0.8rem; color: #6b7280; }
    @media print {
        body { background: #fff; padding: 0; }
        .toolbar { display: none; }
        .sheet { grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .qr-card { box-shadow: none; border: 1px solid #ddd; }
    }
</style>
</head>
<body>
    <div class="toolbar"><button onclick="window.print()">Cetak / Simpan sebagai PDF</button></div>
    <div class="sheet">
        <?php foreach ($tables as $t): ?>
        <?php $menuUrl = $baseUrl . '/menu.php?table=' . $t['table_number'] . '&token=' . $t['qr_token']; ?>
        <div class="qr-card">
            <img src="<?php echo qrDataUri($menuUrl, 5, 4); ?>" width="180" height="180" alt="QR Meja <?php echo (int) $t['table_number']; ?>">
            <div class="table-label">MEJA <?php echo (int) $t['table_number']; ?></div>
            <div class="capacity-label">Kapasitas <?php echo htmlspecialchars($t['capacity_label']); ?> orang</div>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
