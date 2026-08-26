<?php
/**
 * Kode order 6 digit (dicari manual oleh pelanggan di kasir) + token QR Order
 * (dienkode ke QR, dipindai kasir). Dua identitas terpisah untuk satu order:
 * kode gampang diingat/diketik, token opaque untuk QR biar tidak gampang
 * ditebak (lihat plan di /home/dzarel/.claude/plans/purrfect-conjuring-dewdrop.md).
 */

function generateUniqueOrderCode(PDO $pdo): string
{
    do {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare('SELECT 1 FROM orders WHERE order_code = ?');
        $stmt->execute([$code]);
    } while ($stmt->fetchColumn());

    return $code;
}

function generateLookupToken(): string
{
    return bin2hex(random_bytes(16));
}
