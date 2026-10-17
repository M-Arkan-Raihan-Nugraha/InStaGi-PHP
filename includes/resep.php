<?php
/**
 * Modul Kumpulan Resep InStaGi
 * -----------------------------------------------------------------------------
 * Sumber data untuk galeri "Kumpulan Resep" di halaman Informasi Gizi.
 *
 * Tiap entri adalah satu lembar resep bergambar (satu halaman berisi 7 resep)
 * beserta bahan, cara memasak, dan nilai gizinya.
 *
 * CARA MENAMBAH RESEP
 * -------------------
 * 1. Simpan gambar ke  assets/resep/<slug>.jpeg
 *    (slug = huruf kecil, boleh pakai tanda hubung, mis. "soto-nusantara")
 * 2. Tambahkan entri pada instagi_resep_list() dengan kunci slug tersebut.
 * 3. (Opsional, disarankan) Jalankan pembuat thumbnail:
 *        php includes/galeri_build_thumbs.php
 *
 * Berkas ini TIDAK menyentuh rumus perhitungan (includes/gizi.php).
 */

require_once __DIR__ . '/galeri_aset.php';

if (!function_exists('instagi_resep_list')) {
    /**
     * Daftar lembar resep, diurutkan sesuai urutan tampil yang diinginkan.
     *
     * @return array<string, array{judul:string,subjudul:string,ringkas:string,tag:string,unduh:string}>
     */
    function instagi_resep_list()
    {
        return [
            'menu-ayam' => [
                'judul'    => 'Resep Menu Ayam',
                'subjudul' => '7 olahan ayam rendah lemak untuk menu harian',
                'ringkas'  => 'Ayam panggang bumbu rempah, ayam kukus jahe & jamur, tumis paprika brokoli, ayam bumbu kuning, rica-rica, saus lemon, dan teriyaki rendah gula (±180–230 kkal, protein 23–28 g).',
                'tag'      => 'Lauk Hewani',
                'unduh'    => 'Resep-InStaGi-Menu-Ayam.jpeg',
            ],
            'menu-ikan' => [
                'judul'    => 'Resep Menu Ikan',
                'subjudul' => '7 olahan ikan & seafood kaya omega-3',
                'ringkas'  => 'Ikan bakar bumbu kuning, ikan kukus jahe & sayuran, ikan asam manis, pepes kemangi, cumi goreng tepung, udang saus tiram, dan ikan kuah santan sayuran (±190–240 kkal).',
                'tag'      => 'Lauk Hewani',
                'unduh'    => 'Resep-InStaGi-Menu-Ikan.jpeg',
            ],
            'sup-sayuran-mix-protein' => [
                'judul'    => 'Sup Sayuran Mix Protein',
                'subjudul' => '7 sup sayur dengan tambahan protein (2 porsi)',
                'ringkas'  => 'Sup ayam, ikan, tahu-tempe, daging, udang, telur, dan sup ayam-jamur sayuran (±180–280 kkal, protein 14–30 g per 2 porsi).',
                'tag'      => 'Sup',
                'unduh'    => 'Resep-InStaGi-Sup-Sayuran-Mix-Protein.jpeg',
            ],
            'krim-sup' => [
                'judul'    => 'Krim Sup Ayam',
                'subjudul' => '7 resep krim sup berbahan ayam, tekstur lembut',
                'ringkas'  => 'Krim sup ayam jagung, brokoli, jamur, labu, bayam, wortel, dan kembang kol (±210–250 kkal, protein 20–24 g per 2 porsi).',
                'tag'      => 'Sup',
                'unduh'    => 'Resep-InStaGi-Krim-Sup-Ayam.jpeg',
            ],
            'krim-sup-selain-ayam' => [
                'judul'    => 'Krim Sup Selain Ayam',
                'subjudul' => '7 krim sup dengan sumber protein lain',
                'ringkas'  => 'Krim sup ikan salmon, udang & jagung, daging sapi, tahu & sayuran, telur, kacang merah, serta kacang polong & jamur (±180–250 kkal) — pilihan bila ingin tanpa ayam.',
                'tag'      => 'Sup',
                'unduh'    => 'Resep-InStaGi-Krim-Sup-Selain-Ayam.jpeg',
            ],
            'menu-bubur' => [
                'judul'    => 'Resep Menu Bubur',
                'subjudul' => '7 resep bubur untuk semua usia, termasuk masa pemulihan',
                'ringkas'  => 'Bubur ayam, ikan, udang, daging, tahu, telur, dan tempe sayuran (±180–230 kkal per 2 porsi) — mudah dicerna untuk anak, lansia, dan pemulihan.',
                'tag'      => 'Makanan Pokok',
                'unduh'    => 'Resep-InStaGi-Menu-Bubur.jpeg',
            ],
            'bubur-bayi-kaya-protein' => [
                'judul'    => 'Bubur Bayi Kaya Protein',
                'subjudul' => '7 resep MPASI usia 6 bulan ke atas',
                'ringkas'  => 'Bubur ayam-wortel-brokoli, ikan kacang merah, telur bayam, daging kentang, udang jagung manis, tahu ayam sayuran, dan ikan salmon kentang (±170–200 kkal, protein 13–16 g) lengkap dengan usia pemberian.',
                'tag'      => 'MPASI',
                'unduh'    => 'Resep-InStaGi-Bubur-Bayi-Kaya-Protein.jpeg',
            ],
            'nasi-nusantara' => [
                'judul'    => 'Resep Nasi Nusantara',
                'subjudul' => '7 olahan nasi khas Indonesia',
                'ringkas'  => 'Nasi liwet ayam, nasi uduk ayam & telur, nasi kuning ikan, nasi bakar ayam kemangi, nasi timbel ayam, nasi goreng kampung, dan nasi megono (±390–430 kkal per porsi).',
                'tag'      => 'Nusantara',
                'unduh'    => 'Resep-InStaGi-Nasi-Nusantara.jpeg',
            ],
            'soto-nusantara' => [
                'judul'    => 'Resep Soto Nusantara',
                'subjudul' => '7 soto khas daerah yang kaya rempah',
                'ringkas'  => 'Soto ayam Lamongan, soto Betawi, soto Padang, soto Madura, soto Bandung, soto Makassar, dan soto Kudus (±300–420 kkal, protein 24–30 g per 2 porsi).',
                'tag'      => 'Nusantara',
                'unduh'    => 'Resep-InStaGi-Soto-Nusantara.jpeg',
            ],
            'sate-nusantara' => [
                'judul'    => 'Resep Sate Nusantara',
                'subjudul' => '7 sate khas Nusantara, dipanggang tanpa arang',
                'ringkas'  => 'Sate ayam Madura, sate lilit Bali, sate kambing Tegal, sate sapi Maranggi, sate Padang, sate lilit ikan Lombok, dan sate kelinci Jawa Tengah (±250–350 kkal) — memakai grill pan, oven, atau air fryer.',
                'tag'      => 'Nusantara',
                'unduh'    => 'Resep-InStaGi-Sate-Nusantara.jpeg',
            ],
            'pepes-khas-nusantara' => [
                'judul'    => 'Resep Pepes Khas Nusantara',
                'subjudul' => '7 pepes yang dikukus atau dipanggang, tanpa minyak berlebih',
                'ringkas'  => 'Pepes ikan kembung Sunda, pepes ayam kemangi, pepes tahu jamur, pepes udang daun singkong, pepes ikan teri Medan, pepes ikan patin Palembang, dan pepes tongkol kemangi (±250–320 kkal).',
                'tag'      => 'Nusantara',
                'unduh'    => 'Resep-InStaGi-Pepes-Khas-Nusantara.jpeg',
            ],
            'salad-sayur-mix-protein' => [
                'judul'    => 'Salad Sayur Mix Protein',
                'subjudul' => '7 salad sayur dengan campuran protein',
                'ringkas'  => 'Salad ayam panggang, salad tuna, telur & alpukat, ikan panggang, udang sayuran tropis, tahu & tempe, serta daging rendah lemak (±290–350 kkal) dengan dressing sehat.',
                'tag'      => 'Menu Sehat',
                'unduh'    => 'Resep-InStaGi-Salad-Sayur-Mix-Protein.jpeg',
            ],
            'menu-tinggi-zat-besi' => [
                'judul'    => 'Menu Tinggi Zat Besi',
                'subjudul' => '7 resep untuk membantu mencegah anemia',
                'ringkas'  => 'Daging sapi lada hitam, hati ayam masak kecap, ikan tongkol bumbu rica, tumis bayam daging, sayur kacang merah daging, tempe bayam saus tiram, dan telur balado daun kelor (2,5–6,5 mg zat besi per porsi).',
                'tag'      => 'Zat Besi',
                'unduh'    => 'Resep-InStaGi-Menu-Tinggi-Zat-Besi.jpeg',
            ],
            'gorengan-favorit' => [
                'judul'    => 'Gorengan Favorit',
                'subjudul' => '7 gorengan rumahan dengan cara yang lebih sehat',
                'ringkas'  => 'Bakwan sayuran, tahu isi, tempe mendoan, pisang goreng, cireng isi, ubi goreng, dan kroket (±90–150 kkal per buah) plus tips menggoreng lebih sehat.',
                'tag'      => 'Camilan',
                'unduh'    => 'Resep-InStaGi-Gorengan-Favorit.jpeg',
            ],
            'jus-buah-mix-sayuran' => [
                'judul'    => 'Jus Buah Mix Sayuran',
                'subjudul' => '7 resep jus untuk membantu memenuhi vitamin harian',
                'ringkas'  => 'Carrot orange, green fresh, pineapple spinach, tomato apple, mango carrot, watermelon cucumber, dan berry beet (±55–130 kkal per gelas) — tanpa gula tambahan, serat tetap terkonsumsi.',
                'tag'      => 'Minuman',
                'unduh'    => 'Resep-InStaGi-Jus-Buah-Mix-Sayuran.jpeg',
            ],
        ];
    }
}

if (!function_exists('instagi_resep_find')) {
    /** Nama berkas gambar resep, atau null bila belum tersedia. */
    function instagi_resep_find($slug)
    {
        return instagi_galeri_find('resep', $slug);
    }
}

if (!function_exists('instagi_resep_web_url')) {
    /** URL gambar resep ukuran penuh. */
    function instagi_resep_web_url($slug)
    {
        return instagi_galeri_web_url('resep', $slug);
    }
}

if (!function_exists('instagi_resep_thumb_url')) {
    /** URL thumbnail resep (cadangan: gambar ukuran penuh). */
    function instagi_resep_thumb_url($slug)
    {
        return instagi_galeri_thumb_url('resep', $slug);
    }
}

if (!function_exists('instagi_resep_daftar')) {
    /** Daftar resep siap tampil (hanya yang gambarnya ada). */
    function instagi_resep_daftar()
    {
        return instagi_galeri_daftar_tampil('resep', instagi_resep_list());
    }
}
