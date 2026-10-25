<?php
/**
 * Modul Tips Bugar InStaGi
 * -----------------------------------------------------------------------------
 * Sumber data untuk galeri "Tips Bugar" di halaman Informasi Gizi.
 *
 * Tiap entri adalah panduan/infografis kebugaran, peregangan, dan gaya hidup aktif.
 */

require_once __DIR__ . '/galeri_aset.php';

if (!function_exists('instagi_aktivitas_dir')) {
    /** Folder fisik penyimpanan gambar aktivitas fisik. */
    function instagi_aktivitas_dir()
    {
        return instagi_galeri_dir('aktivitas');
    }
}

if (!function_exists('instagi_aktivitas_list')) {
    /**
     * Daftar materi aktivitas fisik, diurutkan sesuai urutan tampil.
     *
     * @return array<string, array{judul:string,subjudul:string,ringkas:string,tag:string,unduh:string}>
     */
    function instagi_aktivitas_list()
    {
        return [
            'manfaat-berjalan-kaki-setiap-hari' => [
                'judul'    => 'Manfaat Berjalan Kaki Setiap Hari',
                'subjudul' => 'Aktivitas sederhana dengan sejuta manfaat kesehatan',
                'ringkas'  => 'Manfaat rutin jalan kaki harian: menjaga kebugaran jantung, membakar kalori, memperbaiki suasana hati (mood), dan memperkuat otot serta tulang.',
                'tag'      => 'Jalan Kaki',
                'unduh'    => 'Panduan-InStaGi-Manfaat-Berjalan-Kaki-Setiap-Hari.webp',
            ],
            'stretching-sederhana-setiap-hari' => [
                'judul'    => 'Stretching Sederhana Setiap Hari',
                'subjudul' => 'Peregangan mudah yang dapat dilakukan di mana saja',
                'ringkas'  => 'Gerakan peregangan praktis setiap hari untuk menjaga kelenturan tubuh, melancarkan peredaran darah, meredakan pegal, dan mengurangi kaku otot.',
                'tag'      => 'Peregangan',
                'unduh'    => 'Panduan-InStaGi-Stretching-Sederhana-Setiap-Hari.webp',
            ],
        ];
    }
}

if (!function_exists('instagi_aktivitas_find')) {
    /** Nama berkas gambar ukuran penuh, atau null bila belum ada. */
    function instagi_aktivitas_find($slug)
    {
        return instagi_galeri_find('aktivitas', $slug);
    }
}

if (!function_exists('instagi_aktivitas_thumb_find')) {
    /** Nama berkas thumbnail, atau null bila belum dibuat. */
    function instagi_aktivitas_thumb_find($slug)
    {
        return instagi_galeri_thumb_find('aktivitas', $slug);
    }
}

if (!function_exists('instagi_aktivitas_web_url')) {
    /** URL gambar ukuran penuh. */
    function instagi_aktivitas_web_url($slug)
    {
        return instagi_galeri_web_url('aktivitas', $slug);
    }
}

if (!function_exists('instagi_aktivitas_thumb_url')) {
    /** URL thumbnail (cadangan: gambar ukuran penuh). */
    function instagi_aktivitas_thumb_url($slug)
    {
        return instagi_galeri_thumb_url('aktivitas', $slug);
    }
}

if (!function_exists('instagi_aktivitas_daftar')) {
    /** Daftar aktivitas fisik siap tampil (hanya yang gambarnya ada). */
    function instagi_aktivitas_daftar()
    {
        return instagi_galeri_daftar_tampil('aktivitas', instagi_aktivitas_list());
    }
}
