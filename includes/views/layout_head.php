<?php
/**
 * Partial ATAS halaman InStaGi: <head> lengkap + pembuka <body> + navigasi.
 * -----------------------------------------------------------------------------
 * Tujuan: menghapus duplikasi. Sebelumnya blok <head> (meta description, Open
 * Graph, Twitter Card, daftar CSS) dan navigasi disalin di tujuh halaman;
 * sekarang cukup diisi variabelnya lalu `require` berkas ini.
 *
 * Cara pakai (semua variabel opsional kecuali $judul):
 *
 *   $nav_active = 'hitung';                    // menu aktif untuk nav.php
 *   $judul      = 'InStaGi - Cek Status Gizi';
 *   $deskripsi  = 'Masukkan data untuk cek status gizi...';
 *   $og_path    = '/imt';                      // alamat RAPI (tanpa .php) untuk og:url & canonical
 *   $css        = ['imt.css', 'nav.css'];      // css/base.css selalu dimuat lebih dulu
 *   $css_ekstra = '<link rel="stylesheet" href="https://cdn...">'; // opsional
 *   require __DIR__ . '/includes/layout_head.php';
 *
 * CATATAN: berkas ini TIDAK menyentuh rumus perhitungan (includes/gizi.php).
 */

$judul      = isset($judul) ? $judul : 'InStaGi';
$deskripsi  = isset($deskripsi) ? $deskripsi
    : 'InStaGi membantu Anda cek status gizi (IMT), kebutuhan kalori harian, dan memantaunya dari waktu ke waktu.';
$og_path    = isset($og_path) ? $og_path : '';
$css        = isset($css) && is_array($css) ? $css : ['index.css', 'nav.css', 'pages.css'];
$css_ekstra = isset($css_ekstra) ? $css_ekstra : '';
$meta_ekstra = isset($meta_ekstra) ? $meta_ekstra : '';

// Alamat kanonik dibuat dari domain yang sedang aktif (og:url & <link canonical>).
$instagi_dasar = instagi_base_url();
$instagi_kanonik = $instagi_dasar . ($og_path === '' ? '/' : $og_path);
$instagi_gambar  = $instagi_dasar . '/assets/og-image.jpg';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($judul, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="shortcut icon" href="/assets/favicon.ico" type="image/x-icon">
    <link rel="canonical" href="<?= htmlspecialchars($instagi_kanonik, ENT_QUOTES, 'UTF-8') ?>">

    <!-- CSS bersama SELALU dimuat lebih dulu, lalu CSS khusus halaman. -->
    <link rel="stylesheet" href="/css/base.css">
    <?php foreach ($css as $berkas_css): ?>
    <link rel="stylesheet" href="/css/<?= htmlspecialchars($berkas_css, ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
    <?= $css_ekstra ?>
    <?= $meta_ekstra ?>

    <meta name="description" content="<?= htmlspecialchars($deskripsi, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="theme-color" content="#e74c3c">

    <!-- Pratinjau tautan (WhatsApp, Facebook, X/Twitter). URL mengikuti domain aktif. -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="InStaGi">
    <meta property="og:title" content="<?= htmlspecialchars($judul, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($deskripsi, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($instagi_kanonik, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($instagi_gambar, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Logo InStaGi">
    <meta property="og:locale" content="id_ID">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($judul, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($deskripsi, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($instagi_gambar, ENT_QUOTES, 'UTF-8') ?>">
</head>
<body><?php require __DIR__ . '/nav.php'; ?>
