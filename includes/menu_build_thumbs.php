<?php
/**
 * Pembuat thumbnail contoh menu InStaGi (jalankan dari CLI).
 * -----------------------------------------------------------------------------
 * Galeri "Contoh Menu" di result.php memuat beberapa gambar berukuran besar
 * (~2 MB total). Agar halaman tetap ringan di ponsel, skrip ini membuat versi
 * kecil dari setiap menu ke folder assets/menu/thumbs/.
 *
 * CARA PAKAI
 * ----------
 *     php includes/menu_build_thumbs.php
 *
 * Jalankan ulang setiap kali menambah / mengganti gambar menu.
 * Galeri tetap berfungsi walau skrip ini belum dijalankan (memakai gambar
 * ukuran penuh sebagai cadangan).
 *
 * Butuh ekstensi PHP "gd" dengan dukungan JPEG (umumnya sudah aktif di hosting).
 */

// Skrip ini hanya untuk CLI, bukan untuk diakses lewat browser.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Skrip ini hanya dapat dijalankan dari command line.\n");
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/menu.php';

/** Lebar maksimum thumbnail (piksel). Tinggi mengikuti rasio asli. */
const MENU_THUMB_MAX_WIDTH = 600;
/** Kualitas kompresi JPEG (0-100). */
const MENU_THUMB_QUALITY = 78;

$src_dir   = instagi_menu_dir();
$thumb_dir = $src_dir . '/thumbs';

if (!is_dir($src_dir)) {
    exit("Folder sumber tidak ditemukan: {$src_dir}\n");
}
if (!is_dir($thumb_dir) && !@mkdir($thumb_dir, 0755, true)) {
    exit("Gagal membuat folder thumbnail: {$thumb_dir}\n");
}
if (!extension_loaded('gd')) {
    exit("Ekstensi PHP 'gd' tidak aktif. Thumbnail dilewati; galeri akan memakai gambar ukuran penuh.\n");
}

$info = gd_info();
if (empty($info['JPEG Support'])) {
    exit("Dukungan JPEG pada GD tidak aktif. Thumbnail dilewati.\n");
}

$made = 0;
$skip = 0;
$fail = 0;

foreach (array_keys(instagi_menu_list()) as $kkal) {
    $slug = instagi_menu_slug($kkal);
    $file = instagi_menu_find($kkal);
    if ($file === null) {
        printf("  [LEWAT] %-22s gambar tidak ditemukan\n", $slug);
        $skip++;
        continue;
    }

    $src_path  = $src_dir . '/' . $file;
    $dst_path  = $thumb_dir . '/' . $slug . '.jpg';
    $ext       = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    // Lewati bila thumbnail sudah lebih baru dari gambar sumber.
    if (is_file($dst_path) && filemtime($dst_path) >= filemtime($src_path)) {
        printf("  [OK]    %-22s sudah terbaru (%s KB)\n", $slug, round(filesize($dst_path) / 1024));
        $skip++;
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
        printf("  [GAGAL] %-22s tidak dapat dibaca\n", $slug);
        $fail++;
        continue;
    }

    $src_w = imagesx($src_img);
    $src_h = imagesy($src_img);

    // Jangan pernah memperbesar gambar.
    $new_w = min(MENU_THUMB_MAX_WIDTH, $src_w);
    $new_h = (int) round($src_h * ($new_w / $src_w));

    $dst_img = imagecreatetruecolor($new_w, $new_h);

    // Latar putih (untuk PNG/WEBP transparan).
    $white = imagecolorallocate($dst_img, 255, 255, 255);
    imagefilledrectangle($dst_img, 0, 0, $new_w, $new_h, $white);

    imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_w, $new_h, $src_w, $src_h);

    $ok = imagejpeg($dst_img, $dst_path, MENU_THUMB_QUALITY);

    // Catatan: imagedestroy() sudah tidak diperlukan sejak PHP 8.0 dan
    // memicu deprecation di PHP 8.5+. Cukup lepaskan referensinya.
    unset($src_img, $dst_img);

    if ($ok) {
        printf(
            "  [BUAT]  %-22s %dx%d -> %dx%d (%s KB)\n",
            $slug,
            $src_w,
            $src_h,
            $new_w,
            $new_h,
            round(filesize($dst_path) / 1024)
        );
        $made++;
    } else {
        printf("  [GAGAL] %-22s gagal menulis thumbnail\n", $slug);
        $fail++;
    }
}

printf("\nSelesai: %d dibuat, %d dilewati, %d gagal.\n", $made, $skip, $fail);
printf("Lokasi: %s\n", $thumb_dir);
exit($fail > 0 ? 1 : 0);
