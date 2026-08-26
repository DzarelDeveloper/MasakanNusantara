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

    if ($action === 'toggle_stock') {
        $id = (int) ($_POST['id'] ?? 0);
        $pdo->prepare(
            "UPDATE menu_items SET stock_status = IF(stock_status = 'tersedia', 'habis', 'tersedia') WHERE id = ?"
        )->execute([$id]);
        header('Location: menu-manage.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $inUse = $pdo->prepare('SELECT COUNT(*) FROM order_items WHERE menu_item_id = ?');
        $inUse->execute([$id]);
        if ($inUse->fetchColumn() > 0) {
            $errors[] = 'Menu ini tidak bisa dihapus karena sudah pernah dipesan. Tandai "Habis" saja.';
        } else {
            $pdo->prepare('DELETE FROM menu_items WHERE id = ?')->execute([$id]);
            header('Location: menu-manage.php');
            exit;
        }
    }
}

$items = $pdo->query('SELECT * FROM menu_items ORDER BY category ASC, sort_order ASC')->fetchAll();

$pageTitleOwner = 'Kelola Menu';
include __DIR__ . '/includes/layout_top.php';
?>

<div class="owner-toolbar">
    <h2 style="margin:0;">Kelola Menu (<?php echo count($items); ?> item)</h2>
    <a href="menu-add.php" class="owner-btn owner-btn--primary"><i class="fa-solid fa-plus"></i> Tambah Menu Baru</a>
</div>

<?php foreach ($errors as $e): ?>
<div class="owner-modal-form" style="border-left:4px solid #b91c1c;"><?php echo htmlspecialchars($e); ?></div>
<?php endforeach; ?>

<div class="owner-table-wrap">
    <table class="owner-table">
        <thead>
            <tr><th>Foto</th><th>Kategori</th><th>Nama</th><th>Harga</th><th>Suhu</th><th>Pedas</th><th>Status</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td data-label="Foto">
                    <?php if ($item['image']): ?>
                    <img src="../<?php echo htmlspecialchars($item['image']); ?>" alt="" width="48" height="48" style="object-fit:cover;border-radius:8px;">
                    <?php endif; ?>
                </td>
                <td data-label="Kategori"><?php echo htmlspecialchars($item['category']); ?></td>
                <td data-label="Nama"><?php echo htmlspecialchars($item['name']); ?></td>
                <td data-label="Harga"><?php echo rupiah((int) $item['price']); ?></td>
                <td data-label="Suhu"><?php echo $item['category'] === 'Minuman' ? ucfirst($item['serve_temp']) : '—'; ?></td>
                <td data-label="Pedas"><?php echo $item['category'] === 'Makanan' ? ($item['spice_option'] === 'ada' ? 'Ada' : 'Tidak ada') : '—'; ?></td>
                <td data-label="Status"><span class="pill <?php echo $item['stock_status']; ?>"><?php echo ucfirst($item['stock_status']); ?></span></td>
                <td data-label="Aksi" class="owner-table__actions">
                    <a href="menu-edit.php?id=<?php echo (int) $item['id']; ?>" class="owner-btn">Edit</a>
                    <form method="post">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="toggle_stock">
                        <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
                        <button type="submit" class="owner-btn"><?php echo $item['stock_status'] === 'tersedia' ? 'Tandai Habis' : 'Tandai Tersedia'; ?></button>
                    </form>
                    <form method="post" data-confirm="Hapus menu ini? Aksi ini tidak bisa dibatalkan." data-confirm-danger>
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
                        <button type="submit" class="owner-btn owner-btn--danger">Hapus</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
