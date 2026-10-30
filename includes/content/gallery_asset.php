<?php
/**
 * Perkakas bersama untuk galeri aset InStaGi
 * -----------------------------------------------------------------------------
 * Dipakai oleh seluruh galeri gambar di halaman Informasi Gizi
 * (leaflet, contoh menu, resep, dan panduan gizi) supaya pencarian berkas,
 * thumbnail, dan URL hanya ditulis SEKALI di satu tempat.
 *
 * Setiap galeri tetap punya berkas datanya sendiri
 * (includes/leaflet.php, menu.php, resep.php, panduan.php) yang memuat judul,
 * ringkasan, dan nama berkas unduhan.
 *
 * Nama folder di dalam assets/ menentukan "nama galeri":
 *     leaflet, menu, resep, panduan
 *
 * Cara kerja pencarian berkas:
 *   1. Cari gambar di  assets/<galeri>/<slug>.<ext>
 *   2. Bila ada, cari thumbnail di  assets/<galeri>/thumbs/<slug>.<ext>
 *   3. Bila thumbnail belum dibuat, galeri memakai gambar ukuran penuh
 *      (halaman tetap jalan, hanya lebih berat).
 *
 * Ekstensi yang didukung: .jpeg .jpg .png .webp
 */

if (!function_exists('instagi_galeri_dir')) {
    /**
     * Folder fisik sebuah galeri, mis. instagi_galeri_dir('resep').
     * Nama galeri dibersihkan supaya tidak bisa dipakai keluar dari assets/.
     */
    function instagi_galeri_dir($galeri)
    {
        $root   = defined('APP_ROOT') ? APP_ROOT : dirname(dirname(__DIR__));
        $bersih = preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $galeri));
        if ($bersih === 'aktivitas') {
            $bersih = 'tips-bugar';
        }
        if ($bersih === '') {
            $bersih = 'leaflet';
        }
        return $root . '/assets/' . $bersih;
    }
}

if (!function_exists('instagi_galeri_slug_sah')) {
    /** Penjaga path traversal: slug hanya boleh huruf, angka, garis, dan garis bawah. */
    function instagi_galeri_slug_sah($slug)
    {
        return (bool) preg_match('/^[A-Za-z0-9_\-]+$/', (string) $slug);
    }
}

if (!function_exists('instagi_galeri_find')) {
    /**
     * Cari berkas gambar ukuran penuh.
     *
     * @return string|null Nama berkas (mis. "soto-nusantara.jpeg") atau null.
     */
    function instagi_galeri_find($galeri, $slug)
    {
        if (!instagi_galeri_slug_sah($slug)) {
            return null;
        }
        $dir = instagi_galeri_dir($galeri);
        foreach (['webp', 'jpeg', 'jpg', 'png'] as $ext) {
            if (is_file($dir . '/' . $slug . '.' . $ext)) {
                return $slug . '.' . $ext;
            }
        }
        return null;
    }
}

if (!function_exists('instagi_galeri_thumb_find')) {
    /**
     * Cari berkas thumbnail (folder thumbs/).
     *
     * @return string|null Nama berkas thumbnail atau null bila belum dibuat.
     */
    function instagi_galeri_thumb_find($galeri, $slug)
    {
        if (!instagi_galeri_slug_sah($slug)) {
            return null;
        }
        $dir = instagi_galeri_dir($galeri) . '/thumbs';
        foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
            if (is_file($dir . '/' . $slug . '.' . $ext)) {
                return $slug . '.' . $ext;
            }
        }
        return null;
    }
}

if (!function_exists('instagi_galeri_web_url')) {
    /** URL gambar ukuran penuh (relatif terhadap root aplikasi). */
    function instagi_galeri_web_url($galeri, $slug)
    {
        $berkas = instagi_galeri_find($galeri, $slug);
        if ($berkas === null) {
            return null;
        }
        $bersih = preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $galeri));
        if ($bersih === 'aktivitas') {
            $bersih = 'tips-bugar';
        }
        return '/assets/' . $bersih . '/' . $berkas;
    }
}

if (!function_exists('instagi_galeri_thumb_url')) {
    /** URL thumbnail; bila belum ada, pakai gambar ukuran penuh. */
    function instagi_galeri_thumb_url($galeri, $slug)
    {
        $thumb = instagi_galeri_thumb_find($galeri, $slug);
        if ($thumb !== null) {
            $bersih = preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $galeri));
            if ($bersih === 'aktivitas') {
                $bersih = 'tips-bugar';
            }
            return '/assets/' . $bersih . '/thumbs/' . $thumb;
        }
        return instagi_galeri_web_url($galeri, $slug);
    }
}

if (!function_exists('instagi_galeri_daftar_tampil')) {
    /**
     * Susun daftar siap-tampil untuk sebuah galeri: hanya entri yang gambarnya
     * benar-benar ada, sudah dilengkapi URL gambar & thumbnail.
     *
     * @param  string        $galeri  Nama folder di dalam assets/, mis. 'resep'.
     * @param  array         $daftar  Hasil fungsi daftar galeri (kunci => metadata).
     * @param  callable|null $slug_fn Pengubah kunci menjadi nama berkas, dipakai
     *                                galeri contoh menu (kunci 1400 -> "1400kkal").
     * @return array<int, array{slug:string,judul:string,subjudul:string,ringkas:string,tag:string,unduh:string,full:string,thumb:string}>
     */
    function instagi_galeri_daftar_tampil($galeri, array $daftar, $slug_fn = null)
    {
        $keluar = [];
        foreach ($daftar as $slug => $meta) {
            $berkas_slug = $slug_fn === null ? $slug : call_user_func($slug_fn, $slug);
            $full = instagi_galeri_web_url($galeri, $berkas_slug);
            if ($full === null) {
                continue; // gambar belum tersedia -> jangan tampilkan kartu kosong
            }
            $keluar[] = [
                'slug'     => $berkas_slug,
                'judul'    => $meta['judul'],
                'subjudul' => isset($meta['subjudul']) ? $meta['subjudul'] : '',
                'ringkas'  => $meta['ringkas'],
                'tag'      => $meta['tag'],
                'unduh'    => $meta['unduh'],
                'full'     => $full,
                'thumb'    => instagi_galeri_thumb_url($galeri, $berkas_slug),
            ];
        }
        return $keluar;
    }
}
