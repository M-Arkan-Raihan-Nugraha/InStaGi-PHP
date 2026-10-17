<?php
/**
 * Partial navigasi utama InStaGi.
 * -----------------------------------------------------------------------------
 * Dipakai oleh halaman publik & admin agar pilihan menu konsisten:
 *   Hitung IMT · Informasi · Monitoring · Login Admin
 *
 * Cara pakai (setelah session.php dimuat):
 *   $nav_active = 'hitung';
 *   require __DIR__ . '/includes/nav.php';
 *
 * Halaman yang tidak memakai session.php boleh memuat responden.php sendiri;
 * partial ini aman dijalankan berkali-kali.
 */

if (!function_exists('instagi_nav_items')) {
    function instagi_nav_items()
    {
        return [
            'hitung'     => ['label' => 'Hitung IMT',  'href' => 'imt.php',        'icon' => '📊'],
            'informasi'  => ['label' => 'Informasi',   'href' => 'informasi.php',  'icon' => '📄'],
            'monitoring' => ['label' => 'Monitoring',  'href' => 'monitoring.php', 'icon' => '📈'],
        ];
    }
}

$nav_active = isset($nav_active) ? $nav_active : '';
$nav_items  = instagi_nav_items();
$nav_admin  = (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true);
?>
<header class="site-nav">
    <div class="site-nav-inner">
        <a class="site-nav-brand" href="index.php">
            <img src="assets/logo.png" alt="Logo InStaGi" width="512" height="466">
            <span>InStaGi</span>
        </a>
        <nav class="site-nav-links" aria-label="Navigasi utama">
            <?php foreach ($nav_items as $key => $item): ?>
                <a class="site-nav-link<?= $key === $nav_active ? ' is-active' : '' ?>"
                   href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>">
                    <span class="site-nav-ico" aria-hidden="true"><?= $item['icon'] ?></span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
            <?php if ($nav_admin): ?>
                <a class="site-nav-link<?= $nav_active === 'admin' ? ' is-active' : '' ?>" href="admin.php">
                    <span class="site-nav-ico" aria-hidden="true">🛠️</span>Admin
                </a>
            <?php else: ?>
                <a class="site-nav-link<?= $nav_active === 'admin' ? ' is-active' : '' ?>" href="login.php">
                    <span class="site-nav-ico" aria-hidden="true">🔐</span>Login Admin
                </a>
            <?php endif; ?>
        </nav>
    </div>
</header>
