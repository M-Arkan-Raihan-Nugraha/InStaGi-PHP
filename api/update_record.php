<?php
require_once '../includes/session.php';
// Modul perhitungan gizi (dipakai bersama imt.php agar hasil selalu sama).
// >>> RUMUS TIDAK DIUBAH: gizi.php dipakai apa adanya.
require_once '../includes/gizi.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// 1. Wajib sudah login (401 bila belum).
//    Pemeriksaan dilakukan SEBELUM koneksi DB.
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

// Ambil data dari POST (disimpan mentah, TANPA htmlspecialchars —
// lihat penjelasan di imt.php; escaping dilakukan saat menampilkan).
$id = (int)($_POST['id'] ?? 0);
$tanggal = trim($_POST['tanggal'] ?? '');
$nama = trim($_POST['nama'] ?? '');
$usia = (int)($_POST['usia'] ?? 0);
$jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
$no_hp = trim($_POST['no_hp'] ?? '');
$berat_badan = (float)($_POST['berat_badan'] ?? 0);
$tinggi_badan = (int)($_POST['tinggi_badan'] ?? 0);
$aktivitas = (float)($_POST['aktivitas'] ?? 0);

// Validasi data dasar
if ($id <= 0 || $berat_badan <= 0 || $tinggi_badan <= 0 || $usia <= 0) {
    $response['message'] = 'Data tidak valid (ID, BB, TB, atau Usia harus positif).';
    echo json_encode($response);
    exit;
}

// Hitung ulang IMT, status gizi, saran, dan kalori memakai modul bersama.
// Dengan cara ini, hasil edit SELALU identik dengan hasil input awal
// (sebelumnya kalori di sini tidak dibulatkan ke ratusan seperti di imt.php).
// >>> RUMUS TIDAK DIUBAH: tetap memakai instagi_hitung_semua() apa adanya.
$hasil = instagi_hitung_semua($berat_badan, $tinggi_badan, $usia, $jenis_kelamin, $aktivitas);

$imt           = $hasil['imt'];
$status_gizi   = $hasil['status_gizi'];
$saran         = $hasil['saran'];
$kalori_harian = $hasil['kalori_harian'];

// Siapkan statement UPDATE
$stmt = $koneksi->prepare(
    "UPDATE imt_history SET 
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
    // Detail teknis hanya ke log server, TIDAK ke klien.
    error_log('[InStaGi] update_record gagal: ' . $stmt->error);
    http_response_code(500);
    $response['message'] = 'Gagal memperbarui data. Silakan coba beberapa saat lagi.';
}

$stmt->close();
$koneksi->close();
echo json_encode($response);
exit;
