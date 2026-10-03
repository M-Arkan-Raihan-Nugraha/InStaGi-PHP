<?php
/**
 * Modul Leaflet Informasi Gizi InStaGi
 * -----------------------------------------------------------------------------
 * Sumber data untuk galeri leaflet di halaman hasil (result.php).
 *
 * CARA MENGGANTI / MENAMBAH LEAFLET
 * ---------------------------------
 * 1. Simpan gambar ke folder  assets/leaflet/
 *    Nama file (tanpa ekstensi) = "slug" yang dipakai di daftar di bawah.
 *    Contoh: assets/leaflet/sumber_protein.jpeg  -> slug 'sumber_protein'
 * 2. Tambahkan / ubah entri pada instagi_leaflet_list() dengan slug tersebut.
 * 3. (Opsional, disarankan) Jalankan pembuat thumbnail agar galeri ringan:
 *        php includes/leaflet_build_thumbs.php
 *
 * Ekstensi yang didukung: .jpeg .jpg .png .webp
 * Bila thumbnail belum dibuat, galeri otomatis memakai gambar ukuran penuh.
 */

if (!function_exists('instagi_leaflet_dir')) {
    /** Folder fisik penyimpanan leaflet. */
    function instagi_leaflet_dir()
    {
        $root = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__);
        return $root . '/assets/leaflet';
    }
}

if (!function_exists('instagi_leaflet_list')) {
    /**
     * Daftar leaflet yang ditampilkan (urut sesuai tampil di galeri).
     *
     * @return array<string, array{judul:string,subjudul:string,ringkas:string,tag:string,unduh:string}>
     */
    function instagi_leaflet_list()
    {
        return [
            'gizi_seimbang' => [
                'judul'    => 'Gizi Seimbang',
                'subjudul' => 'Panduan pola makan sehat untuk hidup lebih sehat',
                'ringkas'  => 'Prinsip gizi seimbang, Tumpeng Gizi Seimbang, Piring Makanku, dan contoh menu sehari (±1800 kkal).',
                'tag'      => 'Dasar',
                'unduh'    => 'Leaflet-InStaGi-Gizi-Seimbang.jpeg',
            ],
            'sumber_karbohidrat' => [
                'judul'    => 'Sumber Karbohidrat Pangan Lokal',
                'subjudul' => 'Pilihan makanan sumber energi untuk menu sehari-hari',
                'ringkas'  => 'Pilihan sumber energi dari serealia, umbi-umbian, dan buah, lengkap dengan kandungan energinya.',
                'tag'      => 'Karbohidrat',
                'unduh'    => 'Leaflet-InStaGi-Sumber-Karbohidrat.jpeg',
            ],
            'sumber_protein' => [
                'judul'    => 'Sumber Protein Pangan Lokal',
                'subjudul' => 'Pilihan lauk hewani dan nabati untuk menu gizi seimbang',
                'ringkas'  => 'Pilihan lauk hewani dan nabati, contoh porsi sajian, serta pesan gizi seimbang.',
                'tag'      => 'Protein',
                'unduh'    => 'Leaflet-InStaGi-Sumber-Protein.jpeg',
            ],
            'sumber_lemak' => [
                'judul'    => 'Kandungan Lemak & Kolesterol',
                'subjudul' => 'Per 100 gram bahan makanan',
                'ringkas'  => 'Daftar makanan menurut kadar lemak total dan kolesterol, plus tips mengurangi lemak jahat.',
                'tag'      => 'Lemak',
                'unduh'    => 'Leaflet-InStaGi-Lemak-dan-Kolesterol.jpeg',
            ],
            'sumber_vitamin' => [
                'judul'    => 'Sumber Vitamin & Serat',
                'subjudul' => 'Buah-buahan dan sayuran pangan lokal Indonesia',
                'ringkas'  => 'Buah dan sayur lokal kaya vitamin, mineral, dan serat beserta anjuran konsumsi harian.',
                'tag'      => 'Vitamin & Serat',
                'unduh'    => 'Leaflet-InStaGi-Vitamin-dan-Serat.jpeg',
            ],
            'sumber_natrium' => [
                'judul'    => 'Kandungan Natrium (Garam)',
                'subjudul' => 'mg natrium per 100 gram bahan makanan',
                'ringkas'  => 'Kadar natrium pada makanan, sumber natrium tersembunyi, dan batas konsumsi harian WHO.',
                'tag'      => 'Batasi',
                'unduh'    => 'Leaflet-InStaGi-Natrium.jpeg',
            ],
            'sumber_gula' => [
                'judul'    => 'Gula & Indeks Glikemik',
                'subjudul' => 'Kandungan gula dan nilai IG makanan',
                'ringkas'  => 'Kadar gula dan nilai indeks glikemik makanan, serta contoh minuman dan olahan tinggi gula.',
                'tag'      => 'Batasi',
                'unduh'    => 'Leaflet-InStaGi-Gula-dan-Indeks-Glikemik.jpeg',
            ],
            'sumber_purin' => [
                'judul'    => 'Kandungan Purin (Asam Urat)',
                'subjudul' => 'mg purin per 100 gram bahan makanan',
                'ringkas'  => 'Daftar makanan menurut kadar purin, mitos vs fakta, dan tips pencegahan asam urat.',
                'tag'      => 'Batasi',
                'unduh'    => 'Leaflet-InStaGi-Purin.jpeg',
            ],
        ];
    }
}

if (!function_exists('instagi_leaflet_find')) {
    /**
     * Cari file gambar leaflet berdasarkan slug (tanpa ekstensi).
     *
     * @return string|null Nama file (mis. "sumber_protein.jpeg") atau null bila tidak ada.
     */
    function instagi_leaflet_find($slug)
    {
        // Lindungi dari path traversal.
        if (!preg_match('/^[A-Za-z0-9_\-]+$/', (string) $slug)) {
            return null;
        }

        $dir = instagi_leaflet_dir();
        foreach (['jpeg', 'jpg', 'png', 'webp'] as $ext) {
            $file = $dir . '/' . $slug . '.' . $ext;
            if (is_file($file)) {
                return $slug . '.' . $ext;
            }
        }
        return null;
    }
}

if (!function_exists('instagi_leaflet_thumb_find')) {
    /**
     * Cari file thumbnail leaflet. Bila belum ada, kembalikan null
     * (pemanggil akan memakai gambar ukuran penuh sebagai gantinya).
     *
     * @return string|null Nama file di dalam subfolder thumbs/, mis. "sumber_protein.jpg"
     */
    function instagi_leaflet_thumb_find($slug)
    {
        if (!preg_match('/^[A-Za-z0-9_\-]+$/', (string) $slug)) {
            return null;
        }

        $dir = instagi_leaflet_dir() . '/thumbs';
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            if (is_file($dir . '/' . $slug . '.' . $ext)) {
                return $slug . '.' . $ext;
            }
        }
        return null;
    }
}

if (!function_exists('instagi_leaflet_web_url')) {
    /**
     * URL (relatif terhadap root aplikasi) untuk gambar ukuran penuh.
     *
     * @return string|null
     */
    function instagi_leaflet_web_url($slug)
    {
        $file = instagi_leaflet_find($slug);
        return $file === null ? null : 'assets/leaflet/' . $file;
    }
}

if (!function_exists('instagi_leaflet_thumb_url')) {
    /**
     * URL thumbnail; bila thumbnail tidak tersedia, pakai gambar ukuran penuh.
     *
     * @return string|null
     */
    function instagi_leaflet_thumb_url($slug)
    {
        $thumb = instagi_leaflet_thumb_find($slug);
        if ($thumb !== null) {
            return 'assets/leaflet/thumbs/' . $thumb;
        }
        return instagi_leaflet_web_url($slug);
    }
}
