<?php
require_once __DIR__ . '/../includes/session.php';
startSecureSession();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

$staff = requireRole(['admin']);
$pdo = db();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $category = trim($_POST['category'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (int) ($_POST['price'] ?? 0);
    $image = trim($_POST['image'] ?? '');
    $serveTemp = $_POST['serve_temp'] ?? 'keduanya';
    if (!in_array($serveTemp, ['keduanya', 'dingin', 'panas'], true)) {
        $serveTemp = 'keduanya';
    }
    $spiceOption = $_POST['spice_option'] ?? 'ada';
    if (!in_array($spiceOption, ['ada', 'tidak_ada'], true)) {
        $spiceOption = 'ada';
    }

    if (!in_array($category, ['Makanan', 'Minuman', 'Dimsum'], true) || $name === '' || $price <= 0) {
        $errors[] = 'Kategori, nama, dan harga (lebih dari 0) wajib diisi.';
    }

    // Uploaded file takes precedence over the manual URL field.
    if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $tmpPath = $_FILES['image_file']['tmp_name'];
        $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $tmpPath);

        if (!isset($allowedMimes[$mime])) {
            $errors[] = 'Format gambar harus JPG, PNG, atau WEBP.';
        } elseif ($_FILES['image_file']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Ukuran gambar maksimal 2MB.';
        } else {
            $filename = bin2hex(random_bytes(8)) . '.' . $allowedMimes[$mime];
            $destDir = __DIR__ . '/../assets/images/menu/';
            if (move_uploaded_file($tmpPath, $destDir . $filename)) {
                $image = 'assets/images/menu/' . $filename;
            } else {
                $errors[] = 'Gagal mengunggah gambar.';
            }
        }
    }

    if (empty($errors)) {
        $maxSort = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM menu_items')->fetchColumn();
        $pdo->prepare(
            'INSERT INTO menu_items (category, name, description, price, image, sort_order, serve_temp, spice_option) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$category, $name, $description, $price, $image, $maxSort + 1, $serveTemp, $spiceOption]);
        header('Location: menu-manage.php');
        exit;
    }
}

$pageTitleOwner = 'Tambah Menu Baru';
include __DIR__ . '/includes/layout_top.php';
?>

<div class="owner-toolbar">
    <h2 style="margin:0;">Tambah Menu Baru</h2>
    <a href="menu-manage.php" class="owner-btn"><i class="fa-solid fa-arrow-left"></i> Kembali ke Kelola Menu</a>
</div>

<?php foreach ($errors as $e): ?>
<div class="owner-error"><?php echo htmlspecialchars($e); ?></div>
<?php endforeach; ?>

<div class="owner-modal-form" style="max-width:820px;margin:0 auto;">
    <form method="post" enctype="multipart/form-data">
        <?php echo csrfField(); ?>
        <div class="owner-field-row">
            <div class="owner-field">
                <label>Kategori</label>
                <?php $currentCategory = $_POST['category'] ?? ''; ?>
                <select id="menuCategory" name="category" required>
                    <option value="" <?php echo $currentCategory === '' ? 'selected' : ''; ?> disabled>Pilih kategori</option>
                    <option value="Makanan" <?php echo $currentCategory === 'Makanan' ? 'selected' : ''; ?>>Makanan</option>
                    <option value="Minuman" <?php echo $currentCategory === 'Minuman' ? 'selected' : ''; ?>>Minuman</option>
                    <option value="Dimsum" <?php echo $currentCategory === 'Dimsum' ? 'selected' : ''; ?>>Dimsum</option>
                </select>
            </div>
            <div class="owner-field">
                <label>Nama Menu</label>
                <input type="text" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
            </div>
            <div class="owner-field">
                <label>Harga (Rp)</label>
                <input type="number" name="price" min="1" required value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
            </div>
        </div>
        <div class="owner-field-row owner-field-row--2col">
            <div class="owner-field" id="pedasOptionField">
                <label>Pilihan Tingkat Pedas</label>
                <?php $currentSpice = $_POST['spice_option'] ?? 'ada'; ?>
                <select name="spice_option">
                    <option value="ada" <?php echo $currentSpice === 'ada' ? 'selected' : ''; ?>>Ada pilihan (pelanggan pilih Tidak Pedas/Sedang/Pedas/Sangat Pedas)</option>
                    <option value="tidak_ada" <?php echo $currentSpice === 'tidak_ada' ? 'selected' : ''; ?>>Tidak ada pilihan (disajikan dengan level tetap)</option>
                </select>
            </div>
            <div class="owner-field" id="suhuPenyajianField">
                <label>Suhu Penyajian</label>
                <?php $currentTemp = $_POST['serve_temp'] ?? 'keduanya'; ?>
                <select name="serve_temp">
                    <option value="keduanya" <?php echo $currentTemp === 'keduanya' ? 'selected' : ''; ?>>Keduanya (pelanggan pilih Dingin/Panas)</option>
                    <option value="dingin" <?php echo $currentTemp === 'dingin' ? 'selected' : ''; ?>>Dingin saja (tidak perlu ditanya)</option>
                    <option value="panas" <?php echo $currentTemp === 'panas' ? 'selected' : ''; ?>>Panas saja (tidak perlu ditanya)</option>
                </select>
            </div>
            <div class="owner-field">
                <label>Unggah Foto (JPG/PNG/WEBP, maks 2MB)</label>
                <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp">
            </div>
        </div>
        <div class="owner-field">
            <label>atau URL Gambar (dipakai kalau tidak unggah file)</label>
            <input type="text" name="image" placeholder="assets/images/photos/..." value="<?php echo htmlspecialchars($_POST['image'] ?? ''); ?>">
        </div>
        <div class="owner-field">
            <label>Deskripsi</label>
            <textarea name="description" rows="2"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>
        <button type="submit" class="owner-btn owner-btn--primary owner-btn--block">Tambah Menu</button>
    </form>
</div>

<script>
(function () {
    var categoryInput = document.getElementById('menuCategory');
    var suhuField = document.getElementById('suhuPenyajianField');
    var pedasField = document.getElementById('pedasOptionField');
    if (!categoryInput || !suhuField || !pedasField) { return; }

    function updateOptionFields() {
        suhuField.style.display = categoryInput.value === 'Minuman' ? '' : 'none';
        pedasField.style.display = categoryInput.value === 'Makanan' ? '' : 'none';
    }

    categoryInput.addEventListener('change', updateOptionFields);
    updateOptionFields();
})();
</script>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
