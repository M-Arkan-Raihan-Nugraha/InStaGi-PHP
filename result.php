<?php
// Session aman (HttpOnly/SameSite/Secure + strict mode) — sekaligus memuat config.php.
require_once 'includes/session.php';

// Modul akun responden (untuk tautan ke menu Monitoring)
require_once 'includes/responden.php';

$nav_active = 'hitung';

// Redirect jika tidak ada data hasil analisis di session
if (!isset($_SESSION['imt_result'])) {
    header("location: /imt");
    exit;
}

// Ambil data hasil analisis dari session
$result = $_SESSION['imt_result'];

// Hapus data hasil analisis dari session setelah diambil
// unset($_SESSION['imt_result']); // uncomment this line if you want the results to be displayed only once

// Nilai MENTAH (belum di-escape) dipakai untuk hal non-HTML:
// pesan WhatsApp & nama file PDF. Escaping HTML hanya untuk tampilan.
$nama_raw = (string) $result['nama'];
$imt_raw = (string) $result['imt'];
$status_gizi_raw = (string) $result['status_gizi'];
$saran_raw = (string) $result['saran'];
$kalori_harian_raw = (string) $result['kalori_harian'];

// Ekstrak variabel untuk kemudahan penggunaan (versi HTML-safe)
$nama = htmlspecialchars($nama_raw);
$imt = htmlspecialchars($imt_raw);
$status_gizi = htmlspecialchars($status_gizi_raw);
$status_class = htmlspecialchars($result['status_class']);
$saran = htmlspecialchars($saran_raw);
$bb_ideal = htmlspecialchars($result['bb_ideal']);
$bb_sehat_min = htmlspecialchars($result['bb_sehat_min']);
$bb_sehat_max = htmlspecialchars($result['bb_sehat_max']);
$kalori_harian = htmlspecialchars($kalori_harian_raw);
$no_hp_pengguna = htmlspecialchars($result['no_hp']); // No HP pengguna
$success_message = htmlspecialchars($result['success_message']);

// Nama file PDF yang aman: hanya huruf, angka, spasi, titik, minus, underscore.
$nama_file_pdf = preg_replace('/[^\p{L}\p{N} ._-]+/u', '', $nama_raw);
$nama_file_pdf = trim(str_replace(' ', '_', (string) $nama_file_pdf));
if ($nama_file_pdf === '') {
    $nama_file_pdf = 'Responden';
}

// Nomor WhatsApp tujuan konsultasi — SATU sumber: includes/config.php
// (dapat dioverride lewat environment variable INSTAGI_WA_NUMBER).
$whatsapp_number = INSTAGI_WA_NUMBER;

// Versi tampilan lokal (0…): 6281224948388 -> 081224948388, agar teks disclaimer
// dan tautan wa.me selalu menunjuk nomor yang sama (sebelumnya ditulis dua kali
// dengan format berbeda sehingga rawan tidak sinkron).
$whatsapp_display = (strpos($whatsapp_number, '62') === 0)
    ? '0' . substr($whatsapp_number, 2)
    : $whatsapp_number;

// Pesan WhatsApp yang akan dikirim.
// Memakai nilai MENTAH + http_build_query() agar teks seperti "A & B" tidak
// terkirim sebagai "A &amp; B" (dulu nilai sudah di-htmlspecialchars lalu
// di-urlencode sehingga ter-escape dua kali).
$whatsapp_message = http_build_query([
    'text' => "Halo, saya *{$nama_raw}*. Hasil perhitungan IMT saya adalah *{$imt_raw}* dengan status gizi *{$status_gizi_raw}*. Estimasi Kebutuhan kalori harian saya adalah *{$kalori_harian_raw} kkal*. Saran untuk saya: *{$saran_raw}* Saya ingin konsultasi lebih lanjut.",
]);

// URL WhatsApp
$whatsapp_url = "https://wa.me/{$whatsapp_number}?{$whatsapp_message}";

// Judul, deskripsi, alamat rapi (tanpa .php), dan daftar CSS halaman ini.
$judul     = 'InStaGi - Hasil Analisis IMT';
$deskripsi = 'Hasil analisis IMT dan status gizi Anda, lengkap dengan berat badan ideal, estimasi kebutuhan kalori harian, serta saran gizi praktis dari InStaGi.';
$og_path   = '/result';
$css       = ['result.css', 'nav.css', 'pages.css'];
require __DIR__ . '/includes/layout_head.php';
?>
    <div class="container">
        <div style="text-align: center;" id="page-logo">
            <img src="assets/logo.webp" alt="InStaGi Logo" width="512" height="466" style="max-width: 150px; height: auto; margin-bottom: 10px;">
        </div>
        <h1>Hasil Analisis IMT (Indeks Massa Tubuh) Anda</h1>

        <?php if (!empty($success_message)): ?>
            <div class="message success-message"><?= $success_message ?></div>
        <?php endif; ?>

        <div class="result-container" id="printable-area">
            <h2>Analisis untuk <?= $nama ?></h2>
            <?php
            // Calculate marker position for IMT bar
            $min_imt_scale = 10;
            $max_imt_scale = 40;
            $imt_value_for_bar = max($min_imt_scale, min($max_imt_scale, (float) $imt)); // Cap IMT within scale
            $marker_position = (($imt_value_for_bar - $min_imt_scale) / ($max_imt_scale - $min_imt_scale)) * 100;
            ?>
            <!-- IMT Bar Visualization -->
            <div class="imt-bar-container">
                <div class="imt-segments-wrapper">
                    <div class="imt-segment underweight-segment"></div>
                    <div class="imt-segment normal-segment"></div>
                    <div class="imt-segment overweight-segment"></div>
                    <div class="imt-segment obese-segment"></div>
                </div>
                <div class="imt-marker" style="left: <?= $marker_position ?>%;">
                    <div class="marker-value"><?= $imt ?></div>
                    <div class="marker-arrow"></div>
                </div>
                <div class="imt-threshold-labels">
                    <span style="left: calc(((10 - 10) / 30) * 100%); transform: translateX(-50%);">10</span>
                    <span style="left: calc(((18.5 - 10) / 30) * 100%); transform: translateX(-50%);">18.5</span>
                    <span style="left: calc(((25 - 10) / 30) * 100%); transform: translateX(-50%);">25</span>
                    <span style="left: calc(((27 - 10) / 30) * 100%); transform: translateX(-50%);">27</span>
                    <span style="left: calc(((40 - 10) / 30) * 100%); transform: translateX(-50%);">40</span>
                </div>
                <div class="imt-category-labels">
                    <span class="label-underweight">Kurus</span>
                    <span class="label-normal">Normal</span>
                    <span class="label-overweight">Gemuk</span>
                    <span class="label-obese">Obesitas</span>
                </div>
            </div>
            <!-- End IMT Bar Visualization -->
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
            <p class="skrining-disclaimer">Hasil merupakan skrining awal, bukan diagnosis medis.</p>
            <h3 class="disclaimer">
                Bila Anda ingin mendapatkan informasi gizi lebih lanjut dan membutuhkan layanan katering diet silahkan
                menghubungi nomor WA <?= htmlspecialchars($whatsapp_display) ?>
            </h3>
        </div>

        <div class="action-buttons">
            <a href="<?= $whatsapp_url ?>" target="_blank" class="whatsapp-btn">
                Hubungi WhatsApp untuk Konsultasi
            </a>
            <button onclick="downloadPDF()" class="pdf-btn">
                Simpan ke PDF
            </button>
            <a href="/imt" class="back-btn">Hitung Ulang / Kembali</a>
        </div>

        <?php $responden_aktif = instagi_responden_saat_ini(); ?>
        <div class="account-box">
            <?php if ($responden_aktif): ?>
                <h3>Riwayat tersimpan untuk akun: <?= htmlspecialchars($responden_aktif['nama'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p>Hasil ini otomatis masuk ke riwayat Anda. Lihat perkembangan status gizi Anda di menu Monitoring.</p>
                <div class="account-actions">
                    <a class="back-btn" href="/monitoring">Lihat Monitoring Saya</a>
                </div>
            <?php else: ?>
                <h3>Simpan & pantau hasil Anda</h3>
                <p>Buka menu <strong>Monitoring</strong> lalu masukkan Nama dan No. HP yang sama untuk melihat riwayat pengukuran Anda.</p>
                <div class="account-actions">
                    <a class="back-btn" href="/monitoring">Buka Monitoring</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Banner Menuju Informasi Gizi -->
        <div class="account-box" style="margin-top: 25px; text-align: center;">
            <h3 style="color: var(--primary-color); margin-bottom: 8px;">Pelajari Informasi & Panduan Gizi Lengkap</h3>
            <p style="margin-bottom: 16px;">Temukan Leaflet Informasi Gizi, Contoh Menu Kalori Harian, Kumpulan Resep Sehat, dan Buku DBMP di halaman khusus Informasi Gizi.</p>
            <div class="account-actions" style="justify-content: center;">
                <a class="back-btn" href="/informasi">Buka Halaman Informasi Gizi</a>
            </div>
        </div>
    </div>

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
                <img src="assets/logo.webp" width="512" height="466" alt="Logo InStaGi" style="max-width: 80px; height: auto; margin-bottom: 8px; display: block; margin-left: auto; margin-right: auto;">
                <h1 style="color: #e74c3c; font-size: 16px; margin: 0 0 3px 0; font-family: Arial, sans-serif; font-weight: bold;">Hasil Analisis InStaGi</h1>
                <p style="color: #555; font-size: 11px; margin: 0; font-family: Arial, sans-serif;">Informasi Status Gizi</p>
            `;
            element.insertBefore(pdfHeader, element.firstChild);

            const options = {
                margin: [5, 8, 5, 8],
                filename: 'Hasil_Analisis_Gizi_' + <?= json_encode($nama_file_pdf, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?> + '.pdf',
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

    <?php require __DIR__ . '/includes/layout_footer.php'; ?>