<?php
/**
 * Modul Tips Bugar InStaGi
 * -----------------------------------------------------------------------------
 * Sumber data untuk galeri "Tips Bugar" di halaman Informasi Gizi.
 *
 * Tiap entri adalah panduan/infografis kebugaran, peregangan, dan gaya hidup aktif.
 */

require_once __DIR__ . '/galeri_aset.php';

if (!function_exists('instagi_tips_bugar_dir')) {
    /** Folder fisik penyimpanan gambar tips bugar. */
    function instagi_tips_bugar_dir()
    {
        return instagi_galeri_dir('tips-bugar');
    }
}

if (!function_exists('instagi_tips_bugar_list')) {
    /**
     * Daftar materi tips bugar, diurutkan sesuai urutan tampil.
     *
     * @return array<string, array{judul:string,subjudul:string,ringkas:string,tag:string,unduh:string}>
     */
    function instagi_tips_bugar_list()
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

if (!function_exists('instagi_tips_bugar_find')) {
    /** Nama berkas gambar ukuran penuh, atau null bila belum ada. */
    function instagi_tips_bugar_find($slug)
    {
        return instagi_galeri_find('tips-bugar', $slug);
    }
}

if (!function_exists('instagi_tips_bugar_thumb_find')) {
    /** Nama berkas thumbnail, atau null bila belum dibuat. */
    function instagi_tips_bugar_thumb_find($slug)
    {
        return instagi_galeri_thumb_find('tips-bugar', $slug);
    }
}

if (!function_exists('instagi_tips_bugar_web_url')) {
    /** URL gambar ukuran penuh. */
    function instagi_tips_bugar_web_url($slug)
    {
        return instagi_galeri_web_url('tips-bugar', $slug);
    }
}

if (!function_exists('instagi_tips_bugar_thumb_url')) {
    /** URL thumbnail (cadangan: gambar ukuran penuh). */
    function instagi_tips_bugar_thumb_url($slug)
    {
        return instagi_galeri_thumb_url('tips-bugar', $slug);
    }
}

if (!function_exists('instagi_tips_bugar_daftar')) {
    /** Daftar tips bugar siap tampil (hanya yang gambarnya ada). */
    function instagi_tips_bugar_daftar()
    {
        return instagi_galeri_daftar_tampil('tips-bugar', instagi_tips_bugar_list());
    }
}

// --- Alias untuk kompatibilitas mundur ---
if (!function_exists('instagi_aktivitas_dir')) {
    function instagi_aktivitas_dir() { return instagi_tips_bugar_dir(); }
}
if (!function_exists('instagi_aktivitas_list')) {
    function instagi_aktivitas_list() { return instagi_tips_bugar_list(); }
}
if (!function_exists('instagi_aktivitas_find')) {
    function instagi_aktivitas_find($slug) { return instagi_tips_bugar_find($slug); }
}
if (!function_exists('instagi_aktivitas_thumb_find')) {
    function instagi_aktivitas_thumb_find($slug) { return instagi_tips_bugar_thumb_find($slug); }
}
if (!function_exists('instagi_aktivitas_web_url')) {
    function instagi_aktivitas_web_url($slug) { return instagi_tips_bugar_web_url($slug); }
}
if (!function_exists('instagi_aktivitas_thumb_url')) {
    function instagi_aktivitas_thumb_url($slug) { return instagi_tips_bugar_thumb_url($slug); }
}
if (!function_exists('instagi_aktivitas_daftar')) {
    function instagi_aktivitas_daftar() { return instagi_tips_bugar_daftar(); }
}
