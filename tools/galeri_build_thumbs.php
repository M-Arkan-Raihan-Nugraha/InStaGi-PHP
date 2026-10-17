<?php
/**
 * Pembuat thumbnail SEMUA galeri aset InStaGi (jalankan dari CLI)
 * -----------------------------------------------------------------------------
 * Galeri di halaman Informasi Gizi memuat gambar berukuran besar (poster
 * beberapa ratus KB sampai 2 MB per gambar). Skrip ini membuat versi kecil
 * untuk tiap gambar ke folder  assets/<galeri>/thumbs/  supaya halaman tetap
 * ringan, terutama di ponsel.
 *
 * CARA PAKAI
 * ----------
 *     php includes/galeri_build_thumbs.php
 *
 * Jalankan ulang setiap kali menambah / mengganti gambar pada galeri.
 * Galeri tetap berfungsi walau skrip ini belum dijalankan — kartu akan memakai
 * gambar ukuran penuh sebagai cadangan (hanya lebih berat).
 *
 * Butuh ekstensi PHP "gd" dengan dukungan JPEG (umumnya sudah aktif di hosting).
 *
 * Skrip ini hanya menyentuh berkas GAMBAR. Rumus perhitungan di
 * includes/gizi.php tidak tersentuh.
 */

// Skrip ini hanya untuk CLI, bukan untuk diakses lewat browser.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Skrip ini hanya dapat dijalankan dari command line.\n");
}

require_once dirname(__DIR__) . '/includes/core/config.php';
require_once dirname(__DIR__) . '/includes/content/galeri_aset.php';
require_once dirname(__DIR__) . '/includes/content/leaflet.php';
require_once dirname(__DIR__) . '/includes/content/menu.php';
require_once dirname(__DIR__) . '/includes/content/resep.php';
require_once dirname(__DIR__) . '/includes/content/panduan.php';

/**
 * Daftar galeri yang diproses.
 *
 * "lebar" = lebar maksimum thumbnail (piksel), tinggi mengikuti rasio asli.
 * "mutu"  = kualitas kompresi JPEG (0-100).
 *
 * Catatan: galeri resep & panduan memakai gambar poster memanjang, jadi
 * lebarnya dibuat lebih kecil agar kartu di ponsel tidak berat.
 */
$daftar_galeri = [
    'leaflet' => ['nama_tampil' => 'Leaflet Informasi Gizi',   'fungsi_daftar' => 'instagi_leaflet_list', 'lebar' => 600, 'mutu' => 78],
    'menu'    => ['nama_tampil' => 'Contoh Menu Kalori',       'fungsi_daftar' => 'instagi_menu_list',    'lebar' => 600, 'mutu' => 78, 'fungsi_slug' => 'instagi_menu_slug'],
    'resep'   => ['nama_tampil' => 'Kumpulan Resep',           'fungsi_daftar' => 'instagi_resep_list',   'lebar' => 480, 'mutu' => 76],
    'panduan' => ['nama_tampil' => 'Panduan & Edukasi Gizi',   'fungsi_daftar' => 'instagi_panduan_list', 'lebar' => 480, 'mutu' => 76],
];

if (!extension_loaded('gd')) {
    exit("Ekstensi PHP 'gd' tidak aktif. Thumbnail dilewati; galeri akan memakai gambar ukuran penuh.\n");
}
$info = gd_info();
if (empty($info['JPEG Support'])) {
    exit("Dukungan JPEG pada GD tidak aktif. Thumbnail dilewati.\n");
}

$total_buat = 0;
$total_lewat = 0;
$total_gagal = 0;

foreach ($daftar_galeri as $galeri => $atur) {
    $src_dir   = instagi_galeri_dir($galeri);
    $thumb_dir = $src_dir . '/thumbs';

    printf("\n=== %s (assets/%s, maks %d px, mutu %d) ===\n", $atur['nama_tampil'], $galeri, $atur['lebar'], $atur['mutu']);

    if (!is_dir($src_dir)) {
        echo "  [LEWAT] folder sumber belum ada\n";
        continue;
    }
    if (!is_dir($thumb_dir) && !@mkdir($thumb_dir, 0755, true)) {
        printf("  [GAGAL] tidak dapat membuat folder %s\n", $thumb_dir);
        continue;
    }

    $daftar = call_user_func($atur['fungsi_daftar']);

    foreach (array_keys($daftar) as $kunci) {
        $slug = isset($atur['fungsi_slug']) ? call_user_func($atur['fungsi_slug'], $kunci) : $kunci;
        $berkas = instagi_galeri_find($galeri, $slug);

        if ($berkas === null) {
            printf("  [LEWAT] %-42s gambar tidak ditemukan\n", $slug);
            $total_lewat++;
            continue;
        }

        $src_path = $src_dir . '/' . $berkas;
        $dst_path = $thumb_dir . '/' . $slug . '.jpg';
        $ext      = strtolower(pathinfo($berkas, PATHINFO_EXTENSION));

        // Lewati bila thumbnail sudah lebih baru dari gambar sumbernya.
        if (is_file($dst_path) && filemtime($dst_path) >= filemtime($src_path)) {
            printf("  [OK]    %-42s sudah terbaru (%s KB)\n", $slug, round(filesize($dst_path) / 1024));
            $total_lewat++;
            continue;
        }

        $src_img = null;
        switch ($ext) {
            case 'jpeg':
            case 'jpg':
                $src_img = @imagecreatefromjpeg($src_path);
                break;
            case 'png':
                $src_img = @imagecreatefrompng($src_path);
                break;
            case 'webp':
                $src_img = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src_path) : null;
                break;
        }

        if (!$src_img) {
            printf("  [GAGAL] %-42s tidak dapat dibaca\n", $slug);
            $total_gagal++;
            continue;
        }

        $src_w = imagesx($src_img);
        $src_h = imagesy($src_img);

        // Jangan pernah memperbesar gambar.
        $new_w = min($atur['lebar'], $src_w);
        $new_h = (int) round($src_h * ($new_w / $src_w));

        $dst_img = imagecreatetruecolor($new_w, $new_h);
        $putih   = imagecolorallocate($dst_img, 255, 255, 255);
        imagefilledrectangle($dst_img, 0, 0, $new_w, $new_h, $putih);
        imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_w, $new_h, $src_w, $src_h);

        $ok = imagejpeg($dst_img, $dst_path, $atur['mutu']);

        // imagedestroy() tidak diperlukan sejak PHP 8.0 (dan memicu deprecation
        // di PHP 8.5+); cukup lepaskan referensinya.
        unset($src_img, $dst_img);

        if ($ok) {
            printf(
                "  [BUAT]  %-42s %dx%d -> %dx%d (%s KB)\n",
                $slug,
                $src_w,
                $src_h,
                $new_w,
                $new_h,
                round(filesize($dst_path) / 1024)
            );
            $total_buat++;
        } else {
            printf("  [GAGAL] %-42s gagal menulis thumbnail\n", $slug);
            $total_gagal++;
        }
    }
}

printf("\nRINGKASAN: %d thumbnail dibuat, %d dilewati, %d gagal.\n", $total_buat, $total_lewat, $total_gagal);
exit($total_gagal > 0 ? 1 : 0);
