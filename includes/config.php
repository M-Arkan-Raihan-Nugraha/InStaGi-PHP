<?php
/**
 * Konfigurasi Umum dan Pengaturan Produksi
 */

// --- Pengaturan Timezone ---
// Set timezone default ke Jakarta untuk konsistensi tanggal dan waktu.
date_default_timezone_set('Asia/Jakarta');

// --- Pengaturan Error Handling untuk Lingkungan Produksi ---
// Matikan tampilan error ke pengguna. Ini adalah langkah keamanan penting.
// '0' berarti 'Off'.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

// Aktifkan logging error ke file.
// '1' berarti 'On'.
ini_set('log_errors', '1');

// Tentukan path ke file log. 
// __DIR__ adalah konstanta PHP yang menunjuk ke direktori file ini.
// Ini memastikan path akan selalu benar di server manapun.
ini_set('error_log', __DIR__ . '/logs/php-error.log');

// Laporkan semua jenis error.
error_reporting(E_ALL);

?>
