<?php
require_once '../includes/session.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'data' => null];

// Cek apakah user sudah login sebagai admin (401 bila belum).
// Dilakukan SEBELUM memuat db.php agar permintaan tanpa login tidak
// menyentuh database sama sekali.
instagi_require_login_json();

require_once '../includes/db.php';

// Pastikan ada ID yang dikirim
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Validasi ID
    if ($id <= 0) {
        $response['message'] = 'ID data tidak valid.';
        echo json_encode($response);
        exit;
    }

    // Siapkan statement SELECT
    $stmt = $koneksi->prepare("SELECT * FROM bmi_history WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $response['success'] = true;
        $response['data'] = $result->fetch_assoc();
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
