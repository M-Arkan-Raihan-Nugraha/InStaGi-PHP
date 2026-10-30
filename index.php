<?php
/**
 * Halaman utama InStaGi.
 *
 * Berkas ini sengaja bernama index.php (bukan index.html) supaya alamat
 * kanonik untuk Open Graph (og:url / og:image) dapat dibuat DINAMIS.
 *
 * Isi halaman:
 *   1. Pilihan menu utama: Cek Status Gizi · Informasi Gizi · Monitoring Status Gizi
 *   2. Layanan: Konsultasi Gizi & Katering Gizi (tombol WhatsApp)
 *   (Catatan: Bestie InStaGi telah dipindahkan ke halaman Informasi Gizi)
 *
 * CATATAN: tautan "Login Admin" tidak lagi ditampilkan (lihat includes/nav.php).
 */
require_once __DIR__ . '/includes/core/session.php';
require_once __DIR__ . '/includes/views/icon.php';

$nav_active = '';

// --- Layanan via WhatsApp ----------------------------------------------------
// Nomor diambil dari includes/config.php (satu sumber, bisa dioverride env).
$wa_layanan = instagi_wa_url(
    'Halo InStaGi, saya ingin berkonsultasi mengenai gizi dan memesan katering gizi. Mohon informasinya.',
    INSTAGI_WA_KATERING
);

// Judul, deskripsi, alamat rapi (tanpa .php), dan daftar CSS halaman ini.
// Semua bagian <head> (meta, Open Graph, Twitter Card, CSS) kini ada di satu
// tempat: includes/layout_head.php.
$judul     = 'InStaGi - Cek dan Pantau Status Gizi Anda';
$deskripsi = 'InStaGi membantu Anda cek status gizi (IMT), kebutuhan kalori harian, dan memantaunya dari waktu ke waktu. Tersedia juga layanan konsultasi gizi dan katering gizi.';
$og_path   = '/';
$css       = ['index.css', 'nav.css', 'pages.css'];
require __DIR__ . '/includes/views/layout_head.php';
?>
    <div class="container">
        <div class="header">
            <div class="logo"><img src="/assets/logo.webp" alt="InStaGi Logo" width="512" height="466" style="max-width: 150px; height: auto;"></div>
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
        <div class="home-menu-buttons">
            <a class="home-btn home-btn-burgundy" href="/imt">
                <span class="home-btn-ico" aria-hidden="true"><?= instagi_ikon('cek-gizi') ?></span>
                <span>CEK STATUS GIZI</span>
            </a>
            <a class="home-btn home-btn-baby-yellow" href="/informasi">
                <span class="home-btn-ico" aria-hidden="true"><?= instagi_ikon('informasi') ?></span>
                <span>INFORMASI GIZI</span>
            </a>
            <a class="home-btn home-btn-sage-green" href="/monitoring">
                <span class="home-btn-ico" aria-hidden="true"><?= instagi_ikon('monitoring') ?></span>
                <span>MONITORING STATUS GIZI</span>
            </a>
        </div>

        <!-- ===== LAYANAN: KONSULTASI & KATERING GIZI ===== -->
        <section class="layanan-section" id="layanan">
            <h2>Layanan Konsultasi & Katering Gizi</h2>
            <p class="layanan-intro">Butuh pendampingan langsung? Kami siap mendampingi pola makan sehat dan menyediakan menu katering sesuai kebutuhan kalori Anda.</p>

            <div class="layanan-grid layanan-grid-single">
                <article class="layanan-card layanan-card-unified">
                    <div class="layanan-ico-group">
                        <span class="layanan-ico" aria-hidden="true"><?= instagi_ikon('konsultasi') ?></span>
                        <span class="layanan-ico-plus" aria-hidden="true">+</span>
                        <span class="layanan-ico" aria-hidden="true"><?= instagi_ikon('katering') ?></span>
                    </div>
                    <h3>Konsultasi & Katering Gizi Personal</h3>
                    <p>Konsultasikan status gizi, pola makan, dan target kesehatan Anda bersama ahli gizi, serta nikmati menu katering harian yang dihitung tepat sesuai estimasi kebutuhan kalori Anda (program penurunan, kenaikan, atau pemeliharaan berat badan).</p>
                    <div class="layanan-actions">
                        <a class="layanan-btn layanan-btn-wa" href="<?= htmlspecialchars($wa_layanan, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                            <span class="site-nav-ico" aria-hidden="true"><?= instagi_ikon('whatsapp') ?></span> Konsultasi & Pesan Katering via WhatsApp
                        </a>
                    </div>
                </article>
            </div>
        </section>
    </div>
<?php require __DIR__ . '/includes/views/layout_footer.php'; ?>
