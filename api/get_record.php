<?php
require_once '../includes/core/session.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'data' => null];

// Cek apakah user sudah login sebagai admin (401 bila belum).
// Dilakukan SEBELUM memuat db.php agar permintaan tanpa login tidak
// menyentuh database sama sekali.
instagi_require_login_json();

require_once '../includes/core/db.php';
require_once '../includes/services/nutrition.php';

// Pastikan ada ID yang dikirim
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Validasi ID
    if ($id <= 0) {
        $response['message'] = 'ID data tidak valid.';
        echo json_encode($response);
        exit;
    }

    $stmt = $koneksi->prepare("SELECT * FROM imt_history WHERE id = ?");
    if ($stmt === false) {
        error_log('[InStaGi] get_record prepare gagal: ' . $koneksi->error);
        http_response_code(500);
        $response['message'] = 'Gagal mengambil data. Silakan coba beberapa saat lagi.';
        $koneksi->close();
        echo json_encode($response);
        exit;
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $row['bb_ideal'] = instagi_hitung_bbi((int)$row['tinggi_badan'], (string)$row['jenis_kelamin']) . ' kg';
        $rentang = instagi_hitung_rentang_bb_sehat((int)$row['tinggi_badan']);
        $row['bb_sehat_min'] = $rentang['min'];
        $row['bb_sehat_max'] = $rentang['max'];
        $row['rentang_sehat'] = $rentang['min'] . ' - ' . $rentang['max'] . ' kg';
        $row['kalori_label'] = number_format((int)$row['kalori'], 0, ',', '.') . ' kkal';
        $response['success'] = true;
        $response['data'] = $row;
    } else {
        $response['message'] = 'Data tidak ditemukan.';
    }

    $stmt->close();
} else {
    $response['message'] = 'Permintaan tidak valid.';
}

$koneksi->close();
echo json_encode($response);
exit;
