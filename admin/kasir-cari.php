<?php
require_once __DIR__ . '/../includes/session.php';
startSecureSession();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

$staff = requireRole(['admin', 'staff']);
$pdo = db();

$errors = [];
$notice = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm_cash') {
    verifyCsrf();
    $orderId = (int) ($_POST['order_id'] ?? 0);

    $pdo->beginTransaction();

    $updOrder = $pdo->prepare("UPDATE orders SET status = 'baru_masuk' WHERE id = ? AND status = 'menunggu_bayar'");
    $updOrder->execute([$orderId]);
    $orderChanged = $updOrder->rowCount();

    $fakeTxnId = 'CASH-' . strtoupper(bin2hex(random_bytes(5)));
    $updPayment = $pdo->prepare(
        "UPDATE payments SET status = 'sukses', cashier_id = ?, fake_transaction_id = ?, paid_at = NOW()
         WHERE order_id = ? AND status = 'pending'"
    );
    $updPayment->execute([$staff['id'], $fakeTxnId, $orderId]);
    $paymentChanged = $updPayment->rowCount();

    if ($orderChanged && $paymentChanged) {
        $stmt = $pdo->prepare('SELECT table_id FROM orders WHERE id = ?');
        $stmt->execute([$orderId]);
        $tableId = $stmt->fetchColumn();
        if ($tableId) {
            $pdo->prepare("UPDATE dining_tables SET status = 'terisi' WHERE id = ?")->execute([$tableId]);
        }
        $pdo->commit();
        $notice = 'Pembayaran dikonfirmasi.';
    } else {
        $pdo->rollBack();
        $notice = 'Pesanan ini sudah dikonfirmasi sebelumnya.';
    }

    $_SESSION['kasir_notice'] = $notice;
    header('Location: kasir-cari.php?code_or_token=' . urlencode($_POST['order_code'] ?? ''));
    exit;
}

if (isset($_SESSION['kasir_notice'])) {
    $notice = $_SESSION['kasir_notice'];
    unset($_SESSION['kasir_notice']);
}

$query = trim($_GET['code_or_token'] ?? $_GET['token'] ?? '');
$order = null;
if ($query !== '') {
    $stmt = $pdo->prepare(
        'SELECT o.*, t.table_number, p.status AS payment_status, p.method, p.cashier_id
         FROM orders o
         JOIN dining_tables t ON t.id = o.table_id
         LEFT JOIN payments p ON p.order_id = o.id
         WHERE o.order_code = ? OR o.qr_lookup_token = ?'
    );
    $stmt->execute([$query, $query]);
    $order = $stmt->fetch();
    if (!$order) {
        $errors[] = 'Pesanan tidak ditemukan.';
    }
}

$orderItems = [];
if ($order) {
    $stmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
    $stmt->execute([$order['id']]);
    $orderItems = $stmt->fetchAll();
}

$pageTitleOwner = 'Kasir — Cari Pesanan';
include __DIR__ . '/includes/layout_top.php';
?>

<div class="owner-toolbar">
    <h2 style="margin:0;">Kasir — Cari &amp; Konfirmasi Pembayaran</h2>
</div>

<?php if ($notice): ?>
<div class="owner-success"><?php echo htmlspecialchars($notice); ?></div>
<?php endif; ?>

<?php foreach ($errors as $e): ?>
<div class="owner-error"><?php echo htmlspecialchars($e); ?></div>
<?php endforeach; ?>

<div class="owner-modal-form" style="max-width:520px;">
    <h3 style="margin-bottom:12px;">Cari Pesanan</h3>
    <form method="get" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
        <div class="owner-field" style="flex:1;margin-bottom:0;">
            <label>Kode 6 Digit atau Scan QR Pesanan</label>
            <input type="text" name="code_or_token" required value="<?php echo htmlspecialchars($query); ?>" placeholder="mis. 482917">
        </div>
        <button type="submit" class="owner-btn owner-btn--primary">Cari</button>
    </form>
</div>

<?php if ($order): ?>
<div class="owner-modal-form" style="max-width:640px;">
    <h3 style="margin-bottom:12px;">Pesanan #<?php echo (int) $order['id']; ?> — Kode <?php echo htmlspecialchars($order['order_code']); ?></h3>

    <div class="owner-table-wrap" style="margin-bottom:16px;">
        <table class="owner-table">
            <thead>
                <tr><th>Menu</th><th>Jumlah</th><th>Harga</th></tr>
            </thead>
            <tbody>
                <?php foreach ($orderItems as $oi): ?>
                <tr>
                    <td data-label="Menu">
                        <?php echo htmlspecialchars($oi['item_name']); ?>
                        <?php if ($oi['notes']): ?>
                        <div style="font-size:.78rem;color:#6b7280;font-style:italic;"><?php echo htmlspecialchars($oi['notes']); ?></div>
                        <?php endif; ?>
                    </td>
                    <td data-label="Jumlah"><?php echo (int) $oi['quantity']; ?>x</td>
                    <td data-label="Harga"><?php echo rupiah((int) $oi['price_at_order'] * (int) $oi['quantity']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p style="text-align:right;font-weight:800;font-size:1.05rem;margin:0 4px 20px;">Total: <?php echo rupiah((int) $order['subtotal']); ?></p>

    <p><strong>Meja</strong> <?php echo (int) $order['table_number']; ?></p>
    <p><strong>Metode</strong> <?php echo htmlspecialchars($order['method'] ?? '-'); ?></p>
    <p><strong>Status Bayar</strong>
        <span class="pill <?php echo $order['payment_status'] === 'sukses' ? 'tersedia' : ($order['payment_status'] === 'gagal' ? 'habis' : 'terisi'); ?>">
            <?php echo ucfirst($order['payment_status'] ?? '-'); ?>
        </span>
    </p>

    <?php if ($order['payment_status'] === 'sukses'): ?>
    <p style="color:var(--color-accent, #2A9D8F);font-weight:700;">Sudah dibayar.</p>
    <a href="../struk.php?order=<?php echo (int) $order['id']; ?>" target="_blank" class="owner-btn">Cetak Struk</a>
    <?php elseif ($order['payment_status'] === 'pending'): ?>
    <form method="post">
        <input type="hidden" name="action" value="confirm_cash">
        <input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>">
        <input type="hidden" name="order_code" value="<?php echo htmlspecialchars($order['order_code']); ?>">
        <?php echo csrfField(); ?>
        <button type="submit" class="owner-btn owner-btn--primary">Konfirmasi Pembayaran</button>
    </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
