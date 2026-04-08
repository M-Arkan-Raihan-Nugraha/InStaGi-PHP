<?php
require_once 'includes/config.php';

// Mulai session untuk mengambil data hasil analisis
session_start();

// Redirect jika tidak ada data hasil analisis di session
if (!isset($_SESSION['bmi_result'])) {
    header("location: bmi.php");
    exit;
}

// Ambil data hasil analisis dari session
$result = $_SESSION['bmi_result'];

// Hapus data hasil analisis dari session setelah diambil
// unset($_SESSION['bmi_result']); // uncomment this line if you want the results to be displayed only once

// Ekstrak variabel untuk kemudahan penggunaan
$nama = htmlspecialchars($result['nama']);
$imt = htmlspecialchars($result['imt']);
$status_gizi = htmlspecialchars($result['status_gizi']);
$status_class = htmlspecialchars($result['status_class']);
$saran = htmlspecialchars($result['saran']);
$bb_ideal = htmlspecialchars($result['bb_ideal']);
$bb_sehat_min = htmlspecialchars($result['bb_sehat_min']);
$bb_sehat_max = htmlspecialchars($result['bb_sehat_max']);
$kalori_harian = htmlspecialchars($result['kalori_harian']);
$no_hp_pengguna = htmlspecialchars($result['no_hp']); // No HP pengguna
$success_message = htmlspecialchars($result['success_message']);

// Nomor WhatsApp tujuan konsultasi (Ganti dengan nomor WhatsApp Anda)
$whatsapp_number = "6281224948388"; // Contoh: Ganti dengan nomor WhatsApp yang valid

// Pesan WhatsApp yang akan dikirim
$whatsapp_message = urlencode("Halo, saya *$nama*. Hasil perhitungan IMT saya adalah *$imt* dengan status gizi *$status_gizi*. Estimasi Kebutuhan kalori harian saya adalah *$kalori_harian kkal*. Saran untuk saya: *$saran* Saya ingin konsultasi lebih lanjut.");

// URL WhatsApp
$whatsapp_url = "https://wa.me/{$whatsapp_number}?text={$whatsapp_message}";

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InStaGi - Hasil Analisis IMT</title>
    <link rel="shortcut icon" href="assets/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/result.css">
</head>

<body>

    <div class="container">
        <div style="text-align: center;" id="page-logo">
            <img src="assets/logo.png" alt="InStaGi Logo" style="max-width: 150px; margin-bottom: 10px;">
        </div>
        <h1>Hasil Analisis IMT (Indeks Massa Tubuh) Anda</h1>

        <?php if (!empty($success_message)): ?>
            <div class="message success-message"><?= $success_message ?></div>
        <?php endif; ?>

        <div class="result-container" id="printable-area">
            <h2>Analisis untuk <?= $nama ?></h2>
            <?php
            // Calculate marker position for BMI bar
            $min_imt_scale = 10;
            $max_imt_scale = 40;
            $imt_value_for_bar = max($min_imt_scale, min($max_imt_scale, (float) $imt)); // Cap IMT within scale
            $marker_position = (($imt_value_for_bar - $min_imt_scale) / ($max_imt_scale - $min_imt_scale)) * 100;
            ?>
            <!-- BMI Bar Visualization -->
            <div class="bmi-bar-container">
                <div class="bmi-segments-wrapper">
                    <div class="bmi-segment underweight-segment"></div>
                    <div class="bmi-segment normal-segment"></div>
                    <div class="bmi-segment overweight-segment"></div>
                    <div class="bmi-segment obese-segment"></div>
                </div>
                <div class="bmi-marker" style="left: <?= $marker_position ?>%;">
                    <div class="marker-value"><?= $imt ?></div>
                    <div class="marker-arrow"></div>
                </div>
                <div class="bmi-threshold-labels">
                    <span style="left: calc(((10 - 10) / 30) * 100%); transform: translateX(-50%);">10</span>
                    <span style="left: calc(((18.5 - 10) / 30) * 100%); transform: translateX(-50%);">18.5</span>
                    <span style="left: calc(((25 - 10) / 30) * 100%); transform: translateX(-50%);">25</span>
                    <span style="left: calc(((27 - 10) / 30) * 100%); transform: translateX(-50%);">27</span>
                    <span style="left: calc(((40 - 10) / 30) * 100%); transform: translateX(-50%);">40</span>
                </div>
                <div class="bmi-category-labels">
                    <span class="label-underweight">Kurus</span>
                    <span class="label-normal">Normal</span>
                    <span class="label-overweight">Gemuk</span>
                    <span class="label-obese">Obesitas</span>
                </div>
            </div>
            <!-- End BMI Bar Visualization -->
            <div class="result-grid">
                <div class="result-item">
                    <h3>Indeks Massa Tubuh</h3>
                    <p class="value"><?= $imt ?></p>
                </div>
                <div class="result-item">
                    <h3>Status Gizi</h3>
                    <p class="value"><span class="status <?= $status_class ?>"><?= $status_gizi ?></span></p>
                </div>
                <div class="result-item">
                    <h3>Berat Badan Ideal</h3>
                    <p class="value"><?= $bb_ideal ?> kg</p>
                </div>
                <div class="result-item">
                    <h3>Rentang BB Sehat</h3>
                    <p class="value"><?= $bb_sehat_min ?> - <?= $bb_sehat_max ?> kg</p>
                </div>
                <div class="result-item" style="grid-column: 1 / -1; background: #fff3f3; border: 1px solid #ffcccc;">
                    <h3 style="color: #e74c3c;">Estimasi Kebutuhan Kalori Harian</h3>
                    <p class="value" style="color: #e74c3c;"><?= $kalori_harian ?> kkal/hari</p>
                    <p style="font-size: 0.9em; margin-top: 10px; color: #555;">
                        <?php if ($status_class == 'underweight'): ?>
                            (Sudah termasuk tambahan 500 kkal untuk meningkatkan berat badan)
                        <?php elseif ($status_class == 'overweight' || $status_class == 'obese'): ?>
                            (Sudah termasuk pengurangan 500 kkal untuk menurunkan berat badan)
                        <?php else: ?>
                            (Kebutuhan kalori untuk mempertahankan berat badan ideal)
                        <?php endif; ?>
                    </p>
                </div>
                <div class="saran-item">
                    <h3>Saran untuk Anda</h3>
                    <p><?= $saran ?></p>
                </div>
            </div>
            <h3 class="disclaimer">
                Bila anda ingin mendapatkan informasi gizi lebih lanjut dan membutuhkan layanan katering diet silahkan
                menghubungi nomor WA RS Paru dr. H. A. Rotinsulu: 081224948388
            </h3>
        </div>

        <div class="action-buttons">
            <a href="<?= $whatsapp_url ?>" target="_blank" class="whatsapp-btn">
                Hubungi WhatsApp untuk Konsultasi
            </a>
            <button onclick="downloadPDF()" class="pdf-btn">
                Simpan ke PDF
            </button>
            <a href="bmi.php" class="back-btn">Hitung Ulang / Kembali</a>
        </div>
    </div>

    <footer class="main-footer">
        <p>Copyright © 2026 InStaGi | Created With ❤️</p>
    </footer>

    <!-- html2pdf.js library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPDF() {
            const btn = document.querySelector('.pdf-btn');
            const originalText = btn.innerHTML;

            btn.innerHTML = 'Sedang memproses...';
            btn.disabled = true;

            // Scroll ke atas
            window.scrollTo(0, 0);

            const element = document.getElementById('printable-area');
            
            // Tambahkan class khusus cetak
            element.classList.add('is-printing');

            // Tambahkan Header Sementara
            const pdfHeader = document.createElement('div');
            pdfHeader.id = 'temp-pdf-header';
            pdfHeader.style.textAlign = 'center';
            pdfHeader.style.marginBottom = '15px';
            pdfHeader.style.padding = '10px 0 12px 0';
            pdfHeader.style.borderBottom = '2px solid #e74c3c';
            pdfHeader.innerHTML = `
                <img src="assets/logo.png" style="max-width: 80px; margin-bottom: 8px; display: block; margin-left: auto; margin-right: auto;">
                <h1 style="color: #e74c3c; font-size: 16px; margin: 0 0 3px 0; font-family: Arial, sans-serif; font-weight: bold;">Hasil Analisis InStaGi</h1>
                <p style="color: #555; font-size: 11px; margin: 0; font-family: Arial, sans-serif;">Informasi Status Gizi</p>
            `;
            element.insertBefore(pdfHeader, element.firstChild);

            const options = {
                margin: [5, 8, 5, 8],
                filename: 'Hasil_Analisis_Gizi_<?= $nama ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: {
                    scale: 2,
                    useCORS: true,
                    allowTaint: true
                },
                jsPDF: { 
                    unit: 'mm', 
                    format: 'a4', 
                    orientation: 'portrait'
                },
                pagebreak: { after: '#page-break' }
            };

            // Tunggu sebentar agar DOM ter-update
            setTimeout(() => {
                html2pdf().set(options).from(element).save().then(() => {
                    // Cleanup setelah selesai
                    pdfHeader.remove();
                    element.classList.remove('is-printing');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }).catch(err => {
                    console.error('PDF Error:', err);
                    if(document.getElementById('temp-pdf-header')) {
                        document.getElementById('temp-pdf-header').remove();
                    }
                    element.classList.remove('is-printing');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    alert('Gagal membuat PDF. Silakan coba lagi.');
                });
            }, 300);
        }
    </script>
</body>

</html>