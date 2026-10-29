<?php
require_once '../includes/core/session.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'data' => null];

// Cek apakah user sudah login sebagai admin (401 bila belum).
// Dilakukan SEBELUM memuat db.php agar permintaan tanpa login tidak
// menyentuh database sama sekali.
instagi_require_login_json();

require_once '../includes/core/db.php';

// Pastikan ada ID yang dikirim
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Validasi ID
    if ($id <= 0) {
        $response['message'] = 'ID data tidak valid.';
        echo json_encode($response);
        exit;
    }

    // Siapkan statement SELECT.
    // Penjaga: prepare() bisa gagal (mis. tabel belum siap). Karena mode
    // exception mysqli dimatikan (includes/db.php), kegagalan menghasilkan
    // nilai `false` — memanggil bind_param() pada false memicu fatal yang
    // membuat respons bukan JSON lagi. Pola sama dengan imt.php.
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
