<?php
require_once __DIR__ . '/../includes/session.php';
startSecureSession();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

$staff = requireStaffLogin();
$pdo = db();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM staff_users WHERE id = ?');
    $stmt->execute([$staff['id']]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($current, $row['password_hash'])) {
        $error = 'Kata sandi saat ini salah.';
    } elseif (strlen($new) < 6) {
        $error = 'Kata sandi baru minimal 6 karakter.';
    } elseif ($new !== $confirm) {
        $error = 'Konfirmasi kata sandi tidak cocok.';
    } else {
        $pdo->prepare('UPDATE staff_users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $staff['id']]);
        $success = 'Kata sandi berhasil diubah.';
    }
}

$pageTitleOwner = 'Ganti Kata Sandi';
include __DIR__ . '/includes/layout_top.php';
?>

<div class="owner-modal-form" style="max-width:420px;">
    <h3 style="margin-bottom:12px;">Ganti Kata Sandi</h3>

    <?php if ($error): ?>
    <div class="owner-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="owner-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="post">
        <?php echo csrfField(); ?>
        <div class="owner-field">
            <label>Kata Sandi Saat Ini</label>
            <input type="password" name="current_password" required>
        </div>
        <div class="owner-field">
            <label>Kata Sandi Baru</label>
            <input type="password" name="new_password" required minlength="6">
        </div>
        <div class="owner-field">
            <label>Konfirmasi Kata Sandi Baru</label>
            <input type="password" name="confirm_password" required minlength="6">
        </div>
        <button type="submit" class="owner-btn owner-btn--primary owner-btn--block">Simpan</button>
    </form>
</div>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
