<?php
/**
 * Halaman Informasi InStaGi.
 * -----------------------------------------------------------------------------
 * Menampilkan materi edukasi gizi dalam satu halaman:
 *   1. Leaflet Informasi Gizi
 *   2. Contoh Menu Sesuai Kebutuhan Kalori
 *   3. DBMP (Daftar Bahan Makanan Penukar)
 *
 * Konten diambil dari modul yang sama dengan result.php, sehingga menambah /
 * mengganti materi cukup dilakukan di includes/leaflet.php, menu.php, dbmp.php.
 */
require_once 'includes/session.php';
require_once 'includes/leaflet.php';
require_once 'includes/menu.php';
require_once 'includes/dbmp.php';

$nav_active = 'informasi';

// --- Kumpulkan leaflet yang gambarnya tersedia ---
$leaflets = [];
foreach (instagi_leaflet_list() as $slug => $meta) {
    $thumb = instagi_leaflet_thumb_url($slug);
    $full  = instagi_leaflet_web_url($slug);
    if ($thumb === null || $full === null) {
        continue;
    }
    $leaflets[] = [
        'judul'    => $meta['judul'],
        'subjudul' => $meta['subjudul'],
        'ringkas'  => $meta['ringkas'],
        'tag'      => $meta['tag'],
        'thumb'    => $thumb,
        'full'     => $full,
        'unduh'    => $meta['unduh'],
    ];
}

// --- Kumpulkan contoh menu yang gambarnya tersedia ---
$menus = [];
foreach (instagi_menu_list() as $kkal => $meta) {
    $thumb = instagi_menu_thumb_url($kkal);
    $full  = instagi_menu_web_url($kkal);
    if ($thumb === null || $full === null) {
        continue;
    }
    $menus[] = [
        'judul'    => $meta['judul'],
        'subjudul' => $meta['subjudul'],
        'ringkas'  => $meta['ringkas'],
        'tag'      => $meta['tag'],
        'thumb'    => $thumb,
        'full'     => $full,
        'unduh'    => $meta['unduh'],
    ];
}

// --- DBMP (satu berkas PDF) ---
$dbmp_pdf   = instagi_dbmp_pdf();
$dbmp_cover = instagi_dbmp_cover();
?>
<?php
// Judul, deskripsi, alamat rapi (tanpa .php), dan daftar CSS halaman ini.
$judul     = 'InStaGi - Informasi Gizi';
$deskripsi = 'Kumpulan leaflet informasi gizi, contoh menu sesuai kebutuhan kalori, dan Daftar Bahan Makanan Penukar (DBMP) dari InStaGi.';
$og_path   = '/informasi';
$css       = ['index.css', 'nav.css', 'pages.css'];
require __DIR__ . '/includes/layout_head.php';
?>

    <div class="container">
        <h1 style="text-align:left;color:var(--primary-color);">Informasi Gizi</h1>
        <p class="info-intro">Materi edukasi gizi InStaGi: leaflet, contoh menu sesuai kebutuhan kalori, dan Daftar Bahan Makanan Penukar (DBMP). Klik gambar untuk membuka versi besar, atau unduh untuk disimpan.</p>

        <!-- ===== LEAFLET INFORMASI GIZI ===== -->
        <section class="info-section" id="leaflet">
            <h2>Leaflet Informasi Gizi</h2>
            <p class="info-intro">Panduan praktis seputar gizi seimbang, sumber zat gizi, hingga bahan yang perlu dibatasi.</p>

            <?php if (!empty($leaflets)): ?>
                <div class="info-grid">
                    <?php foreach ($leaflets as $lf): ?>
                    <article class="info-card">
                        <a href="<?= htmlspecialchars($lf['full'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                            <img src="<?= htmlspecialchars($lf['thumb'], ENT_QUOTES, 'UTF-8') ?>"
                                 alt="Leaflet <?= htmlspecialchars($lf['judul'], ENT_QUOTES, 'UTF-8') ?>"
                                 loading="lazy" decoding="async">
                        </a>
                        <div class="info-card-body">
                            <span class="info-tag"><?= htmlspecialchars($lf['tag'], ENT_QUOTES, 'UTF-8') ?></span>
                            <h3><?= htmlspecialchars($lf['judul'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="info-desc"><?= htmlspecialchars($lf['ringkas'], ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="info-card-actions">
                                <a href="<?= htmlspecialchars($lf['full'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Lihat</a>
                                <a class="alt" href="<?= htmlspecialchars($lf['full'], ENT_QUOTES, 'UTF-8') ?>"
                                   download="<?= htmlspecialchars($lf['unduh'], ENT_QUOTES, 'UTF-8') ?>">Unduh</a>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="info-empty">Belum ada leaflet yang tersedia.</div>
            <?php endif; ?>
        </section>

        <!-- ===== CONTOH MENU ===== -->
        <section class="info-section" id="menu">
            <h2>Contoh Menu Sesuai Kebutuhan Kalori</h2>
            <p class="info-intro">Contoh menu sehari (3 makan utama + 2 selingan) untuk beberapa tingkat kebutuhan kalori.</p>

            <?php if (!empty($menus)): ?>
                <div class="info-grid">
                    <?php foreach ($menus as $mn): ?>
                    <article class="info-card">
                        <a href="<?= htmlspecialchars($mn['full'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                            <img src="<?= htmlspecialchars($mn['thumb'], ENT_QUOTES, 'UTF-8') ?>"
                                 alt="<?= htmlspecialchars($mn['judul'], ENT_QUOTES, 'UTF-8') ?>"
                                 loading="lazy" decoding="async">
                        </a>
                        <div class="info-card-body">
                            <span class="info-tag"><?= htmlspecialchars($mn['tag'], ENT_QUOTES, 'UTF-8') ?></span>
                            <h3><?= htmlspecialchars($mn['judul'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="info-desc"><?= htmlspecialchars($mn['ringkas'], ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="info-card-actions">
                                <a href="<?= htmlspecialchars($mn['full'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Lihat</a>
                                <a class="alt" href="<?= htmlspecialchars($mn['full'], ENT_QUOTES, 'UTF-8') ?>"
                                   download="<?= htmlspecialchars($mn['unduh'], ENT_QUOTES, 'UTF-8') ?>">Unduh</a>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="info-empty">Belum ada contoh menu yang tersedia.</div>
            <?php endif; ?>
        </section>

        <!-- ===== DBMP ===== -->
        <section class="info-section" id="dbmp">
            <h2>Daftar Bahan Makanan Penukar (DBMP)</h2>
            <p class="info-intro">Panduan menukar bahan makanan dengan nilai gizi setara, disusun per golongan pangan.</p>

            <?php if ($dbmp_pdf !== null): ?>
                <div class="info-grid">
                    <article class="info-card">
                        <?php if ($dbmp_cover !== null): ?>
                            <img src="<?= htmlspecialchars($dbmp_cover, ENT_QUOTES, 'UTF-8') ?>"
                                 alt="Sampul DBMP" loading="lazy" decoding="async">
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
    </div>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
