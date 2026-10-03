<?php
require_once '../includes/session.php';

// Cek apakah user sudah login sebagai admin (dilakukan sebelum koneksi DB).
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Berada di dalam folder /api/, jadi harus naik satu level ke login.php
    // (sebelumnya menunjuk ke "login.php" -> /api/login.php yang tidak ada / 404).
    header("location: ../login.php");
    exit;
}

require_once '../includes/db.php';

// Nama file CSV yang akan di-download
$filename = "data_responden_imt_" . date('Ymd_His') . ".csv";

// Set header untuk memberitahu browser bahwa ini adalah file CSV dan harus di-download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Buka output stream
$output = fopen('php://output', 'w');

// Tulis BOM UTF-8 agar Excel menampilkan karakter Indonesia dengan benar
// (tanpa BOM, huruf beraksen sering tampil rusak di Excel versi lama).
fwrite($output, "\xEF\xBB\xBF");

/**
 * Cegah CSV Injection: nilai yang diawali = + - @ bisa dieksekusi sebagai
 * rumus saat dibuka di Excel/Spreadsheet. Tambahkan apostrof di depan.
 */
if (!function_exists('instagi_csv_safe')) {
    function instagi_csv_safe($value)
    {
        $value = (string) $value;
        if ($value !== '' && strpos("=+-@\t
", $value[0]) !== false) {
            return "'" . $value;
        }
        return $value;
    }
}

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
// Parameter $escape ditulis eksplisit agar tidak memicu deprecation di PHP 8.4+.
fputcsv($output, $header, ',', '"', '');

// Ambil semua data dari database
$query = "SELECT id, tanggal, nama, usia, jenis_kelamin, no_hp, berat_badan, tinggi_badan, aktivitas, kalori, imt, status_gizi, saran, created_at FROM imt_history ORDER BY tanggal DESC, id DESC";
$result = $koneksi->query($query);

// Penjaga: bila query gagal (mis. tabel belum siap), jangan fatal.
if ($result === false) {
    error_log('[InStaGi] export_csv query gagal: ' . $koneksi->error);
    fputcsv($output, ['Gagal mengambil data dari database.'], ',', '"', '');
    fclose($output);
    $koneksi->close();
    exit;
}

// Tulis setiap baris data ke file CSV
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Konversi format tanggal jika diperlukan
        $row['tanggal'] = date("d-m-Y", strtotime($row['tanggal']));
        $row['created_at'] = date("d-m-Y H:i:s", strtotime($row['created_at']));

        // Netralkan nilai yang berpotensi jadi rumus (CSV injection).
        foreach ($row as $key => $value) {
            $row[$key] = instagi_csv_safe($value);
        }

        fputcsv($output, $row, ',', '"', '');
    }
}

// Tutup output stream
fclose($output);

$koneksi->close();
exit;
?>
