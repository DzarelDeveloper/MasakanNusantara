<?php
/**
 * PDO connection for the QR table-ordering system (masakan_nusantara DB).
 * Separate from the old frontend-only pages, which don't touch this file.
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    // Hosting ini (Hostinger shared) tidak expose panel env var yang
    // terjangkau — lihat Production.md Bab 4. Sebagai fallback terakhir,
    // pakai kredensial production kalau env var tidak diset DAN request
    // memang datang dari domain production, supaya dev lokal (XAMPP) tetap
    // pakai default root/tanpa password seperti biasa.
    $isProdHost = ($_SERVER['HTTP_HOST'] ?? '') === 'masakannusantara.my.id'
        || ($_SERVER['HTTP_HOST'] ?? '') === 'www.masakannusantara.my.id';

    $host = getenv('DB_HOST') ?: ($isProdHost ? 'localhost' : '127.0.0.1');
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: ($isProdHost ? 'u758001970_nusantara' : 'masakan_nusantara');
    $user = getenv('DB_USER') ?: ($isProdHost ? 'u758001970_nusantara' : 'root');
    $pass = getenv('DB_PASS') ?: ($isProdHost ? '$1&Q?wj83?+e2mkH' : '');

    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    return $pdo;
}

function rupiah(int $amount): string
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

const ORDER_STATUS_LABELS = [
    'menunggu_bayar' => 'Menunggu Pembayaran',
    'baru_masuk'      => 'Baru Masuk',
    'diproses'        => 'Diproses',
    'siap_disajikan'  => 'Siap Disajikan',
    'selesai'         => 'Selesai',
    'dibatalkan'      => 'Dibatalkan',
];
