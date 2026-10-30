<?php
/**
 * Halaman Informasi InStaGi.
 * -----------------------------------------------------------------------------
 * Menampilkan materi edukasi gizi dalam satu halaman:
 *   1. Leaflet Informasi Gizi
 *   2. Contoh Menu Sesuai Kebutuhan Kalori
 *   3. Kumpulan Resep (menu ayam, ikan, sup, nusantara, camilan, dst.)
 *   4. Panduan & Edukasi Gizi (diet DM/PPOK/protein, albumin, zat besi, dst.)
 *   5. DBMP (Daftar Bahan Makanan Penukar)
 *
 * Konten diambil dari modul yang sama dengan result.php, sehingga menambah /
 * mengganti materi cukup dilakukan di includes/leaflet.php, menu.php, dbmp.php.
 */
require_once 'includes/core/session.php';
require_once 'includes/content/leaflet.php';
require_once 'includes/content/menu.php';
require_once 'includes/content/recipes.php';
require_once 'includes/content/guidelines.php';
require_once 'includes/content/fitness_tips.php';
require_once 'includes/content/food_exchange.php';
require_once 'includes/content/bestie.php';

$nav_active = 'informasi';

// --- Kumpulkan daftar siap tampil untuk tiap galeri ---
// (helper bersama: includes/content/gallery_asset.php — hanya entri yang gambarnya ada)
$leaflets     = instagi_galeri_daftar_tampil('leaflet', instagi_leaflet_list());
$menus        = instagi_galeri_daftar_tampil('menu', instagi_menu_list(), 'instagi_menu_slug');
$reseps       = instagi_resep_daftar();
$panduans     = instagi_panduan_daftar();
$tips_bugar   = instagi_tips_bugar_daftar();
$bestie_items = instagi_bestie_daftar();

// --- DBMP (satu berkas PDF) ---
$dbmp_pdf   = instagi_dbmp_pdf();
$dbmp_cover = instagi_dbmp_cover();
?>
<?php
// Judul, deskripsi, alamat rapi (tanpa .php), dan daftar CSS halaman ini.
$judul     = 'InStaGi - Informasi Gizi';
$deskripsi = 'Etalase mitra Bestie InStaGi dan materi edukasi gizi: leaflet, panduan diet & edukasi gizi, Daftar Bahan Makanan Penukar (DBMP), contoh menu sesuai kebutuhan kalori, kumpulan resep, serta tips bugar.';
$og_path   = '/informasi';
$css       = ['index.css', 'nav.css', 'pages.css'];
require __DIR__ . '/includes/views/layout_head.php';
?>

    <div class="container">
        <!-- ===== BESTIE INSTAGI: ETALASE MITRA & SPONSOR PALING ATAS ===== -->
        <?php require __DIR__ . '/includes/views/bestie_showcase.php'; ?>

        <h1 style="text-align:left;color:var(--primary-color);">Informasi Gizi</h1>
            <p class="info-intro">Materi edukasi gizi InStaGi: leaflet, panduan diet &amp; edukasi gizi, Daftar Bahan Makanan Penukar (DBMP), contoh menu sesuai kebutuhan kalori, kumpulan resep, serta tips bugar. Klik gambar untuk membuka versi besar, atau unduh untuk disimpan.</p>

            <nav class="info-jump" aria-label="Lompat ke bagian">
                <a href="#bestie"><span>Bestie InStaGi</span><span class="info-jump-count"><?= count($bestie_items) ?></span></a>
                <a href="#leaflet"><span>Leaflet</span><span class="info-jump-count"><?= count($leaflets) ?></span></a>
                <a href="#panduan"><span>Panduan &amp; Edukasi</span><span class="info-jump-count"><?= count($panduans) ?></span></a>
                <a href="#dbmp"><span>DBMP</span><span class="info-jump-count"><?= $dbmp_pdf !== null ? 1 : 0 ?></span></a>
                <a href="#menu"><span>Contoh Menu</span><span class="info-jump-count"><?= count($menus) ?></span></a>
                <a href="#resep"><span>Kumpulan Resep</span><span class="info-jump-count"><?= count($reseps) ?></span></a>
                <a href="#tips-bugar"><span>Tips Bugar</span><span class="info-jump-count"><?= count($tips_bugar) ?></span></a>
            </nav>

            <!-- ===== 1. LEAFLET INFORMASI GIZI ===== -->
<?php
$kartu_id     = 'leaflet';
$kartu_judul  = 'Leaflet Informasi Gizi';
$kartu_intro  = 'Panduan praktis seputar gizi seimbang, sumber zat gizi, hingga bahan yang perlu dibatasi.';
$kartu_daftar = $leaflets;
$kartu_alt    = 'Leaflet';
$kartu_kosong = 'Belum ada leaflet yang tersedia.';
require __DIR__ . '/includes/views/gallery_card.php';
?>

            <!-- ===== 2. PANDUAN & EDUKASI GIZI ===== -->
<?php
$kartu_id     = 'panduan';
$kartu_judul  = 'Panduan & Edukasi Gizi';
$kartu_intro  = 'Panduan diet untuk kondisi tertentu, daftar bahan makanan pilihan, keamanan pangan, dan edukasi kesehatan pencernaan.';
$kartu_daftar = $panduans;
$kartu_alt    = 'Panduan';
$kartu_kosong = 'Belum ada panduan yang tersedia.';
require __DIR__ . '/includes/views/gallery_card.php';
?>

            <!-- ===== 3. DBMP ===== -->
            <section class="info-section" id="dbmp">
                <h2>Daftar Bahan Makanan Penukar (DBMP)</h2>
                <p class="info-intro">Panduan menukar bahan makanan dengan nilai gizi setara, disusun per golongan pangan.</p>

                <?php if ($dbmp_pdf !== null): ?>
                    <div class="info-grid">
                        <article class="info-card">
                            <?php if ($dbmp_cover !== null): ?>
                                <img src="<?= htmlspecialchars($dbmp_cover, ENT_QUOTES, 'UTF-8') ?>"
                                     alt="Sampul DBMP" decoding="async">
                            <?php endif; ?>
                            <div class="info-card-body">
                                <span class="info-tag">Panduan Diet</span>
                                <h3>Daftar Bahan Makanan Penukar</h3>
                                <p class="info-desc">Satu berkas PDF — <?= (int) instagi_dbmp_jumlah_halaman() ?> halaman golongan bahan makanan.</p>
                                <div class="info-card-actions">
                                    <a href="<?= htmlspecialchars($dbmp_pdf, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Lihat PDF</a>
                                    <a class="alt" href="<?= htmlspecialchars($dbmp_pdf, ENT_QUOTES, 'UTF-8') ?>"
                                       download="DBMP-InStaGi-Daftar-Bahan-Makanan-Penukar.pdf">Unduh</a>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php else: ?>
                    <div class="info-empty">Belum ada berkas DBMP yang tersedia.</div>
                <?php endif; ?>
            </section>

            <!-- ===== 4. CONTOH MENU ===== -->
<?php
$kartu_id     = 'menu';
$kartu_judul  = 'Contoh Menu Sesuai Kebutuhan Kalori';
$kartu_intro  = 'Contoh menu sehari (3 makan utama + 2 selingan) untuk beberapa tingkat kebutuhan kalori.';
$kartu_daftar = $menus;
$kartu_alt    = 'Contoh menu';
$kartu_kosong = 'Belum ada contoh menu yang tersedia.';
require __DIR__ . '/includes/views/gallery_card.php';
?>

            <!-- ===== 5. KUMPULAN RESEP ===== -->
<?php
$kartu_id     = 'resep';
$kartu_judul  = 'Kumpulan Resep';
$kartu_intro  = 'Lembar resep bergambar: setiap lembar memuat 7 resep lengkap dengan bahan, cara memasak, dan nilai gizinya.';
$kartu_daftar = $reseps;
$kartu_alt    = 'Resep';
$kartu_kosong = 'Belum ada lembar resep yang tersedia.';
require __DIR__ . '/includes/views/gallery_card.php';
?>

            <!-- ===== 6. TIPS BUGAR ===== -->
            <span id="aktivitas" style="display:none;" aria-hidden="true"></span>
<?php
$kartu_id     = 'tips-bugar';
$kartu_judul  = 'Tips Bugar';
$kartu_intro  = 'Panduan dan infografis kebugaran, peregangan harian, serta aktivitas fisik untuk mendukung gaya hidup sehat dan bugar.';
$kartu_daftar = $tips_bugar;
$kartu_alt    = 'Tips Bugar';
$kartu_kosong = 'Belum ada materi tips bugar yang tersedia.';
require __DIR__ . '/includes/views/gallery_card.php';
?>
    </div>

<?php require __DIR__ . '/includes/views/layout_footer.php'; ?>
