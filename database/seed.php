<?php
/**
 * One-off CLI seed script for Fase 1.
 * Run: php database/seed.php
 *
 * Seeds 35 dining tables (1-15 capacity "1-2", 16-35 capacity "2-4")
 * and a starter menu. Safe to re-run — it wipes and re-inserts.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

require_once __DIR__ . '/../includes/db.php';

$pdo = db();

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
$pdo->exec('TRUNCATE TABLE payments');
$pdo->exec('TRUNCATE TABLE order_items');
$pdo->exec('TRUNCATE TABLE orders');
$pdo->exec('TRUNCATE TABLE table_sessions');
$pdo->exec('TRUNCATE TABLE menu_items');
$pdo->exec('TRUNCATE TABLE dining_tables');
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

// --- Default admin account (Fase 2 owner login) ---
// NOT truncated above — re-running seed.php shouldn't lock out an admin
// who already changed their password. Insert once, skip if present.
$exists = $pdo->query("SELECT COUNT(*) FROM staff_users WHERE email = 'admin@masakannusantara.co.id'")->fetchColumn();
if (!$exists) {
    $pdo->prepare('INSERT INTO staff_users (name, email, password_hash, role) VALUES (?, ?, ?, ?)')
        ->execute(['Admin Resto', 'admin@masakannusantara.co.id', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);
    echo "Seeded default admin (admin@masakannusantara.co.id / admin123 — ganti setelah login pertama).\n";
}

// --- Tables: 1-15 capacity 1-2 orang, 16-35 capacity 2-4 orang ---
$insertTable = $pdo->prepare(
    'INSERT INTO dining_tables (table_number, capacity_label, qr_token) VALUES (?, ?, ?)'
);
for ($n = 1; $n <= 35; $n++) {
    $capacity = $n <= 15 ? '1-2' : '2-4';
    $token = bin2hex(random_bytes(16));
    $insertTable->execute([$n, $capacity, $token]);
}
echo "Seeded 35 meja.\n";

// --- Menu: 3 kategori (Makanan 10, Minuman 8, Dimsum 7), foto asli hidangan
// (diunduh dari Wikimedia Commons, bukan foto interior/lifestyle generik).
// serve_temp cuma relevan untuk kategori Minuman: 'keduanya' = tampilkan
// pilihan Dingin/Panas ke pelanggan, 'dingin'/'panas' = item ini cuma bisa
// satu suhu jadi pelanggan tidak perlu ditanya pilihan lagi. Diisi
// 'keduanya' (default, diabaikan) untuk Makanan/Dimsum.
$menu = [
    // category, name, description, price, image, serve_temp
    ['Makanan', 'Nasi Goreng Spesial', 'Nasi goreng dengan ayam, telur, dan kerupuk', 28000, 'assets/images/dishes/nasi-goreng.jpg', 'keduanya'],
    ['Makanan', 'Rendang Sapi', 'Daging sapi masak rempah khas Padang', 35000, 'assets/images/dishes/rendang.jpg', 'keduanya'],
    ['Makanan', 'Sate Ayam (5 tusuk)', 'Sate ayam bakar bumbu kecap, bumbu kacang', 20000, 'assets/images/dishes/sate-ayam.jpg', 'keduanya'],
    ['Makanan', 'Gado-Gado', 'Sayuran rebus dengan siraman bumbu kacang', 18000, 'assets/images/dishes/gado-gado.jpg', 'keduanya'],
    ['Makanan', 'Ayam Penyet', 'Ayam goreng geprek, sambal terasi, lalapan', 25000, 'assets/images/dishes/ayam-penyet.jpg', 'keduanya'],
    ['Makanan', 'Soto Ayam', 'Sup ayam bening kuah kunyit dengan soun, telur, dan koya', 22000, 'assets/images/dishes/soto-ayam.jpg', 'keduanya'],
    ['Makanan', 'Mie Goreng Jawa', 'Mie goreng bumbu kecap khas Jawa dengan telur dan sayuran', 20000, 'assets/images/dishes/mie-goreng.jpg', 'keduanya'],
    ['Makanan', 'Ikan Bakar', 'Ikan bakar bumbu kecap pedas manis, disajikan dengan sambal', 32000, 'assets/images/dishes/ikan-bakar.jpg', 'keduanya'],
    ['Makanan', 'Cap Cai', 'Tumis aneka sayuran segar dengan bakso dan udang', 22000, 'assets/images/dishes/capcay.jpg', 'keduanya'],
    ['Makanan', 'Sop Buntut', 'Sup buntut sapi kaya rempah, gurih dan hangat', 38000, 'assets/images/dishes/sop-buntut.jpg', 'keduanya'],

    ['Minuman', 'Teh Manis', 'Teh manis khas Nusantara — pilih dingin atau panas', 6000, 'assets/images/dishes/teh-manis.jpg', 'keduanya'],
    ['Minuman', 'Teh Tawar', 'Teh tawar hangat/dingin tanpa gula', 5000, 'assets/images/dishes/teh-tawar.jpg', 'keduanya'],
    ['Minuman', 'Es Jeruk', 'Perasan jeruk segar dengan es', 8000, 'assets/images/dishes/es-jeruk.jpg', 'dingin'],
    ['Minuman', 'Es Cendol', 'Cendol, santan, dan gula merah', 12000, 'assets/images/dishes/es-cendol.jpg', 'dingin'],
    ['Minuman', 'Kopi Tubruk', 'Kopi hitam khas Nusantara', 10000, 'assets/images/dishes/kopi-tubruk.jpg', 'panas'],
    ['Minuman', 'Teh Tarik', 'Teh susu ditarik khas warung', 10000, 'assets/images/dishes/teh-tarik.jpg', 'keduanya'],
    ['Minuman', 'Es Kelapa Muda', 'Kelapa muda segar dengan sirup', 15000, 'assets/images/dishes/es-kelapa-muda.jpg', 'dingin'],
    ['Minuman', 'Jus Alpukat', 'Jus alpukat kental dengan susu cokelat', 15000, 'assets/images/dishes/jus-alpukat.jpg', 'dingin'],

    ['Dimsum', 'Siomay Udang', 'Siomay udang kukus, disajikan dengan saus', 15000, 'assets/images/dishes/siomay.jpg', 'keduanya'],
    ['Dimsum', 'Hakau Udang', 'Dumpling udang kukus kulit transparan', 18000, 'assets/images/dishes/hakau.jpg', 'keduanya'],
    ['Dimsum', 'Bakpao Ayam', 'Bakpao kukus isi ayam', 12000, 'assets/images/dishes/bakpao.jpg', 'keduanya'],
    ['Dimsum', 'Pangsit Goreng', 'Pangsit renyah isi ayam dan udang, disajikan dengan saus', 14000, 'assets/images/dishes/pangsit-goreng.jpg', 'keduanya'],
    ['Dimsum', 'Lumpia Semarang', 'Lumpia goreng isi rebung dan ayam, khas Semarang', 15000, 'assets/images/dishes/lumpia.jpg', 'keduanya'],
    ['Dimsum', 'Xiao Long Bao', 'Dumpling kukus isi kuah dan daging ayam cincang ala Shanghai', 20000, 'assets/images/dishes/xiao-long-bao.jpg', 'keduanya'],
    ['Dimsum', 'Kuotie', 'Dumpling goreng-kukus isi ayam dan sayuran, gurih dan renyah', 17000, 'assets/images/dishes/kuotie.jpg', 'keduanya'],
];

$insertMenu = $pdo->prepare(
    'INSERT INTO menu_items (category, name, description, price, image, sort_order, serve_temp) VALUES (?, ?, ?, ?, ?, ?, ?)'
);
foreach ($menu as $i => $item) {
    $insertMenu->execute([$item[0], $item[1], $item[2], $item[3], $item[4], $i, $item[5]]);
}
echo 'Seeded ' . count($menu) . " item menu.\n";

echo "Selesai.\n";
