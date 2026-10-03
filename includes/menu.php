<?php
/**
 * Modul Contoh Menu InStaGi
 * -----------------------------------------------------------------------------
 * Sumber data untuk galeri "Contoh Menu Sesuai Kebutuhan Kalori" di result.php.
 *
 * Tiap menu adalah satu gambar leaflet berisi contoh menu sehari
 * (3 kali makan utama + 2 kali selingan) dengan total kalori tertentu.
 * Halaman hasil memakai daftar ini untuk merekomendasikan menu yang PALING
 * DEKAT dengan estimasi kebutuhan kalori pengguna.
 *
 * CARA MENGGANTI / MENAMBAH MENU
 * ------------------------------
 * 1. Simpan gambar ke folder  assets/menu/
 *    Nama file (tanpa ekstensi) memakai pola "<kkal>kkal", contoh:
 *    assets/menu/1800kkal.jpeg  -> kunci 1800
 * 2. Tambahkan / ubah entri pada instagi_menu_list() dengan kunci kkal tersebut.
 * 3. (Opsional, disarankan) Jalankan pembuat thumbnail agar galeri ringan:
 *        php includes/menu_build_thumbs.php
 *
 * Ekstensi yang didukung: .jpeg .jpg .png .webp
 * Bila thumbnail belum dibuat, galeri otomatis memakai gambar ukuran penuh.
 */

if (!function_exists('instagi_menu_dir')) {
    /** Folder fisik penyimpanan gambar contoh menu. */
    function instagi_menu_dir()
    {
        $root = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__);
        return $root . '/assets/menu';
    }
}

if (!function_exists('instagi_menu_list')) {
    /**
     * Daftar contoh menu, diurutkan naik berdasarkan total kalori.
     * Kunci array = total kalori (kkal) yang dipakai untuk mencocokkan
     * dengan kebutuhan kalori pengguna.
     *
     * @return array<int, array{judul:string,subjudul:string,ringkas:string,tag:string,unduh:string}>
     */
    function instagi_menu_list()
    {
        return [
            1400 => [
                'judul'    => 'Contoh Menu 1.400 kkal',
                'subjudul' => '3 kali makan utama + 2 kali selingan',
                'ringkas'  => 'Contoh menu sehari ±1.400 kkal lengkap dengan porsi ukuran rumah tangga (URT) dan contoh bahan penukar.',
                'tag'      => '±1.400 kkal',
                'unduh'    => 'Leaflet-InStaGi-Contoh-Menu-1400-kkal.jpeg',
            ],
            1500 => [
                'judul'    => 'Contoh Menu 1.500 kkal',
                'subjudul' => '3 kali makan utama + 2 kali selingan',
                'ringkas'  => 'Contoh menu sehari ±1.500 kkal dengan porsi berimbang, pilihan buah, serta lauk rendah lemak.',
                'tag'      => '±1.500 kkal',
                'unduh'    => 'Leaflet-InStaGi-Contoh-Menu-1500-kkal.jpeg',
            ],
            1700 => [
                'judul'    => 'Contoh Menu 1.700 kkal',
                'subjudul' => '3 kali makan utama + 2 kali selingan',
                'ringkas'  => 'Contoh menu sehari ±1.700 kkal untuk kebutuhan sedang, lengkap distribusi energi tiap waktu makan.',
                'tag'      => '±1.700 kkal',
                'unduh'    => 'Leaflet-InStaGi-Contoh-Menu-1700-kkal.jpeg',
            ],
            2000 => [
                'judul'    => 'Contoh Menu 2.000 kkal',
                'subjudul' => '3 kali makan utama + 2 kali selingan',
                'ringkas'  => 'Contoh menu sehari ±2.000 kkal dengan porsi lebih besar serta variasi lauk hewani dan nabati.',
                'tag'      => '±2.000 kkal',
                'unduh'    => 'Leaflet-InStaGi-Contoh-Menu-2000-kkal.jpeg',
            ],
            2400 => [
                'judul'    => 'Contoh Menu 2.400 kkal',
                'subjudul' => '3 kali makan utama + 2 kali selingan',
                'ringkas'  => 'Contoh menu sehari ±2.400 kkal untuk kebutuhan tinggi, cocok bagi aktivitas berat atau masa pemulihan.',
                'tag'      => '±2.400 kkal',
                'unduh'    => 'Leaflet-InStaGi-Contoh-Menu-2400-kkal.jpeg',
            ],
        ];
    }
}

if (!function_exists('instagi_menu_slug')) {
    /** Ubah kunci kalori menjadi nama berkas (slug) tanpa ekstensi, mis. 1400 -> "1400kkal". */
    function instagi_menu_slug($kkal)
    {
        return (int) $kkal . 'kkal';
    }
}

if (!function_exists('instagi_menu_find')) {
    /**
     * Cari file gambar contoh menu berdasarkan total kalori.
     *
     * @return string|null Nama file (mis. "1400kkal.jpeg") atau null bila tidak ada.
     */
    function instagi_menu_find($kkal)
    {
        $slug = instagi_menu_slug($kkal);
        // Lindungi dari path traversal.
        if (!preg_match('/^[A-Za-z0-9_\-]+$/', $slug)) {
            return null;
        }

        $dir = instagi_menu_dir();
        foreach (['jpeg', 'jpg', 'png', 'webp'] as $ext) {
            $file = $dir . '/' . $slug . '.' . $ext;
            if (is_file($file)) {
                return $slug . '.' . $ext;
            }
        }
        return null;
    }
}

if (!function_exists('instagi_menu_thumb_find')) {
    /**
     * Cari file thumbnail contoh menu. Bila belum ada, kembalikan null
     * (pemanggil akan memakai gambar ukuran penuh sebagai gantinya).
     *
     * @return string|null Nama file di dalam subfolder thumbs/, mis. "1400kkal.jpg"
     */
    function instagi_menu_thumb_find($kkal)
    {
        $slug = instagi_menu_slug($kkal);
        if (!preg_match('/^[A-Za-z0-9_\-]+$/', $slug)) {
            return null;
        }

        $dir = instagi_menu_dir() . '/thumbs';
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            if (is_file($dir . '/' . $slug . '.' . $ext)) {
                return $slug . '.' . $ext;
            }
        }
        return null;
    }
}

if (!function_exists('instagi_menu_web_url')) {
    /**
     * URL (relatif terhadap root aplikasi) untuk gambar ukuran penuh.
     *
     * @return string|null
     */
    function instagi_menu_web_url($kkal)
    {
        $file = instagi_menu_find($kkal);
        return $file === null ? null : 'assets/menu/' . $file;
    }
}

if (!function_exists('instagi_menu_thumb_url')) {
    /**
     * URL thumbnail; bila thumbnail tidak tersedia, pakai gambar ukuran penuh.
     *
     * @return string|null
     */
    function instagi_menu_thumb_url($kkal)
    {
        $thumb = instagi_menu_thumb_find($kkal);
        if ($thumb !== null) {
            return 'assets/menu/thumbs/' . $thumb;
        }
        return instagi_menu_web_url($kkal);
    }
}

if (!function_exists('instagi_menu_terdekat')) {
    /**
     * Pilih total kalori menu yang paling dekat dengan target kebutuhan kalori.
     * Bila jaraknya sama, menu dengan kalori lebih kecil yang dipilih.
     *
     * @param  float|int $target_kkal Kebutuhan kalori pengguna (kkal/hari).
     * @return int|null  Kunci kalori menu terdekat, atau null bila daftar kosong.
     */
    function instagi_menu_terdekat($target_kkal)
    {
        $list = instagi_menu_list();
        if (empty($list)) {
            return null;
        }

        $target = (float) $target_kkal;
        $best = null;
        $best_diff = null;

        foreach (array_keys($list) as $kkal) {
            $diff = abs($kkal - $target);
            if ($best_diff === null || $diff < $best_diff) {
                $best_diff = $diff;
                $best = $kkal;
            }
        }

        return $best;
    }
}
