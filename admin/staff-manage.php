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
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'staff';

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Nama dan email valid wajib diisi.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Kata sandi minimal 6 karakter.';
        } else {
            $dup = $pdo->prepare('SELECT COUNT(*) FROM staff_users WHERE email = ?');
            $dup->execute([$email]);
            if ($dup->fetchColumn() > 0) {
                $errors[] = 'Email ini sudah dipakai akun lain.';
            } else {
                $pdo->prepare('INSERT INTO staff_users (name, email, password_hash, role) VALUES (?, ?, ?, ?)')
                    ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
                header('Location: staff-manage.php');
                exit;
            }
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === $staff['id']) {
            $errors[] = 'Tidak bisa menghapus akun yang sedang login.';
        } else {
            $pdo->prepare('DELETE FROM staff_users WHERE id = ?')->execute([$id]);
            header('Location: staff-manage.php');
            exit;
        }
    }
}

$staffList = $pdo->query('SELECT * FROM staff_users ORDER BY role ASC, name ASC')->fetchAll();

$pageTitleOwner = 'Kelola Staf';
include __DIR__ . '/includes/layout_top.php';
?>

<div class="owner-toolbar">
    <h2 style="margin:0;">Kelola Staf (<?php echo count($staffList); ?>)</h2>
</div>

<?php foreach ($errors as $e): ?>
<div class="owner-modal-form" style="border-left:4px solid #b91c1c;"><?php echo htmlspecialchars($e); ?></div>
<?php endforeach; ?>

<div class="owner-modal-form">
    <h3 style="margin-bottom:12px;">Tambah Akun Staf/Admin</h3>
    <form method="post">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="add">
        <div class="owner-field-row">
            <div class="owner-field">
                <label>Nama</label>
                <input type="text" name="name" required>
            </div>
            <div class="owner-field">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="owner-field">
                <label>Kata Sandi</label>
                <input type="password" name="password" required minlength="6">
            </div>
            <div class="owner-field">
                <label>Peran</label>
                <select name="role">
                    <option value="staff">Staf/Kasir (hanya akses Pesanan)</option>
                    <option value="admin">Admin (akses penuh)</option>
                </select>
            </div>
        </div>
        <button type="submit" class="owner-btn owner-btn--primary owner-btn--block" style="margin-top:12px;">Tambah Akun</button>
    </form>
</div>

<div class="owner-table-wrap">
    <table class="owner-table">
        <thead>
            <tr><th>Nama</th><th>Email</th><th>Peran</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            <?php foreach ($staffList as $s): ?>
            <tr>
                <td data-label="Nama"><?php echo htmlspecialchars($s['name']); ?><?php echo $s['id'] === $staff['id'] ? ' <span style="color:#6b7280;">(Anda)</span>' : ''; ?></td>
                <td data-label="Email"><?php echo htmlspecialchars($s['email']); ?></td>
                <td data-label="Peran"><span class="pill <?php echo $s['role'] === 'admin' ? 'tersedia' : 'kosong'; ?>"><?php echo ucfirst($s['role']); ?></span></td>
                <td data-label="Aksi" class="owner-table__actions">
                    <?php if ((int) $s['id'] !== $staff['id']): ?>
                    <form method="post" data-confirm="Hapus akun staf ini? Aksi ini tidak bisa dibatalkan." data-confirm-danger>
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo (int) $s['id']; ?>">
                        <button type="submit" class="owner-btn owner-btn--danger">Hapus</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
