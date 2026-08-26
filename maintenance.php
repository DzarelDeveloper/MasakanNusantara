<?php
/**
 * Ditampilkan lewat rewrite di .htaccess ketika file .maintenance ada di
 * docroot — lihat admin/dashboard.php untuk toggle-nya. /admin/ selalu
 * dikecualikan dari rewrite ini supaya staf/admin tetap bisa masuk buat
 * matikan mode maintenance.
 */
http_response_code(503);
header('Retry-After: 3600');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sedang Pemeliharaan — Masakan Nusantara</title>
<link rel="icon" href="assets/images/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #264653, #1a2f38);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
  }
  .maintenance-box {
    max-width: 560px;
    text-align: center;
    color: #fff;
  }
  .maintenance-box__icon {
    width: 96px;
    height: 96px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.12);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.4rem;
    color: #F4A261;
    margin: 0 auto 28px;
  }
  h1 {
    font-size: clamp(2.5rem, 6vw, 4rem);
    font-weight: 800;
    letter-spacing: -0.02em;
    margin-bottom: 16px;
  }
  .maintenance-box p {
    color: rgba(255, 255, 255, 0.75);
    font-size: 1.05rem;
    line-height: 1.6;
    margin-bottom: 40px;
  }
  .maintenance-actions {
    display: flex;
    justify-content: center;
    gap: 36px;
    flex-wrap: wrap;
  }
  .maintenance-actions a {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    color: #fff;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
  }
  .maintenance-actions__icon {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    border: 1.5px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: #F4A261;
    transition: all 0.2s ease;
  }
  .maintenance-actions a:hover .maintenance-actions__icon {
    background: rgba(255, 255, 255, 0.12);
    transform: translateY(-3px);
  }
</style>
</head>
<body>
  <div class="maintenance-box">
    <div class="maintenance-box__icon"><i class="fa-solid fa-utensils"></i></div>
    <h1>Segera Kembali</h1>
    <p>
        Masakan Nusantara sedang dalam pemeliharaan sistem sebentar. Kami akan
        kembali secepatnya — silakan coba lagi beberapa saat lagi.
    </p>
    <div class="maintenance-actions">
        <a href="javascript:location.reload();">
            <span class="maintenance-actions__icon"><i class="fa-solid fa-rotate-right"></i></span>
            Coba Lagi
        </a>
        <a href="https://wa.me/6288293208245" target="_blank" rel="noopener">
            <span class="maintenance-actions__icon"><i class="fa-brands fa-whatsapp"></i></span>
            Hubungi Kami
        </a>
    </div>
  </div>
</body>
</html>
