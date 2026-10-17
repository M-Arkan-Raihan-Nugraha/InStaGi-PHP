<?php
/**
 * Perkakas FORMAT TAMPILAN InStaGi
 * -----------------------------------------------------------------------------
 * Mengatur bagaimana angka dan tanggal DITAMPILKAN. Tidak ada perhitungan di
 * sini — rumus tetap satu-satunya sumber di includes/gizi.php.
 *
 * Aturan tampilan yang dipakai seluruh halaman:
 *   - IMT/berat badan: bilangan bulat ditampilkan tanpa koma desimal
 *     (22,0 -> "22"), sedangkan nilai yang memang berdesimal tetap ditampilkan
 *     (24,2 -> "24,2") supaya tidak bertentangan dengan label status gizinya.
 *   - Pemisah desimal memakai KOMA (kebiasaan Indonesia).
 *   - Tanggal ditampilkan "17-10-2026" (hari-bulan-tahun).
 */

if (!function_exists('instagi_angka')) {
    /**
     * Format angka untuk tampilan.
     *
     * @param  float|int|string|null $nilai Nilai angka (boleh string dari DB).
     * @param  int                   $desimal_maks Jumlah desimal maksimum (default 1).
     * @return string Angka siap tampil, mis. "22" atau "24,2".
     */
    function instagi_angka($nilai, $desimal_maks = 1)
    {
        if ($nilai === null || $nilai === '') {
            return '';
        }

        $angka = (float) $nilai;

        // Bilangan bulat (atau hampir bulat, mis. 22,0/21,999) -> tanpa desimal.
        if (abs($angka - round($angka)) < 0.05) {
            return number_format(round($angka), 0, ',', '.');
        }

        $teks = number_format($angka, max(0, (int) $desimal_maks), ',', '.');

        // Buang nol desimal yang tidak perlu: "24,20" -> "24,2".
        if (strpos($teks, ',') !== false) {
            $teks = rtrim($teks, '0');
            $teks = rtrim($teks, ',');
        }

        return $teks;
    }
}

if (!function_exists('instagi_angka_imt')) {
    /**
     * Format nilai IMT untuk tampilan — nama khusus supaya jelas maksudnya
     * ketika dibaca di halaman (mis. "IMT 22" atau "IMT 24,2").
     */
    function instagi_angka_imt($nilai)
    {
        return instagi_angka($nilai, 1);
    }
}

if (!function_exists('instagi_tanggal')) {
    /**
     * Format tanggal untuk tampilan: "17-10-2026".
     *
     * @param  string|null $tanggal Tanggal dari DB (Y-m-d) atau apa pun yang
     *                              dapat dibaca strtotime().
     * @return string Tanggal siap tampil, atau "-" bila tidak terbaca.
     */
    function instagi_tanggal($tanggal)
    {
        if ($tanggal === null || $tanggal === '') {
            return '-';
        }

        $waktu = strtotime((string) $tanggal);
        if ($waktu === false) {
            return '-';
        }

        return date('d-m-Y', $waktu);
    }
}
