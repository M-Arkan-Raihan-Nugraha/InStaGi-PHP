<?php
// Session aman + helper CSRF (sekaligus memuat includes/config.php).
require_once 'includes/session.php';

// --- LOGIC PHP UNTUK KALKULATOR IMT ---
// Sisipkan file koneksi untuk menghubungkan ke database.
require_once 'includes/db.php';

// Modul perhitungan gizi (satu sumber kebenaran untuk IMT/kalori/saran).
require_once 'includes/gizi.php';

// Inisialisasi variabel hasil agar tidak error saat halaman pertama kali dibuka
$nama = '';
$usia = '';
$jenis_kelamin = '';
$bb = '';
$tb = '';
$no_hp = '';
$tanggal_input = date('Y-m-d'); // Pre-fill dengan tanggal hari ini

$imt = null;
$status_gizi = '';
$saran = '';
$bb_ideal = null;
$bb_sehat_min = null;
$bb_sehat_max = null;
$error_message = '';
// $success_message = ''; // This message will be handled by result.php if needed


// Cek jika form sudah di-submit (metode POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Ambil data dari form.
    //    CATATAN: data disimpan APA ADANYA (mentah) ke database, TIDAK
    //    di-htmlspecialchars di sini. Escaping hanya dilakukan saat MENAMPILKAN
    //    data (result.php / admin.php). Jika di-escape di sini, nama seperti
    //    "A & B" akan tersimpan sebagai "A &amp; B" dan rusak saat ditampilkan.
    $nama = trim($_POST['nama'] ?? '');
    $usia = (int)($_POST['usia'] ?? 0);
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
    $bb = (float)($_POST['bb'] ?? 0);
    $tb = (float)($_POST['tb'] ?? 0);
    $no_hp = trim($_POST['no_hp'] ?? '');
    $tanggal_input = trim($_POST['tanggal_input'] ?? '');
    $aktivitas = (float)($_POST['aktivitas'] ?? 0);

    // 2. Validasi input
    //    Termasuk token CSRF: form ini publik, jadi submit lintas situs ditolak.
    if (!instagi_csrf_check($_POST['csrf_token'] ?? '')) {
        $error_message = 'Sesi form tidak valid. Silakan muat ulang halaman lalu coba lagi.';
    } elseif ($bb <= 0 || $tb <= 0 || $usia <= 0) {
        $error_message = 'Usia, Berat Badan, dan Tinggi Badan harus diisi dengan angka positif.';
    } else {
        // 3. Lakukan Perhitungan jika data valid
        //    Semua rumus ada di includes/gizi.php agar identik dengan
        //    api/update_record.php (tidak lagi duplikat & tidak lagi berbeda).
        $hasil = instagi_hitung_semua($bb, $tb, $usia, $jenis_kelamin, $aktivitas);

        $imt            = $hasil['imt'];
        $status_gizi    = $hasil['status_gizi'];
        $status_class   = $hasil['status_class'];
        $saran          = $hasil['saran'];
        $kalori_harian  = $hasil['kalori_harian'];
        $bb_ideal       = $hasil['bb_ideal'];
        $bb_sehat_min   = $hasil['bb_sehat_min'];
        $bb_sehat_max   = $hasil['bb_sehat_max'];

        // 4. Simpan data ke database
        $stmt = $koneksi->prepare(
            "INSERT INTO bmi_history (tanggal, nama, usia, jenis_kelamin, no_hp, berat_badan, tinggi_badan, aktivitas, kalori, imt, status_gizi, saran) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        // Penjaga: bila prepare gagal (mis. tabel belum ada), tampilkan pesan
        // error yang jelas alih-alih fatal "call to a member function on bool".
        if ($stmt === false) {
            error_log('[InStaGi] bmi.php prepare gagal: ' . $koneksi->error);
            $error_message = 'Gagal menyiapkan penyimpanan data. Silakan coba beberapa saat lagi.';
            $koneksi->close();
        } else {
            $stmt->bind_param("ssissdddidss", $tanggal_input, $nama, $usia, $jenis_kelamin, $no_hp, $bb, $tb, $aktivitas, $kalori_harian, $imt, $status_gizi, $saran);

            if($stmt->execute()) {
                // $success_message = "Data Anda berhasil dihitung dan disimpan!"; // Moved to session for result.php

                // Simpan hasil analisis ke session untuk ditampilkan di result.php
                $_SESSION['imt_result'] = [
                    'nama' => $nama,
                    'imt' => $imt,
                    'status_gizi' => $status_gizi,
                    'status_class' => $status_class,
                    'saran' => $saran,
                    'bb_ideal' => $bb_ideal,
                    'bb_sehat_min' => $bb_sehat_min,
                    'bb_sehat_max' => $bb_sehat_max,
                    'kalori_harian' => $kalori_harian,
                    'no_hp' => $no_hp, // Simpan no_hp untuk keperluan WhatsApp
                    'success_message' => "Data Anda berhasil dihitung dan disimpan!"
                ];

                $stmt->close();
                $koneksi->close();
                header("location: result.php"); // Redirect ke halaman hasil
                exit;

            } else {
                // Detail teknis hanya ke log server, TIDAK ditampilkan ke pengguna.
                error_log('[InStaGi] bmi.php execute gagal: ' . $stmt->error);
                $error_message = "Gagal menyimpan data ke database. Silakan coba beberapa saat lagi.";
                $stmt->close();
                $koneksi->close(); // Close connection on error
            }
        }
    }
} else {
    $koneksi->close(); // Close connection if form is not submitted
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InStaGi - Input Data</title>
    <link rel="shortcut icon" href="assets/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/bmi.css">
</head>
<body>

    <div class="container">
        <div style="text-align: center;">
            <img src="assets/logo.png" alt="InStaGi Logo" style="max-width: 150px; margin-bottom: 10px;">
        </div>
        <h1>Input Data Diri Anda</h1>

        <?php if ($error_message): ?>
            <div class="message error-message"><?= $error_message ?></div>
        <?php endif; ?>


        <form action="bmi.php" method="POST" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(instagi_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-group full-width">
                <label for="tanggal_input">Tanggal Input</label>
                <input type="date" id="tanggal_input" name="tanggal_input" value="<?= htmlspecialchars($tanggal_input) ?>" readonly>
            </div>
            
            <div class="form-group">
                <label for="nama">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($nama) ?>" required>
            </div>
            <div class="form-group">
                <label for="usia">Usia (Tahun)</label>
                <input type="number" id="usia" name="usia" value="<?= htmlspecialchars($usia) ?>" required>
            </div>
            <div class="form-group">
                <label>Jenis Kelamin</label>
                <div class="radio-group">
                    <input type="radio" id="laki-laki" name="jenis_kelamin" value="Laki-laki" <?= ($jenis_kelamin == 'Laki-laki' || $jenis_kelamin == '') ? 'checked' : '' ?> required>
                    <label for="laki-laki">Laki-laki</label>
                    <input type="radio" id="perempuan" name="jenis_kelamin" value="Perempuan" <?= ($jenis_kelamin == 'Perempuan') ? 'checked' : '' ?> required>
                    <label for="perempuan">Perempuan</label>
                </div>
            </div>
            <div class="form-group">
                 <label for="no_hp">No. HP</label>
                <input type="tel" id="no_hp" name="no_hp" value="<?= htmlspecialchars($no_hp) ?>" required>
            </div>
            <div class="form-group">
                <label for="bb">Berat Badan (kg)</label>
                <input type="number" step="0.1" id="bb" name="bb" value="<?= htmlspecialchars($bb) ?>" required>
            </div>
            <div class="form-group">
                <label for="tb">Tinggi Badan (cm)</label>
                <input type="number" id="tb" name="tb" value="<?= htmlspecialchars($tb) ?>" required>
            </div>
            <div class="form-group full-width">
                <label for="aktivitas">Tingkat Aktivitas Fisik</label>
                <select id="aktivitas" name="aktivitas" required style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; box-sizing: border-box;">
                    <option value="1.2">Sangat Ringan: Istirahat di tempat tidur atau jarang/tidak pernah berolahraga.</option>
                    <option value="1.375">Ringan: Olahraga ringan atau aktivitas fisik 1-3 hari per minggu.</option>
                    <option value="1.55">Sedang: Olahraga sedang atau aktivitas fisik 3-5 hari per minggu.</option>
                    <option value="1.725">Berat: Olahraga berat atau aktivitas fisik intensif 6-7 hari per minggu.</option>
                    <option value="1.9">Sangat Berat: Aktivitas fisik sangat intensif (atlet, pekerjaan fisik berat) atau olahraga dua kali sehari.</option>
                </select>
            </div>
            <button type="submit" class="submit-btn">Hitung IMT & Simpan</button>
        </form>

    </div>
    
    <footer class="main-footer">
        <p>Halaman data responden hanya bisa diakses oleh admin. <a href="login.php">Login Admin</a></p>
        <p>Copyright © 2026 InStaGi | Created With ❤️</p>
    </footer>
</body>
</html>
