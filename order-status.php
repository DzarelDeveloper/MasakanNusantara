<?php
require_once __DIR__ . '/includes/db.php';

$pdo = db();
$orderId = (int) ($_GET['order'] ?? 0);
$token = (string) ($_GET['token'] ?? '');

$stmt = $pdo->prepare(
    'SELECT o.*, t.table_number, p.status AS payment_status, p.fake_transaction_id, p.paid_at, p.method
     FROM orders o
     JOIN dining_tables t ON t.id = o.table_id
     LEFT JOIN payments p ON p.order_id = o.id
     WHERE o.id = ?'
);
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order || $token === '' || !hash_equals((string) $order['qr_lookup_token'], $token)) {
    header('Location: menu.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$stmt->execute([$orderId]);
$orderItems = $stmt->fetchAll();

$pageTitle = 'Status Pesanan #' . $orderId . ' — Masakan Nusantara';
$pageStylesheet = 'assets/css/pages/order.css';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/order-topbar.php';
?>

<main class="container" style="padding:24px 24px 40px;max-width:720px;">
    <p style="text-align:center;color:var(--color-text-muted);font-size:.85rem;margin-bottom:8px;">
        Meja <?php echo (int) $order['table_number']; ?> · Pesanan #<?php echo $orderId; ?> · Kode <?php echo htmlspecialchars($order['order_code']); ?>
    </p>

    <?php if ($order['status'] === 'dibatalkan'): ?>
    <div class="order-flash" style="margin:0 0 16px;padding:0;">
        <div class="order-flash__box error"><i class="fa-solid fa-circle-xmark"></i> Pesanan ini dibatalkan (pembayaran gagal).</div>
    </div>
    <?php elseif ($order['status'] === 'menunggu_bayar' && $order['method'] === 'Tunai'): ?>
    <div class="order-flash" style="margin:0 0 16px;padding:0;">
        <div class="order-flash__box error"><i class="fa-solid fa-clock"></i> Menunggu pembayaran di kasir. Kode Pesanan: <strong><?php echo htmlspecialchars($order['order_code']); ?></strong></div>
    </div>
    <?php elseif ($order['status'] === 'menunggu_bayar'): ?>
    <div class="order-flash" style="margin:0 0 16px;padding:0;">
        <div class="order-flash__box error"><i class="fa-solid fa-clock"></i> Menunggu pembayaran. <a href="payment.php?order=<?php echo $orderId; ?>&token=<?php echo urlencode($token); ?>">Lanjutkan bayar</a>.</div>
    </div>
    <?php else: ?>
    <div style="text-align:center;">
        <div style="font-size:2.2rem;color:var(--color-accent, #2A9D8F);"><i class="fa-solid fa-circle-check"></i></div>
        <h1 style="margin-top:8px;">Pembayaran Berhasil</h1>
        <p style="color:var(--color-text-muted);">
            Terima kasih, <?php echo htmlspecialchars($order['customer_name']); ?>! Pesanan Anda telah diterima.<br>
            Silakan menunggu, pesanan akan diantar pelayan ke meja Anda.
        </p>
    </div>

    <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center;margin:20px 0;">
        <a href="struk.php?order=<?php echo $orderId; ?>&token=<?php echo urlencode($token); ?>" target="_blank" class="btn btn-primary">
            <i class="fa-solid fa-receipt"></i> Unduh / Cetak Struk
        </a>
    </div>
    <?php endif; ?>

    <?php if ($order['payment_status'] !== 'sukses'): ?>
    <div class="cart-summary">
        <?php foreach ($orderItems as $oi): ?>
        <div class="cart-summary__row">
            <div>
                <?php echo (int) $oi['quantity']; ?>x <?php echo htmlspecialchars($oi['item_name']); ?>
                <?php if ($oi['notes']): ?>
                <div class="cart-row__notes"><?php echo htmlspecialchars($oi['notes']); ?></div>
                <?php endif; ?>
            </div>
            <span><?php echo rupiah((int) $oi['price_at_order'] * (int) $oi['quantity']); ?></span>
        </div>
        <?php endforeach; ?>
        <div class="cart-summary__row total">
            <span>Total</span>
            <span><?php echo rupiah((int) $order['subtotal']); ?></span>
        </div>
    </div>

    <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center;">
        <a href="menu.php" class="btn btn-outline"><i class="fa-solid fa-plus"></i> Pesan Lagi (meja ini)</a>
    </div>
    <?php endif; ?>

    <?php if ($order['status'] === 'menunggu_bayar'): ?>
    <p style="text-align:center;color:var(--color-text-muted);font-size:.8rem;margin-top:24px;">
        Halaman ini otomatis diperbarui setiap 8 detik.
    </p>
    <script>setTimeout(function () { window.location.reload(); }, 8000);</script>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/order-footer.php'; ?>
