<?php
require_once __DIR__ . '/includes/session.php';
startSecureSession();
require_once __DIR__ . '/includes/db.php';

$pdo = db();

// Resolve which table this visit belongs to: from the QR URL (?table=&token=)
// or, if already scanned earlier in this session, from the session itself.
$tableNumber = isset($_GET['table']) ? (int) $_GET['table'] : null;
$token = $_GET['token'] ?? null;

if ($tableNumber !== null && $token !== null) {
    $stmt = $pdo->prepare('SELECT * FROM dining_tables WHERE table_number = ? AND qr_token = ?');
    $stmt->execute([$tableNumber, $token]);
    $table = $stmt->fetch();

    if (!$table) {
        http_response_code(404);
        $pageTitle = 'QR Tidak Valid — Masakan Nusantara';
        $pageStylesheet = 'assets/css/pages/order.css';
        include __DIR__ . '/includes/header.php';
        include __DIR__ . '/includes/order-topbar.php';
        ?>
        <main class="container" style="padding:80px 24px;text-align:center;">
            <i class="fa-solid fa-triangle-exclamation" style="font-size:3rem;color:var(--color-primary);"></i>
            <h1 style="margin-top:16px;">QR Meja Tidak Dikenali</h1>
            <p style="color:var(--color-text-muted);margin-top:8px;">
                Kode QR ini tidak cocok dengan meja manapun. Silakan scan ulang QR yang ada di meja Anda,
                atau panggil staf kami untuk bantuan.
            </p>
        </main>
        <?php
        include __DIR__ . '/includes/order-footer.php';
        exit;
    }

    $_SESSION['active_table_id']     = (int) $table['id'];
    $_SESSION['active_table_number'] = (int) $table['table_number'];
    $_SESSION['active_table_token']  = $table['qr_token'];
} elseif (isset($_SESSION['active_table_id'])) {
    $stmt = $pdo->prepare('SELECT * FROM dining_tables WHERE id = ?');
    $stmt->execute([$_SESSION['active_table_id']]);
    $table = $stmt->fetch();
} else {
    $table = null;
}

if (!$table) {
    $pageTitle = 'Scan QR Meja — Masakan Nusantara';
    $pageStylesheet = 'assets/css/pages/order.css';
    include __DIR__ . '/includes/header.php';
    include __DIR__ . '/includes/order-topbar.php';
    ?>
    <main class="container" style="padding:80px 24px;text-align:center;">
        <i class="fa-solid fa-qrcode" style="font-size:3rem;color:var(--color-primary);"></i>
        <h1 style="margin-top:16px;">Scan QR di Meja Anda</h1>
        <p style="color:var(--color-text-muted);margin-top:8px;">
            Untuk memesan, silakan scan kode QR yang tertempel di meja tempat Anda duduk.
            Menu akan otomatis muncul sesuai meja Anda.
        </p>
    </main>
    <?php
    include __DIR__ . '/includes/order-footer.php';
    exit;
}

// --- Add-to-cart (PRG pattern to avoid resubmission on refresh) ---
// Cart is a LIST of lines (not keyed by menu_item_id) so the same dish can
// be added twice with different spice level / temperature / notes.
$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $menuItemId = (int) ($_POST['menu_item_id'] ?? 0);
    $itemNotes = trim($_POST['item_notes'] ?? '');
    $optionQtysRaw = $_POST['option_qtys'] ?? [];

    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ? AND stock_status = 'tersedia'");
    $stmt->execute([$menuItemId]);
    $item = $stmt->fetch();

    if ($item) {
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Picker (dan opsi label yang valid) ditentukan dari kategori/
        // konfigurasi item di server, bukan dipercaya dari input klien —
        // jadi tidak mungkin ada label pedas/suhu palsu nyelip ke notes.
        $hasPicker = ($item['category'] === 'Makanan' && $item['spice_option'] === 'ada')
            || ($item['category'] === 'Minuman' && $item['serve_temp'] === 'keduanya');

        $allowedOptions = [];
        if ($item['category'] === 'Makanan') {
            $allowedOptions = ['Tidak Pedas', 'Sedang', 'Pedas', 'Sangat Pedas'];
        } elseif ($item['category'] === 'Minuman') {
            $allowedOptions = ['Dingin', 'Panas'];
        }

        // Satu form bisa langsung bikin beberapa baris keranjang sekaligus
        // (mis. 1 Pedas + 2 Sedang), berguna kalau satu meja pesan menu
        // yang sama tapi level pedas/suhunya beda-beda per orang.
        $linesToAdd = [];
        if ($hasPicker && is_array($optionQtysRaw)) {
            foreach ($allowedOptions as $label) {
                $qty = max(0, min(20, (int) ($optionQtysRaw[$label] ?? 0)));
                if ($qty > 0) {
                    $linesToAdd[] = ['qty' => $qty, 'option' => $label];
                }
            }
        } else {
            $qty = max(1, min(20, (int) ($_POST['qty'] ?? 1)));
            $linesToAdd[] = ['qty' => $qty, 'option' => null];
        }

        $addedTotal = 0;
        foreach ($linesToAdd as $line) {
            $optionParts = [];
            if ($line['option'] !== null) {
                $optionParts[] = $line['option'];
            }
            if ($itemNotes !== '') {
                $optionParts[] = $itemNotes;
            }

            $lineId = uniqid('line_', true);
            $_SESSION['cart'][$lineId] = [
                'menu_item_id' => $menuItemId,
                'qty' => $line['qty'],
                'notes' => implode(' · ', $optionParts),
            ];
            $addedTotal += $line['qty'];
        }

        if ($addedTotal > 0) {
            $_SESSION['flash'] = $item['name'] . ' (' . $addedTotal . ' porsi) ditambahkan ke keranjang.';
        }
    }

    header('Location: menu.php');
    exit;
}

if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// Habis items are auto-hidden from the customer menu (not just greyed out).
$items = $pdo->query("SELECT * FROM menu_items WHERE stock_status = 'tersedia' ORDER BY sort_order ASC")->fetchAll();
$byCategory = [];
foreach ($items as $item) {
    $byCategory[$item['category']][] = $item;
}

$cartCount = 0;
$cartTotal = 0;
if (!empty($_SESSION['cart'])) {
    $ids = array_values(array_unique(array_column($_SESSION['cart'], 'menu_item_id')));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, price FROM menu_items WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $prices = array_column($stmt->fetchAll(), 'price', 'id');
    foreach ($_SESSION['cart'] as $line) {
        $cartCount += $line['qty'];
        $cartTotal += ($prices[$line['menu_item_id']] ?? 0) * $line['qty'];
    }
}

$pageTitle = 'Menu — Meja ' . $table['table_number'] . ' — Masakan Nusantara';
$pageStylesheet = 'assets/css/pages/order.css';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/order-topbar.php';

$categoryTags = array_keys($byCategory);
?>

<section class="menu-hero">
    <img src="assets/images/banner/restaurant-interior.jpg" alt="Suasana Masakan Nusantara">
    <div class="menu-hero__content">
        <span class="menu-hero__eyebrow">Selamat Datang</span>
        <h1 class="menu-hero__title">Masakan Nusantara</h1>
        <div class="menu-hero__badge">
            <i class="fa-solid fa-chair"></i>
            Meja <?php echo (int) $table['table_number']; ?> · <?php echo htmlspecialchars($table['capacity_label']); ?> orang
        </div>
    </div>
</section>

<?php if ($flash): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.showToast) {
        window.showToast(<?php echo json_encode($flash); ?>, 'success', 2000);
    }
});
</script>
<?php endif; ?>

<div class="menu-search">
    <div class="menu-search__box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" id="menuSearch" placeholder="Cari menu...">
    </div>
</div>

<div class="menu-tags" id="menuTags">
    <button type="button" class="menu-tag active" data-filter="semua">Semua</button>
    <?php foreach ($categoryTags as $cat): ?>
    <button type="button" class="menu-tag" data-filter="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></button>
    <?php endforeach; ?>
</div>

<main class="container" style="padding-bottom:100px;">
    <?php foreach ($byCategory as $category => $categoryItems): ?>
    <section class="menu-category" data-category="<?php echo htmlspecialchars($category); ?>">
        <h2><?php echo htmlspecialchars($category); ?></h2>
        <div class="menu-grid">
            <?php foreach ($categoryItems as $item): ?>
            <button type="button"
                    class="menu-item menu-item--pick"
                    data-name="<?php echo htmlspecialchars(strtolower($item['name'])); ?>"
                    data-id="<?php echo (int) $item['id']; ?>"
                    data-item-name="<?php echo htmlspecialchars($item['name']); ?>"
                    data-desc="<?php echo htmlspecialchars($item['description']); ?>"
                    data-price="<?php echo (int) $item['price']; ?>"
                    data-price-label="<?php echo htmlspecialchars(rupiah((int) $item['price'])); ?>"
                    data-image="<?php echo htmlspecialchars($item['image']); ?>"
                    data-category="<?php echo htmlspecialchars($item['category']); ?>"
                    data-serve-temp="<?php echo htmlspecialchars($item['serve_temp']); ?>"
                    data-spice-option="<?php echo htmlspecialchars($item['spice_option']); ?>">
                <div class="menu-item__image">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" loading="lazy">
                </div>
                <div class="menu-item__body">
                    <div class="menu-item__name"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div class="menu-item__desc"><?php echo htmlspecialchars($item['description']); ?></div>
                    <div class="menu-item__footer">
                        <span class="menu-item__price"><?php echo rupiah((int) $item['price']); ?></span>
                        <span class="menu-item__pick-cta">Pilih <i class="fa-solid fa-chevron-right"></i></span>
                    </div>
                </div>
            </button>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
</main>

<?php if ($cartCount > 0): ?>
<div class="cart-float">
    <div class="cart-float__info">
        <span class="cart-float__count"><i class="fa-solid fa-basket-shopping"></i> <?php echo $cartCount; ?> item</span>
        <span class="cart-float__total"><?php echo rupiah($cartTotal); ?></span>
    </div>
    <a href="cart.php" class="btn btn-primary btn-sm">Lihat Keranjang <i class="fa-solid fa-arrow-right"></i></a>
</div>
<?php endif; ?>

<div class="item-sheet" id="itemSheet" aria-hidden="true">
    <div class="item-sheet__backdrop" data-sheet-close></div>
    <div class="item-sheet__panel" role="dialog" aria-modal="true" aria-labelledby="sheetName">
        <div class="item-sheet__handle"></div>
        <button type="button" class="item-sheet__close" data-sheet-close aria-label="Tutup">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="item-sheet__image">
            <img id="sheetImage" src="" alt="">
        </div>

        <div class="item-sheet__body">
            <h3 id="sheetName"></h3>
            <p class="item-sheet__desc" id="sheetDesc"></p>
            <div class="item-sheet__price" id="sheetPrice"></div>

            <form method="post" id="sheetForm">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="menu_item_id" id="sheetItemId" value="">

                <div class="item-sheet__section" id="sheetSpiceSection">
                    <label class="item-sheet__label">Tingkat Kepedasan</label>
                    <div class="option-qty-list">
                        <?php foreach (['Tidak Pedas', 'Sedang', 'Pedas', 'Sangat Pedas'] as $spiceLabel): ?>
                        <div class="option-qty-row">
                            <span class="option-qty-row__label"><?php echo $spiceLabel; ?></span>
                            <div class="qty-stepper qty-stepper--row">
                                <button type="button" class="qty-stepper__btn" data-row-minus>−</button>
                                <input type="text" class="option-qty-row__input" name="option_qtys[<?php echo $spiceLabel; ?>]" value="0" inputmode="numeric" readonly>
                                <button type="button" class="qty-stepper__btn" data-row-plus>+</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="item-sheet__section" id="sheetTempSection">
                    <label class="item-sheet__label">Suhu</label>
                    <div class="option-qty-list">
                        <?php foreach (['Dingin', 'Panas'] as $tempLabel): ?>
                        <div class="option-qty-row">
                            <span class="option-qty-row__label"><?php echo $tempLabel; ?></span>
                            <div class="qty-stepper qty-stepper--row">
                                <button type="button" class="qty-stepper__btn" data-row-minus>−</button>
                                <input type="text" class="option-qty-row__input" name="option_qtys[<?php echo $tempLabel; ?>]" value="0" inputmode="numeric" readonly>
                                <button type="button" class="qty-stepper__btn" data-row-plus>+</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="item-sheet__section">
                    <label class="item-sheet__label" for="sheetNotes">Catatan (opsional)</label>
                    <input type="text" name="item_notes" id="sheetNotes" placeholder="mis. sausnya dipisah" maxlength="150">
                </div>

                <p class="item-sheet__hint" id="sheetHint">Pilih minimal 1 porsi dulu ya.</p>

                <div class="item-sheet__footer">
                    <div class="qty-stepper" id="sheetQtyFooter">
                        <button type="button" class="qty-stepper__btn" data-qty-minus aria-label="Kurangi jumlah">−</button>
                        <input type="text" name="qty" id="sheetQty" value="1" inputmode="numeric" readonly>
                        <button type="button" class="qty-stepper__btn" data-qty-plus aria-label="Tambah jumlah">+</button>
                    </div>
                    <button type="submit" class="btn btn-primary" id="sheetSubmit">Tambah ke Keranjang</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $pageScript = 'assets/js/menu-order.js'; include __DIR__ . '/includes/order-footer.php'; ?>
