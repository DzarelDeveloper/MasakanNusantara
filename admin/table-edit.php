<?php
require_once __DIR__ . '/../includes/session.php';
startSecureSession();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

$staff = requireRole(['admin']);
$pdo = db();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM dining_tables WHERE id = ?');
$stmt->execute([$id]);
$table = $stmt->fetch();

if (!$table) {
    header('Location: tables.php');
    exit;
}

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
        $dup = $pdo->prepare('SELECT COUNT(*) FROM dining_tables WHERE table_number = ? AND id != ?');
        $dup->execute([$number, $id]);
        if ($dup->fetchColumn() > 0) {
            $errors[] = "Meja nomor $number sudah ada.";
        } else {
            $pdo->prepare('UPDATE dining_tables SET table_number = ?, capacity_label = ? WHERE id = ?')
                ->execute([$number, $capacity, $id]);
            header('Location: tables.php');
            exit;
        }
    }
    $table['table_number'] = $number;
    $table['capacity_label'] = $capacity;
}

$pageTitleOwner = 'Edit Meja #' . (int) $table['table_number'];
include __DIR__ . '/includes/layout_top.php';
?>

<div class="owner-toolbar">
    <h2 style="margin:0;">Edit Meja #<?php echo (int) $table['table_number']; ?></h2>
    <a href="tables.php" class="owner-btn"><i class="fa-solid fa-arrow-left"></i> Kembali ke Kelola Meja</a>
</div>

<?php foreach ($errors as $e): ?>
<div class="owner-error"><?php echo htmlspecialchars($e); ?></div>
<?php endforeach; ?>

<div class="owner-modal-form" style="max-width:480px;margin:0 auto;">
    <form method="post">
        <?php echo csrfField(); ?>
        <input type="hidden" name="id" value="<?php echo (int) $table['id']; ?>">
        <div class="owner-field">
            <label>Nomor Meja</label>
            <input type="number" name="table_number" min="1" required autofocus value="<?php echo htmlspecialchars($table['table_number']); ?>">
        </div>
        <div class="owner-field">
            <label>Kapasitas (mis. 1-2 atau 2-4)</label>
            <input type="text" name="capacity_label" required value="<?php echo htmlspecialchars($table['capacity_label']); ?>">
        </div>
        <button type="submit" class="owner-btn owner-btn--primary owner-btn--block">Simpan Perubahan</button>
    </form>
</div>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
