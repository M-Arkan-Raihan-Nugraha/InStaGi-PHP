<?php
/**
 * Konfigurasi Umum dan Pengaturan Produksi
 *
 * File ini berada di dalam folder "includes/", sehingga semua path yang
 * mengarah ke luar folder tersebut WAJIB memakai dirname(__DIR__)
 * (satu level di atas "includes/" = root aplikasi).
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

// Laporkan semua jenis error.
error_reporting(E_ALL);

// --- Direktori Aplikasi & Log ---
// __DIR__ = <root aplikasi>/includes
// APP_ROOT = <root aplikasi>
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}
if (!defined('APP_LOG_DIR')) {
    define('APP_LOG_DIR', APP_ROOT . '/logs');
}

// Buat folder logs bila belum ada agar error_log tidak gagal secara senyap.
// (Aman dipanggil berulang kali.)
if (!is_dir(APP_LOG_DIR)) {
    @mkdir(APP_LOG_DIR, 0755, true);
}

// Tentukan path ke file log. Dulu bernilai __DIR__ . '/logs/php-error.log'
// yang salah, karena akan mengarah ke 'includes/logs/' yang tidak pernah ada.
ini_set('error_log', APP_LOG_DIR . '/php-error.log');

// --- Kontak & Identitas Aplikasi (satu sumber kebenaran) ---
// Nomor WhatsApp konsultasi dipakai di result.php (tombol WA + teks disclaimer).
// Ubah di SATU tempat ini saja. Bisa juga dioverride lewat environment
// variable INSTAGI_WA_NUMBER tanpa mengedit kode.
if (!defined('INSTAGI_APP_NAME')) {
    define('INSTAGI_APP_NAME', 'InStaGi');
}
if (!defined('INSTAGI_WA_NUMBER')) {
    // Format internasional tanpa tanda '+', contoh: 6281224948388
    define('INSTAGI_WA_NUMBER', getenv('INSTAGI_WA_NUMBER') ?: '6281224948388');
}
if (!defined('INSTAGI_WA_LABEL')) {
    define('INSTAGI_WA_LABEL', 'RS Paru dr. H. A. Rotinsulu');
}
