<?php
/**
 * Contact page — contact form, info cards, map placeholder, FAQ accordion.
 */
$pageTitle = 'Hubungi Kami — Masakan Nusantara';
$pageDescription = 'Hubungi Masakan Nusantara — pertanyaan seputar cara pesan di meja, pembayaran QRIS, atau masukan lainnya, tim kami siap membantu.';
$pageStylesheet = 'assets/css/pages/contact.css';
$pageScript = 'assets/js/contact.js';

$contactInfo = [
    ['icon' => 'fa-location-dot', 'title' => 'Alamat Restoran', 'lines' => ['Jl. Sudirman No. 10', 'Jakarta 10220']],
    ['icon' => 'fa-phone',        'title' => 'Telepon',         'lines' => ['+62 21-5000-0001', 'Senin–Minggu, 09.00–21.00']],
    ['icon' => 'fa-envelope',     'title' => 'Email',           'lines' => ['hello@masakannusantara.co.id', 'support@masakannusantara.co.id']],
];

$faqs = [
    ['q' => 'Bagaimana cara memesan di Masakan Nusantara?', 'a' => 'Scan kode QR yang tertempel di meja Anda — menu akan otomatis terbuka sesuai meja itu. Pilih menu, checkout, lalu bayar via QRIS langsung dari HP Anda.'],
    ['q' => 'Apakah saya perlu membuat akun untuk memesan?', 'a' => 'Tidak. Anda cukup mengisi nama dan email saat checkout — tanpa perlu mendaftar akun atau kata sandi.'],
    ['q' => 'Apakah pesanan saya bisa dibatalkan?', 'a' => 'Selama status pembayaran masih "menunggu", Anda bisa membatalkan dari halaman pembayaran. Setelah pembayaran berhasil, pesanan langsung diproses dapur.'],
    ['q' => 'Bagaimana saya mendapatkan bukti pembayaran?', 'a' => 'Setelah pembayaran berhasil, tombol "Unduh/Cetak Struk" akan muncul di halaman status pesanan Anda — bisa disimpan sebagai PDF atau langsung dicetak.'],
    ['q' => 'Saya menutup halaman pesanan saya, bagaimana cara membukanya lagi?', 'a' => 'Buka halaman "Cari Pesanan Saya" di footer situs, lalu masukkan email yang Anda pakai saat checkout untuk melihat kembali riwayat pesanan Anda.'],
];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main>

    <!-- ================= PAGE HERO ================= -->
    <section class="page-hero-sm" style="--hero-sm-bg:url('assets/images/photos/1554118811-1e0d58224f24.jpg');">
        <div class="container">
            <div class="breadcrumb on-dark">
                <a href="index.php">Beranda</a>
                <i class="fa-solid fa-chevron-right"></i>
                <span class="current">Kontak</span>
            </div>
            <h1>Hubungi Kami</h1>
            <p>Pertanyaan, masukan, atau kerja sama — tim kami siap membantu.</p>
        </div>
    </section>

    <!-- ================= CONTACT INFO CARDS ================= -->
    <section class="section contact-info-section">
        <div class="container">
            <div class="grid contact-info-grid">
                <?php foreach ($contactInfo as $info): ?>
                <div class="contact-info-card fade-up">
                    <div class="contact-info-card__icon"><i class="fa-solid <?php echo $info['icon']; ?>"></i></div>
                    <h4><?php echo htmlspecialchars($info['title']); ?></h4>
                    <?php foreach ($info['lines'] as $line): ?>
                    <p><?php echo htmlspecialchars($line); ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ================= FORM + MAP ================= -->
    <section class="section" style="background:var(--color-card);">
        <div class="container contact-main__grid">

            <!-- Contact Form -->
            <div class="contact-form-card fade-up">
                <span class="eyebrow">Kirim Pesan</span>
                <h2>Kami Senang Mendengar dari Anda</h2>
                <form class="contact-form" id="contactForm" novalidate>
                    <div class="contact-form__row">
                        <div class="form-group">
                            <label class="form-label" for="contactName">Nama Lengkap</label>
                            <input class="form-control" type="text" id="contactName" name="name" placeholder="Nama Anda" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="contactEmail">Alamat Email</label>
                            <input class="form-control" type="email" id="contactEmail" name="email" placeholder="nama@contoh.com" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="contactSubject">Subjek</label>
                        <input class="form-control" type="text" id="contactSubject" name="subject" placeholder="Ada yang bisa kami bantu?" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="contactMessage">Pesan</label>
                        <textarea class="form-control" id="contactMessage" name="message" placeholder="Ceritakan lebih lanjut..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg btn-block">
                        <i class="fa-solid fa-paper-plane"></i> Kirim Pesan
                    </button>
                </form>
            </div>

            <!-- Map -->
            <div class="contact-map fade-up">
                <span class="eyebrow">Lokasi Kami</span>
                <h2>Temukan Kami di Sini</h2>
                <div class="map-placeholder">
                    <div class="map-placeholder__pin">
                        <i class="fa-solid fa-map-location-dot"></i>
                        <strong>Jl. Sudirman No. 10</strong>
                        <span>Jakarta 10220</span>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- ================= FAQ ================= -->
    <section class="section" id="faq">
        <div class="container">
            <div class="section-header center">
                <span class="eyebrow">Butuh Bantuan</span>
                <h2>Pertanyaan yang Sering Diajukan</h2>
            </div>
            <div class="faq-list fade-up">
                <?php foreach ($faqs as $faq): ?>
                <div class="accordion-item">
                    <button type="button" class="accordion-header">
                        <span><?php echo htmlspecialchars($faq['q']); ?></span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <div class="accordion-body">
                        <p><?php echo htmlspecialchars($faq['a']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
