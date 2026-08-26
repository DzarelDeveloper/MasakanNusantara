<?php
require_once __DIR__ . '/includes/session.php';
startSecureSession();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/qr.php';
require_once __DIR__ . '/includes/payment-guard.php';

$pdo = db();

function walletAppUrl(int $orderId, string $token): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $root = dirname($_SERVER['SCRIPT_NAME']);
    $root = ($root === '/' || $root === '\\') ? '' : $root;
    return $scheme . '://' . $_SERVER['HTTP_HOST'] . $root . '/qris-wallet.php?order=' . $orderId . '&token=' . rawurlencode($token);
}

$orderId = (int) ($_GET['order'] ?? $_POST['order'] ?? 0);
$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$order = loadPendingPaymentOrder($pdo, $orderId, $token);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm' && ($_POST['result'] ?? '') === 'failed') {
    $pdo->prepare('UPDATE orders SET status = "dibatalkan" WHERE id = ?')->execute([$orderId]);
    $pdo->prepare('UPDATE payments SET status = "gagal" WHERE order_id = ?')->execute([$orderId]);

    header('Location: cart.php?payment_failed=1');
    exit;
}

$pageTitle = 'Bayar QRIS — Masakan Nusantara';
$pageStylesheet = 'assets/css/pages/order.css';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/order-topbar.php';
?>

<p style="text-align:center;color:var(--color-text-muted);font-size:.85rem;margin-top:20px;">
    Meja <?php echo (int) $order['table_number']; ?> · Pesanan #<?php echo $orderId; ?>
</p>

<main class="container" style="padding:24px 24px 40px;">
    <div class="qris-box">
        <h2>Scan &amp; Bayar QRIS</h2>
        <p style="color:var(--color-text-muted);font-size:.9rem;margin-top:6px;">
            Scan QR di bawah menggunakan aplikasi e-wallet/m-banking Anda untuk membayar.
        </p>

        <img src="<?php echo qrDataUri(walletAppUrl($orderId, $token), 6, 4); ?>" width="220" height="220" alt="QR Pembayaran Pesanan #<?php echo $orderId; ?>" class="qris-real-qr">

        <div class="qris-amount"><?php echo rupiah((int) $order['subtotal']); ?></div>

        <p class="qris-note" style="margin-top:0;">Buka aplikasi e-wallet/m-banking Anda, lalu scan QR di atas untuk membayar.</p>

        <div class="qris-waiting">
            <span class="qris-waiting__spinner"></span> Menunggu pembayaran...
        </div>

        <a href="qris-wallet.php?order=<?php echo $orderId; ?>&token=<?php echo urlencode($token); ?>" class="qris-upload-link">
            <i class="fa-solid fa-wallet"></i> Tidak bisa scan? Buka Wallet Saya di HP ini
        </a>

        <form method="post">
            <input type="hidden" name="action" value="confirm">
            <input type="hidden" name="order" value="<?php echo $orderId; ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="hidden" name="result" value="failed">
            <button type="submit" class="qris-cancel-link">Batalkan Pembayaran</button>
        </form>
    </div>
</main>

<script>setTimeout(function () { window.location.reload(); }, 8000);</script>

<?php include __DIR__ . '/includes/order-footer.php'; ?>
