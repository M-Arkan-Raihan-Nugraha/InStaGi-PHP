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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Permintaan tidak valid.';
    echo json_encode($response);
    exit;
}

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
        $response['message'] = 'Gagal menghapus data: ' . $stmt->error;
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
        $response['message'] = 'Gagal menghapus data: ' . $stmt->error;
    }
    $stmt->close();

} else {
    $response['message'] = 'Tidak ada data yang dikirim untuk dihapus.';
}

$koneksi->close();
echo json_encode($response);
exit;
?>
