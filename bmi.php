<?php
require_once 'includes/config.php';

// --- LOGIC PHP UNTUK KALKULATOR BMI ---
// Sisipkan file koneksi untuk menghubungkan ke database.
require_once 'includes/db.php';

session_start(); // Mulai session untuk menyimpan data hasil analisis

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
    // 1. Ambil dan sanitasi data dari form
    $nama = htmlspecialchars($_POST['nama']);
    $usia = (int)$_POST['usia'];
    $jenis_kelamin = htmlspecialchars($_POST['jenis_kelamin']);
    $bb = (float)$_POST['bb'];
    $tb = (float)$_POST['tb'];
    $no_hp = htmlspecialchars($_POST['no_hp']);
    $tanggal_input = htmlspecialchars($_POST['tanggal_input']);
    $aktivitas = (float)$_POST['aktivitas'];

    // 2. Validasi input
    if ($bb <= 0 || $tb <= 0 || $usia <= 0) {
        $error_message = 'Usia, Berat Badan, dan Tinggi Badan harus diisi dengan angka positif.';
    } else {
        // 3. Lakukan Perhitungan jika data valid

        // Konversi tinggi badan dari cm ke meter
        $tb_meter = $tb / 100;

        // Hitung IMT (Indeks Massa Tubuh)
        $imt = round($bb / ($tb_meter * $tb_meter), 1);

        // Tentukan Status Gizi dan Saran berdasarkan IMT
        if ($imt <= 18.49) {
            $status_gizi = "Berat badan kurang (underweight)";
            $status_class = "underweight";
            $saran = "Berat badan ada masih dibawah normal, Cobalah untuk menambah porsi makan secara bertahap, utamakan makanan tinggi protein (daging, ikan, telur, dan kacang-kacangan), makanlah secara teratur 3x sehari dan makanan selingan 2x sehari, lakukan pemantauan status gizi melalui InStaGi 1 bulan sekali.";
            $kalori_adj = 500;
        } elseif ($imt >= 18.5 && $imt <= 24.9) {
            $status_gizi = "Berat badan normal (ideal)";
            $status_class = "normal";
            $saran = "Status gizi Anda normal. Pertahankan pola makan seimbang, cukup sayur dan buah, serta tetap aktif bergerak. Lakukan pemantauan setiap 3-6 bulan.";
            $kalori_adj = 0;
        } elseif ($imt >= 25 && $imt <= 27) {
            $status_gizi = "Berat badan berlebih (overweight)";
            $status_class = "overweight";
            $saran = "Berat badan mulai berlebih, Kurangi makanan manis, gorengan, dan minuman kemasan. Perbanyak sayuran, air putih, dan aktivitas fisik rutin. Pantau kembali memalui InStaGi 1 bulan lagi.";
            $kalori_adj = -500;
        } else { // IMT > 27
            $status_gizi = "Obesitas";
            $status_class = "obese";
            $saran = "Berat badan anda termasuk obesitas. Mulai atur porsi makan, kurangi gula dan lemak dan lakukan aktivitas fisik minimal 30 menit setiap hari. Disarankan pemantauan setiap bulan melalui InStaGi.";
            $kalori_adj = -500;
        }

        // Hitung Kebutuhan Kalori Harian (Harris-Benedict)
        if ($jenis_kelamin == 'Laki-laki') {
            $bmr = 66.5 + (13.75 * $bb) + (5.003 * $tb) - (6.75 * $usia);
        } else {
            $bmr = 655.1 + (9.563 * $bb) + (1.850 * $tb) - (4.676 * $usia);
        }

        $tdee = $bmr * $aktivitas;
        $kalori_raw = round($tdee + $kalori_adj);
        $remainder = $kalori_raw % 100;
        if ($remainder < 50) {
            $kalori_harian = floor($kalori_raw / 100) * 100;
        } elseif ($remainder == 50) {
            $kalori_harian = $kalori_raw;
        } else {
            $kalori_harian = ceil($kalori_raw / 100) * 100;
        }

        // Hitung Berat Badan Ideal menggunakan rumus baru: (tinggi badan dalam cm - 100) * 0.9
        $bb_ideal = ($tb - 100) * 0.9;
        $bb_ideal = round($bb_ideal, 1);

        // Hitung Rentang Berat Badan Sehat
        $bb_sehat_min = round(18.5 * ($tb_meter * $tb_meter), 1);
        $bb_sehat_max = round(24.9 * ($tb_meter * $tb_meter), 1);
        
        // 4. Simpan data ke database
        $stmt = $koneksi->prepare(
            "INSERT INTO bmi_history (tanggal, nama, usia, jenis_kelamin, no_hp, berat_badan, tinggi_badan, aktivitas, kalori, imt, status_gizi, saran) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssissdddidss", $tanggal_input, $nama, $usia, $jenis_kelamin, $no_hp, $bb, $tb, $aktivitas, $kalori_harian, $imt, $status_gizi, $saran);
        
        if($stmt->execute()) {
            // $success_message = "Data Anda berhasil dihitung dan disimpan!"; // Moved to session for result.php

            // Simpan hasil analisis ke session untuk ditampilkan di result.php
            $_SESSION['bmi_result'] = [
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
            $error_message = "Gagal menyimpan data ke database: " . $stmt->error;
            $stmt->close();
            $koneksi->close(); // Close connection on error
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
