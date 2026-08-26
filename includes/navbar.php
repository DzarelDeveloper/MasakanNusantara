<?php
/**
 * Sticky navbar + mobile navigation.
 * Relies on $currentPage set in header.php for active-link state.
 * No "Reservasi"/"Cabang"/profile links — ordering is QR-only (see menu.php),
 * reached by scanning the code on a physical table, not through site nav.
 */
if (!isset($currentPage)) {
    $currentPage = basename($_SERVER['PHP_SELF']);
}

$navLinks = [
    'index.php'   => 'Beranda',
    'about.php'   => 'Tentang',
    'contact.php' => 'Kontak',
];
?>
<header class="navbar" id="navbar">
    <div class="container">
        <a href="index.php" class="navbar__logo">
            <i class="fa-solid fa-utensils"></i>
            Masakan<span> Nusantara</span>
        </a>

        <nav class="navbar__links">
            <?php foreach ($navLinks as $href => $label): ?>
            <a href="<?php echo $href; ?>" class="<?php echo $currentPage === $href ? 'active' : ''; ?>"><?php echo $label; ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="navbar__actions">
            <button class="theme-toggle" id="themeToggle" aria-label="Ganti mode gelap">
                <i class="fa-solid fa-moon"></i>
            </button>
            <a href="index.php#cara-pesan" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-qrcode"></i> Cara Pesan
            </a>
            <button class="nav-toggle" id="navToggle" aria-label="Buka menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</header>

<div class="nav-backdrop" id="navBackdrop"></div>
<aside class="mobile-nav" id="mobileNav">
    <?php foreach ($navLinks as $href => $label): ?>
    <a href="<?php echo $href; ?>" class="<?php echo $currentPage === $href ? 'active' : ''; ?>"><?php echo $label; ?></a>
    <?php endforeach; ?>
    <a href="index.php#cara-pesan" class="btn btn-primary btn-block">Cara Pesan</a>
</aside>
