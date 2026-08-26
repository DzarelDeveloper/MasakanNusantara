<?php
require_once __DIR__ . '/includes/session.php';
startSecureSession();
require_once __DIR__ . '/includes/db.php';

$pdo = db();
$orderId = (int) ($_GET['order'] ?? 0);
$token = (string) ($_GET['token'] ?? '');

$stmt = $pdo->prepare(
    'SELECT o.*, t.table_number, p.status AS payment_status, p.fake_transaction_id, p.paid_at, p.method, su.name AS cashier_name
     FROM orders o
     JOIN dining_tables t ON t.id = o.table_id
     LEFT JOIN payments p ON p.order_id = o.id
     LEFT JOIN staff_users su ON su.id = p.cashier_id
     WHERE o.id = ?'
);
$stmt->execute([$orderId]);
$order = $stmt->fetch();

// Diakses dua jalur: pelanggan lewat link struk di order-status.php (bukti
// pemilikan = token), atau staf yang sudah login dari admin panel (cetak
// ulang struk order manapun) — salah satu cukup.
$isStaff = !empty($_SESSION['staff_id']);
$hasValidToken = $order && $token !== '' && hash_equals((string) $order['qr_lookup_token'], $token);

if (!$order || $order['payment_status'] !== 'sukses' || (!$isStaff && !$hasValidToken)) {
    http_response_code(404);
    echo 'Struk tidak ditemukan (pesanan belum dibayar atau tidak ada).';
    exit;
}

$isCash = ($order['method'] ?? 'QRIS') === 'Tunai';
$subtitle = $isCash ? 'Struk Pembayaran (Tunai — Bayar di Kasir)' : 'Struk Pembayaran (QRIS)';

$stmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$stmt->execute([$orderId]);
$orderItems = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Struk Pesanan #<?php echo $orderId; ?></title>
<link rel="icon" href="assets/images/favicon.svg" type="image/svg+xml">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: 'Courier New', Courier, monospace;
        background: #e5e7eb;
        display: flex;
        justify-content: center;
        padding: 24px 0;
        color: #111;
    }
    .receipt {
        width: 80mm;
        max-width: 320px;
        background: #fff;
        padding: 12px 14px;
        box-shadow: 0 4px 16px rgba(0,0,0,.15);
    }
    .center { text-align: center; }
    .name { font-weight: bold; font-size: 15px; }
    .muted { color: #555; font-size: 11px; }
    hr { border: none; border-top: 1px dashed #999; margin: 8px 0; }
    .row { display: flex; justify-content: space-between; font-size: 12px; margin: 2px 0; }
    .row.total { font-weight: bold; font-size: 13px; }
    .item-name { flex: 1; padding-right: 6px; }
    table.items { width: 100%; font-size: 12px; border-collapse: collapse; margin: 6px 0; }
    table.items td { padding: 2px 0; vertical-align: top; }
    .item-notes { font-size: 11px; font-style: italic; color: #444; padding-bottom: 4px !important; }
    .qty-col { width: 24px; }
    .price-col { text-align: right; white-space: nowrap; }
    .footer-note { font-size: 11px; text-align: center; margin-top: 10px; }
    .print-btn {
        display: block;
        width: 80mm;
        max-width: 320px;
        margin: 14px auto 0;
        padding: 10px;
        font-family: sans-serif;
        font-size: 14px;
        font-weight: bold;
        background: #264653;
        color: #fff;
        border: none;
        border-radius: 8px;
        cursor: pointer;
    }
    @media print {
        body { background: #fff; padding: 0; }
        .receipt { box-shadow: none; width: 100%; }
        .print-btn { display: none; }
        @page { margin: 0; size: 80mm auto; }
    }
</style>
</head>
<body>
    <div>
        <div class="receipt">
            <div class="center">
                <div class="name">MASAKAN NUSANTARA</div>
                <div class="muted"><?php echo htmlspecialchars($subtitle); ?></div>
            </div>
            <hr>
            <div class="row"><span>No. Pesanan</span><span>#<?php echo $orderId; ?></span></div>
            <div class="row"><span>Meja</span><span><?php echo (int) $order['table_number']; ?></span></div>
            <div class="row"><span>Tanggal</span><span><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span></div>
            <div class="row"><span>Pemesan</span><span><?php echo htmlspecialchars($order['customer_name']); ?></span></div>
            <div class="row"><span>Email</span><span><?php echo htmlspecialchars($order['customer_email']); ?></span></div>
            <hr>
            <table class="items">
                <?php foreach ($orderItems as $oi): ?>
                <tr>
                    <td class="qty-col"><?php echo (int) $oi['quantity']; ?>x</td>
                    <td class="item-name"><?php echo htmlspecialchars($oi['item_name']); ?></td>
                    <td class="price-col"><?php echo number_format((int) $oi['price_at_order'] * (int) $oi['quantity'], 0, ',', '.'); ?></td>
                </tr>
                <?php if ($oi['notes']): ?>
                <tr>
                    <td></td>
                    <td colspan="2" class="item-notes">- <?php echo htmlspecialchars($oi['notes']); ?></td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
            </table>
            <hr>
            <div class="row total"><span>TOTAL</span><span>Rp <?php echo number_format((int) $order['subtotal'], 0, ',', '.'); ?></span></div>
            <hr>
            <div class="row"><span>Metode</span><span><?php echo htmlspecialchars($order['method'] ?? 'QRIS'); ?></span></div>
            <?php if ($isCash && $order['cashier_name']): ?>
            <div class="row"><span>Dikonfirmasi oleh</span><span><?php echo htmlspecialchars($order['cashier_name']); ?></span></div>
            <?php else: ?>
            <div class="row"><span>ID Transaksi</span><span><?php echo htmlspecialchars($order['fake_transaction_id'] ?? '-'); ?></span></div>
            <?php endif; ?>
            <div class="row"><span>Dibayar</span><span><?php echo $order['paid_at'] ? date('d/m/Y H:i', strtotime($order['paid_at'])) : '-'; ?></span></div>
            <div class="footer-note">Terima kasih telah bersantap di<br>Masakan Nusantara</div>
        </div>
        <button class="print-btn" onclick="window.print()">Cetak / Simpan sebagai PDF</button>
    </div>
</body>
</html>
