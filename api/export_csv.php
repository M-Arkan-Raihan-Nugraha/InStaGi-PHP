<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

session_start();

// Cek apakah user sudah login sebagai admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Nama file CSV yang akan di-download
$filename = "data_responden_bmi_" . date('Ymd_His') . ".csv";

// Set header untuk memberitahu browser bahwa ini adalah file CSV dan harus di-download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Buka output stream
$output = fopen('php://output', 'w');

// Kolom header CSV
$header = [
    'ID',
    'Tanggal',
    'Nama',
    'Usia',
    'Jenis Kelamin',
    'No. HP',
    'Berat Badan (kg)',
    'Tinggi Badan (cm)',
    'Aktivitas',
    'Kalori (kkal)',
    'IMT',
    'Status Gizi',
    'Saran',
    'Dibuat Pada'
];
fputcsv($output, $header);

// Ambil semua data dari database
$query = "SELECT id, tanggal, nama, usia, jenis_kelamin, no_hp, berat_badan, tinggi_badan, aktivitas, kalori, imt, status_gizi, saran, created_at FROM bmi_history ORDER BY tanggal DESC, id DESC";
$result = $koneksi->query($query);

// Tulis setiap baris data ke file CSV
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Konversi format tanggal jika diperlukan
        $row['tanggal'] = date("d-m-Y", strtotime($row['tanggal']));
        $row['created_at'] = date("d-m-Y H:i:s", strtotime($row['created_at']));
        fputcsv($output, $row);
    }
}

// Tutup output stream
fclose($output);

$koneksi->close();
exit;
?>
