<?php
/**
 * Modul Validasi Input InStaGi (SATU SUMBER KEBENARAN)
 * -----------------------------------------------------------------------------
 * Sebelumnya aturan validasi ditulis TERPISAH: lengkap di imt.php (form publik),
 * tetapi hampir tidak ada di api/update_record.php (jalur edit admin). Akibatnya:
 *   - `aktivitas` & `jenis_kelamin` tidak diperiksa di server — nilai apa pun
 *     lolos (mis. aktivitas 999 -> kalori tidak masuk akal; jenis kelamin salah
 *     tulis otomatis dianggap Perempuan oleh rumus);
 *   - No. HP yang tidak bisa dipakai untuk MASUK ke halaman Monitoring tetap
 *     diterima (mis. "81234567890" tanpa awalan 0), sehingga akun responden
 *     terbentuk tetapi tidak pernah bisa dipakai login;
 *   - `tanggal` diambil apa adanya dari POST. Kolom ber-atribut `readonly`
 *     hanya pengaman di peramban, bukan di server — nilainya bisa dipalsukan
 *     atau kosong dan membuat penyimpanan gagal.
 *
 * Modul ini dipakai KEDUA jalur tersebut supaya aturannya selalu sama
 * (prinsip yang sama dengan includes/gizi.php untuk perhitungan).
 *
 * >>> CATATAN PENTING: file ini TIDAK menyentuh rumus perhitungan apa pun
 *     (IMT, kalori Harris-Benedict, ambang klasifikasi ada di includes/gizi.php).
 *     Validasi hanya memeriksa & merapikan INPUT sebelum disimpan.
 *
 * Cara pakai:
 *   require_once 'includes/validasi.php';   // + includes/responden.php (otomatis)
 *   $v = instagi_validasi_input($_POST);                       // form publik
 *   $v = instagi_validasi_input($_POST, ['konteks_api' => true]); // API admin
 *   if (!$v['valid']) { ...tampilkan $v['error']... }
 *   $data = $v['data'];  // nama, usia, jenis_kelamin, bb, tb, no_hp, hp_norm,
 *                        // aktivitas, tanggal
 */

// Modul akun responden dipakai untuk menormalkan No. HP (satu sumber aturan).
if (!function_exists('instagi_norm_hp')) {
    require_once __DIR__ . '/responden.php';
}

if (!function_exists('instagi_aktivitas_diizinkan')) {
    /**
     * Faktor aktivitas fisik yang sah — SAMA dengan pilihan di form imt.php
     * dan di modal edit admin.php.
     *
     * @return float[]
     */
    function instagi_aktivitas_diizinkan()
    {
        return [1.2, 1.375, 1.55, 1.725, 1.9];
    }
}

if (!function_exists('instagi_input_str')) {
    /**
     * Ambil nilai dari $_POST dengan aman: hanya string/angka yang diterima,
     * selain itu dianggap kosong.
     *
     * Tanpa ini, kiriman berbentuk array (mis. `no_hp[]=0812`) memicu
     * peringatan PHP "Array to string conversion" — pengirim yang iseng bisa
     * memenuhi log error server hanya dengan mengirim form yang dimodifikasi.
     */
    function instagi_input_str($nilai)
    {
        if (is_string($nilai)) {
            return $nilai;
        }
        if (is_int($nilai) || is_float($nilai)) {
            return (string) $nilai;
        }
        return '';
    }
}

if (!function_exists('instagi_input_angka')) {
    /** Ambil nilai numerik dengan aman (selain angka -> 0), lihat di atas. */
    function instagi_input_angka($nilai)
    {
        return is_numeric($nilai) ? $nilai : 0;
    }
}

if (!function_exists('instagi_aktivitas_sah')) {
    /**
     * Cocokkan faktor aktivitas dengan daftar yang diizinkan (toleran terhadap
     * perbedaan penulisan desimal, mis. "1.550" dari database).
     */
    function instagi_aktivitas_sah($nilai)
    {
        $nilai = (float) $nilai;
        foreach (instagi_aktivitas_diizinkan() as $sah) {
            if (abs($nilai - $sah) < 0.0005) {
                return $sah;
            }
        }
        return null;
    }
}

if (!function_exists('instagi_tanggal_sah')) {
    /**
     * Periksa apakah sebuah tanggal berbentuk YYYY-MM-DD dan merupakan tanggal
     * yang benar-benar ada (menolak 2026-02-31 atau 0000-00-00).
     */
    function instagi_tanggal_sah($tanggal)
    {
        if (!is_string($tanggal) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $tanggal, $m)) {
            return false;
        }
        $tahun = (int) $m[1];
        $bulan = (int) $m[2];
        $hari  = (int) $m[3];

        // Batas tahun: menolak nilai rusak dari data lama, tetapi tetap longgar
        // untuk data yang di-backdate (riwayat lama).
        if ($tahun < 1900 || $tahun > 2100) {
            return false;
        }
        return checkdate($bulan, $hari, $tahun);
    }
}

if (!function_exists('instagi_validasi_input')) {
    /**
     * Validasi + rapikan seluruh input form IMT.
     *
     * Menerima nama field versi form publik (`bb`, `tb`, `tanggal_input`)
     * maupun versi API admin (`berat_badan`, `tinggi_badan`, `tanggal`) supaya
     * kedua jalur bisa memakai fungsi yang sama tanpa mengubah HTML.
     *
     * @param array $in   Biasanya $_POST.
     * @param array $opsi ['konteks_api' => bool]
     *                    - false (default, form publik): tanggal wajib sah dan
     *                      tidak boleh melebihi hari ini.
     *                    - true (API admin): tanggal yang tidak sah TIDAK
     *                      ditolak, melainkan dikembalikan sebagai '' agar
     *                      pemanggil mempertahankan tanggal lama. Ini mencegah
     *                      baris lama yang tanggalnya rusak menjadi mustahil
     *                      diperbaiki (kolom tanggal di modal edit readonly).
     *
     * @return array{valid:bool,error:string,data:array}
     */
    function instagi_validasi_input(array $in, array $opsi = [])
    {
        $konteks_api = !empty($opsi['konteks_api']);
        $gagal = static function ($pesan) {
            return ['valid' => false, 'error' => $pesan, 'data' => []];
        };

        // --- 1. Nama ---------------------------------------------------------
        $nama = trim(instagi_input_str($in['nama'] ?? ''));
        if ($nama === '') {
            return $gagal('Nama Lengkap wajib diisi.');
        }
        if (mb_strlen($nama, 'UTF-8') > 100) {
            return $gagal('Nama Lengkap maksimal 100 karakter.');
        }

        // --- 2. Usia ---------------------------------------------------------
        $usia = (int) instagi_input_angka($in['usia'] ?? null);
        if ($usia < 1 || $usia > 120) {
            return $gagal('Usia harus antara 1 sampai 120 tahun.');
        }

        // --- 3. Jenis kelamin ------------------------------------------------
        // Rumus di includes/gizi.php memakai 'Laki-laki' untuk pria dan
        // memperlakukan nilai lain sebagai wanita. Tanpa pemeriksaan ini, salah
        // tulis satu huruf saja sudah mengubah hasil kalori.
        $jenis_kelamin = trim(instagi_input_str($in['jenis_kelamin'] ?? ''));
        if ($jenis_kelamin !== 'Laki-laki' && $jenis_kelamin !== 'Perempuan') {
            return $gagal('Jenis Kelamin tidak valid. Pilih Laki-laki atau Perempuan.');
        }

        // --- 4. Berat & tinggi badan -----------------------------------------
        $bb = (float) instagi_input_angka($in['bb'] ?? ($in['berat_badan'] ?? null));
        if ($bb < 2 || $bb > 500) {
            return $gagal('Berat Badan harus antara 2 sampai 500 kg.');
        }

        $tb = (float) instagi_input_angka($in['tb'] ?? ($in['tinggi_badan'] ?? null));
        if ($tb < 40 || $tb > 250) {
            return $gagal('Tinggi Badan harus antara 40 sampai 250 cm.');
        }
        // Kolom tinggi_badan bertipe INT, jadi desimal akan dibulatkan oleh
        // database dan membuat nilai tersimpan berbeda dari yang dihitung.
        if (abs($tb - round($tb)) > 0.001) {
            return $gagal('Tinggi Badan dalam cm harus bilangan bulat (tanpa koma), mis. 165.');
        }

        // --- 5. No. HP atau Email --------------------------------------------
        // Pengguna dapat mengisi nomor HP WhatsApp atau alamat email.
        $no_hp   = trim(instagi_input_str($in['no_hp'] ?? ''));
        $hp_norm = '';
        if (filter_var($no_hp, FILTER_VALIDATE_EMAIL)) {
            $hp_norm = strtolower($no_hp);
        } else {
            $hp_norm = instagi_norm_hp($no_hp);
            if (!preg_match('/^0\d{7,14}$/', $hp_norm)) {
                return $gagal('No. HP atau Email tidak valid. Masukkan nomor HP (8-15 digit) atau alamat email yang benar.');
            }
        }

        // --- 6. Aktivitas fisik ---------------------------------------------
        $aktivitas = instagi_aktivitas_sah($in['aktivitas'] ?? null);
        if ($aktivitas === null) {
            return $gagal('Tingkat Aktivitas Fisik tidak valid. Pilih salah satu pilihan yang tersedia.');
        }

        // --- 7. Tanggal ------------------------------------------------------
        $tanggal = trim(instagi_input_str($in['tanggal'] ?? ($in['tanggal_input'] ?? '')));
        if ($tanggal === '') {
            // Form publik: kolom tanggal sudah terisi otomatis, jadi nilai
            // kosong hanya muncul bila JavaScript/atribut form tidak jalan.
            // Dianggap "hari ini" alih-alih menolak pengguna.
            $tanggal = date('Y-m-d');
        }
        $tanggal_sah = instagi_tanggal_sah($tanggal);
        if ($tanggal_sah && !$konteks_api && $tanggal > date('Y-m-d')) {
            // Tanggal di masa depan hanya bisa datang dari kiriman form publik
            // yang dipalsukan (kolomnya readonly) -> ditolak. Jalur admin tidak
            // dibatasi, karena admin memang boleh merapikan data lama.
            $tanggal_sah = false;
        }
        if (!$tanggal_sah) {
            if ($konteks_api) {
                $tanggal = ''; // sinyal: pertahankan tanggal lama di database
            } else {
                return $gagal('Tanggal tidak valid. Muat ulang halaman lalu coba lagi.');
            }
        }

        return [
            'valid' => true,
            'error' => '',
            'data'  => [
                'nama'          => $nama,
                'usia'          => $usia,
                'jenis_kelamin' => $jenis_kelamin,
                'bb'            => $bb,
                'tb'            => (int) round($tb),
                'no_hp'         => $no_hp,
                'hp_norm'       => $hp_norm,
                'aktivitas'     => $aktivitas,
                'tanggal'       => $tanggal,
            ],
        ];
    }
}

if (!function_exists('instagi_validasi_password')) {
    /**
     * Periksa password akun Monitoring (opsional — hanya bila dikirim).
     *
     * Aturan: minimal 8 karakter, dan (bila konfirmasi dikirim) harus sama.
     * Sengaja TIDAK memaksa simbol/angka: pengguna aplikasi ini banyak yang
     * mengakses dari ponsel, dan yang penting panjang + tidak mudah ditebak.
     *
     * @param string $password   Isi kolom password.
     * @param string $konfirmasi Isi kolom ulangi-password ('' bila tidak ada).
     * @return string Pesan kesalahan, atau '' bila sah / tidak diisi.
     */
    function instagi_validasi_password($password, $konfirmasi = '')
    {
        $password   = (string) $password;
        $konfirmasi = (string) $konfirmasi;

        if ($password === '') {
            return ''; // tidak ada password = fitur akun aman tidak dipakai
        }
        if (strlen($password) < 8) {
            return 'Password minimal 8 karakter.';
        }
        if (strlen($password) > 100) {
            return 'Password maksimal 100 karakter.';
        }
        if ($konfirmasi !== '' && $password !== $konfirmasi) {
            return 'Password dan ulangi password tidak sama.';
        }
        return '';
    }
}
