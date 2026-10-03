<?php
/**
 * Modul Perhitungan Gizi InStaGi (SATU SUMBER KEBENARAN)
 * -----------------------------------------------------------------------------
 * Sebelumnya logika perhitungan ini DUPLIKAT di dua tempat:
 *   - bmi.php               (saat responden mengisi form)
 *   - api/update_record.php (saat admin mengedit data)
 * dan keduanya TIDAK sama:
 *   - bmi.php membulatkan kalori ke RATUSAN terdekat
 *   - update_record.php memakai round() biasa
 *   - teks "saran" juga berbeda kata
 * Akibatnya, sekadar mengedit data tanpa mengubah BB/TB tetap mengubah nilai
 * kalori dan menimpa saran asli (data jadi tidak konsisten).
 *
 * Modul ini dipakai oleh KEDUA file tersebut agar hasilnya selalu identik.
 */

if (!function_exists('instagi_hitung_imt')) {
    /**
     * Hitung Indeks Massa Tubuh (IMT).
     *
     * @param float $bb_kg  Berat badan (kg)
     * @param float $tb_cm  Tinggi badan (cm)
     * @return float IMT dengan 1 angka di belakang koma
     */
    function instagi_hitung_imt($bb_kg, $tb_cm)
    {
        $tb_meter = $tb_cm / 100;
        if ($tb_meter <= 0) {
            return 0.0;
        }
        return round($bb_kg / ($tb_meter * $tb_meter), 1);
    }
}

if (!function_exists('instagi_kategori_gizi')) {
    /**
     * Tentukan status gizi, class CSS, saran, dan koreksi kalori dari nilai IMT.
     * Ambang batas mengikuti kategori yang sudah dipakai aplikasi.
     *
     * @param float $imt
     * @return array{status_gizi:string,status_class:string,saran:string,kalori_adj:int}
     */
    function instagi_kategori_gizi($imt)
    {
        if ($imt <= 18.49) {
            return [
                'status_gizi'  => 'Berat badan kurang (underweight)',
                'status_class' => 'underweight',
                'saran'        => 'Berat badan Anda masih di bawah normal. Cobalah untuk menambah porsi makan secara bertahap, utamakan makanan tinggi protein (daging, ikan, telur, dan kacang-kacangan), makanlah secara teratur 3x sehari dan makanan selingan 2x sehari, serta lakukan pemantauan status gizi melalui InStaGi 1 bulan sekali.',
                'kalori_adj'   => 500,
            ];
        }

        if ($imt >= 18.5 && $imt <= 24.9) {
            return [
                'status_gizi'  => 'Berat badan normal (ideal)',
                'status_class' => 'normal',
                'saran'        => 'Status gizi Anda normal. Pertahankan pola makan seimbang, cukup sayur dan buah, serta tetap aktif bergerak. Lakukan pemantauan setiap 3-6 bulan.',
                'kalori_adj'   => 0,
            ];
        }

        if ($imt >= 25 && $imt <= 27) {
            return [
                'status_gizi'  => 'Berat badan berlebih (overweight)',
                'status_class' => 'overweight',
                'saran'        => 'Berat badan mulai berlebih. Kurangi makanan manis, gorengan, dan minuman kemasan. Perbanyak sayuran, air putih, dan aktivitas fisik rutin. Pantau kembali melalui InStaGi 1 bulan lagi.',
                'kalori_adj'   => -500,
            ];
        }

        // IMT > 27
        return [
            'status_gizi'  => 'Obesitas',
            'status_class' => 'obese',
            'saran'        => 'Berat badan Anda sudah masuk kategori obesitas. Segera konsultasikan dengan dokter atau ahli gizi. Mulai kurangi porsi makan, hindari makanan tinggi gula dan lemak, serta tingkatkan aktivitas fisik secara bertahap. Pantau kembali melalui InStaGi 1 bulan lagi.',
            'kalori_adj'   => -500,
        ];
    }
}

if (!function_exists('instagi_kalori_harian')) {
    /**
     * Hitung kebutuhan kalori harian (Harris-Benedict x faktor aktivitas/TDEE),
     * lalu dibulatkan ke RATUSAN terdekat (aturan asli bmi.php).
     *
     * @param float  $bb_kg
     * @param float  $tb_cm
     * @param int    $usia
     * @param string $jenis_kelamin  'Laki-laki' atau lainnya (dianggap Perempuan)
     * @param float  $aktivitas      Faktor aktivitas (mis. 1.2 - 1.9)
     * @param int    $kalori_adj     Koreksi +500 / 0 / -500
     * @return int
     */
    function instagi_kalori_harian($bb_kg, $tb_cm, $usia, $jenis_kelamin, $aktivitas, $kalori_adj)
    {
        if ($jenis_kelamin === 'Laki-laki') {
            $bmr = 66.5 + (13.75 * $bb_kg) + (5.003 * $tb_cm) - (6.75 * $usia);
        } else {
            $bmr = 655.1 + (9.563 * $bb_kg) + (1.850 * $tb_cm) - (4.676 * $usia);
        }

        $tdee = $bmr * $aktivitas;
        $kalori_raw = (int) round($tdee + $kalori_adj);

        // Pembulatan ke ratusan terdekat (50 dibiarkan apa adanya).
        $remainder = $kalori_raw % 100;
        if ($remainder < 50) {
            return (int) (floor($kalori_raw / 100) * 100);
        }
        if ($remainder == 50) {
            return $kalori_raw;
        }
        return (int) (ceil($kalori_raw / 100) * 100);
    }
}

if (!function_exists('instagi_hitung_semua')) {
    /**
     * Hitung seluruh hasil analisis sekaligus (IMT, status, saran, kalori,
     * berat badan ideal, dan rentang berat badan sehat).
     *
     * @return array
     */
    function instagi_hitung_semua($bb_kg, $tb_cm, $usia, $jenis_kelamin, $aktivitas)
    {
        $tb_meter = $tb_cm / 100;

        $imt = instagi_hitung_imt($bb_kg, $tb_cm);
        $kategori = instagi_kategori_gizi($imt);

        $kalori_harian = instagi_kalori_harian(
            $bb_kg,
            $tb_cm,
            $usia,
            $jenis_kelamin,
            $aktivitas,
            $kategori['kalori_adj']
        );

        return [
            'imt'           => $imt,
            'status_gizi'   => $kategori['status_gizi'],
            'status_class'  => $kategori['status_class'],
            'saran'         => $kategori['saran'],
            'kalori_harian' => $kalori_harian,
            'bb_ideal'      => round(($tb_cm - 100) * 0.9, 1),
            'bb_sehat_min'  => round(18.5 * ($tb_meter * $tb_meter), 1),
            'bb_sehat_max'  => round(24.9 * ($tb_meter * $tb_meter), 1),
        ];
    }
}
