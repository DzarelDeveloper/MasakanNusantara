<?php
/**
 * About page — cerita, misi, visi, dan statistik singkat.
 */

$pageTitle = 'Tentang Kami — Masakan Nusantara';
$pageDescription = 'Kenali kisah Masakan Nusantara dan sistem pesan-di-meja yang kami pakai — scan QR, pesan, dan bayar langsung dari meja Anda.';
$pageStylesheet = 'assets/css/pages/about.css';

$stats = [
    ['value' => 35,   'suffix' => '', 'label' => 'Meja Tersedia'],
    ['value' => 19,   'suffix' => '+', 'label' => 'Pilihan Menu'],
    ['value' => 100,  'suffix' => '%', 'label' => 'Pesan Tanpa Antre'],
    ['value' => 4.7,  'suffix' => '/5', 'label' => 'Rating Rata-rata'],
];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main>

    <!-- ================= PAGE HERO ================= -->
    <section class="page-hero-sm" style="--hero-sm-bg:url('assets/images/photos/1424847651672-bf20a4b0982b.jpg');">
        <div class="container">
            <div class="breadcrumb on-dark">
                <a href="index.php">Beranda</a>
                <i class="fa-solid fa-chevron-right"></i>
                <span class="current">Tentang</span>
            </div>
            <h1>Tentang Masakan Nusantara</h1>
            <p>Kami ingin menghadirkan cita rasa Indonesia asli dengan cara memesan yang lebih simpel — langsung dari meja Anda.</p>
        </div>
    </section>

    <!-- ================= COMPANY STORY ================= -->
    <section class="section story">
        <div class="container story__grid">
            <div class="story__image fade-up">
                <img src="assets/images/photos/1517248135467-4c7edcad34c4.jpg" alt="Suasana Masakan Nusantara" loading="lazy">
            </div>
            <div class="story__content fade-up">
                <span class="eyebrow">Kisah Kami</span>
                <h2>Resep yang Layak Dibagikan</h2>
                <p>Masakan Nusantara berawal dari satu dapur kecil di Jakarta, menyajikan resep rumahan khas Indonesia yang diwariskan turun-temurun. Yang dimulai sebagai restoran lingkungan sederhana, kini dikenal karena konsistensinya — rendang yang sama, sambal yang sama, kehangatan yang sama, setiap kali Anda berkunjung.</p>
                <p>Untuk membuat pengalaman bersantap makin nyaman, kami menghadirkan sistem pesan-di-meja: setiap meja punya kode QR sendiri, sehingga Anda bisa memesan dan membayar langsung dari HP tanpa perlu menunggu pelayan atau mengantre di kasir.</p>
                <a href="index.php#cara-pesan" class="btn btn-primary btn-lg">Lihat Cara Pesan <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    </section>

    <!-- ================= MISSION & VISION ================= -->
    <section class="section" style="background:var(--color-card);">
        <div class="container">
            <div class="section-header center">
                <span class="eyebrow">Yang Menggerakkan Kami</span>
                <h2>Misi &amp; Visi</h2>
            </div>
            <div class="grid mission-vision-grid">
                <div class="mv-card fade-up">
                    <div class="mv-card__icon"><i class="fa-solid fa-bullseye"></i></div>
                    <h3>Misi Kami</h3>
                    <p>Menghadirkan masakan Indonesia rumahan yang autentik, dengan cara memesan yang cepat, jujur, dan tanpa ribet — mulai dari scan QR hingga hidangan sampai di meja Anda.</p>
                </div>
                <div class="mv-card fade-up">
                    <div class="mv-card__icon"><i class="fa-solid fa-eye"></i></div>
                    <h3>Visi Kami</h3>
                    <p>Menjadi contoh restoran Indonesia yang menggabungkan kehangatan resep rumahan dengan kemudahan teknologi pemesanan modern.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= STATISTICS ================= -->
    <section class="section stats-section">
        <div class="container">
            <div class="grid stats-bar">
                <?php foreach ($stats as $s): ?>
                <div class="stat-item fade-up">
                    <div class="hero__stat-value" data-counter="<?php echo $s['value']; ?>" data-suffix="<?php echo htmlspecialchars($s['suffix']); ?>">0</div>
                    <div class="hero__stat-label"><?php echo htmlspecialchars($s['label']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
