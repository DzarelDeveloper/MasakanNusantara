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
    $number = (int) ($_POST['table_number'] ?? 0);
    $capacity = trim($_POST['capacity_label'] ?? '');

    if ($number <= 0) {
        $errors[] = 'Nomor meja harus lebih dari 0.';
    }
    if ($capacity === '') {
        $errors[] = 'Kapasitas wajib diisi (mis. 1-2).';
    }

    if (empty($errors)) {
        $dup = $pdo->prepare('SELECT COUNT(*) FROM dining_tables WHERE table_number = ?');
        $dup->execute([$number]);
        if ($dup->fetchColumn() > 0) {
            $errors[] = "Meja nomor $number sudah ada.";
        } else {
            $pdo->prepare('INSERT INTO dining_tables (table_number, capacity_label, qr_token) VALUES (?, ?, ?)')
                ->execute([$number, $capacity, bin2hex(random_bytes(16))]);
            // QR-nya sudah otomatis ter-generate begitu meja tersimpan (token
            // unik dibuat saat insert) — tidak perlu mampir halaman QR dulu,
            // langsung balik ke daftar meja supaya bisa lanjut tambah lagi.
            header('Location: tables.php');
            exit;
        }
    }
}

$pageTitleOwner = 'Tambah Meja Baru';
include __DIR__ . '/includes/layout_top.php';
?>

<div class="owner-toolbar">
    <h2 style="margin:0;">Tambah Meja Baru</h2>
    <a href="tables.php" class="owner-btn"><i class="fa-solid fa-arrow-left"></i> Kembali ke Kelola Meja</a>
</div>

<?php foreach ($errors as $e): ?>
<div class="owner-error"><?php echo htmlspecialchars($e); ?></div>
<?php endforeach; ?>

<div class="owner-modal-form" style="max-width:480px;margin:0 auto;">
    <form method="post">
        <?php echo csrfField(); ?>
        <div class="owner-field">
            <label>Nomor Meja</label>
            <input type="number" name="table_number" min="1" required autofocus value="<?php echo htmlspecialchars($_POST['table_number'] ?? ''); ?>">
        </div>
        <div class="owner-field">
            <label>Kapasitas (mis. 1-2 atau 2-4)</label>
            <input type="text" name="capacity_label" required value="<?php echo htmlspecialchars($_POST['capacity_label'] ?? ''); ?>">
        </div>
        <button type="submit" class="owner-btn owner-btn--primary owner-btn--block">Tambah &amp; Buat QR Meja</button>
    </form>
</div>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
