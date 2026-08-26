<?php
/**
 * Fase 2: daftar pesanan masuk untuk admin/dapur (KDS), sekarang di
 * balik login. Hanya pesanan yang pembayarannya sudah "sukses" yang
 * tampil di sini.
 */
require_once __DIR__ . '/../includes/session.php';
startSecureSession();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

$staff = requireStaffLogin();
$pdo = db();

// Begitu pesanan dibayar, dapur langsung menyiapkannya — tidak perlu
// dilacak tahap "diproses"/"siap disajikan" terpisah, staf tinggal tandai
// selesai sekali begitu makanan sudah diantar ke meja.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    verifyCsrf();
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    if ($newStatus === 'selesai') {
        $pdo->prepare("UPDATE orders SET status = 'selesai' WHERE id = ? AND status != 'selesai'")->execute([$orderId]);
    }
    header('Location: orders.php');
    exit;
}

$stmt = $pdo->query(
    "SELECT o.*, t.table_number, p.status AS payment_status, p.fake_transaction_id
     FROM orders o
     JOIN dining_tables t ON t.id = o.table_id
     JOIN payments p ON p.order_id = o.id
     WHERE p.status = 'sukses' AND o.status != 'selesai'
     ORDER BY t.table_number ASC, o.created_at ASC"
);
$orders = $stmt->fetchAll();

$orderIds = array_column($orders, 'id');
$itemsByOrder = [];
if ($orderIds) {
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id IN ($placeholders)");
    $stmt->execute($orderIds);
    foreach ($stmt->fetchAll() as $oi) {
        $itemsByOrder[$oi['order_id']][] = $oi;
    }
}

$pageTitleOwner = 'Antrian Pesanan';
include __DIR__ . '/includes/layout_top.php';
?>

<div class="owner-toolbar">
    <h2 style="margin:0;">Antrian Pesanan (<?php echo count($orders); ?> aktif)</h2>
    <span style="font-size:.8rem;color:#6b7280;">Halaman ini otomatis muat ulang tiap 15 detik.</span>
</div>

<?php if (empty($orders)): ?>
<p style="text-align:center;color:#6b7280;padding:60px 0;">Belum ada pesanan yang masuk.</p>
<?php else: ?>
<div class="order-board">
    <?php foreach ($orders as $order): ?>
    <div class="order-card status-<?php echo htmlspecialchars($order['status']); ?>">
        <div class="order-card__head">
            <span class="order-card__table">Meja <?php echo (int) $order['table_number']; ?></span>
            <span class="order-card__badge"><?php echo htmlspecialchars(ORDER_STATUS_LABELS[$order['status']] ?? $order['status']); ?></span>
        </div>
        <div class="order-card__customer">
            <?php echo htmlspecialchars($order['customer_name']); ?>
            <span class="muted">&lt;<?php echo htmlspecialchars($order['customer_email']); ?>&gt;</span>
        </div>
        <ul class="order-card__items">
            <?php foreach ($itemsByOrder[$order['id']] ?? [] as $oi): ?>
            <li>
                <?php echo (int) $oi['quantity']; ?>x <?php echo htmlspecialchars($oi['item_name']); ?>
                <?php if ($oi['notes']): ?>
                <span class="order-card__item-notes"><?php echo htmlspecialchars($oi['notes']); ?></span>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($order['notes']): ?>
        <div class="order-card__notes">Catatan: <?php echo htmlspecialchars($order['notes']); ?></div>
        <?php endif; ?>
        <div class="order-card__total">Total: <?php echo rupiah((int) $order['subtotal']); ?></div>
        <div class="order-card__meta">#<?php echo (int) $order['id']; ?> · <?php echo date('d/m H:i', strtotime($order['created_at'])); ?></div>

        <div class="order-card__actions">
            <form method="post" data-confirm="Pesanan ini sudah selesai disiapkan dan diantar ke meja?">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>">
                <input type="hidden" name="status" value="selesai">
                <button type="submit" class="owner-btn owner-btn--primary">Tandai Selesai</button>
            </form>
            <a href="../struk.php?order=<?php echo (int) $order['id']; ?>" target="_blank" class="owner-btn">Cetak Struk</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>setTimeout(function () { window.location.reload(); }, 15000);</script>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
