<?php
if (!isset($currentPage)) {
    $currentPage = basename($_SERVER['PHP_SELF']);
}
?>
<footer class="footer">
    <div class="container">
        <div class="footer__grid">
            <div>
                <div class="footer__logo">Masakan<span> Nusantara</span></div>
                <p class="footer__about">
                    Masakan Nusantara adalah restoran Indonesia dengan sistem pesan-di-meja —
                    scan QR di meja Anda, pilih menu, dan bayar langsung via QRIS.
                </p>
                <div class="footer__social">
                    <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fa-brands fa-x-twitter"></i></a>
                    <a href="#" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
                </div>
            </div>

            <div>
                <h4 class="footer__title">Jelajahi</h4>
                <div class="footer__links">
                    <a href="index.php">Beranda</a>
                    <a href="index.php#cara-pesan">Cara Pesan</a>
                    <a href="about.php">Tentang Kami</a>
                </div>
            </div>

            <div>
                <h4 class="footer__title">Bantuan</h4>
                <div class="footer__links">
                    <a href="order-lookup.php">Cari Pesanan Saya</a>
                    <a href="contact.php">Hubungi Kami</a>
                    <a href="contact.php#faq">FAQ</a>
                    <a href="#">Kebijakan Privasi</a>
                    <a href="#">Syarat &amp; Ketentuan</a>
                </div>
            </div>

            <div>
                <h4 class="footer__title">Dapatkan Info Terbaru</h4>
                <p class="footer__about">Menu baru dan penawaran eksklusif.</p>
                <form class="footer__newsletter" action="#" method="post">
                    <input type="email" name="email" placeholder="Alamat email Anda" required>
                    <button type="submit" class="btn-icon" aria-label="Berlangganan">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="footer__bottom">
            <span>&copy; <?php echo date('Y'); ?> Masakan Nusantara. Hak cipta dilindungi. Made by Dzarel Alghifari.</span>
            <div class="footer__bottom-links">
                <a href="#">Privasi</a>
                <a href="#">Ketentuan</a>
                <a href="#">Cookie</a>
            </div>
        </div>
    </div>
</footer>

<!-- Back To Top -->
<button class="back-to-top" id="backToTop" aria-label="Kembali ke atas">
    <i class="fa-solid fa-arrow-up"></i>
</button>

<script src="<?php echo htmlspecialchars(assetUrl('assets/js/main.js')); ?>"></script>
<?php if (isset($pageScript)): ?>
<script src="<?php echo htmlspecialchars(assetUrl($pageScript)); ?>"></script>
<?php endif; ?>
</body>
</html>
