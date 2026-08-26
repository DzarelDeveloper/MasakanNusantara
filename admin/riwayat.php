<?php
/**
 * Riwayat pesanan yang sudah selesai/dibatalkan — begitu ditandai selesai
 * di orders.php (KDS), pesanan hilang dari antrian aktif; halaman ini jadi
 * jejak audit biar tetap bisa dicek/cetak ulang struknya kalau ada masalah.
 */
require_once __DIR__ . '/../includes/session.php';
startSecureSession();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$staff = requireStaffLogin();
$pdo = db();

$range = $_GET['range'] ?? 'hari_ini';
$today = date('Y-m-d');
switch ($range) {
    case '7hari':
        $from = date('Y-m-d', strtotime('-6 days'));
        break;
    case '30hari':
        $from = date('Y-m-d', strtotime('-29 days'));
        break;
    case 'hari_ini':
    default:
        $from = $today;
        $range = 'hari_ini';
        break;
}

$search = trim($_GET['q'] ?? '');

$sql = "SELECT o.*, t.table_number, p.status AS payment_status, p.method, p.paid_at, p.fake_transaction_id
        FROM orders o
        JOIN dining_tables t ON t.id = o.table_id
        LEFT JOIN payments p ON p.order_id = o.id
        WHERE o.status IN ('selesai', 'dibatalkan') AND DATE(o.created_at) BETWEEN ? AND ?";
$params = [$from, $today];

if ($search !== '') {
    $sql .= ' AND (o.order_code = ? OR o.customer_name LIKE ? OR o.customer_email LIKE ?)';
    $params[] = $search;
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= ' ORDER BY o.created_at DESC LIMIT 200';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$pageTitleOwner = 'Riwayat Pesanan';
include __DIR__ . '/includes/layout_top.php';
?>

<div class="owner-toolbar">
    <h2 style="margin:0;">Riwayat Pesanan</h2>
    <nav class="owner-nav" style="background:#fff;border-radius:8px;padding:4px;">
        <a href="?range=hari_ini<?php echo $search !== '' ? '&q=' . urlencode($search) : ''; ?>" class="<?php echo $range === 'hari_ini' ? 'active' : ''; ?>" style="color:#264653;">Hari Ini</a>
        <a href="?range=7hari<?php echo $search !== '' ? '&q=' . urlencode($search) : ''; ?>" class="<?php echo $range === '7hari' ? 'active' : ''; ?>" style="color:#264653;">7 Hari</a>
        <a href="?range=30hari<?php echo $search !== '' ? '&q=' . urlencode($search) : ''; ?>" class="<?php echo $range === '30hari' ? 'active' : ''; ?>" style="color:#264653;">30 Hari</a>
    </nav>
</div>

<div class="owner-modal-form">
    <form method="get" class="owner-field-row">
        <input type="hidden" name="range" value="<?php echo htmlspecialchars($range); ?>">
        <div class="owner-field">
            <label>Cari (kode pesanan / nama / email)</label>
            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="mis. 482917 atau Budi">
        </div>
        <div class="owner-field" style="display:flex;align-items:flex-end;">
            <button type="submit" class="owner-btn owner-btn--primary">Cari</button>
        </div>
    </form>
</div>

<div class="owner-table-wrap">
    <table class="owner-table">
        <thead>
            <tr><th>Waktu</th><th>Meja</th><th>Kode</th><th>Pemesan</th><th>Total</th><th>Bayar</th><th>Status</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
            <tr><td colspan="8" style="text-align:center;color:#6b7280;">Belum ada riwayat di rentang ini.</td></tr>
            <?php endif; ?>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td data-label="Waktu"><?php echo date('d/m H:i', strtotime($o['created_at'])); ?></td>
                <td data-label="Meja">Meja <?php echo (int) $o['table_number']; ?></td>
                <td data-label="Kode"><?php echo htmlspecialchars($o['order_code'] ?? '-'); ?></td>
                <td data-label="Pemesan"><?php echo htmlspecialchars($o['customer_name']); ?></td>
                <td data-label="Total"><?php echo rupiah((int) $o['subtotal']); ?></td>
                <td data-label="Bayar"><?php echo htmlspecialchars($o['method'] ?? '-'); ?></td>
                <td data-label="Status">
                    <span class="pill <?php echo $o['status'] === 'selesai' ? 'tersedia' : 'habis'; ?>">
                        <?php echo htmlspecialchars(ORDER_STATUS_LABELS[$o['status']] ?? $o['status']); ?>
                    </span>
                </td>
                <td data-label="Aksi" class="owner-table__actions">
                    <?php if ($o['payment_status'] === 'sukses'): ?>
                    <a href="../struk.php?order=<?php echo (int) $o['id']; ?>" target="_blank" class="owner-btn">Cetak Struk</a>
                    <?php else: ?>
                    <span style="color:#9ca3af;">–</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
