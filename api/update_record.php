<?php
require_once '../includes/core/session.php';
// Modul perhitungan gizi (dipakai bersama imt.php agar hasil selalu sama).
// >>> RUMUS TIDAK DIUBAH: gizi.php dipakai apa adanya.
require_once '../includes/services/nutrition.php';
// Modul akun responden: saat admin mengedit, tautan akun tetap konsisten.
require_once '../includes/services/respondent.php';
// Modul validasi input: aturan SAMA dengan form publik imt.php.
require_once '../includes/services/validation.php';

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

require_once '../includes/core/db.php';

// ID diperiksa lebih dulu: tanpa ID yang sah tidak ada yang bisa diperbarui.
$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    $response['message'] = 'ID data tidak valid.';
    echo json_encode($response);
    exit;
}

// Validasi seluruh isi form memakai modul bersama (includes/validasi.php).
// Sebelumnya jalur ini hanya memeriksa "> 0", sehingga admin bisa menyimpan
// nilai yang tidak mungkin diketik responden sendiri — mis. aktivitas 999
// (kalori jadi tidak masuk akal) atau No. HP yang bentuknya tidak bisa dipakai
// untuk masuk ke halaman Monitoring.
//
// Catatan tanggal: pada konteks API, tanggal yang rusak TIDAK menolak seluruh
// penyimpanan. Validator mengembalikannya sebagai '' dan kolom Tanggal di modal
// edit bersifat readonly, jadi tanggal LAMA dipertahankan oleh SQL di bawah.
// Ini mencegah baris lama bertanggal rusak menjadi mustahil diperbaiki.
// >>> RUMUS TIDAK DIUBAH: validasi hanya memeriksa & merapikan INPUT.
$validasi = instagi_validasi_input($_POST, ['konteks_api' => true]);
if (!$validasi['valid']) {
    http_response_code(422); // 422 = data terkirim tetapi tidak lolos pemeriksaan
    $response['message'] = $validasi['error'];
    echo json_encode($response);
    exit;
}

// Nilai bersih hasil validasi (bukan $_POST mentah). Teks tetap disimpan
// MENTAH tanpa htmlspecialchars — lihat penjelasan di imt.php; escaping
// dilakukan saat menampilkan (admin.php / monitoring.php).
$data          = $validasi['data'];
$tanggal       = $data['tanggal']; // '' = pertahankan tanggal lama (lihat SQL)
$nama          = $data['nama'];
$usia          = $data['usia'];
$jenis_kelamin = $data['jenis_kelamin'];
$no_hp         = $data['no_hp'];
$berat_badan   = $data['bb'];
$tinggi_badan  = $data['tb'];
$aktivitas     = $data['aktivitas'];

// Hitung ulang IMT, status gizi, saran, dan kalori memakai modul bersama.
// Dengan cara ini, hasil edit SELALU identik dengan hasil input awal
// (sebelumnya kalori di sini tidak dibulatkan ke ratusan seperti di imt.php).
// >>> RUMUS TIDAK DIUBAH: tetap memakai instagi_hitung_semua() apa adanya.
$hasil = instagi_hitung_semua($berat_badan, $tinggi_badan, $usia, $jenis_kelamin, $aktivitas);

$imt           = $hasil['imt'];
$status_gizi   = $hasil['status_gizi'];
$saran         = $hasil['saran'];
$kalori_harian = $hasil['kalori_harian'];

// Tautkan ulang ke akun responden (1 nama + 1 nomor HP) agar riwayat tetap
// muncul di halaman Monitoring milik responden yang benar.
// ($hp_norm sudah dihitung & diperiksa di includes/validasi.php.)
$responden_id = instagi_responden_find_or_create($koneksi, $nama, $data['hp_norm']);

// Siapkan statement UPDATE.
// IFNULL(NULLIF(?, ''), tanggal) = "pakai tanggal baru bila sah; bila tidak,
// pertahankan tanggal lama" (lihat catatan validasi tanggal di atas).
$stmt = $koneksi->prepare(
    "UPDATE imt_history SET 
        responden_id = ?, tanggal = IFNULL(NULLIF(?, ''), tanggal), nama = ?, usia = ?, jenis_kelamin = ?, no_hp = ?, 
        berat_badan = ?, tinggi_badan = ?, aktivitas = ?, kalori = ?, imt = ?, status_gizi = ?, saran = ?
    WHERE id = ?"
);

// Penjaga: prepare() bisa gagal (mis. kolom/tabel belum siap). Mode exception
// mysqli sengaja dimatikan di includes/db.php, jadi kegagalan menghasilkan
// nilai `false` — dan memanggil bind_param() pada nilai false memicu fatal
// "Call to a member function bind_param() on bool" (halaman kosong tanpa
// penjelasan, bukan JSON). Pola pemeriksaan ini sama dengan imt.php.
if ($stmt === false) {
    error_log('[InStaGi] update_record prepare gagal: ' . $koneksi->error);
    http_response_code(500);
    $response['message'] = 'Gagal menyiapkan pembaruan data. Silakan coba beberapa saat lagi.';
    $koneksi->close();
    echo json_encode($response);
    exit;
}

$stmt->bind_param(
    "ississdddidssi",
    $responden_id, $tanggal, $nama, $usia, $jenis_kelamin, $no_hp,
    $berat_badan, $tinggi_badan, $aktivitas, $kalori_harian, $imt, $status_gizi, $saran,
    $id
);

if ($stmt->execute()) {
    // Bila tidak ada baris yang berubah, pastikan datanya memang masih ada.
    // (0 baris yang berubah juga terjadi saat admin menyimpan tanpa mengubah
    // apa pun — itu bukan kesalahan, jadi dibedakan lewat pemeriksaan ini.)
    if ($stmt->affected_rows === 0) {
        $cek = $koneksi->prepare("SELECT id FROM imt_history WHERE id = ? LIMIT 1");
        if ($cek !== false) {
            $cek->bind_param('i', $id);
            $cek->execute();
            $pernah_ada = ($cek->get_result()->num_rows > 0);
            $cek->close();
            if (!$pernah_ada) {
                http_response_code(404);
                $response['message'] = 'Data tidak ditemukan atau sudah dihapus.';
                $stmt->close();
                $koneksi->close();
                echo json_encode($response);
                exit;
            }
        }
    }
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
