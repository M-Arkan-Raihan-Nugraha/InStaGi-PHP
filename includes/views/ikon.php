<?php
/**
 * Ikon garis (SVG) InStaGi — pengganti emoji
 * -----------------------------------------------------------------------------
 * Seluruh ikon antarmuka dibuat sebagai gambar vektor (SVG), bukan emoji, supaya
 * tampilannya sama di semua perangkat dan warnanya otomatis mengikuti warna teks
 * di sekitarnya (currentColor) — termasuk saat ikon aktif/non-aktif.
 *
 * CARA PAKAI
 * ----------
 *   <?= instagi_ikon('cek-gizi') ?>                    // tanpa kelas tambahan
 *   <?= instagi_ikon('informasi', 'home-menu-ico') ?>  // ikut kelas CSS yang ada
 *
 * Ukuran: default 1em (mengikuti ukuran huruf induknya). Untuk memperbesar /
 * memperkecil, atur font-size elemen induk atau tambahkan kelas CSS sendiri.
 *
 * DAFTAR NAMA IKON
 * ----------------
 *   cek-gizi    orang kurus & orang gemuk   (menu Cek Status Gizi)
 *   informasi   piring + sendok & garpu     (menu Informasi Gizi)
 *   monitoring  grafik garis naik           (menu Monitoring Status Gizi)
 *   admin       pengatur (slider)           (tautan Admin)
 *   konsultasi  gelembung percakapan        (layanan Konsultasi Gizi)
 *   catering    tudung saji                 (layanan Catering Gizi)
 *   perhatian   segitiga peringatan         (badge peringatan)
 *   hati        hati penuh                  (footer)
 *
 * Ikon diidentifikasi dari NAMA di atas (bukan warna/emoji), jadi aman dipanggil
 * dengan nama yang tidak dikenal: fungsi mengembalikan string kosong.
 */

if (!function_exists('instagi_ikon_bentuk')) {
    /**
     * Bentuk dasar tiap ikon (isi elemen <svg>, kanvas 24x24).
     * Semua memakai garis (stroke) dengan currentColor, kecuali "hati" yang penuh.
     */
    function instagi_ikon_bentuk($nama)
    {
        $bentuk = [
            // Orang kurus (kiri) dan orang gemuk (kanan) berdampingan.
            'cek-gizi' =>
                '<circle cx="7.4" cy="5.9" r="2.6"/>'
                . '<path d="M7.4 9.1c-1.6 0-2.6 1.1-2.6 2.6v4.7a1 1 0 0 0 1 1h3.2a1 1 0 0 0 1-1v-4.7c0-1.5-1-2.6-2.6-2.6z"/>'
                . '<circle cx="16.6" cy="5.9" r="2.6"/>'
                . '<path d="M16.6 9.1c-2.3 0-3.8 1.6-3.8 3.8v3.4a1 1 0 0 0 1 1h5.6a1 1 0 0 0 1-1v-3.4c0-2.2-1.5-3.8-3.8-3.8z"/>',

            // Piring di tengah, garpu di kiri, sendok di kanan.
            'informasi' =>
                '<circle cx="12" cy="12.4" r="6.5"/>'
                . '<circle cx="12" cy="12.4" r="3.4"/>'
                . '<path d="M3.2 3.4v3.2M4.4 3.4v3.2M5.6 3.4v3.2"/>'
                . '<path d="M4.4 6.6v14"/>'
                . '<path d="M19.6 3.4c-1.4 0-2.4 1.2-2.4 2.9s1 2.7 2.4 2.7 2.4-1 2.4-2.7-1-2.9-2.4-2.9z"/>'
                . '<path d="M19.6 9v11.6"/>',

            // Grafik garis naik dengan sumbu dan dua titik data.
            'monitoring' =>
                '<path d="M3.4 20.4h17.2"/>'
                . '<path d="M5 16.6l4.6-5 3.8 3.1 5.5-6.2"/>'
                . '<circle cx="9.6" cy="11.6" r="1.2"/>'
                . '<circle cx="18.9" cy="8.5" r="1.2"/>',

            // Pengatur (tiga baris dengan tombol geser).
            'admin' =>
                '<path d="M4 6.6h8.4"/><circle cx="15.2" cy="6.6" r="2.4"/><path d="M17.6 6.6H20"/>'
                . '<path d="M4 12h2.4"/><circle cx="9.2" cy="12" r="2.4"/><path d="M11.6 12H20"/>'
                . '<path d="M4 17.4h8.4"/><circle cx="15.2" cy="17.4" r="2.4"/><path d="M17.6 17.4H20"/>',

            // Gelembung percakapan (konsultasi).
            'konsultasi' =>
                '<path d="M4.4 5.4h15.2a1.6 1.6 0 0 1 1.6 1.6v8a1.6 1.6 0 0 1-1.6 1.6h-9.1l-4.3 3.6v-3.6H4.4a1.6 1.6 0 0 1-1.6-1.6v-8a1.6 1.6 0 0 1 1.6-1.6z"/>'
                . '<circle cx="8.8" cy="11" r=".9"/>'
                . '<circle cx="12" cy="11" r=".9"/>'
                . '<circle cx="15.2" cy="11" r=".9"/>',

            // Tudung saji (catering).
            'catering' =>
                '<path d="M3.4 17.8h17.2"/>'
                . '<path d="M5.4 17.8a6.6 6.6 0 0 1 13.2 0"/>'
                . '<path d="M12 11.2V9.6"/>'
                . '<circle cx="12" cy="8.5" r="1.1"/>',

            // Segitiga peringatan.
            'perhatian' =>
                '<path d="M12 4.6l8.3 14.4H3.7L12 4.6z"/>'
                . '<path d="M12 9.8v4"/>'
                . '<circle cx="12" cy="16.4" r=".9"/>',

            // Hati penuh (footer).
            'hati' =>
                '<path d="M12 20.2S4.6 15.8 4.6 10.6A4 4 0 0 1 12 8.3a4 4 0 0 1 7.4 2.3c0 5.2-7.4 9.6-7.4 9.6z" fill="currentColor" stroke="none"/>',
        ];

        return isset($bentuk[$nama]) ? $bentuk[$nama] : '';
    }
}

if (!function_exists('instagi_ikon')) {
    /**
     * Kembalikan markup SVG sebuah ikon.
     *
     * @param  string $nama  Nama ikon (lihat daftar di kepala berkas).
     * @param  string $kelas Kelas CSS tambahan untuk elemen <svg>.
     * @return string        Markup <svg> siap cetak (string kosong bila nama tidak dikenal).
     */
    function instagi_ikon($nama, $kelas = '')
    {
        $bentuk = instagi_ikon_bentuk($nama);
        if ($bentuk === '') {
            return '';
        }

        $kelas_svg = trim('ikon ' . $kelas);
        return '<svg class="' . htmlspecialchars($kelas_svg, ENT_QUOTES, 'UTF-8') . '"'
            . ' viewBox="0 0 24 24" width="1em" height="1em" fill="none"'
            . ' stroke="currentColor" stroke-width="1.7" stroke-linecap="round"'
            . ' stroke-linejoin="round" aria-hidden="true" focusable="false">'
            . $bentuk
            . '</svg>';
    }
}
