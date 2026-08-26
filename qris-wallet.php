<?php
require_once __DIR__ . '/includes/session.php';
startSecureSession();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/payment-guard.php';

$pdo = db();

$orderId = (int) ($_GET['order'] ?? 0);
$token = (string) ($_GET['token'] ?? '');
$order = loadPendingPaymentOrder($pdo, $orderId, $token);

$pageTitle = 'Wallet Saya — Bayar QRIS';
$pageStylesheet = 'assets/css/pages/order.css';
include __DIR__ . '/includes/header.php';
?>

<div class="qris-wallet-topbar">
    <i class="fa-solid fa-wallet"></i> Wallet Saya
</div>

<main class="container" style="padding:24px 24px 40px;">
    <div class="qris-box">
        <p style="color:var(--color-text-muted);font-size:.85rem;">QR berhasil dipindai. Anda akan membayar ke:</p>

        <div class="qris-merchant">
            <i class="fa-solid fa-store"></i> Masakan Nusantara
        </div>

        <div class="qris-amount"><?php echo rupiah((int) $order['subtotal']); ?></div>
        <p style="color:var(--color-text-muted);font-size:.8rem;">Meja <?php echo (int) $order['table_number']; ?> · Pesanan #<?php echo $orderId; ?></p>

        <div class="qris-actions">
            <a href="qris-wallet-pin.php?order=<?php echo $orderId; ?>&token=<?php echo urlencode($token); ?>" class="btn btn-primary btn-block">Bayar Sekarang</a>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/order-footer.php'; ?>
