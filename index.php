<?php
/**
 * Landing page — Masakan Nusantara.
 * Single restaurant, QR-per-table ordering (see menu.php). This page is
 * informational only — actual ordering starts by scanning the QR code at
 * a physical table, not from a link here.
 */
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Masakan Nusantara — Restoran Indonesia, Pesan Langsung di Meja';
$pageDescription = 'Info menu, lokasi, dan jam buka Masakan Nusantara — restoran Indonesia dengan sistem pesan-di-meja unik: begitu Anda datang, scan QR di meja, pilih menu, dan bayar langsung via QRIS.';
$pageStylesheet = 'assets/css/pages/home.css';

$howItWorks = [
    ['title' => 'Scan QR di Meja', 'desc' => 'Setiap meja punya kode QR sendiri. Scan dengan kamera HP Anda untuk membuka menu.'],
    ['title' => 'Pilih Menu',      'desc' => 'Jelajahi menu, tambahkan ke keranjang, sesuaikan jumlah dan catatan khusus.'],
    ['title' => 'Bayar via QRIS',  'desc' => 'Selesaikan pembayaran langsung dari HP Anda, tanpa perlu memanggil kasir.'],
    ['title' => 'Pesanan Diantar', 'desc' => 'Dapur langsung menerima pesanan Anda begitu pembayaran berhasil, dan mengantarnya ke meja.'],
];

$whyChoose = [
    ['icon' => 'fa-bolt',          'title' => 'Pesan Tanpa Antre',   'desc' => 'Langsung dari meja Anda, tanpa menunggu pelayan atau antre kasir.',        'color' => 'primary'],
    ['icon' => 'fa-shield-heart',  'title' => 'Bayar Aman via QRIS', 'desc' => 'Transaksi langsung dari HP Anda sendiri, jelas dan tercatat.',              'color' => 'accent'],
    ['icon' => 'fa-receipt',       'title' => 'Struk Digital',       'desc' => 'Bukti pembayaran bisa diunduh atau dicetak kapan saja Anda perlu.',         'color' => 'secondary'],
    ['icon' => 'fa-circle-check',  'title' => 'Status Real-Time',    'desc' => 'Pantau progres pesanan Anda — dari dapur hingga sampai ke meja.',           'color' => 'dark'],
];

$testimonials = [
    ['name' => 'Amelia Rahman', 'role' => 'Blogger Kuliner', 'avatar' => 'assets/images/photos/1494790108377-be9c29b29330.jpg', 'quote' => 'Scan, pilih menu, bayar — semua dari HP tanpa perlu panggil pelayan. Pesanan juga cepat sampai ke meja.', 'rating' => 5],
    ['name' => 'David Chen', 'role' => 'Pelanggan Setia', 'avatar' => 'assets/images/photos/1500648767791-00dcc994a43e.jpg', 'quote' => 'Dulu harus antre lama buat pesan. Sekarang tinggal scan QR di meja dan pesanan langsung masuk dapur.', 'rating' => 5],
    ['name' => 'Sofia Martinez', 'role' => 'Manajer Pemasaran', 'avatar' => 'assets/images/photos/1544005313-94ddf0286df2.jpg', 'quote' => 'Rendangnya juara, bumbunya meresap sampai dalam. Suasana tempatnya juga nyaman buat makan santai bareng tim kantor.', 'rating' => 4.5],
];

$hours = [
    'Senin – Kamis' => '11.00 – 22.00',
    'Jumat – Sabtu'  => '11.00 – 23.00',
    'Minggu'         => '10.00 – 21.00',
];

// Statistik hero diambil live dari database, bukan angka tetap, biar tidak
// basi begitu jumlah meja/menu berubah.
$totalTablesCount = (int) db()->query('SELECT COUNT(*) FROM dining_tables')->fetchColumn();
$totalMenuCount = (int) db()->query("SELECT COUNT(*) FROM menu_items WHERE stock_status = 'tersedia'")->fetchColumn();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main>

    <!-- ================= HERO ================= -->
    <section class="hero hero--split">
        <div class="container hero-split">
            <div class="hero-split__text">
                <span class="eyebrow">Satu Restoran. Pesan Hanya di Meja Kami.</span>
                <h1>Scan QR di Meja.<br><span class="text-accent">Pesan &amp; Bayar Sendiri.</span></h1>
                <p>Pemesanan hanya bisa dilakukan saat Anda sudah duduk di meja kami — tinggal scan QR yang tersedia di setiap meja. Masih cari tahu dulu? Lihat lokasi dan jam buka kami di bawah.</p>

                <div class="hero__actions">
                    <a href="#lokasi" class="btn btn-primary btn-lg">
                        <i class="fa-solid fa-location-dot"></i> Lihat Lokasi &amp; Jam Buka
                    </a>
                    <a href="#cara-pesan" class="btn btn-outline btn-lg">
                        <i class="fa-solid fa-circle-info"></i> Cara Kerja QR
                    </a>
                </div>

                <div class="hero__stats">
                    <div class="stat-item">
                        <div class="hero__stat-value" data-counter="<?php echo $totalTablesCount; ?>" data-suffix="">0</div>
                        <div class="hero__stat-label">Meja Tersedia</div>
                    </div>
                    <div class="stat-item">
                        <div class="hero__stat-value" data-counter="<?php echo $totalMenuCount; ?>" data-suffix="+">0</div>
                        <div class="hero__stat-label">Pilihan Menu</div>
                    </div>
                    <div class="stat-item">
                        <div class="hero__stat-value" data-counter="4.7" data-suffix="/5">0</div>
                        <div class="hero__stat-label">Rating Rata-rata</div>
                    </div>
                    <div class="stat-item">
                        <div class="hero__stat-value" data-counter="100" data-suffix="%">0</div>
                        <div class="hero__stat-label">Bayar Aman via QRIS</div>
                    </div>
                </div>
            </div>
            <div class="hero-split__image">
                <img src="assets/images/photos/1517248135467-4c7edcad34c4.jpg" alt="Interior restoran Masakan Nusantara">
            </div>
        </div>
    </section>

    <!-- ================= KENAPA PILIH KAMI ================= -->
    <section class="section">
        <div class="container">
            <div class="section-header center">
                <span class="eyebrow">Kenapa Masakan Nusantara</span>
                <h2>Bersantap Tanpa <span class="text-accent">Ribet</span></h2>
                <p>Semua yang biasanya bikin makan di luar ribet, kami sederhanakan lewat satu QR code di meja Anda.</p>
            </div>
            <div class="grid badge-grid">
                <?php foreach ($whyChoose as $item): ?>
                <div class="badge-card fade-up">
                    <div class="badge-card__icon badge-card__icon--<?php echo $item['color']; ?>"><i class="fa-solid <?php echo $item['icon']; ?>"></i></div>
                    <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                    <p><?php echo htmlspecialchars($item['desc']); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ================= CARA PESAN ================= -->
    <section class="section section--tint" id="cara-pesan">
        <div class="container">
            <div class="section-header center">
                <span class="eyebrow">Simpel &amp; Cepat</span>
                <h2>Cara <span class="text-accent">Pesan</span> di Masakan Nusantara</h2>
                <p>Empat langkah, tanpa aplikasi tambahan dan tanpa perlu bikin akun.</p>
            </div>
            <div class="steps-split">
                <div class="steps-split__image">
                    <img src="assets/images/photos/1590846406792-0adc7f938f1d.jpg" alt="Pelanggan memesan lewat HP di meja" loading="lazy">
                </div>
                <ol class="steps-list">
                    <?php foreach ($howItWorks as $i => $item): ?>
                    <li class="fade-up">
                        <span class="steps-list__num"><?php echo $i + 1; ?></span>
                        <div>
                            <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                            <p><?php echo htmlspecialchars($item['desc']); ?></p>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
    </section>

    <!-- ================= TENTANG KAMI ================= -->
    <section class="section">
        <div class="container">
            <div class="about-split fade-up">
                <div class="about-split__image">
                    <img src="assets/images/photos/1512058564366-18510be2db19.jpg" alt="Hidangan Masakan Nusantara" loading="lazy">
                </div>
                <div class="about-split__content">
                    <span class="eyebrow">Tentang Kami</span>
                    <h2>Apa Itu <span class="text-accent">Masakan Nusantara</span></h2>
                    <p>
                        Masakan Nusantara lahir dari kecintaan pada cita rasa autentik Indonesia — mulai dari rendang Padang
                        yang dimasak berjam-jam sampai bumbunya meresap, sate ayam bakar dengan bumbu kacang racikan sendiri,
                        hingga dimsum kukus yang lembut.
                    </p>
                    <p>
                        Kami percaya bersantap yang enak gak harus ribet: begitu Anda duduk di meja kami, cukup scan QR yang
                        tersedia untuk langsung melihat menu, memesan, dan membayar sendiri dari HP — tanpa perlu menunggu
                        pelayan atau antre di kasir.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= TESTIMONIALS ================= -->
    <section class="section testimonials">
        <div class="container">
            <div class="section-header center">
                <span class="eyebrow">Kata Pelanggan</span>
                <h2>Apa Kata <span class="text-accent">Pelanggan</span> Kami</h2>
            </div>
            <?php $needsSlider = count($testimonials) > 3; ?>
            <div class="<?php echo $needsSlider ? 'testimonial-track' : 'testimonial-grid'; ?>">
                <?php foreach ($testimonials as $t): ?>
                <div class="testimonial-card">
                    <div class="rating">
                        <?php
                        $full = floor($t['rating']);
                        $half = ($t['rating'] - $full) >= 0.5;
                        for ($i = 0; $i < $full; $i++) echo '<i class="fa-solid fa-star"></i>';
                        if ($half) echo '<i class="fa-solid fa-star-half-stroke"></i>';
                        ?>
                    </div>
                    <p>&ldquo;<?php echo htmlspecialchars($t['quote']); ?>&rdquo;</p>
                    <div class="testimonial-author">
                        <img src="<?php echo htmlspecialchars($t['avatar']); ?>" alt="<?php echo htmlspecialchars($t['name']); ?>" loading="lazy">
                        <div>
                            <strong><?php echo htmlspecialchars($t['name']); ?></strong>
                            <span><?php echo htmlspecialchars($t['role']); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if ($needsSlider): ?>
            <div class="testimonial-nav">
                <button class="btn-icon" data-testimonial-prev aria-label="Testimoni sebelumnya"><i class="fa-solid fa-arrow-left"></i></button>
                <button class="btn-icon" data-testimonial-next aria-label="Testimoni berikutnya"><i class="fa-solid fa-arrow-right"></i></button>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ================= CTA BANNER ================= -->
    <section class="section" style="padding-block:56px;border-bottom:1px solid var(--color-border);">
        <div class="container">
            <div class="cta-banner fade-up">
                <div>
                    <h2>Sudah di Restoran Kami?</h2>
                    <p>Scan QR di meja Anda sekarang dan mulai pesan dalam hitungan detik.</p>
                </div>
                <a href="#lokasi" class="btn btn-ghost btn-lg">
                    <i class="fa-solid fa-location-dot"></i> Lihat Lokasi Kami
                </a>
            </div>
        </div>
    </section>

    <!-- ================= LOCATION & HOURS ================= -->
    <section class="section" id="lokasi">
        <div class="container">
            <div class="location-cta fade-up">
                <div>
                    <span class="eyebrow">Kunjungi Kami</span>
                    <h2><span class="text-accent">Lokasi</span> &amp; Jam Buka</h2>
                    <p style="color:var(--color-text-muted);margin:12px 0 20px;">
                        <i class="fa-solid fa-location-dot"></i> Jl. Sudirman No. 10, Jakarta 10220
                    </p>
                    <ul class="hours-list">
                        <?php foreach ($hours as $day => $time): ?>
                        <li><span><?php echo htmlspecialchars($day); ?></span><span><?php echo htmlspecialchars($time); ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="contact.php" class="btn btn-outline" style="margin-top:20px;">Hubungi Kami <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <img src="assets/images/photos/1517248135467-4c7edcad34c4.jpg" alt="Masakan Nusantara" loading="lazy">
            </div>
        </div>
    </section>

    <!-- ================= TENTANG SISTEM INI ================= -->
    <section class="section team-section">
        <div class="container">
            <div class="section-header center">
                <span class="eyebrow">Tentang Sistem Ini</span>
                <h2>Dikembangkan Sebagai <span class="text-accent">Tugas Sekolah</span></h2>
                <p>Website dan sistem pemesanan QR per meja ini dibuat dan dikembangkan sebagai proyek tugas sekolah oleh:</p>
            </div>
            <div class="team-grid">
                <div class="team-card fade-up">
                    <div class="team-card__frame"><img src="assets/images/photos/team-dzarel.jpg" alt="Muhamad Dzarel Alghifari" loading="lazy"></div>
                    <h4>Muhamad Dzarel Alghifari</h4>
                    <p><a href="https://instagram.com/buildwithdzarel" target="_blank" rel="noopener" class="team-card__social"><i class="fa-brands fa-instagram"></i> @buildwithdzarel</a></p>
                </div>
            </div>
        </div>
    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
