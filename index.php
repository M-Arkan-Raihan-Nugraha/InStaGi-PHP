<?php
/**
 * Halaman utama InStaGi.
 *
 * Berkas ini sengaja bernama index.php (bukan index.html) supaya alamat
 * kanonik untuk Open Graph (og:url / og:image) dapat dibuat DINAMIS.
 *
 * Isi halaman:
 *   1. Pilihan menu utama: Cek Status Gizi · Informasi Gizi · Monitoring Status Gizi
 *   2. Layanan: Konsultasi Gizi & Catering Gizi (tombol WhatsApp)
 *   3. Bestie InStaGi: wadah sponsor / kerja sama iklan
 *
 * CATATAN: tautan "Login Admin" tidak lagi ditampilkan (lihat includes/nav.php).
 */
require_once __DIR__ . '/includes/session.php';

$instagi_base = htmlspecialchars(instagi_base_url(), ENT_QUOTES, 'UTF-8');
$nav_active = '';

// --- Layanan via WhatsApp ----------------------------------------------------
// Nomor diambil dari includes/config.php (satu sumber, bisa dioverride env).
$wa_konsultasi = instagi_wa_url(
    'Halo InStaGi, saya ingin menggunakan layanan Konsultasi Gizi. Mohon informasinya.',
    INSTAGI_WA_KONSULTASI
);
$wa_catering = instagi_wa_url(
    'Halo InStaGi, saya ingin bertanya tentang layanan Catering Gizi. Mohon informasinya.',
    INSTAGI_WA_CATERING
);
$wa_bestie = instagi_wa_url(
    'Halo InStaGi, saya ingin bekerja sama / memasang iklan di InStaGi (Bestie InStaGi). Mohon informasinya.',
    INSTAGI_WA_KERJASAMA
);

// --- Bestie InStaGi: logo sponsor/partner ------------------------------------
// Cukup letakkan berkas gambar di assets/bestie/ (mis. apotek-sehat.png).
// Semua gambar di folder itu tampil otomatis; urutannya mengikuti nama berkas.
// Bila foldernya kosong, bagian logo disembunyikan (tanpa error).
$bestie_logo = [];
$bestie_dir  = __DIR__ . '/assets/bestie';
if (is_dir($bestie_dir)) {
    foreach ((array) scandir($bestie_dir) as $berkas) {
        if (preg_match('/\.(jpe?g|png|webp|gif)$/i', (string) $berkas)) {
            $bestie_logo[] = $berkas;
        }
    }
    sort($bestie_logo, SORT_NATURAL | SORT_FLAG_CASE);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InStaGi - Cek dan Pantau Status Gizi Anda</title>
    <link rel="shortcut icon" href="assets/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/nav.css">
    <link rel="stylesheet" href="css/pages.css">

    <meta name="description" content="InStaGi membantu Anda cek status gizi (IMT), kebutuhan kalori harian, dan memantaunya dari waktu ke waktu. Tersedia juga layanan konsultasi gizi dan catering gizi.">
    <meta name="theme-color" content="#e74c3c">

    <!-- Pratinjau tautan (WhatsApp, Facebook, X/Twitter). URL dibuat dinamis. -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="InStaGi">
    <meta property="og:title" content="InStaGi - Cek dan Pantau Status Gizi Anda">
    <meta property="og:description" content="InStaGi membantu Anda cek status gizi (IMT), kebutuhan kalori harian, dan memantaunya dari waktu ke waktu. Tersedia juga layanan konsultasi gizi dan catering gizi.">
    <meta property="og:url" content="<?= $instagi_base ?>/">
    <meta property="og:image" content="<?= $instagi_base ?>/assets/og-image.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Logo InStaGi">
    <meta property="og:locale" content="id_ID">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="InStaGi - Cek dan Pantau Status Gizi Anda">
    <meta name="twitter:description" content="InStaGi membantu Anda cek status gizi (IMT), kebutuhan kalori harian, dan memantaunya dari waktu ke waktu. Tersedia juga layanan konsultasi gizi dan catering gizi.">
    <meta name="twitter:image" content="<?= $instagi_base ?>/assets/og-image.jpg">
</head>
<body>
    <?php require __DIR__ . '/includes/nav.php'; ?>

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
            <li>Pantau perubahan setiap bulan lewat grafik</li>
            <li>Dapatkan saran gizi praktis</li>
        </ul>

        <!-- ===== PILIHAN MENU DI AWAL ===== -->
        <div class="home-menu">
            <a class="home-menu-card" href="imt.php">
                <span class="home-menu-ico" aria-hidden="true">📊</span>
                <h3>Cek Status Gizi</h3>
                <p>Hitung Indeks Massa Tubuh (IMT), status gizi, dan kebutuhan kalori harian Anda.</p>
            </a>
            <a class="home-menu-card" href="informasi.php">
                <span class="home-menu-ico" aria-hidden="true">📄</span>
                <h3>Informasi Gizi</h3>
                <p>Leaflet gizi, contoh menu sesuai kalori, dan Daftar Bahan Makanan Penukar (DBMP).</p>
            </a>
            <a class="home-menu-card" href="monitoring.php">
                <span class="home-menu-ico" aria-hidden="true">📈</span>
                <h3>Monitoring Status Gizi</h3>
                <p>Lihat riwayat dan grafik perkembangan status gizi akun Anda dari waktu ke waktu.</p>
            </a>
        </div>

        <!-- ===== LAYANAN: KONSULTASI & CATERING GIZI ===== -->
        <section class="layanan-section" id="layanan">
            <h2>Layanan Kami</h2>
            <p class="layanan-intro">Butuh pendampingan langsung? Hubungi kami lewat WhatsApp untuk konsultasi gizi maupun pemesanan catering gizi.</p>

            <div class="layanan-grid">
                <article class="layanan-card">
                    <span class="layanan-ico" aria-hidden="true">🥗</span>
                    <h3>Konsultasi Gizi</h3>
                    <p>Konsultasikan hasil cek status gizi Anda dengan ahli gizi: pola makan, kebutuhan kalori, dan target berat badan yang realistis.</p>
                    <a class="layanan-btn layanan-btn-wa" href="<?= htmlspecialchars($wa_konsultasi, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                        Hubungi via WhatsApp
                    </a>
                </article>

                <article class="layanan-card">
                    <span class="layanan-ico" aria-hidden="true">🍱</span>
                    <h3>Catering Gizi</h3>
                    <p>Menu harian yang disusun sesuai kebutuhan kalori dan kondisi Anda — praktis untuk program penurunan, kenaikan, atau pemeliharaan berat badan.</p>
                    <a class="layanan-btn layanan-btn-wa" href="<?= htmlspecialchars($wa_catering, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                        Pesan via WhatsApp
                    </a>
                </article>
            </div>
        </section>

        <!-- ===== BESTIE INSTAGI: SPONSOR & KERJA SAMA IKLAN ===== -->
        <section class="bestie-section" id="bestie">
            <h2>Bestie InStaGi</h2>
            <p class="bestie-intro">
                Ruang untuk <strong>sponsor</strong> dan <strong>kerja sama iklan</strong>: produk kesehatan,
                jasa katering, klinik, apotek, komunitas olahraga, dan usaha lain yang sejalan dengan hidup sehat.
                Logo mitra tampil di halaman ini dan ikut terbawa saat tautan InStaGi dibagikan.
            </p>

            <?php if (!empty($bestie_logo)): ?>
                <div class="bestie-grid">
                    <?php foreach ($bestie_logo as $logo): ?>
                        <div class="bestie-logo">
                            <img src="assets/bestie/<?= htmlspecialchars(rawurlencode($logo), ENT_QUOTES, 'UTF-8') ?>"
                                 alt="Mitra InStaGi: <?= htmlspecialchars(pathinfo($logo, PATHINFO_FILENAME), ENT_QUOTES, 'UTF-8') ?>"
                                 loading="lazy" decoding="async">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bestie-empty">
                    Slot mitra masih kosong. Jadilah <strong>Bestie InStaGi</strong> pertama — hubungi kami untuk
                    menempatkan logo dan tautan usaha Anda di sini.
                </div>
            <?php endif; ?>

            <div class="bestie-actions">
                <a class="layanan-btn layanan-btn-wa" href="<?= htmlspecialchars($wa_bestie, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                    Jadi Bestie InStaGi
                </a>
            </div>
        </section>

        <p class="disclaimer">Hasil merupakan skrining awal, bukan diagnosis medis.</p>
    </div>
    <footer class="main-footer">
        <p>Copyright © 2026 InStaGi | Created With ❤️</p>
    </footer>
</body>
</html>
