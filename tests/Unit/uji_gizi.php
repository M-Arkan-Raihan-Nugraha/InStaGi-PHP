<?php
/**
 * Uji Unit Perhitungan Gizi InStaGi
 * Memastikan tidak ada celah ambang batas, floating point gap, atau kesalahan klasifikasi.
 */
require_once __DIR__ . '/../../includes/services/gizi.php';

$total = 0;
$lulus = 0;

function cek($kondisi, $nama) {
    global $total, $lulus;
    $total++;
    if ($kondisi) {
        $lulus++;
        echo "  [OK] $nama\n";
    } else {
        echo "  [GAGAL] $nama\n";
    }
}

echo "== 1. Pengujian Ambang Batas Klasifikasi IMT (instagi_kategori_gizi) ==\n";

$k = instagi_kategori_gizi(15.0);
cek($k['status_class'] === 'underweight', 'IMT 15.0 -> underweight');

$k = instagi_kategori_gizi(18.49);
cek($k['status_class'] === 'underweight', 'IMT 18.49 -> underweight');

$k = instagi_kategori_gizi(18.495); // Dulu jatuh ke obesitas karena gap!
cek($k['status_class'] === 'underweight', 'IMT 18.495 (gap lama) -> underweight');

$k = instagi_kategori_gizi(18.5);
cek($k['status_class'] === 'normal', 'IMT 18.5 -> normal');

$k = instagi_kategori_gizi(22.0);
cek($k['status_class'] === 'normal', 'IMT 22.0 -> normal');

$k = instagi_kategori_gizi(24.9);
cek($k['status_class'] === 'normal', 'IMT 24.9 -> normal');

$k = instagi_kategori_gizi(24.95); // Dulu jatuh ke obesitas karena gap!
cek($k['status_class'] === 'normal', 'IMT 24.95 (gap lama) -> normal');

$k = instagi_kategori_gizi(25.0);
cek($k['status_class'] === 'overweight', 'IMT 25.0 -> overweight');

$k = instagi_kategori_gizi(26.5);
cek($k['status_class'] === 'overweight', 'IMT 26.5 -> overweight');

$k = instagi_kategori_gizi(27.0);
cek($k['status_class'] === 'overweight', 'IMT 27.0 -> overweight');

$k = instagi_kategori_gizi(27.01);
cek($k['status_class'] === 'obese', 'IMT 27.01 -> obese');

$k = instagi_kategori_gizi(35.0);
cek($k['status_class'] === 'obese', 'IMT 35.0 -> obese');

echo "\n== 2. Pengujian Tanpa Pembulatan Prematur (instagi_hitung_semua) ==\n";

// BB 50.3 kg, TB 165 cm -> IMT mentah = 50.3 / (1.65^2) = 18.47566... (< 18.5)
// Sebelum diperbaiki, ini dibulatkan ke 18.5 lalu diklasifikasikan Normal.
// Dengan perbaikan, klasifikasi tetap Underweight (koreksi +500 kkal).
$hasil = instagi_hitung_semua(50.3, 165, 25, 'Laki-laki', 1.2);
cek($hasil['imt'] == 18.5, 'Tampilan IMT dibulatkan ke 18.5');
cek($hasil['status_class'] === 'underweight', 'Status gizi tetap underweight (menggunakan nilai mentah 18.475...)');

echo "\n=========================================\n";
echo "LULUS: $lulus / $total\n";
if ($lulus !== $total) {
    exit(1);
}
