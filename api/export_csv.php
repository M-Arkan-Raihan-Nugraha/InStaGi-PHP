<?php
require_once '../includes/core/session.php';

// Cek apakah user sudah login sebagai admin (dilakukan sebelum koneksi DB).
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /login");
    exit;
}

require_once '../includes/core/db.php';
require_once '../includes/services/nutrition.php';

// Nama file CSV yang akan di-download
$filename = "data_responden_instagi_" . date('Ymd_His') . ".csv";

// Set header untuk memberitahu browser bahwa ini adalah file CSV dan harus di-download
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Buka output stream
$output = fopen('php://output', 'w');

// Tulis BOM UTF-8 agar Excel menampilkan karakter Indonesia dengan benar
fwrite($output, "\xEF\xBB\xBF");

/**
 * Cegah CSV Injection: nilai yang diawali = + - @ bisa dieksekusi sebagai
 * rumus saat dibuka di Excel/Spreadsheet. Tambahkan apostrof di depan.
 */
if (!function_exists('instagi_csv_safe')) {
    function instagi_csv_safe($value)
    {
        $value = (string) $value;
        if ($value !== '' && strpos("=+-@\t\r\n", $value[0]) !== false) {
            return "'" . $value;
        }
        return $value;
    }
}

// Kolom header CSV yang lengkap
$header = [
    'ID',
    'Tanggal',
    'Nama Responden',
    'Usia (Tahun)',
    'Jenis Kelamin',
    'No. HP',
    'Berat Badan (kg)',
    'Tinggi Badan (cm)',
    'BB Ideal (kg)',
    'Rentang BB Sehat (kg)',
    'Faktor Aktivitas',
    'Kebutuhan Kalori (kkal)',
    'IMT',
    'Status Gizi',
    'Saran Praktis',
    'Waktu Input'
];
fputcsv($output, $header, ',', '"', '');

// Ambil semua data dari database
$query = "SELECT id, tanggal, nama, usia, jenis_kelamin, no_hp, berat_badan, tinggi_badan, aktivitas, kalori, imt, status_gizi, saran, created_at FROM imt_history ORDER BY tanggal DESC, id DESC";
$result = $koneksi->query($query);

if ($result === false) {
    error_log('[InStaGi] export_csv query gagal: ' . $koneksi->error);
    fputcsv($output, ['Gagal mengambil data dari database.'], ',', '"', '');
    fclose($output);
    $koneksi->close();
    exit;
}

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bb_ideal = instagi_hitung_bbi((int)$row['tinggi_badan'], (string)$row['jenis_kelamin']);
        $rentang  = instagi_hitung_rentang_bb_sehat((int)$row['tinggi_badan']);

        $csv_row = [
            'ID'                     => $row['id'],
            'Tanggal'                => date("d-m-Y", strtotime($row['tanggal'])),
            'Nama Responden'         => $row['nama'],
            'Usia (Tahun)'           => $row['usia'],
            'Jenis Kelamin'          => $row['jenis_kelamin'],
            'No. HP'                 => $row['no_hp'],
            'Berat Badan (kg)'       => $row['berat_badan'],
            'Tinggi Badan (cm)'      => $row['tinggi_badan'],
            'BB Ideal (kg)'          => $bb_ideal,
            'Rentang BB Sehat (kg)'  => $rentang['min'] . ' - ' . $rentang['max'],
            'Faktor Aktivitas'       => $row['aktivitas'],
            'Kebutuhan Kalori (kkal)'=> $row['kalori'],
            'IMT'                    => $row['imt'],
            'Status Gizi'            => $row['status_gizi'],
            'Saran Praktis'          => $row['saran'],
            'Waktu Input'            => date("d-m-Y H:i:s", strtotime($row['created_at']))
        ];

        // Netralkan nilai yang berpotensi jadi rumus (CSV injection).
        foreach ($csv_row as $key => $value) {
            $csv_row[$key] = instagi_csv_safe($value);
        }

        fputcsv($output, array_values($csv_row), ',', '"', '');
    }
}

fclose($output);
$koneksi->close();
exit;
