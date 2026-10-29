<?php
/**
 * Modul Data Bestie InStaGi (Mitra, Sponsor, & Etalase Flyer)
 * -----------------------------------------------------------------------------
 * Menyimpan data flyer promosi mitra InStaGi dalam berbagai kategori:
 * Travel & Hotel, Land & Property, Kuliner, Minimarket, dan Layanan Kesehatan.
 */

if (!function_exists('instagi_bestie_dir')) {
    /** Folder fisik penyimpanan flyer dan aset Bestie. */
    function instagi_bestie_dir()
    {
        $root = defined('APP_ROOT') ? APP_ROOT : dirname(dirname(__DIR__));
        return $root . '/assets/bestie';
    }
}

if (!function_exists('instagi_bestie_kategori')) {
    /**
     * Daftar kategori etalase Bestie InStaGi.
     *
     * @return array<string, string>
     */
    function instagi_bestie_kategori()
    {
        return [
            'all'        => 'Semua',
            'travel'     => 'Travel & Hotel',
            'property'   => 'Land & Property',
            'kuliner'    => 'Kuliner',
            'minimarket' => 'Minimarket',
            'kesehatan'  => 'Layanan Kesehatan',
        ];
    }
}

if (!function_exists('instagi_bestie_daftar')) {
    /**
     * Mengembalikan seluruh daftar flyer Bestie yang aktif.
     *
     * @return array<int, array{
     *   id: string,
     *   kategori: string,
     *   kategori_label: string,
     *   judul: string,
     *   subjudul: string,
     *   deskripsi: string,
     *   lokasi: string,
     *   harga: string,
     *   wa: string,
     *   wa_text: string,
     *   gambar: string
     * }>
     */
    function instagi_bestie_daftar()
    {
        return [
            [
                'id'             => 'spirit-holiday-bali-lombok',
                'kategori'       => 'travel',
                'kategori_label' => 'Travel & Hotel',
                'judul'          => 'Open Trip Bali - Lombok',
                'subjudul'       => 'Spirit Holiday Indonesia',
                'deskripsi'      => 'Liburan Akhir Tahun 2026 - Tahun Baru 2027 (29 Des \'26 - 4 Jan \'27). Bus Pariwisata Executive, Hotel Bintang 3 di Bali & Lombok, 18x makan, tiket masuk wisata & kapal ferry PP.',
                'lokasi'         => 'Bali & Lombok (GWK, Bedugul, Senggigi, Gili Gede)',
                'harga'          => 'Mulai Rp 3,65 Juta / pax',
                'wa'             => '6281214364730',
                'wa_text'        => 'Halo Spirit Holiday Indonesia, saya tertarik dengan Open Trip Overland Bali - Lombok yang ada di Bestie InStaGi. Mohon info detail pendaftaran.',
                'gambar'         => 'spirit-holiday-bali-lombok.jpeg',
            ],
            [
                'id'             => 'dago-wisata-bobotoh-singapore',
                'kategori'       => 'travel',
                'kategori_label' => 'Travel & Hotel',
                'judul'          => 'Bobotoh Away Days Singapore',
                'subjudul'       => 'PT. Dago Wisata Internasional',
                'deskripsi'      => 'Liburan 2 negara sambil dukung langsung PERSIB vs Lion City Sailors FC (09 - 11 Des 2026). Bonus City Tour KL & Singapore, tiket pesawat PP Citilink/TransNusa, hotel, dan free syal + jersey exclusive.',
                'lokasi'         => 'Kuala Lumpur & Singapore',
                'harga'          => 'Promo mulai Rp 6,99 Juta',
                'wa'             => '62811222056',
                'wa_text'        => 'Halo Dago Wisata, saya ingin mendaftar promo Bobotoh Away Days Goes to Singapore via Bestie InStaGi. Mohon info ketersediaan seat.',
                'gambar'         => 'dago-wisata-bobotoh-singapore.jpeg',
            ],
            [
                'id'             => 'rumah-tanah-ciranjang-cianjur',
                'kategori'       => 'property',
                'kategori_label' => 'Land & Property',
                'judul'          => 'Dijual Rumah & Tanah Ciranjang',
                'subjudul'       => 'Lokasi Strategis Samping Puskesmas',
                'deskripsi'      => 'Lahan luas & bangunan kokoh siap huni (LT 329 m², LB 250 m²). Dilengkapi 4 Kamar Tidur, 3 Kamar Mandi, Garasi + Carport, SHM. Sangat cocok untuk hunian keluarga besar atau tempat usaha.',
                'lokasi'         => 'Jl. Rumahsakit, Ciranjang, Cianjur, Jawa Barat',
                'harga'          => 'Rp 950 Juta (Nego)',
                'wa'             => '6281224948388',
                'wa_text'        => 'Halo, saya tertarik dengan informasi Dijual Rumah & Tanah Ciranjang Cianjur di Bestie InStaGi. Mohon info jadwal survei lokasi.',
                'gambar'         => 'rumah-tanah-ciranjang-cianjur.jpeg',
            ],
            [
                'id'             => 'tanah-sawah-cigaok-sukabumi',
                'kategori'       => 'property',
                'kategori_label' => 'Land & Property',
                'judul'          => 'Dijual Tanah Sawah & Daratan 3 Ha',
                'subjudul'       => 'Tanah Subur & Sumber Mata Air Mandiri',
                'deskripsi'      => 'Lahan seluas 3 Hektar gabungan sawah produktif dan daratan berbukit asri. Memiliki sumber mata air sendiri, tanah sangat subur, cocok untuk agrowisata, perkebunan, maupun investasi jangka panjang.',
                'lokasi'         => 'Kp. Cigaok Desa Girijaya, Warung Kiara, Sukabumi',
                'harga'          => 'Harga Spesial (Nego)',
                'wa'             => '6281224948388',
                'wa_text'        => 'Halo, saya tertarik dengan informasi Dijual Tanah Sawah & Daratan 3 Ha Sukabumi di Bestie InStaGi. Mohon rincian legalitas dan harga penawarannya.',
                'gambar'         => 'tanah-sawah-cigaok-sukabumi.jpeg',
            ],
            [
                'id'             => 'tanah-kolam-sawah-cugenang-cianjur',
                'kategori'       => 'property',
                'kategori_label' => 'Land & Property',
                'judul'          => 'Tanah Daratan, Kolam & Sawah',
                'subjudul'       => 'Luas 1.600 m² Bersertifikat SHM',
                'deskripsi'      => 'Properti serbaguna terdiri dari daratan, kolam ikan air mengalir, dan sawah produktif. Memiliki sumber mata air mandiri, udara sejuk pegunungan, serta akses jalan yang baik.',
                'lokasi'         => 'Desa Talaga, Kec. Cugenang, Kab. Cianjur',
                'harga'          => 'Rp 1,1 Juta / m² (Nego)',
                'wa'             => '6281224948388',
                'wa_text'        => 'Halo, saya melihat info Dijual Tanah Kolam & Sawah di Cugenang Cianjur melalui Bestie InStaGi. Boleh minta detail lokasinya?',
                'gambar'         => 'tanah-kolam-sawah-cugenang-cianjur.jpeg',
            ],
            [
                'id'             => 'rumah-tanah-jagapura-brebes',
                'kategori'       => 'property',
                'kategori_label' => 'Land & Property',
                'judul'          => 'Rumah & Tanah Luas 1.249 m²',
                'subjudul'       => 'SHM Siap Huni Dekat Kawasan Industri',
                'deskripsi'      => 'Rumah siap huni dengan halaman sangat luas (1.249 m²). Status Sertifikat Hak Milik (SHM), lingkungan teduh asri, lokasi strategis dekat area industri perdagangan dan jasa Kersana.',
                'lokasi'         => 'Desa Jagapura, Kec. Kersana, Kab. Brebes, Jateng',
                'harga'          => 'Harga Nego',
                'wa'             => '6281224948388',
                'wa_text'        => 'Halo, saya tertarik dengan iklan Rumah & Tanah 1.249 m² di Kersana Brebes yang terpasang di Bestie InStaGi. Mohon informasinya.',
                'gambar'         => 'rumah-tanah-jagapura-brebes.jpeg',
            ],
            [
                'id'             => 'dapur-rifka-kuliner',
                'kategori'       => 'kuliner',
                'kategori_label' => 'Kuliner',
                'judul'          => 'Dapur Rifka - Makanan & Minuman',
                'subjudul'       => 'Masakan Rumahan Rasa Istimewa',
                'deskripsi'      => 'Sedia aneka minuman segar botol 250ml (Kopi, Alpukat Kocok, Lemon Sereh, Gogoma Ubi Ungu) dan masakan rumahan favorit: Gabin Tape, Tape Ketan Hitam, Seblak Kering, Rendang Jengkol, Suir Tongkol, Sambel Goreng Kentang.',
                'lokasi'         => 'Ciumbuleuit, Bandung (Takeaway Only)',
                'harga'          => 'Mulai Rp 10.000',
                'wa'             => '6282318974678',
                'wa_text'        => 'Halo Dapur Rifka, saya ingin pesan makanan/minuman rumahan dari etalase Bestie InStaGi. Mohon daftar menu hari ini.',
                'gambar'         => 'dapur-rifka-kuliner.jpeg',
            ],
            [
                'id'             => 'marwa-fresh-markisa',
                'kategori'       => 'kuliner',
                'kategori_label' => 'Kuliner',
                'judul'          => 'Marwa Fresh - Jus Markisa Asli',
                'subjudul'       => 'Homegrown Passion Fruit Drink',
                'deskripsi'      => 'Minuman sari buah markisa asli hasil panen kebun rumahan. Segar alami, kaya vitamin C, tanpa bahan pengawet, cocok dinikmati dingin saat santai bersama keluarga.',
                'lokasi'         => 'Bandung & Sekitarnya',
                'harga'          => 'Segar & Alami',
                'wa'             => '6281224948388',
                'wa_text'        => 'Halo Marwa Fresh, saya tertarik memesan Homegrown Passion Fruit Drink yang terpasang di Bestie InStaGi. Mohon info harga & ongkir.',
                'gambar'         => 'marwa-fresh-markisa.jpeg',
            ],
            [
                'id'             => 'dapur-ummi-frozen-risoles',
                'kategori'       => 'kuliner',
                'kategori_label' => 'Kuliner',
                'judul'          => 'Dapur Ummi - Frozen Risoles',
                'subjudul'       => 'Kriuk di Luar, Lembut di Dalam',
                'deskripsi'      => 'Risoles beku praktis siap goreng kapan saja (isi 5 & 10 pcs). Pilihan Varian Savory (Sayur, Rogout Ayam, Mayo, Mushroom, Beef Bolognese, Ayam Pedas) dan Sweet (Choco Cheese, Matcha, Jasuke, Ubi).',
                'lokasi'         => 'Jl. Cibadak, Bandung',
                'harga'          => 'Mulai Rp 10.000',
                'wa'             => '6289602751277',
                'wa_text'        => 'Halo Dapur Ummi, saya tertarik memesan Frozen Risoles yang saya lihat di Bestie InStaGi. Varian apa saja yang ready stock?',
                'gambar'         => 'dapur-ummi-frozen-risoles.jpeg',
            ],
            [
                'id'             => 'pd-puspita-minimarket',
                'kategori'       => 'minimarket',
                'kategori_label' => 'Minimarket',
                'judul'          => 'PD Puspita - Kebutuhan Sehari-hari',
                'subjudul'       => 'Toko Sembako & Minimarket Lengkap',
                'deskripsi'      => 'Menyediakan aneka ragam kebutuhan pokok harian, sembako berkualitas, makanan ringan, aneka minuman kemasan, serta perlengkapan dapur dan rumah tangga secara grosir maupun eceran.',
                'lokasi'         => 'Baros - Arjasari, Kab. Bandung',
                'harga'          => 'Harga Grosir & Eceran Murah',
                'wa'             => '6287718604058',
                'wa_text'        => 'Halo PD Puspita, saya menemukan toko Anda melalui etalase Bestie InStaGi. Ingin menanyakan info pemesanan sembako/barang kebutuhan harian.',
                'gambar'         => 'pd-puspita-minimarket.jpeg',
            ],
            [
                'id'             => 'klinik-sunat-hd',
                'kategori'       => 'kesehatan',
                'kategori_label' => 'Layanan Kesehatan',
                'judul'          => 'Klinik Sunat HD Care Center',
                'subjudul'       => 'Sunat Modern & Profesional 5.G Plus',
                'deskripsi'      => 'Solusi sunat nyaman untuk si kecil dengan metode mutakhir 5.G Plus: Tanpa suntik, tanpa jahit, tanpa perban, tanpa kontrol berbelit, dan minim trauma. Dapatkan bonus gratis mainan Mobil Remote Control.',
                'lokasi'         => 'Cipagalo, Kec. Bojongsoang, Kab. Bandung',
                'harga'          => 'Paket Lengkap & Nyaman',
                'wa'             => '6281320469003',
                'wa_text'        => 'Halo Klinik Sunat HD, saya ingin berkonsultasi mengenai layanan sunat modern 5.G Plus yang tertera di Bestie InStaGi. Mohon info biaya dan reservasinya.',
                'gambar'         => 'klinik-sunat-hd.jpeg',
            ],
        ];
    }
}

if (!function_exists('instagi_bestie_list')) {
    /**
     * Mengembalikan daftar flyer berindeks slug/id (kompatibel dengan galeri_build_thumbs).
     *
     * @return array<string, array<string, mixed>>
     */
    function instagi_bestie_list()
    {
        $list = [];
        foreach (instagi_bestie_daftar() as $item) {
            $list[$item['id']] = $item;
        }
        return $list;
    }
}

