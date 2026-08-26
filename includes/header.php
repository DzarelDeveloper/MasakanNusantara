<?php
/**
 * Global <head> include.
 * Expects optional $pageTitle and $pageDescription to be set before include.
 */
if (!isset($pageTitle)) {
    $pageTitle = 'Masakan Nusantara — Scan, Pesan, Bayar di Meja Anda';
}
if (!isset($pageDescription)) {
    $pageDescription = 'Masakan Nusantara — restoran Indonesia dengan sistem pesan-di-meja. Scan QR di meja Anda, pilih menu, dan bayar langsung via QRIS.';
}

$currentPage = basename($_SERVER['PHP_SELF']);

// Cache-bust local CSS/JS with the file's mtime so browsers pick up edits
// immediately instead of serving a stale cached copy.
if (!function_exists('assetUrl')) {
    function assetUrl(string $path): string
    {
        $full = __DIR__ . '/../' . $path;
        $v = is_file($full) ? filemtime($full) : time();
        return $path . '?v=' . $v;
    }
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">

    <!-- Favicon -->
    <link rel="icon" href="assets/images/favicon.svg" type="image/svg+xml">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo htmlspecialchars(assetUrl('assets/css/base.css')); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(assetUrl('assets/css/components.css')); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(assetUrl('assets/css/layout.css')); ?>">
    <?php if (isset($pageStylesheet)): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(assetUrl($pageStylesheet)); ?>">
    <?php endif; ?>
</head>
<body>

    <!-- Elegant Page Loader -->
    <div class="page-loader" id="pageLoader">
        <div class="loader-ring"></div>
    </div>

    <!-- Toast Notifications Mount -->
    <div class="toast-container" id="toastContainer"></div>
