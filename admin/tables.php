<?php
require_once __DIR__ . '/../includes/session.php';
startSecureSession();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/qr.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

$staff = requireRole(['admin', 'staff']);
$pdo = db();

function ownerBaseUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']); // .../owner
    $root = dirname($scriptDir);
    $root = ($root === '/' || $root === '\\') ? '' : $root;
    return $scheme . '://' . $_SERVER['HTTP_HOST'] . $root;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'regenerate_token' && $staff['role'] === 'admin') {
        $id = (int) ($_POST['id'] ?? 0);
        $pdo->prepare('UPDATE dining_tables SET qr_token = ? WHERE id = ?')
            ->execute([bin2hex(random_bytes(16)), $id]);
        // QR lama tidak berlaku lagi begitu di-regenerasi — langsung tampilkan
        // QR baru biar admin bisa cetak/tempel gantinya saat itu juga.
        header('Location: print-qr.php?table=' . $id);
        exit;
    }

    if ($action === 'close_session') {
        $tableId = (int) ($_POST['table_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT id FROM table_sessions WHERE table_id = ? AND status = 'aktif'");
        $stmt->execute([$tableId]);
        $sessionId = $stmt->fetchColumn();

        if ($sessionId) {
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM orders WHERE table_session_id = ? AND status NOT IN ('selesai', 'dibatalkan')"
            );
            $stmt->execute([$sessionId]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Meja ini masih punya pesanan yang belum selesai — tidak bisa ditutup dulu.';
            } else {
                $pdo->prepare("UPDATE table_sessions SET status = 'selesai', ended_at = NOW() WHERE id = ?")->execute([$sessionId]);
                // Pembersihan meja dikerjakan langsung offline oleh staf, tidak
                // perlu status antara di aplikasi — meja langsung kosong lagi.
                $pdo->prepare("UPDATE dining_tables SET status = 'kosong' WHERE id = ?")->execute([$tableId]);
                header('Location: tables.php');
                exit;
            }
        }
    }

    if ($action === 'delete' && $staff['role'] === 'admin') {
        $id = (int) ($_POST['id'] ?? 0);
        $inUse = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE table_id = ?');
        $inUse->execute([$id]);
        if ($inUse->fetchColumn() > 0) {
            $errors[] = 'Meja ini tidak bisa dihapus karena sudah punya riwayat pesanan.';
        } else {
            $pdo->prepare('DELETE FROM dining_tables WHERE id = ?')->execute([$id]);
            header('Location: tables.php');
            exit;
        }
    }
}

$tables = $pdo->query('SELECT * FROM dining_tables ORDER BY table_number ASC')->fetchAll();
$baseUrl = ownerBaseUrl();

// Active session running total per table (sum of successfully paid orders in
// that session) — informational for the cashier, even though each order is
// paid separately (split bill stays per-order, this is just a visibility aid).
$sessionTotals = [];
$stmt = $pdo->query(
    "SELECT ts.table_id, COUNT(o.id) AS order_count, COALESCE(SUM(o.subtotal), 0) AS total
     FROM table_sessions ts
     JOIN orders o ON o.table_session_id = ts.id AND o.status != 'menunggu_bayar' AND o.status != 'dibatalkan'
     WHERE ts.status = 'aktif'
     GROUP BY ts.table_id"
);
foreach ($stmt->fetchAll() as $row) {
    $sessionTotals[$row['table_id']] = $row;
}

$pageTitleOwner = 'Kelola Meja';
include __DIR__ . '/includes/layout_top.php';
?>

<div class="owner-toolbar">
    <h2 style="margin:0;">Kelola Meja (<?php echo count($tables); ?>)</h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <?php if ($staff['role'] === 'admin'): ?>
        <a href="table-add.php" class="owner-btn owner-btn--primary"><i class="fa-solid fa-plus"></i> Tambah Meja Baru</a>
        <?php endif; ?>
        <a href="print-qr.php" target="_blank" class="owner-btn"><i class="fa-solid fa-print"></i> Cetak Semua QR</a>
    </div>
</div>

<?php foreach ($errors as $e): ?>
<div class="owner-modal-form" style="border-left:4px solid #b91c1c;"><?php echo htmlspecialchars($e); ?></div>
<?php endforeach; ?>

<div class="owner-filter-tags" id="capacityFilter">
    <button type="button" class="owner-filter-tag active" data-filter="semua">Semua</button>
    <button type="button" class="owner-filter-tag" data-filter="1-2">1-2 Orang</button>
    <button type="button" class="owner-filter-tag" data-filter="2-4">2-4 Orang</button>
</div>

<div class="table-manage-grid" id="tableGrid">
    <?php foreach ($tables as $t): ?>
    <?php $menuUrl = $baseUrl . '/menu.php?table=' . $t['table_number'] . '&token=' . $t['qr_token']; ?>
    <?php $session = $sessionTotals[$t['id']] ?? null; ?>
    <div class="table-manage-card status-<?php echo htmlspecialchars($t['status']); ?>" data-capacity="<?php echo htmlspecialchars($t['capacity_label']); ?>">
        <div class="table-manage-card__head">
            <span class="table-manage-card__number">Meja <?php echo (int) $t['table_number']; ?></span>
            <span class="pill <?php echo htmlspecialchars($t['status']); ?>"><?php echo ucfirst($t['status']); ?></span>
        </div>
        <div class="table-manage-card__body">
            <div class="table-manage-card__qr">
                <img src="<?php echo qrDataUri($menuUrl, 2, 4); ?>" width="60" height="60" alt="QR Meja <?php echo (int) $t['table_number']; ?>">
            </div>
            <div class="table-manage-card__info">
                <span><?php echo htmlspecialchars($t['capacity_label']); ?> orang</span>
                <?php if ($session): ?>
                <span><?php echo (int) $session['order_count']; ?> pesanan aktif · <?php echo rupiah((int) $session['total']); ?></span>
                <?php else: ?>
                <span style="color:#9ca3af;">Tidak ada sesi aktif</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="table-manage-card__actions">
            <?php if ($staff['role'] === 'admin'): ?>
            <a href="table-edit.php?id=<?php echo (int) $t['id']; ?>" class="owner-btn">Edit</a>
            <?php endif; ?>
            <a href="print-qr.php?table=<?php echo (int) $t['id']; ?>" target="_blank" class="owner-btn">Cetak QR</a>
            <?php if ($t['status'] === 'terisi'): ?>
            <form method="post" data-confirm="Tutup sesi meja ini? Hanya bisa kalau semua pesanan sudah selesai.">
                <input type="hidden" name="action" value="close_session">
                <input type="hidden" name="table_id" value="<?php echo (int) $t['id']; ?>">
                <?php echo csrfField(); ?>
                <button type="submit" class="owner-btn">Tutup Sesi</button>
            </form>
            <?php endif; ?>
            <?php if ($staff['role'] === 'admin'): ?>
            <form method="post" data-confirm="Buat ulang token QR meja ini? QR lama tidak akan berlaku lagi." data-confirm-danger>
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="regenerate_token">
                <input type="hidden" name="id" value="<?php echo (int) $t['id']; ?>">
                <button type="submit" class="owner-btn">Regenerasi QR</button>
            </form>
            <form method="post" data-confirm="Hapus meja ini? Aksi ini tidak bisa dibatalkan." data-confirm-danger>
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo (int) $t['id']; ?>">
                <button type="submit" class="owner-btn owner-btn--danger">Hapus</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
(function () {
    var tagsWrap = document.getElementById('capacityFilter');
    var grid = document.getElementById('tableGrid');
    if (!tagsWrap || !grid) { return; }

    tagsWrap.querySelectorAll('.owner-filter-tag').forEach(function (btn) {
        btn.addEventListener('click', function () {
            tagsWrap.querySelectorAll('.owner-filter-tag').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            var filter = btn.getAttribute('data-filter');
            grid.querySelectorAll('.table-manage-card').forEach(function (card) {
                var show = filter === 'semua' || card.getAttribute('data-capacity') === filter;
                card.classList.toggle('is-hidden', !show);
            });
        });
    });
})();
</script>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>
