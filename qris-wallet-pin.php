<?php
require_once __DIR__ . '/includes/session.php';
startSecureSession();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/payment-guard.php';

$pdo = db();

// PIN simulasi tetap (987654), sengaja tidak ditampilkan di UI — cek kode
// ini kalau perlu tahu PIN-nya untuk testing.
$DEMO_PIN = '987654';

$orderId = (int) ($_GET['order'] ?? $_POST['order'] ?? 0);
$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');

// Kalau pembayaran sudah sukses (mis. reload setelah berhasil), tetap
// tampilkan layar sukses alih-alih ikut redirect guard ke order-status —
// biar wallet-nya konsisten tampilkan hasil transaksi terakhirnya sendiri.
$stmt = $pdo->prepare(
    'SELECT o.*, t.table_number, p.status AS payment_status FROM orders o
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

$alreadyPaid = $order['payment_status'] === 'sukses';
$pinError = null;

if (!$alreadyPaid && $order['status'] !== 'menunggu_bayar') {
    header('Location: order-status.php?order=' . $orderId . '&token=' . rawurlencode($token));
    exit;
}

if (!$alreadyPaid && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify_pin') {
    $pin = trim($_POST['pin'] ?? '');

    if ($pin !== $DEMO_PIN) {
        $pinError = 'PIN salah. Coba lagi.';
    } else {
        $fakeTxnId = 'QRIS-' . strtoupper(bin2hex(random_bytes(5)));

        $pdo->beginTransaction();
        $pdo->prepare('UPDATE orders SET status = "baru_masuk" WHERE id = ?')->execute([$orderId]);
        $pdo->prepare(
            'UPDATE payments SET status = "sukses", fake_transaction_id = ?, paid_at = NOW() WHERE order_id = ?'
        )->execute([$fakeTxnId, $orderId]);
        $pdo->prepare('UPDATE dining_tables SET status = "terisi" WHERE id = ?')->execute([$order['table_id']]);
        $pdo->commit();

        $alreadyPaid = true;
    }
}

$pageTitle = 'Wallet Saya — Bayar QRIS';
$pageStylesheet = 'assets/css/pages/order.css';
include __DIR__ . '/includes/header.php';
?>

<div class="qris-wallet-topbar">
    <i class="fa-solid fa-wallet"></i> Wallet Saya
</div>

<main class="container" style="padding:24px 24px 40px;">
    <div class="qris-box">
        <?php if ($alreadyPaid): ?>
        <div class="qris-success-icon"><i class="fa-solid fa-circle-check"></i></div>
        <h2>Pembayaran Berhasil</h2>
        <p style="color:var(--color-text-muted);font-size:.85rem;">Dibayarkan ke Masakan Nusantara</p>
        <div class="qris-amount"><?php echo rupiah((int) $order['subtotal']); ?></div>
        <p style="color:var(--color-text-muted);font-size:.8rem;">Meja <?php echo (int) $order['table_number']; ?> · Pesanan #<?php echo $orderId; ?></p>
        <div class="qris-actions">
            <a href="order-status.php?order=<?php echo $orderId; ?>&token=<?php echo urlencode($token); ?>" class="btn btn-primary btn-block">Kembali ke Pesanan</a>
        </div>
        <?php else: ?>
        <div class="qris-merchant">
            <i class="fa-solid fa-store"></i> Masakan Nusantara
        </div>
        <div class="qris-amount" style="font-size:1.15rem;margin-bottom:4px;"><?php echo rupiah((int) $order['subtotal']); ?></div>
        <p style="color:var(--color-text-muted);font-size:.9rem;">Masukkan PIN pembayaran Anda</p>

        <?php if ($pinError): ?>
        <div class="order-flash" style="margin:12px 0 0;padding:0;">
            <div class="order-flash__box error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($pinError); ?></div>
        </div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="action" value="verify_pin">
            <input type="hidden" name="order" value="<?php echo $orderId; ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="password" name="pin" id="pinInput" class="qris-pin-input" maxlength="6" inputmode="numeric" pattern="[0-9]*" autocomplete="off" placeholder="••••••" autofocus>
            <div class="qris-actions">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fa-solid fa-lock"></i> Konfirmasi Pembayaran
                </button>
                <a href="qris-wallet.php?order=<?php echo $orderId; ?>&token=<?php echo urlencode($token); ?>" class="btn btn-outline btn-block">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</main>

<?php if (!$alreadyPaid): ?>
<script>
document.getElementById('pinInput').addEventListener('input', function (e) {
    e.target.value = e.target.value.replace(/[^0-9]/g, '');
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/order-footer.php'; ?>
