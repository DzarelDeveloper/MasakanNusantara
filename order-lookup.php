<?php
require_once __DIR__ . '/includes/db.php';

$pdo = db();
$email = trim($_GET['email'] ?? '');
$orders = [];
$searched = false;

if ($email !== '') {
    $searched = true;
    $stmt = $pdo->prepare(
        "SELECT o.*, t.table_number, p.status AS payment_status
         FROM orders o
         JOIN dining_tables t ON t.id = o.table_id
         LEFT JOIN payments p ON p.order_id = o.id
         WHERE o.customer_email = ?
         ORDER BY o.created_at DESC
         LIMIT 30"
    );
    $stmt->execute([$email]);
    $orders = $stmt->fetchAll();
}

$pageTitle = 'Cari Pesanan Anda — Masakan Nusantara';
$pageStylesheet = 'assets/css/pages/order.css';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main>
<section class="page-hero-sm" style="--hero-sm-bg:url('assets/images/banner/restaurant-interior.jpg');">
    <div class="container">
        <div class="breadcrumb on-dark">
            <a href="index.php">Beranda</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span class="current">Cari Pesanan</span>
        </div>
        <h1>Cari Pesanan Anda</h1>
        <p>Masukkan email yang Anda pakai saat checkout untuk melihat riwayat pesanan Anda.</p>
    </div>
</section>

<div class="container" style="padding:40px 24px 80px;max-width:720px;">

    <form method="get" style="display:flex;gap:10px;margin:24px 0;flex-wrap:wrap;">
        <input class="form-control" type="email" name="email" placeholder="email@contoh.com" required
               value="<?php echo htmlspecialchars($email); ?>" style="flex:1;min-width:220px;">
        <button type="submit" class="btn btn-primary">Cari</button>
    </form>

    <?php if ($searched): ?>
        <?php if (empty($orders)): ?>
        <p style="color:var(--color-text-muted);">Tidak ada pesanan yang ditemukan untuk email ini.</p>
        <?php else: ?>
        <div class="cart-list">
            <?php foreach ($orders as $o): ?>
            <div class="cart-row" style="flex-wrap:wrap;">
                <div class="cart-row__name">
                    Meja <?php echo (int) $o['table_number']; ?> · Pesanan #<?php echo (int) $o['id']; ?>
                    <div class="cart-row__price">
                        <?php echo date('d/m/Y H:i', strtotime($o['created_at'])); ?> ·
                        <?php echo htmlspecialchars(ORDER_STATUS_LABELS[$o['status']] ?? $o['status']); ?>
                    </div>
                </div>
                <strong><?php echo rupiah((int) $o['subtotal']); ?></strong>
                <a href="order-status.php?order=<?php echo (int) $o['id']; ?>&token=<?php echo urlencode($o['qr_lookup_token']); ?>" class="btn btn-outline btn-sm">Lihat</a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
