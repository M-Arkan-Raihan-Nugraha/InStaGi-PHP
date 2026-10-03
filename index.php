<?php
/**
 * Halaman utama InStaGi.
 *
 * Berkas ini sengaja bernama index.php (bukan index.html) supaya alamat
 * kanonik untuk Open Graph (og:url / og:image) dapat dibuat DINAMIS.
 *
 * Mengapa penting: domain hosting gratis bisa berganti (mis. dari
 * *.iceiy.com ke *.aeonfree.com). Dengan URL dinamis, pratinjau tautan
 * WhatsApp/Facebook tetap benar di domain mana pun tanpa mengedit kode.
 */
require_once __DIR__ . '/includes/config.php';

$instagi_base = htmlspecialchars(instagi_base_url(), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InStaGi - Cek dan Pantau Status Gizi Anda</title>
    <link rel="shortcut icon" href="assets/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/index.css">

    <meta name="description" content="InStaGi membantu Anda cek dan memantau status gizi (IMT) serta kebutuhan kalori harian secara cepat dan gratis. Hasil skrining awal lengkap dengan saran gizi praktis.">
    <meta name="theme-color" content="#e74c3c">

    <!-- Pratinjau tautan (WhatsApp, Facebook, X/Twitter). URL dibuat dinamis. -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="InStaGi">
    <meta property="og:title" content="InStaGi - Cek dan Pantau Status Gizi Anda">
    <meta property="og:description" content="InStaGi membantu Anda cek dan memantau status gizi (IMT) serta kebutuhan kalori harian secara cepat dan gratis. Hasil skrining awal lengkap dengan saran gizi praktis.">
    <meta property="og:url" content="<?= $instagi_base ?>/">
    <meta property="og:image" content="<?= $instagi_base ?>/assets/og-image.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Logo InStaGi">
    <meta property="og:locale" content="id_ID">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="InStaGi - Cek dan Pantau Status Gizi Anda">
    <meta name="twitter:description" content="InStaGi membantu Anda cek dan memantau status gizi (IMT) serta kebutuhan kalori harian secara cepat dan gratis. Hasil skrining awal lengkap dengan saran gizi praktis.">
    <meta name="twitter:image" content="<?= $instagi_base ?>/assets/og-image.jpg">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo"><img src="assets/logo.png" alt="InStaGi Logo" width="512" height="466"></div>
            <h2>Cek dan Pantau Status Gizi Anda Secara Berkala</h2>
        </div>
        <p>InStaGi membantu Anda mengetahui dan memantau status gizi secara cepat dan mudah. Memantau status gizi membantu mengenali risiko kesehatan sejak dini. Dengan pemantauan rutin, perubahan berat badan dapat dikendalikan dan kesehatan tetap terjaga.</p>
        <p><strong>Dengan InStaGi Anda dapat:</strong></p>
        <ul>
            <li>Cek status gizi dalam hitungan detik</li>
            <li>Mengetahui kebutuhan kalori harian</li>
            <li>Pantau perubahan setiap bulan</li>
            <li>Dapatkan saran gizi praktis</li>
        </ul>
        <p class="disclaimer">Hasil merupakan skrining awal, bukan diagnosis medis.</p>
        <div class="button-wrapper">
            <a href="imt.php" class="button">Mulai cek status gizi</a>
        </div>
    </div>
    <footer class="main-footer">
        <p>Copyright © 2026 InStaGi | Created With ❤️</p>
    </footer>
</body>
</html>
