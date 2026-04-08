<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

session_start();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Cek apakah user sudah login sebagai admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $response['message'] = 'Akses tidak diizinkan.';
    echo json_encode($response);
    exit;
}

// Pastikan request adalah POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil dan sanitasi data dari POST
    $id = (int)$_POST['id'];
    $tanggal = htmlspecialchars($_POST['tanggal']);
    $nama = htmlspecialchars($_POST['nama']);
    $usia = (int)$_POST['usia'];
    $jenis_kelamin = htmlspecialchars($_POST['jenis_kelamin']);
    $no_hp = htmlspecialchars($_POST['no_hp']);
    $berat_badan = (float)$_POST['berat_badan'];
    $tinggi_badan = (int)$_POST['tinggi_badan'];
    $aktivitas = (float)$_POST['aktivitas'];

    // Validasi data dasar
    if ($id <= 0 || $berat_badan <= 0 || $tinggi_badan <= 0 || $usia <= 0) {
        $response['message'] = 'Data tidak valid (ID, BB, TB, atau Usia harus positif).';
        echo json_encode($response);
        exit;
    }

    // Hitung ulang IMT, status gizi, dan saran karena BB/TB bisa berubah
    $tb_meter = $tinggi_badan / 100;
    $imt = round($berat_badan / ($tb_meter * $tb_meter), 1);

    $status_gizi = '';
    $saran = '';
    $kalori_adj = 0;
    if ($imt <= 18.49) {
        $status_gizi = "Berat badan kurang (underweight)";
        $saran = "Anda memiliki berat badan kurang. Perbanyak asupan makanan bergizi dan konsultasikan dengan ahli gizi untuk menyusun menu makanan yang dapat membantu menaikkan berat badan ke rentang ideal.";
        $kalori_adj = 500;
    } elseif ($imt >= 18.5 && $imt <= 24.9) {
        $status_gizi = "Berat badan normal (ideal)";
        $saran = "Selamat! Berat badan Anda termasuk dalam kategori ideal. Pertahankan pola makan seimbang dan rutin berolahraga untuk menjaga kesehatan tubuh.";
        $kalori_adj = 0;
    } elseif ($imt >= 25 && $imt <= 27) {
        $status_gizi = "Berat badan berlebih (overweight)";
        $saran = "Anda memiliki berat badan berlebih. Disarankan untuk mengatur pola makan dengan mengurangi asupan kalori dan lemak, serta meningkatkan aktivitas fisik seperti berolahraga secara teratur.";
        $kalori_adj = -500;
    } else { // IMT > 27
        $status_gizi = "Obesitas";
        $saran = "Anda berada dalam kategori obesitas. Segera konsultasikan dengan dokter atau ahli gizi untuk mendapatkan penanganan lebih lanjut. Penting untuk mengubah gaya hidup menjadi lebih sehat.";
        $kalori_adj = -500;
    }

    // Hitung ulang Kebutuhan Kalori Harian (Harris-Benedict)
    if ($jenis_kelamin == 'Laki-laki') {
        $bmr = 66.5 + (13.75 * $berat_badan) + (5.003 * $tinggi_badan) - (6.75 * $usia);
    } else {
        $bmr = 655.1 + (9.563 * $berat_badan) + (1.850 * $tinggi_badan) - (4.676 * $usia);
    }

    $tdee = $bmr * $aktivitas;
    $kalori_harian = round($tdee + $kalori_adj);

    // Siapkan statement UPDATE
    $stmt = $koneksi->prepare(
        "UPDATE bmi_history SET 
            tanggal = ?, nama = ?, usia = ?, jenis_kelamin = ?, no_hp = ?, 
            berat_badan = ?, tinggi_badan = ?, aktivitas = ?, kalori = ?, imt = ?, status_gizi = ?, saran = ?
        WHERE id = ?"
    );
    $stmt->bind_param(
        "ssissdddidssi",
        $tanggal, $nama, $usia, $jenis_kelamin, $no_hp,
        $berat_badan, $tinggi_badan, $aktivitas, $kalori_harian, $imt, $status_gizi, $saran,
        $id
    );

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Data berhasil diperbarui.';
    } else {
        $response['message'] = 'Gagal memperbarui data: ' . $stmt->error;
    }

    $stmt->close();
} else {
    $response['message'] = 'Permintaan tidak valid.';
}

$koneksi->close();
echo json_encode($response);
exit;
?>
