<?php
/**
 * Shared header/nav for authenticated /admin/ pages.
 * Expects $pageTitleOwner (string) and $staff (from requireStaffLogin()/requireRole())
 * to be set before including this file.
 */
$ownerCurrentPage = basename($_SERVER['PHP_SELF']);

// Cache-bust owner-panel CSS/JS with the file's mtime so browsers pick up
// edits immediately instead of serving a stale cached copy.
if (!function_exists('ownerAssetUrl')) {
    function ownerAssetUrl(string $path): string
    {
        $full = __DIR__ . '/../../' . $path;
        $v = is_file($full) ? filemtime($full) : time();
        return '../' . $path . '?v=' . $v;
    }
}

$ownerNavLinks = [
    'dashboard.php'  => 'Dashboard',
    'tables.php'     => 'Meja',
    'orders.php'     => 'Pesanan',
    'kasir-cari.php' => 'Pembayaran',
    'riwayat.php'    => 'Riwayat',
];
if ($staff['role'] === 'admin') {
    $ownerNavLinks = [
        'dashboard.php'    => 'Dashboard',
        'tables.php'       => 'Meja',
        'menu-manage.php'  => 'Menu',
        'orders.php'       => 'Pesanan',
        'kasir-cari.php'   => 'Pembayaran',
        'riwayat.php'      => 'Riwayat',
        'reports.php'      => 'Laporan',
        'staff-manage.php' => 'Staf',
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($pageTitleOwner); ?> — Owner Panel — Masakan Nusantara</title>
<link rel="icon" href="<?php echo htmlspecialchars(ownerAssetUrl('assets/images/favicon.svg')); ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?php echo htmlspecialchars(ownerAssetUrl('assets/css/pages/owner.css')); ?>">
</head>
<body>
<header class="owner-header">
    <div class="owner-header__top">
        <h1><i class="fa-solid fa-store"></i> Masakan Nusantara</h1>
        <div class="owner-header__user">
            <span class="owner-header__name"><?php echo htmlspecialchars($staff['name']); ?> &middot; <?php echo htmlspecialchars(ucfirst($staff['role'])); ?></span>
            <a href="change-password.php"><i class="fa-solid fa-key"></i> Ganti Sandi</a>
            <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a>
        </div>
    </div>
    <nav class="owner-nav">
        <?php foreach ($ownerNavLinks as $href => $label): ?>
        <a href="<?php echo $href; ?>" class="<?php echo $ownerCurrentPage === $href ? 'active' : ''; ?>"><?php echo $label; ?></a>
        <?php endforeach; ?>
    </nav>
</header>
<main class="owner-main">
