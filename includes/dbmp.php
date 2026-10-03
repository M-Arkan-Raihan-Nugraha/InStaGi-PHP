<?php
/**
 * Modul DBMP (Daftar Bahan Makanan Penukar) InStaGi
 * -----------------------------------------------------------------------------
 * Sumber data untuk bagian DBMP di halaman hasil (result.php).
 *
 * DBMP disajikan sebagai SATU berkas PDF (gabungan 8 halaman golongan bahan
 * makanan) agar mudah dibaca, diunduh, dan dicetak.
 *
 * CARA MEMPERBARUI DBMP
 * ---------------------
 * Cara paling mudah: cukup GANTI berkas PDF di  assets/dbmp/  (nama:
 * dbmp-lengkap.pdf) dan gambar sampulnya (dbmp-cover.jpg). Tidak perlu
 * mengubah kode sama sekali.
 *
 * Bila ingin menyusun ulang PDF dari gambar per halaman:
 * 1. Simpan gambar tiap halaman di  assets/dbmp/sumber/  (mis. 0.jpeg … 7.jpeg).
 * 2. Gabungkan menjadi satu PDF bernama  assets/dbmp/dbmp-lengkap.pdf.
 * 3. Perbarui gambar sampul  assets/dbmp/dbmp-cover.jpg  (lebar ±600 px).
 *
 * Bila berkas PDF belum ada, bagian DBMP otomatis tidak ditampilkan
 * (tidak menimbulkan error).
 */

if (!function_exists('instagi_dbmp_dir')) {
    /** Folder fisik penyimpanan berkas DBMP. */
    function instagi_dbmp_dir()
    {
        $root = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__);
        return $root . '/assets/dbmp';
    }
}

if (!function_exists('instagi_dbmp_pdf')) {
    /**
     * URL PDF DBMP (relatif terhadap root aplikasi), atau null bila belum ada.
     *
     * @return string|null
     */
    function instagi_dbmp_pdf()
    {
        $dir = instagi_dbmp_dir();
        foreach (['dbmp-lengkap.pdf', 'dbmp.pdf', 'DBMP.pdf'] as $name) {
            if (is_file($dir . '/' . $name)) {
                return 'assets/dbmp/' . $name;
            }
        }
        return null;
    }
}

if (!function_exists('instagi_dbmp_cover')) {
    /**
     * URL gambar sampul DBMP, atau null bila tidak tersedia.
     *
     * @return string|null
     */
    function instagi_dbmp_cover()
    {
        $dir = instagi_dbmp_dir();
        foreach (['dbmp-cover.jpg', 'dbmp-cover.jpeg', 'dbmp-cover.png', 'dbmp-cover.webp'] as $name) {
            if (is_file($dir . '/' . $name)) {
                return 'assets/dbmp/' . $name;
            }
        }
        return null;
    }
}

if (!function_exists('instagi_dbmp_jumlah_halaman')) {
    /** Jumlah halaman DBMP yang ditampilkan pada lencana kartu (informasi saja). */
    function instagi_dbmp_jumlah_halaman()
    {
        return 8;
    }
}
