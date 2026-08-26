<?php
require_once __DIR__ . '/../includes/session.php';
startSecureSession();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$staff = requireRole(['admin']);
$pdo = db();

$range = $_GET['range'] ?? '7hari';
$today = date('Y-m-d');
switch ($range) {
    case 'hari_ini':
        $from = $today;
        break;
    case '30hari':
        $from = date('Y-m-d', strtotime('-29 days'));
        break;
    case 'custom':
        $from = $_GET['from'] ?? date('Y-m-d', strtotime('-6 days'));
        $today = $_GET['to'] ?? $today;
        break;
    case '7hari':
    default:
        $from = date('Y-m-d', strtotime('-6 days'));
        $range = '7hari';
        break;
}

$stmt = $pdo->prepare(
    "SELECT DATE(p.paid_at) AS tanggal, COUNT(*) AS jumlah_pesanan, SUM(o.subtotal) AS omzet
     FROM orders o JOIN payments p ON p.order_id = o.id
     WHERE p.status = 'sukses' AND DATE(p.paid_at) BETWEEN ? AND ?
     GROUP BY DATE(p.paid_at)
     ORDER BY tanggal DESC"
);
$stmt->execute([$from, $today]);
$dailyRows = $stmt->fetchAll();

$totalOrders = array_sum(array_column($dailyRows, 'jumlah_pesanan'));
$totalRevenue = array_sum(array_column($dailyRows, 'omzet'));

$stmt = $pdo->prepare(
    "SELECT oi.item_name, SUM(oi.quantity) AS qty_terjual, SUM(oi.quantity * oi.price_at_order) AS omzet_item
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     JOIN payments p ON p.order_id = o.id
     WHERE p.status = 'sukses' AND DATE(p.paid_at) BETWEEN ? AND ?
     GROUP BY oi.item_name
     ORDER BY qty_terjual DESC
     LIMIT 10"
);
$stmt->execute([$from, $today]);
$topItems = $stmt->fetchAll();

$pageTitleOwner = 'Laporan Penjualan';
include __DIR__ . '/includes/layout_top.php';
?>

<div class="owner-toolbar">
    <h2 style="margin:0;">Laporan Penjualan</h2>
    <nav class="owner-nav" style="background:#fff;border-radius:8px;padding:4px;">
        <a href="?range=hari_ini" class="<?php echo $range === 'hari_ini' ? 'active' : ''; ?>" style="color:#264653;">Hari Ini</a>
        <a href="?range=7hari" class="<?php echo $range === '7hari' ? 'active' : ''; ?>" style="color:#264653;">7 Hari</a>
        <a href="?range=30hari" class="<?php echo $range === '30hari' ? 'active' : ''; ?>" style="color:#264653;">30 Hari</a>
    </nav>
</div>

<div class="stat-grid">
    <div class="stat-box">
        <div class="stat-box__value"><?php echo $totalOrders; ?></div>
        <div class="stat-box__label">Total Pesanan (<?php echo htmlspecialchars($from); ?> – <?php echo htmlspecialchars($today); ?>)</div>
    </div>
    <div class="stat-box">
        <div class="stat-box__value">Rp <?php echo number_format($totalRevenue, 0, ',', '.'); ?></div>
        <div class="stat-box__label">Total Omzet</div>
    </div>
    <div class="stat-box">
        <div class="stat-box__value">Rp <?php echo number_format($totalOrders > 0 ? round($totalRevenue / $totalOrders) : 0, 0, ',', '.'); ?></div>
        <div class="stat-box__label">Rata-rata per Pesanan</div>
    </div>
</div>

<div class="owner-table-wrap" style="margin-bottom:20px;">
    <table class="owner-table">
        <thead><tr><th>Tanggal</th><th>Jumlah Pesanan</th><th>Omzet</th></tr></thead>
        <tbody>
            <?php if (empty($dailyRows)): ?>
            <tr><td colspan="3" style="text-align:center;color:#6b7280;">Belum ada transaksi di rentang ini.</td></tr>
            <?php endif; ?>
            <?php foreach ($dailyRows as $row): ?>
            <tr>
                <td data-label="Tanggal"><?php echo date('d M Y', strtotime($row['tanggal'])); ?></td>
                <td data-label="Jumlah Pesanan"><?php echo (int) $row['jumlah_pesanan']; ?></td>
                <td data-label="Omzet">Rp <?php echo number_format((int) $row['omzet'], 0, ',', '.'); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<h3 style="max-width:1400px;margin:0 auto 12px;">Menu Terlaris</h3>
<div class="owner-table-wrap">
    <table class="owner-table">
        <thead><tr><th>#</th><th>Menu</th><th>Terjual</th><th>Omzet</th></tr></thead>
        <tbody>
            <?php if (empty($topItems)): ?>
            <tr><td colspan="4" style="text-align:center;color:#6b7280;">Belum ada data.</td></tr>
            <?php endif; ?>
            <?php foreach ($topItems as $i => $row): ?>
            <tr>
                <td data-label="#"><?php echo $i + 1; ?></td>
                <td data-label="Menu"><?php echo htmlspecialchars($row['item_name']); ?></td>
                <td data-label="Terjual"><?php echo (int) $row['qty_terjual']; ?>x</td>
                <td data-label="Omzet">Rp <?php echo number_format((int) $row['omzet_item'], 0, ',', '.'); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
