<?php
require_once __DIR__ . '/includes/session.php';
startSecureSession();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/order-code.php';

$pdo = db();

if (!isset($_SESSION['active_table_id'])) {
    header('Location: menu.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM dining_tables WHERE id = ?');
$stmt->execute([$_SESSION['active_table_id']]);
$table = $stmt->fetch();
if (!$table) {
    header('Location: menu.php');
    exit;
}

// --- Handle cart mutations ---
// $_SESSION['cart'] is a list of lines keyed by a line id:
//   [line_id => ['menu_item_id' => int, 'qty' => int, 'notes' => string]]
// so the same dish can appear twice with different spice/temp/notes.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update') {
        foreach ((array) ($_POST['qty'] ?? []) as $lineId => $qty) {
            if (!isset($_SESSION['cart'][$lineId])) {
                continue;
            }
            $qty = (int) $qty;
            if ($qty <= 0) {
                unset($_SESSION['cart'][$lineId]);
            } else {
                $_SESSION['cart'][$lineId]['qty'] = min(20, $qty);
            }
        }
        header('Location: cart.php');
        exit;
    }

    if ($action === 'remove') {
        $lineId = $_POST['line_id'] ?? '';
        unset($_SESSION['cart'][$lineId]);
        header('Location: cart.php');
        exit;
    }

    if ($action === 'checkout') {
        $name = trim($_POST['customer_name'] ?? '');
        $email = trim($_POST['customer_email'] ?? '');
        $orderNotes = trim($_POST['notes'] ?? '');
        $paymentMethod = ($_POST['payment_method'] ?? 'qris') === 'kasir' ? 'kasir' : 'qris';

        $errors = [];
        if ($name === '') {
            $errors[] = 'Nama pemesan wajib diisi.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email pemesan tidak valid.';
        }
        if (empty($_SESSION['cart'])) {
            $errors[] = 'Keranjang masih kosong.';
        }

        if (empty($errors)) {
            $menuIds = array_values(array_unique(array_column($_SESSION['cart'], 'menu_item_id')));
            $placeholders = implode(',', array_fill(0, count($menuIds), '?'));
            $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id IN ($placeholders)");
            $stmt->execute($menuIds);
            $menuById = [];
            foreach ($stmt->fetchAll() as $row) {
                $menuById[$row['id']] = $row;
            }

            $subtotal = 0;
            foreach ($_SESSION['cart'] as $line) {
                $subtotal += ($menuById[$line['menu_item_id']]['price'] ?? 0) * $line['qty'];
            }

            $pdo->beginTransaction();

            // Reuse the table's active session (so split-bill orders from the
            // same visit group together), or open a new one if the table was empty.
            $stmt = $pdo->prepare("SELECT id FROM table_sessions WHERE table_id = ? AND status = 'aktif'");
            $stmt->execute([$table['id']]);
            $sessionId = $stmt->fetchColumn();
            if (!$sessionId) {
                $pdo->prepare('INSERT INTO table_sessions (table_id) VALUES (?)')->execute([$table['id']]);
                $sessionId = (int) $pdo->lastInsertId();
            }

            $orderCode = generateUniqueOrderCode($pdo);
            $lookupToken = generateLookupToken();

            $insertOrder = $pdo->prepare(
                'INSERT INTO orders (order_code, qr_lookup_token, table_id, table_session_id, customer_name, customer_email, notes, subtotal, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, "menunggu_bayar")'
            );
            $insertOrder->execute([$orderCode, $lookupToken, $table['id'], $sessionId, $name, $email, $orderNotes, $subtotal]);
            $orderId = (int) $pdo->lastInsertId();

            $insertItem = $pdo->prepare(
                'INSERT INTO order_items (order_id, menu_item_id, item_name, price_at_order, quantity, notes)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            foreach ($_SESSION['cart'] as $line) {
                if (!isset($menuById[$line['menu_item_id']])) {
                    continue;
                }
                $menuItem = $menuById[$line['menu_item_id']];
                $insertItem->execute([$orderId, $menuItem['id'], $menuItem['name'], $menuItem['price'], $line['qty'], $line['notes']]);
            }

            $insertPayment = $pdo->prepare(
                'INSERT INTO payments (order_id, method, status) VALUES (?, ?, "pending")'
            );
            $insertPayment->execute([$orderId, $paymentMethod === 'kasir' ? 'Tunai' : 'QRIS']);

            $pdo->commit();

            unset($_SESSION['cart']);
            $tokenParam = '&token=' . rawurlencode($lookupToken);
            if ($paymentMethod === 'kasir') {
                header('Location: kasir-konfirmasi.php?order=' . $orderId . $tokenParam);
            } else {
                header('Location: payment.php?order=' . $orderId . $tokenParam);
            }
            exit;
        }
    }
}

$lines = [];
$subtotal = 0;
if (!empty($_SESSION['cart'])) {
    $menuIds = array_values(array_unique(array_column($_SESSION['cart'], 'menu_item_id')));
    $placeholders = implode(',', array_fill(0, count($menuIds), '?'));
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id IN ($placeholders)");
    $stmt->execute($menuIds);
    $menuById = [];
    foreach ($stmt->fetchAll() as $row) {
        $menuById[$row['id']] = $row;
    }
    foreach ($_SESSION['cart'] as $lineId => $line) {
        if (!isset($menuById[$line['menu_item_id']])) {
            continue;
        }
        $lineTotal = $menuById[$line['menu_item_id']]['price'] * $line['qty'];
        $subtotal += $lineTotal;
        $lines[] = [
            'line_id' => $lineId,
            'item' => $menuById[$line['menu_item_id']],
            'qty' => $line['qty'],
            'notes' => $line['notes'],
            'lineTotal' => $lineTotal,
        ];
    }
}

$checkoutErrors = $errors ?? [];
if (isset($_GET['payment_failed'])) {
    $checkoutErrors[] = 'Pembayaran gagal/dibatalkan. Silakan coba lagi.';
}

$pageTitle = 'Keranjang — Meja ' . $table['table_number'] . ' — Masakan Nusantara';
$pageStylesheet = 'assets/css/pages/order.css';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/order-topbar.php';
?>

<main class="container" style="padding:32px 24px 40px;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:4px;">
        <h1 style="margin:0;">Keranjang Anda — Meja <?php echo (int) $table['table_number']; ?></h1>
        <a href="menu.php" style="color:var(--color-primary);font-size:.85rem;font-weight:600;"><i class="fa-solid fa-arrow-left"></i> Tambah menu lain</a>
    </div>

    <?php if (!empty($checkoutErrors)): ?>
    <div class="order-flash" style="margin:16px 0 0;padding:0;">
        <div class="order-flash__box error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?php echo implode(' ', array_map('htmlspecialchars', $checkoutErrors)); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($lines)): ?>
    <p style="color:var(--color-text-muted);margin-top:16px;">Keranjang masih kosong. <a href="menu.php">Lihat menu</a>.</p>
    <?php else: ?>

    <div class="cart-steps">
        <div class="cart-steps__step active" id="cart-step-label-1"><span class="cart-steps__num">1</span> Rincian Pesanan</div>
        <div class="cart-steps__sep"></div>
        <div class="cart-steps__step" id="cart-step-label-2"><span class="cart-steps__num">2</span> Detail Pemesan</div>
    </div>

    <div id="cart-step-review">
        <form method="post" id="cart-update-form">
            <input type="hidden" name="action" value="update">
        </form>
        <div class="cart-list">
            <?php foreach ($lines as $row): ?>
            <div class="cart-row">
                <div class="cart-row__name">
                    <?php echo htmlspecialchars($row['item']['name']); ?>
                    <?php if ($row['notes']): ?>
                    <div class="cart-row__notes"><?php echo htmlspecialchars($row['notes']); ?></div>
                    <?php endif; ?>
                    <div class="cart-row__price"><?php echo rupiah((int) $row['item']['price']); ?> / porsi</div>
                </div>
                <input type="number" form="cart-update-form" name="qty[<?php echo htmlspecialchars($row['line_id']); ?>]" value="<?php echo (int) $row['qty']; ?>" min="0" max="20" style="width:60px;padding:6px;border-radius:8px;border:1px solid var(--color-border);">
                <strong style="min-width:110px;text-align:right;"><?php echo rupiah((int) $row['lineTotal']); ?></strong>
                <form method="post" class="cart-row__remove-form">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="line_id" value="<?php echo htmlspecialchars($row['line_id']); ?>">
                    <button type="submit" class="cart-row__remove" title="Hapus item" aria-label="Hapus item">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="submit" form="cart-update-form" class="btn btn-outline btn-sm">Update Jumlah</button>

        <div class="cart-summary">
            <div class="cart-summary__row"><span>Subtotal</span><span><?php echo rupiah((int) $subtotal); ?></span></div>
            <div class="cart-summary__row total"><span>Total</span><span><?php echo rupiah((int) $subtotal); ?></span></div>
        </div>

        <div style="text-align:right;">
            <button type="button" id="cart-btn-next" class="btn btn-primary">Lanjut ke Detail Pemesanan <i class="fa-solid fa-arrow-right"></i></button>
        </div>
    </div>

    <div id="cart-step-details" style="display:none;">
        <div class="checkout-form">
            <button type="button" id="cart-btn-back" class="cart-step-back"><i class="fa-solid fa-arrow-left"></i> Kembali ke Rincian Pesanan</button>
            <h3 style="margin-bottom:16px;">Detail Pemesan</h3>
            <form method="post">
                <input type="hidden" name="action" value="checkout">
                <div class="form-group">
                    <label class="form-label" for="customer_name">Nama</label>
                    <input class="form-control" type="text" id="customer_name" name="customer_name" required value="<?php echo htmlspecialchars($_POST['customer_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="customer_email">Email</label>
                    <input class="form-control" type="email" id="customer_email" name="customer_email" required value="<?php echo htmlspecialchars($_POST['customer_email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="notes">Catatan untuk seluruh pesanan (opsional)</label>
                    <input class="form-control" type="text" id="notes" name="notes" placeholder="mis. tolong dibungkus" value="<?php echo htmlspecialchars($_POST['notes'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Metode Pembayaran</label>
                    <label style="display:flex;align-items:center;gap:8px;font-weight:600;margin-bottom:8px;">
                        <input type="radio" name="payment_method" id="pm-qris" value="qris" <?php echo ($_POST['payment_method'] ?? 'qris') !== 'kasir' ? 'checked' : ''; ?>>
                        Bayar dengan QRIS
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;font-weight:600;">
                        <input type="radio" name="payment_method" id="pm-kasir" value="kasir" <?php echo ($_POST['payment_method'] ?? '') === 'kasir' ? 'checked' : ''; ?>>
                        Bayar di Kasir
                    </label>
                </div>
                <button type="submit" class="btn btn-primary btn-block" id="cart-submit-pay">Bayar dengan QRIS <i class="fa-solid fa-qrcode"></i></button>
            </form>
        </div>
    </div>

    <script>
    (function () {
        var review = document.getElementById('cart-step-review');
        var details = document.getElementById('cart-step-details');
        var labelReview = document.getElementById('cart-step-label-1');
        var labelDetails = document.getElementById('cart-step-label-2');
        var btnNext = document.getElementById('cart-btn-next');
        var btnBack = document.getElementById('cart-btn-back');
        var pmQris = document.getElementById('pm-qris');
        var pmKasir = document.getElementById('pm-kasir');
        var submitPay = document.getElementById('cart-submit-pay');

        function updatePaySubmitLabel() {
            if (!submitPay) { return; }
            submitPay.innerHTML = (pmKasir && pmKasir.checked)
                ? 'Lanjutkan ke Kasir <i class="fa-solid fa-arrow-right"></i>'
                : 'Bayar dengan QRIS <i class="fa-solid fa-qrcode"></i>';
        }
        if (pmQris) { pmQris.addEventListener('change', updatePaySubmitLabel); }
        if (pmKasir) { pmKasir.addEventListener('change', updatePaySubmitLabel); }
        updatePaySubmitLabel();

        function showDetails() {
            review.style.display = 'none';
            details.style.display = 'block';
            labelReview.classList.remove('active');
            labelDetails.classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function showReview() {
            details.style.display = 'none';
            review.style.display = 'block';
            labelDetails.classList.remove('active');
            labelReview.classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        if (btnNext) { btnNext.addEventListener('click', showDetails); }
        if (btnBack) { btnBack.addEventListener('click', showReview); }

        <?php if (!empty($checkoutErrors) && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        showDetails();
        <?php endif; ?>
    })();
    </script>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/order-footer.php'; ?>
