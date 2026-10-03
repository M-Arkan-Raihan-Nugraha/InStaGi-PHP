<?php
/**
 * Koneksi Database InStaGi
 * -----------------------------------------------------------------------------
 * Cara kerja (dibuat "dari nol", tanpa langkah migrasi terpisah):
 *   1. Bila database belum ada, aplikasi mencoba membuatnya otomatis.
 *      (Di hosting berbagi seperti AeonFree, database biasanya sudah dibuat
 *      lebih dulu lewat cPanel. Bila user DB tidak berhak CREATE DATABASE,
 *      kegagalan itu diabaikan dan aplikasi lanjut memakai database yang ada.)
 *   2. Bila tabel imt_history belum ada, aplikasi membuatnya otomatis.
 *      Bila tabel versi lama bmi_history masih ada (mis. hasil unggahan lama),
 *      isinya OTOMATIS dipindahkan ke imt_history lewat RENAME TABLE —
 *      jadi data lama tidak hilang dan tidak perlu migrasi manual.
 *   3. Bila kolom aktivitas/kalori belum ada (data lama), kolom itu
 *      ditambahkan otomatis.
 *   Semua langkah di atas memakai IF NOT EXISTS / pengecekan INFORMATION_SCHEMA
 *   sehingga aman dijalankan berulang kali dan TIDAK memakai sintaks
 *   "ADD COLUMN IF NOT EXISTS" (hanya didukung MariaDB, fatal di MySQL 8).
 *   4. Koneksi dibungkus try/catch sehingga kegagalan DB menampilkan pesan
 *      yang jelas (JSON untuk endpoint API, teks untuk halaman biasa)
 *      alih-alih halaman putih.
 *   5. Kredensial dibaca dari includes/db_credentials.php dan dapat
 *      di-override dengan environment variable.
 */

require_once __DIR__ . '/config.php';

// --- Muat kredensial ---
$credentials_file = __DIR__ . '/db_credentials.php';
if (!is_file($credentials_file)) {
    $error_response = [
        'success' => false,
        'message' => 'File konfigurasi database (includes/db_credentials.php) tidak ditemukan.',
    ];
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    echo json_encode($error_response);
    exit;
}
$db_config = require $credentials_file;

// Environment variable menang atas nilai di file (memudahkan deploy).
// Catatan: memakai perbandingan "!== false", bukan "?:", agar env var yang
// sengaja dikosongkan (mis. password kosong) tetap dihormati.
$env_host = getenv('INSTAGI_DB_HOST');
$env_user = getenv('INSTAGI_DB_USER');
$env_pass = getenv('INSTAGI_DB_PASS');
$env_name = getenv('INSTAGI_DB_NAME');
$env_port = getenv('INSTAGI_DB_PORT');

// Fallback: sebagian SAPI (mis. mod_php dengan "SetEnv" Apache) tidak
// mengekspos env var yang bernilai KOSONG lewat getenv(). Nilai kosong yang
// sah (mis. password kosong) masih bisa dibaca dari $_SERVER.
if ($env_host === false && isset($_SERVER['INSTAGI_DB_HOST'])) { $env_host = $_SERVER['INSTAGI_DB_HOST']; }
if ($env_user === false && isset($_SERVER['INSTAGI_DB_USER'])) { $env_user = $_SERVER['INSTAGI_DB_USER']; }
if ($env_pass === false && isset($_SERVER['INSTAGI_DB_PASS'])) { $env_pass = $_SERVER['INSTAGI_DB_PASS']; }
if ($env_name === false && isset($_SERVER['INSTAGI_DB_NAME'])) { $env_name = $_SERVER['INSTAGI_DB_NAME']; }
if ($env_port === false && isset($_SERVER['INSTAGI_DB_PORT'])) { $env_port = $_SERVER['INSTAGI_DB_PORT']; }

$host    = ($env_host !== false) ? $env_host : $db_config['host'];
$user    = ($env_user !== false) ? $env_user : $db_config['user'];
$pass    = ($env_pass !== false) ? $env_pass : $db_config['pass'];
$db_name = ($env_name !== false) ? $env_name : $db_config['name'];
$port    = (int) (($env_port !== false) ? $env_port : ($db_config['port'] ?? 3306));

// Port kosong ("" dari env) tidak valid untuk mysqli -> kembalikan ke default.
if ($port <= 0) {
    $port = (int) ($db_config['port'] ?? 3306);
}

// --- Deteksi apakah pemanggil mengharapkan JSON (endpoint API) ---
$wants_json = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false);

if (!function_exists('instagi_db_fail')) {
    /**
     * Tampilkan error setup database dengan cara yang sesuai konteks,
     * lalu hentikan eksekusi.
     */
    function instagi_db_fail($message, $wants_json, $log_detail = null)
    {
        error_log('[InStaGi] ' . $message . ($log_detail ? ' | detail: ' . $log_detail : ''));

        if ($wants_json) {
            if (!headers_sent()) {
                header('Content-Type: application/json');
                http_response_code(503);
            }
            echo json_encode(['success' => false, 'message' => $message]);
        } else {
            // Halaman biasa: tampilkan pesan singkat yang ramah, tanpa detail teknis.
            if (!headers_sent()) {
                header('Content-Type: text/html; charset=UTF-8');
            }
            http_response_code(503);
            echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">'
               . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
               . '<title>InStaGi - Gangguan Sementara</title></head><body '
               . 'style="font-family:Arial,sans-serif;max-width:600px;margin:80px auto;padding:0 20px;text-align:center;color:#333;">'
               . '<h1 style="color:#e74c3c;">Layanan sedang tidak tersedia</h1>'
               . '<p>Aplikasi tidak dapat terhubung ke database saat ini. '
               . 'Silakan coba beberapa saat lagi.</p>'
               . '<p><a href="index.php" style="color:#e74c3c;">Kembali ke halaman utama</a></p>'
               . '</body></html>';
        }
        exit;
    }
}

// --- Buat koneksi (dibungkus try/catch) ---
try {
    // Nonaktifkan mode exception pada konstruktor agar kita bisa menangani
    // kegagalan koneksi sendiri dengan pesan yang jelas.
    mysqli_report(MYSQLI_REPORT_OFF);

    $koneksi = new mysqli($host, $user, $pass, null, $port);

    if ($koneksi->connect_error) {
        instagi_db_fail(
            'Koneksi ke database gagal. Silakan hubungi administrator.',
            $wants_json,
            $koneksi->connect_error
        );
    }

    $koneksi->set_charset('utf8mb4');

    // --- Setup otomatis "dari nol" -----------------------------------------
    // 1) Buat database bila belum ada. Di hosting berbagi, user DB sering kali
    //    tidak berhak CREATE DATABASE (database dibuat lewat cPanel), jadi
    //    kegagalan langkah ini diabaikan — bukan kesalahan fatal.
    @$koneksi->query("CREATE DATABASE IF NOT EXISTS `" . $koneksi->real_escape_string($db_name)
        . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");

    // 2) Pilih database; ini yang wajib berhasil.
    if (!@$koneksi->select_db($db_name)) {
        instagi_db_fail(
            'Database aplikasi tidak dapat diakses.',
            $wants_json,
            $koneksi->error
        );
    }

    // 3) Pindahkan nama tabel versi lama bmi_history -> imt_history.
    //    RENAME TABLE memindahkan SELURUH isi (data tidak hilang) dan aman:
    //    hanya dijalankan bila tabel lama ada DAN tabel baru belum ada.
    //    Kalau tabel lama sudah tidak ada, langkah ini tidak melakukan apa pun.
    $tabel_lama = 'bmi_history';
    $tabel_baru = 'imt_history';
    $sql_cek = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
                 WHERE TABLE_SCHEMA = '" . $koneksi->real_escape_string($db_name) . "'
                 AND TABLE_NAME IN ('" . $tabel_lama . "', '" . $tabel_baru . "')";
    $tabel_ada = [];
    $res_cek = @$koneksi->query($sql_cek);
    if ($res_cek) {
        while ($row = $res_cek->fetch_row()) {
            $tabel_ada[$row[0]] = true;
        }
    }
    if (isset($tabel_ada[$tabel_lama]) && !isset($tabel_ada[$tabel_baru])) {
        // Gagal rename bukan alasan fatal: bila gagal, CREATE TABLE di bawah
        // akan membuat tabel baru (aplikasi tetap jalan; data lama tetap utuh
        // di tabel bmi_history untuk dipindahkan manual bila perlu).
        @$koneksi->query("RENAME TABLE `" . $tabel_lama . "` TO `" . $tabel_baru . "`");
    }

    // 4) Buat tabel bila belum ada (aman diulang, IF NOT EXISTS).
    $sql_create_table = "
    CREATE TABLE IF NOT EXISTS imt_history (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        tanggal DATE NOT NULL,
        nama VARCHAR(255) NOT NULL,
        usia INT(3) NOT NULL,
        jenis_kelamin VARCHAR(20) NOT NULL,
        no_hp VARCHAR(20) NOT NULL,
        berat_badan DECIMAL(5,1) NOT NULL,
        tinggi_badan INT(5) NOT NULL,
        aktivitas DECIMAL(4,3) NOT NULL,
        kalori INT(11) NOT NULL,
        imt DECIMAL(4,1) NOT NULL,
        status_gizi VARCHAR(50) NOT NULL,
        saran TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    if (!@$koneksi->query($sql_create_table)) {
        instagi_db_fail(
            'Tabel aplikasi tidak dapat disiapkan.',
            $wants_json,
            $koneksi->error
        );
    }

    // 5) Tambah kolom aktivitas/kalori bila belum ada (data dari versi lama).
    //    Memakai pengecekan INFORMATION_SCHEMA — BUKAN "ADD COLUMN IF NOT EXISTS"
    //    yang hanya ada di MariaDB dan membuat fatal di MySQL 8.
    $existing_columns = [];
    $res = @$koneksi->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                             WHERE TABLE_SCHEMA = '" . $koneksi->real_escape_string($db_name) . "'
                             AND TABLE_NAME = 'imt_history'");
    if ($res) {
        while ($row = $res->fetch_row()) {
            $existing_columns[$row[0]] = true;
        }
    }

    $column_migrations = [
        'aktivitas' => "ALTER TABLE imt_history ADD COLUMN aktivitas DECIMAL(4,3) NOT NULL DEFAULT 0 AFTER tinggi_badan",
        'kalori'    => "ALTER TABLE imt_history ADD COLUMN kalori INT(11) NOT NULL DEFAULT 0 AFTER aktivitas",
    ];
    foreach ($column_migrations as $column => $sql) {
        if (!isset($existing_columns[$column])) {
            @$koneksi->query($sql);
        }
    }
    // --- Akhir setup otomatis ----------------------------------------------

    // Mode error mysqli dibiarkan OFF (non-exception) untuk seluruh request.
    // Alasannya: seluruh kode aplikasi (imt.php, api/*.php) sudah memeriksa
    // nilai kembalian ($stmt->execute(), $koneksi->query()) dan menampilkan
    // pesan error-nya sendiri. Dengan mode exception (default PHP 8.1+),
    // kegagalan query apa pun akan melempar exception yang tidak tertangkap
    // dan menghasilkan halaman putih. Mode OFF lebih aman & sesuai kode ini.

} catch (Throwable $e) {
    instagi_db_fail(
        'Terjadi kesalahan saat menyiapkan koneksi database.',
        $wants_json,
        $e->getMessage()
    );
}
