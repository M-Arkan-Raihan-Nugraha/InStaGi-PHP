<?php
/**
 * Halaman Resmi Bestie InStaGi (Mitra, Sponsor, & Etalase Flyer)
 * -----------------------------------------------------------------------------
 * Menampilkan etalase multi-kategori mitra pilihan InStaGi:
 * Layanan Kesehatan, Kuliner & Pangan Sehat, Minimarket, Land & Property, serta Travel & Hotel.
 * Dilengkapi filter interaktif, carousel responsif, dan modal lightbox flyer.
 */
require_once 'includes/core/session.php';
require_once 'includes/content/bestie.php';
require_once 'includes/content/gallery_asset.php';

$nav_active = 'bestie';

$judul     = 'InStaGi - Bestie InStaGi (Mitra & Sponsor)';
$deskripsi = 'Etalase kemitraan dan promosi resmi sahabat InStaGi: penawaran pilihan dari Layanan Kesehatan, Kuliner Sehat, Minimarket, Land & Property, serta Travel & Hotel terpercaya.';
$og_path   = '/bestie';
$css       = ['index.css', 'nav.css', 'pages.css'];
require __DIR__ . '/includes/views/layout_head.php';
?>

    <div class="container">
        <!-- ===== ETALASE BESTIE INSTAGI ===== -->
        <?php require __DIR__ . '/includes/views/bestie_showcase.php'; ?>
    </div>

<?php require __DIR__ . '/includes/views/layout_footer.php'; ?>

