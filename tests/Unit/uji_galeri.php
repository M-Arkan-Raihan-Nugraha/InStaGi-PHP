<?php
/**
 * Uji Unit Galeri Konten & Aset InStaGi (Leaflet, Menu, Resep, Panduan, Tips Bugar, DBMP)
 *
 * Memastikan semua entri memiliki berkas gambar WebP asli, thumbnail WebP,
 * dan helper menghasilkan path root-relative (/assets/...) yang valid.
 */

if (PHP_SAPI !== 'cli') {
    exit("Hanya via CLI.\n");
}

require_once dirname(dirname(__DIR__)) . '/includes/core/config.php';
require_once dirname(dirname(__DIR__)) . '/includes/content/galeri_aset.php';
require_once dirname(dirname(__DIR__)) . '/includes/content/leaflet.php';
require_once dirname(dirname(__DIR__)) . '/includes/content/menu.php';
require_once dirname(dirname(__DIR__)) . '/includes/content/resep.php';
require_once dirname(dirname(__DIR__)) . '/includes/content/panduan.php';
require_once dirname(dirname(__DIR__)) . '/includes/content/tips_bugar.php';
require_once dirname(dirname(__DIR__)) . '/includes/content/dbmp.php';

$total_uji = 0;
$lulus = 0;
$gagal = 0;

function cek($pesan, $kondisi) {
    global $total_uji, $lulus, $gagal;
    $total_uji++;
    if ($kondisi) {
        $lulus++;
        echo "  [OK] $pesan\n";
    } else {
        $gagal++;
        echo "  [FAIL] $pesan\n";
    }
}

echo "== 1. Pengujian Modul Tips Bugar & Kompatibilitas Mundur ==\n";
$daftar_tb = instagi_tips_bugar_daftar();
cek("instagi_tips_bugar_daftar menghasilkan array", is_array($daftar_tb) && count($daftar_tb) >= 2);
$daftar_akt = instagi_aktivitas_daftar();
cek("Alias instagi_aktivitas_daftar identik dengan tips bugar", $daftar_akt === $daftar_tb);
cek("Path tips-bugar folder benar", is_dir(instagi_tips_bugar_dir()));
cek("Path aktivitas alias menunjuk folder tips-bugar", instagi_aktivitas_dir() === instagi_tips_bugar_dir());

echo "\n== 2. Pengujian Berkas & URL Galeri ==\n";
$galeri_tes = [
    'leaflet'    => [instagi_leaflet_list(), null],
    'menu'       => [instagi_menu_list(), 'instagi_menu_slug'],
    'resep'      => [instagi_resep_list(), null],
    'panduan'    => [instagi_panduan_list(), null],
    'tips-bugar' => [instagi_tips_bugar_list(), null],
];

foreach ($galeri_tes as $kategori => [$list, $slug_fn]) {
    echo "-- Galeri: $kategori --\n";
    cek("Daftar $kategori tidak kosong", !empty($list));
    foreach ($list as $key => $item) {
        $slug = $slug_fn ? call_user_func($slug_fn, $key) : $key;
        $file = instagi_galeri_find($kategori, $slug);
        cek("[$kategori:$slug] berkas fisik ditemukan ($file)", $file !== null);

        $full_url = instagi_galeri_web_url($kategori, $slug);
        cek("[$kategori:$slug] URL full root-relative (/assets/...)", strpos($full_url, '/assets/') === 0);

        $thumb_url = instagi_galeri_thumb_url($kategori, $slug);
        cek("[$kategori:$slug] URL thumb root-relative (/assets/...)", strpos($thumb_url, '/assets/') === 0);

        $thumb_disk = instagi_galeri_dir($kategori) . '/thumbs/' . $slug . '.webp';
        cek("[$kategori:$slug] thumbnail WebP fisik ada di disk", is_file($thumb_disk));
    }
}

echo "\n== 3. Pengujian DBMP ==\n";
$dbmp_pdf = instagi_dbmp_pdf();
cek("DBMP PDF URL root-relative", strpos($dbmp_pdf, '/assets/dbmp/') === 0);
$dbmp_cover = instagi_dbmp_cover();
cek("DBMP Cover URL root-relative", strpos($dbmp_cover, '/assets/dbmp/') === 0);
cek("DBMP Cover format WebP", substr($dbmp_cover, -5) === '.webp');

echo "\n=========================================\n";
echo "Total uji: $total_uji | Lulus: $lulus | Gagal: $gagal\n";

if ($gagal > 0) {
    exit(1);
}
