<?php
/**
 * Dipakai bersama oleh payment.php / qris-wallet.php / qris-wallet-pin.php
 * (3 halaman terpisah untuk 3 langkah simulasi QRIS: scan -> konfirmasi
 * nominal -> PIN) supaya guard "order ada, token cocok, & masih
 * menunggu_bayar" tidak diduplikasi di tiap file. Token (qr_lookup_token)
 * jadi satu-satunya bukti kepemilikan order karena pelanggan tidak login —
 * tanpa ini, order id yang berurutan/gampang ditebak bisa dipakai orang
 * lain untuk mengintip/mengganggu pesanan orang.
 */
function loadPendingPaymentOrder(PDO $pdo, int $orderId, string $token): array
{
    $stmt = $pdo->prepare(
        'SELECT o.*, t.table_number FROM orders o JOIN dining_tables t ON t.id = o.table_id WHERE o.id = ?'
    );
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order || $token === '' || !hash_equals((string) $order['qr_lookup_token'], $token)) {
        header('Location: menu.php');
        exit;
    }

    if ($order['status'] !== 'menunggu_bayar') {
        header('Location: order-status.php?order=' . $orderId . '&token=' . rawurlencode($token));
        exit;
    }

    return $order;
}
