<?php
require_once __DIR__ . '/../includes/session.php';
startSecureSession();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

$staff = requireRole(['admin', 'staff']);
$pdo = db();

$maintenanceFlag = __DIR__ . '/../.maintenance';

if ($staff['role'] === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_maintenance') {
    verifyCsrf();
    if (file_exists($maintenanceFlag)) {
        unlink($maintenanceFlag);
    } else {
        file_put_contents($maintenanceFlag, 'Diaktifkan ' . date('Y-m-d H:i:s') . ' oleh ' . $staff['name']);
    }
    header('Location: dashboard.php');
    exit;
}

$maintenanceActive = file_exists($maintenanceFlag);

$totalTables = (int) $pdo->query('SELECT COUNT(*) FROM dining_tables')->fetchColumn();
$occupiedTables = (int) $pdo->query("SELECT COUNT(*) FROM dining_tables WHERE status = 'terisi'")->fetchColumn();
$activeOrders = (int) $pdo->query(
    "SELECT COUNT(*) FROM orders o JOIN payments p ON p.order_id = o.id
     WHERE p.status = 'sukses' AND o.status NOT IN ('selesai', 'dibatalkan')"
)->fetchColumn();
$todayRevenue = (int) $pdo->query(
    "SELECT COALESCE(SUM(o.subtotal), 0) FROM orders o JOIN payments p ON p.order_id = o.id
     WHERE p.status = 'sukses' AND DATE(p.paid_at) = CURDATE()"
)->fetchColumn();
$todayOrders = (int) $pdo->query(
    "SELECT COUNT(*) FROM orders o JOIN payments p ON p.order_id = o.id
     WHERE p.status = 'sukses' AND DATE(p.paid_at) = CURDATE()"
)->fetchColumn();

$tables = $pdo->query('SELECT * FROM dining_tables ORDER BY table_number ASC')->fetchAll();

$pageTitleOwner = 'Dashboard';
include __DIR__ . '/includes/layout_top.php';
?>

<?php if ($staff['role'] === 'admin'): ?>
<div class="owner-modal-form" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:20px;border-left:4px solid <?php echo $maintenanceActive ? '#b91c1c' : '#2A9D8F'; ?>;">
    <div>
        <h3 style="margin-bottom:4px;">Mode Maintenance</h3>
        <p style="color:#6b7280;font-size:.88rem;margin:0;">
            <?php if ($maintenanceActive): ?>
            Situs sedang <strong style="color:#b91c1c;">TERKUNCI</strong> untuk pengunjung — cuma halaman <code>/admin</code> yang bisa diakses.
            <?php else: ?>
            Situs sedang normal, semua halaman bisa diakses pengunjung.
            <?php endif; ?>
        </p>
    </div>
    <form method="post" data-confirm="<?php echo $maintenanceActive ? 'Buka kembali situs untuk pengunjung?' : 'Kunci seluruh situs untuk pengunjung? Cuma admin/staf yang masih bisa akses panel ini.'; ?>">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="toggle_maintenance">
        <button type="submit" class="owner-btn <?php echo $maintenanceActive ? 'owner-btn--primary' : 'owner-btn--danger'; ?>">
            <?php echo $maintenanceActive ? 'Matikan Mode Maintenance' : 'Aktifkan Mode Maintenance'; ?>
        </button>
    </form>
</div>
<?php endif; ?>

<div class="stat-grid">
    <div class="stat-box">
        <div class="stat-box__value"><?php echo $totalTables; ?></div>
        <div class="stat-box__label">Total Meja</div>
    </div>
    <div class="stat-box">
        <div class="stat-box__value"><?php echo $occupiedTables; ?></div>
        <div class="stat-box__label">Meja Terisi</div>
    </div>
    <div class="stat-box">
        <div class="stat-box__value"><?php echo $activeOrders; ?></div>
        <div class="stat-box__label">Pesanan Aktif</div>
    </div>
    <div class="stat-box">
        <div class="stat-box__value">Rp <?php echo number_format($todayRevenue, 0, ',', '.'); ?></div>
        <div class="stat-box__label">Omzet Hari Ini (<?php echo $todayOrders; ?> pesanan)</div>
    </div>
</div>

<h3 style="margin-bottom:12px;">Status Meja</h3>
<div class="table-status-grid">
    <?php foreach ($tables as $t): ?>
    <div class="table-status-card status-<?php echo htmlspecialchars($t['status']); ?>">
        <div class="table-status-card__number">Meja <?php echo (int) $t['table_number']; ?></div>
        <div class="table-status-card__dot"></div>
        <div class="table-status-card__label"><?php echo ucfirst($t['status']); ?></div>
    </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
