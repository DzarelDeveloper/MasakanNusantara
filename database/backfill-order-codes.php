<?php
/**
 * One-off CLI backfill for orders created before order_code/qr_lookup_token
 * existed. Run: php database/backfill-order-codes.php
 * Idempotent — only touches rows where order_code IS NULL, safe to re-run.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/order-code.php';

$pdo = db();

$ids = $pdo->query('SELECT id FROM orders WHERE order_code IS NULL')->fetchAll(PDO::FETCH_COLUMN);

$update = $pdo->prepare('UPDATE orders SET order_code = ?, qr_lookup_token = ? WHERE id = ?');
foreach ($ids as $id) {
    $update->execute([generateUniqueOrderCode($pdo), generateLookupToken(), $id]);
}

echo 'Backfilled ' . count($ids) . " order.\n";
