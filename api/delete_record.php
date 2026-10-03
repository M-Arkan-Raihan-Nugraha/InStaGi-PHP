<?php
require_once '../includes/session.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// 1. Wajib sudah login (401 bila belum).
//    Semua pemeriksaan di bawah dilakukan SEBELUM koneksi DB, agar permintaan
//    yang tidak sah tidak pernah menyentuh database.
instagi_require_login_json();

// 2. Hanya menerima POST (405 bila bukan).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['message'] = 'Permintaan tidak valid.';
    echo json_encode($response);
    exit;
}

// 3. Wajib menyertakan token CSRF yang sah (403 bila tidak).
instagi_csrf_require_json();

require_once '../includes/db.php';

// --- LOGIC ---

// Handle bulk delete
if (isset($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = $_POST['ids'];
    // Sanitize all IDs to integers
    $sanitized_ids = array_map('intval', $ids);
    // Filter out any non-positive IDs
    $valid_ids = array_filter($sanitized_ids, function($id) {
        return $id > 0;
    });

    if (empty($valid_ids)) {
        $response['message'] = 'Tidak ada ID valid yang dipilih untuk dihapus.';
        echo json_encode($response);
        exit;
    }

    // Create placeholders for the IN clause: ?,?,?
    $placeholders = implode(',', array_fill(0, count($valid_ids), '?'));
    // Create type definition string: "iii"
    $types = str_repeat('i', count($valid_ids));

    $stmt = $koneksi->prepare("DELETE FROM bmi_history WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$valid_ids);

    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        $response['success'] = true;
        $response['message'] = $affected_rows . ' data berhasil dihapus.';
    } else {
        // Detail teknis hanya ke log server, TIDAK ke klien.
        error_log('[InStaGi] delete_record (bulk) gagal: ' . $stmt->error);
        http_response_code(500);
        $response['message'] = 'Gagal menghapus data. Silakan coba beberapa saat lagi.';
    }
    $stmt->close();

// Handle single delete (fallback)
} elseif (isset($_POST['id'])) {
    $id = (int)$_POST['id'];

    if ($id <= 0) {
        $response['message'] = 'ID data tidak valid.';
        echo json_encode($response);
        exit;
    }

    $stmt = $koneksi->prepare("DELETE FROM bmi_history WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Data berhasil dihapus.';
        } else {
            $response['message'] = 'Data tidak ditemukan atau sudah dihapus.';
        }
    } else {
        error_log('[InStaGi] delete_record (single) gagal: ' . $stmt->error);
        http_response_code(500);
        $response['message'] = 'Gagal menghapus data. Silakan coba beberapa saat lagi.';
    }
    $stmt->close();

} else {
    $response['message'] = 'Tidak ada data yang dikirim untuk dihapus.';
}

$koneksi->close();
echo json_encode($response);
exit;
