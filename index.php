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
 *   3. Bestie InStaGi: wadah sponsor / kerja sama iklan
 *
 * CATATAN: tautan "Login Admin" tidak lagi ditampilkan (lihat includes/nav.php).
 */
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/ikon.php';

$nav_active = '';

// --- Layanan via WhatsApp ----------------------------------------------------
// Nomor diambil dari includes/config.php (satu sumber, bisa dioverride env).
$wa_layanan = instagi_wa_url(
    'Halo InStaGi, saya ingin berkonsultasi mengenai gizi dan memesan katering gizi. Mohon informasinya.',
    INSTAGI_WA_KATERING
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

// Judul, deskripsi, alamat rapi (tanpa .php), dan daftar CSS halaman ini.
// Semua bagian <head> (meta, Open Graph, Twitter Card, CSS) kini ada di satu
// tempat: includes/layout_head.php.
$judul     = 'InStaGi - Cek dan Pantau Status Gizi Anda';
$deskripsi = 'InStaGi membantu Anda cek status gizi (IMT), kebutuhan kalori harian, dan memantaunya dari waktu ke waktu. Tersedia juga layanan konsultasi gizi dan katering gizi.';
$og_path   = '/';
$css       = ['index.css', 'nav.css', 'pages.css'];
require __DIR__ . '/includes/layout_head.php';
?>
    <div class="container">
        <div class="header">
            <div class="logo"><img src="assets/logo.webp" alt="InStaGi Logo" width="512" height="466" style="max-width: 150px; height: auto;"></div>
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
            <a class="home-menu-card" href="/imt">
                <span class="home-menu-ico" aria-hidden="true"><?= instagi_ikon('cek-gizi') ?></span>
                <h3>Cek Status Gizi</h3>
                <p>Hitung Indeks Massa Tubuh (IMT), status gizi, dan kebutuhan kalori harian Anda.</p>
            </a>
            <a class="home-menu-card" href="/informasi">
                <span class="home-menu-ico" aria-hidden="true"><?= instagi_ikon('informasi') ?></span>
                <h3>Informasi Gizi</h3>
                <p>Leaflet gizi, contoh menu sesuai kalori, kumpulan resep, panduan diet, dan Daftar Bahan Makanan Penukar (DBMP).</p>
            </a>
            <a class="home-menu-card" href="/monitoring">
                <span class="home-menu-ico" aria-hidden="true"><?= instagi_ikon('monitoring') ?></span>
                <h3>Monitoring Status Gizi</h3>
                <p>Lihat riwayat dan grafik perkembangan status gizi akun Anda dari waktu ke waktu.</p>
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
                    Slot mitra masih kosong. Jadilah <strong>Bestie InStaGi</strong> pertama — hubungi kami via email untuk
                    menempatkan logo dan tautan usaha Anda di sini.
                </div>
            <?php endif; ?>

            <div class="bestie-actions">
                <a class="layanan-btn layanan-btn-email" href="mailto:instagiadmin@gmail.com?subject=Kerja%20Sama%20Bestie%20InStaGi">
                    <span class="site-nav-ico" aria-hidden="true"><?= instagi_ikon('email') ?></span> Hubungi via Email (instagiadmin@gmail.com)
                </a>
            </div>
        </section>

        <p class="disclaimer">Hasil merupakan skrining awal, bukan diagnosis medis.</p>
    </div>
<?php require __DIR__ . '/includes/layout_footer.php'; ?>
