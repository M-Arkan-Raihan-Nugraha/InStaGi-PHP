<?php
// --- PENGATURAN KONEKSI DATABASE UNTUK HOSTING ---
//
// PENTING: Saat Anda mengunggah aplikasi ini ke web hosting,
// Anda HARUS mengubah nilai-nilai di bawah ini sesuai dengan
// detail database yang disediakan oleh penyedia hosting Anda.
// Anda bisa mendapatkan informasi ini dari cPanel, Plesk, atau dashboard hosting Anda.
//
// -----------------------------------------------------------------------------

// **LANGKAH 1: Ganti detail koneksi di bawah ini.**
// $host = 'localhost'; // Ganti dengan nama host database Anda (misal: 'localhost' atau '127.0.0.1' atau alamat dari hosting)
// $user = 'root';      // Ganti dengan username database Anda
// $pass = '';          // Ganti dengan password database Anda
// $db_name = 'instagi'; // Ganti dengan nama database Anda
$host = 'sql306.iceiy.com'; // Ganti dengan nama host database Anda (misal: 'localhost' atau '127.0.0.1' atau alamat dari hosting)
$user = 'icei_41170677';      // Ganti dengan username database Anda
$pass = 'arkankafa';          // Ganti dengan password database Anda
$db_name = 'icei_41170677_instagi'; // Ganti dengan nama database Anda

// -----------------------------------------------------------------------------

// Membuat koneksi ke database
$koneksi = new mysqli($host, $user, $pass);

// Cek koneksi
if ($koneksi->connect_error) {
    $error_response = ['success' => false, 'message' => 'Koneksi ke database gagal: ' . $koneksi->connect_error];
    header('Content-Type: application/json'); // Ensure header is set
    echo json_encode($error_response);
    exit;
}

// **LANGKAH 2 (Opsional): Hapus kode pembuatan database otomatis.**
// Di lingkungan hosting, biasanya Anda membuat database terlebih dahulu melalui
// cPanel. Jika Anda sudah membuat database, Anda bisa menghapus atau
// memberikan komentar pada blok kode di bawah ini untuk sedikit performa ekstra.
// -----------------------------------------------------------------------------
// Membuat database jika belum ada
$sql_create_db = "CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
if (!$koneksi->query($sql_create_db)) {
    $error_response = ['success' => false, 'message' => 'Setup database gagal: ' . $koneksi->error];
    header('Content-Type: application/json');
    echo json_encode($error_response);
    exit;
}
// -----------------------------------------------------------------------------

// Memilih database yang akan digunakan
$koneksi->select_db($db_name);

// --- MEMBUAT TABEL JIKA BELUM ADA ---
// Kode ini aman untuk dibiarkan karena `IF NOT EXISTS` akan mencegah
// error jika tabel sudah ada.
$sql_create_table = "
CREATE TABLE IF NOT EXISTS bmi_history (
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
);
";

if (!$koneksi->query($sql_create_table)) {
    $error_response = ['success' => false, 'message' => 'Setup tabel database gagal: ' . $koneksi->error];
    header('Content-Type: application/json');
    echo json_encode($error_response);
    exit;
}

// Tambahkan kolom aktivitas dan kalori jika belum ada (untuk migrasi)
$koneksi->query("ALTER TABLE bmi_history ADD COLUMN IF NOT EXISTS aktivitas DECIMAL(4,3) NOT NULL AFTER tinggi_badan");
$koneksi->query("ALTER TABLE bmi_history ADD COLUMN IF NOT EXISTS kalori INT(11) NOT NULL AFTER aktivitas");
?>

