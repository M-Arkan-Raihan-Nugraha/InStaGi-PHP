<?php
/**
 * Partial navigasi utama InStaGi.
 * -----------------------------------------------------------------------------
 * Dipakai oleh halaman publik agar pilihan menu konsisten:
 *   Cek Status Gizi · Informasi Gizi · Monitoring Status Gizi
 *
 * CATATAN: tautan "Login Admin" SENGAJA TIDAK ditampilkan di navigasi (atas
 * permintaan pemilik proyek: pintu masuk admin jangan tampil untuk umum).
 * Halaman admin tetap ada di alamat login.php, dan tautan "Admin" hanya muncul
 * setelah admin benar-benar sudah masuk.
 *
 * Cara pakai (setelah session.php dimuat):
 *   $nav_active = 'hitung';
 *   require __DIR__ . '/includes/nav.php';
 *
 * Halaman yang tidak memakai session.php boleh memuat responden.php sendiri;
 * partial ini aman dijalankan berkali-kali.
 */

require_once __DIR__ . '/ikon.php';

if (!function_exists('instagi_nav_items')) {
    function instagi_nav_items()
    {
        // Alamat RAPI (tanpa akhiran .php) — diatur oleh aturan rewrite di
        // .htaccess root. Alamat lama (imt.php, informasi.php, dst.) tetap
        // berfungsi, jadi tautan lama/bookmark tidak mati.
        return [
            'hitung'     => ['label' => 'Cek Status Gizi',        'href' => '/imt',        'icon' => 'cek-gizi'],
            'informasi'  => ['label' => 'Informasi Gizi',         'href' => '/informasi',  'icon' => 'informasi'],
            'monitoring' => ['label' => 'Monitoring Status Gizi', 'href' => '/monitoring', 'icon' => 'monitoring'],
        ];
    }
}

$nav_active = isset($nav_active) ? $nav_active : '';
$nav_items  = instagi_nav_items();
$nav_admin  = (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true);
?>
<header class="site-nav">
    <div class="site-nav-inner">
        <a class="site-nav-brand" href="/">
            <img src="assets/logo.png" alt="Logo InStaGi" width="512" height="466">
            <span>InStaGi</span>
        </a>
        <nav class="site-nav-links" aria-label="Navigasi utama">
            <?php foreach ($nav_items as $key => $item): ?>
                <a class="site-nav-link<?= $key === $nav_active ? ' is-active' : '' ?>"
                   href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>">
                    <span class="site-nav-ico" aria-hidden="true"><?= instagi_ikon($item['icon']) ?></span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
            <?php if ($nav_admin): ?>
                <a class="site-nav-link<?= $nav_active === 'admin' ? ' is-active' : '' ?>" href="/admin">
                    <span class="site-nav-ico" aria-hidden="true"><?= instagi_ikon('admin') ?></span>Admin
                </a>
            <?php endif; ?>
        </nav>
    </div>
</header>
