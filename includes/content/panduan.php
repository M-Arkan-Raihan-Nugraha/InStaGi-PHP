<?php
/**
 * Modul Panduan & Edukasi Gizi InStaGi
 * -----------------------------------------------------------------------------
 * Sumber data untuk galeri "Panduan & Edukasi Gizi" di halaman Informasi Gizi:
 * panduan diet (DM, PPOK, tinggi protein), daftar bahan makanan tertentu
 * (albumin, zat besi), keamanan pangan, serta edukasi pencernaan.
 *
 * CARA MENAMBAH PANDUAN
 * ---------------------
 * 1. Simpan gambar ke  assets/panduan/<slug>.jpeg
 * 2. Tambahkan entri pada instagi_panduan_list() dengan kunci slug tersebut.
 * 3. (Opsional, disarankan) Jalankan:  php includes/galeri_build_thumbs.php
 *
 * Berkas ini TIDAK menyentuh rumus perhitungan (includes/gizi.php).
 */

require_once __DIR__ . '/galeri_aset.php';

if (!function_exists('instagi_panduan_list')) {
    /**
     * Daftar panduan/edukasi, diurutkan dari yang paling dasar.
     *
     * @return array<string, array{judul:string,subjudul:string,ringkas:string,tag:string,unduh:string}>
     */
    function instagi_panduan_list()
    {
        return [
            'diet-diabetes-melitus-3j' => [
                'judul'    => 'Diet Diabetes Melitus (DM): Prinsip 3J',
                'subjudul' => 'Jadwal, Jumlah, dan Jenis makanan',
                'ringkas'  => 'Cara mengatur makan penderita diabetes lewat prinsip 3J, lengkap contoh jadwal makan, model piring makan, serta daftar makanan yang dianjurkan dan dibatasi.',
                'tag'      => 'Diet DM',
                'unduh'    => 'Panduan-InStaGi-Diet-Diabetes-Melitus-3J.webp',
            ],
            'diet-rendah-karbohidrat-tinggi-lemak-ppok' => [
                'judul'    => 'Diet Rendah Karbohidrat Tinggi Lemak untuk Pasien PPOK',
                'subjudul' => 'Mengurangi produksi CO₂ dan menjaga massa otot',
                'ringkas'  => 'Prinsip diet pada penyakit paru obstruktif kronik — karbohidrat maksimal 50%, lemak 30–40%, protein 15–20% — lengkap contoh menu sehari ±1.800 kkal dan makanan yang perlu dibatasi.',
                'tag'      => 'Diet PPOK',
                'unduh'    => 'Panduan-InStaGi-Diet-Rendah-Karbohidrat-Tinggi-Lemak-PPOK.webp',
            ],
            'diet-tinggi-protein' => [
                'judul'    => 'Diet Tinggi Protein',
                'subjudul' => 'Menjaga massa otot dan mendukung proses penyembuhan',
                'ringkas'  => 'Kebutuhan protein 1,0–1,5 g per kg berat badan, sumber protein hewani dan nabati, contoh menu sehari ±1.800 kkal (80–90 g protein), serta tips pelaksanaannya.',
                'tag'      => 'Diet',
                'unduh'    => 'Panduan-InStaGi-Diet-Tinggi-Protein.webp',
            ],
            'makanan-sumber-albumin' => [
                'judul'    => '16 Makanan Sumber Albumin',
                'subjudul' => 'Tinggi protein, bergizi dan mudah didapat',
                'ringkas'  => 'Daftar 16 makanan sumber protein untuk mendukung pembentukan albumin — dari ikan gabus dan tuna hingga kerang, telur, tempe, tahu, dan kedelai — beserta kadar protein dan zat besinya.',
                'tag'      => 'Protein',
                'unduh'    => 'Panduan-InStaGi-16-Makanan-Sumber-Albumin.webp',
            ],
            'makanan-tinggi-zat-besi' => [
                'judul'    => '15 Makanan Tinggi Zat Besi',
                'subjudul' => 'Cegah anemia, tubuh lebih sehat',
                'ringkas'  => 'Daftar 15 makanan tinggi zat besi dengan kadarnya per 100 g, tips agar penyerapannya optimal, hal yang perlu dihindari, dan contoh kombinasi menu sehari.',
                'tag'      => 'Zat Besi',
                'unduh'    => 'Panduan-InStaGi-15-Makanan-Tinggi-Zat-Besi.webp',
            ],
            'keamanan-pangan-rumah-tangga' => [
                'judul'    => 'Panduan Keamanan Pangan Tingkat Rumah Tangga',
                'subjudul' => 'Pangan aman, keluarga sehat',
                'ringkas'  => 'Tujuh langkah keamanan pangan di rumah — memilih bahan, mencuci tangan dan bahan, mencegah kontaminasi silang, memasak sampai matang, menyimpan, dan memanaskan kembali — plus 5 kunci keamanan pangan dan zona bahaya suhu 5–60 °C.',
                'tag'      => 'Keamanan Pangan',
                'unduh'    => 'Panduan-InStaGi-Keamanan-Pangan-Rumah-Tangga.webp',
            ],
            'prebiotik-dan-probiotik' => [
                'judul'    => 'Prebiotik dan Probiotik',
                'subjudul' => 'Duo sehat untuk usus yang lebih kuat',
                'ringkas'  => 'Perbedaan prebiotik (serat yang menjadi makanan bakteri baik) dan probiotik (bakteri baik hidup), contoh makanan masing-masing, cara mengombinasikannya (sinbiotik), serta tips konsumsinya.',
                'tag'      => 'Pencernaan',
                'unduh'    => 'Panduan-InStaGi-Prebiotik-dan-Probiotik.webp',
            ],
        ];
    }
}

if (!function_exists('instagi_panduan_find')) {
    /** Nama berkas gambar panduan, atau null bila belum tersedia. */
    function instagi_panduan_find($slug)
    {
        return instagi_galeri_find('panduan', $slug);
    }
}

if (!function_exists('instagi_panduan_web_url')) {
    /** URL gambar panduan ukuran penuh. */
    function instagi_panduan_web_url($slug)
    {
        return instagi_galeri_web_url('panduan', $slug);
    }
}

if (!function_exists('instagi_panduan_thumb_url')) {
    /** URL thumbnail panduan (cadangan: gambar ukuran penuh). */
    function instagi_panduan_thumb_url($slug)
    {
        return instagi_galeri_thumb_url('panduan', $slug);
    }
}

if (!function_exists('instagi_panduan_daftar')) {
    /** Daftar panduan siap tampil (hanya yang gambarnya ada). */
    function instagi_panduan_daftar()
    {
        return instagi_galeri_daftar_tampil('panduan', instagi_panduan_list());
    }
}
