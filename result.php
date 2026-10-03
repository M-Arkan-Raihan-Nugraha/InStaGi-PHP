<?php
// Session aman (HttpOnly/SameSite/Secure + strict mode) — sekaligus memuat config.php.
require_once 'includes/session.php';

// Modul leaflet informasi gizi (data galeri + nama file unduhan)
require_once 'includes/leaflet.php';

// Modul DBMP — Daftar Bahan Makanan Penukar (satu berkas PDF)
require_once 'includes/dbmp.php';

// Redirect jika tidak ada data hasil analisis di session
if (!isset($_SESSION['imt_result'])) {
    header("location: bmi.php");
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

// --- Siapkan daftar leaflet informasi gizi yang tersedia ---
// Leaflet yang file gambarnya belum ada otomatis dilewati (tidak menimbulkan error),
// jadi galeri tetap aman walau sebagian gambar belum diunggah.
$leaflets = [];
foreach (instagi_leaflet_list() as $slug => $meta) {
    $thumb = instagi_leaflet_thumb_url($slug);
    $full  = instagi_leaflet_web_url($slug);
    if ($thumb === null || $full === null) {
        continue;
    }
    $leaflets[] = [
        'judul'    => $meta['judul'],
        'subjudul' => $meta['subjudul'],
        'ringkas'  => $meta['ringkas'],
        'tag'      => $meta['tag'],
        'thumb'    => $thumb,
        'full'     => $full,
        'unduh'    => $meta['unduh'],
    ];
}

// --- Siapkan berkas DBMP (Daftar Bahan Makanan Penukar) ---
// Satu berkas PDF gabungan; bagian ini otomatis dilewati bila PDF belum ada.
$dbmp_pdf   = instagi_dbmp_pdf();
$dbmp_cover = instagi_dbmp_cover();

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
            <h3 class="disclaimer">
                Bila Anda ingin mendapatkan informasi gizi lebih lanjut dan membutuhkan layanan katering diet silahkan
                menghubungi nomor WA <?= htmlspecialchars(INSTAGI_WA_LABEL) ?>: <?= htmlspecialchars($whatsapp_display) ?>
            </h3>
        </div>

        <?php if (!empty($leaflets)): ?>
        <!-- ===== LEAFLET INFORMASI GIZI ===== -->
        <section class="leaflet-section" id="leaflet">
            <h2 class="leaflet-heading">Leaflet Informasi Gizi</h2>
            <p class="leaflet-intro">
                Klik salah satu leaflet untuk melihat versi besarnya, lalu unduh bila ingin disimpan
                atau dibagikan. Cocok dibaca di ponsel maupun dicetak.
            </p>

            <div class="leaflet-grid">
                <?php foreach ($leaflets as $i => $lf): ?>
                <article class="leaflet-card">
                    <button type="button"
                            class="leaflet-thumb"
                            data-index="<?= $i ?>"
                            aria-label="Lihat leaflet: <?= htmlspecialchars($lf['judul']) ?>">
                        <img src="<?= htmlspecialchars($lf['thumb']) ?>"
                             alt="Leaflet <?= htmlspecialchars($lf['judul']) ?>"
                             loading="lazy" decoding="async">
                        <span class="leaflet-zoom" aria-hidden="true">&#128269; Lihat</span>
                    </button>
                    <div class="leaflet-body">
                        <span class="leaflet-tag"><?= htmlspecialchars($lf['tag']) ?></span>
                        <h3 class="leaflet-title"><?= htmlspecialchars($lf['judul']) ?></h3>
                        <p class="leaflet-sub"><?= htmlspecialchars($lf['subjudul']) ?></p>
                        <p class="leaflet-desc"><?= htmlspecialchars($lf['ringkas']) ?></p>
                        <div class="leaflet-actions">
                            <button type="button" class="leaflet-btn leaflet-btn-view" data-index="<?= $i ?>">
                                Lihat
                            </button>
                            <a class="leaflet-btn leaflet-btn-download"
                               href="<?= htmlspecialchars($lf['full']) ?>"
                               download="<?= htmlspecialchars($lf['unduh']) ?>">
                                Unduh Gambar
                            </a>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Lightbox pratinjau leaflet -->
        <div class="leaflet-lightbox" id="leafletLightbox" role="dialog" aria-modal="true" aria-label="Pratinjau leaflet" hidden>
            <div class="leaflet-lb-backdrop" data-lb-close></div>
            <div class="leaflet-lb-panel">
                <div class="leaflet-lb-head">
                    <div class="leaflet-lb-meta">
                        <strong id="lbTitle">&nbsp;</strong>
                        <span id="lbSub">&nbsp;</span>
                    </div>
                    <div class="leaflet-lb-tools">
                        <span class="leaflet-lb-counter" id="lbCounter"></span>
                        <button type="button" class="leaflet-lb-icon" data-lb-prev aria-label="Leaflet sebelumnya">&#10094;</button>
                        <button type="button" class="leaflet-lb-icon" data-lb-next aria-label="Leaflet berikutnya">&#10095;</button>
                        <a class="leaflet-lb-icon leaflet-lb-download" id="lbDownload" href="#" download aria-label="Unduh leaflet">&#11015;</a>
                        <button type="button" class="leaflet-lb-icon leaflet-lb-close" data-lb-close aria-label="Tutup">&times;</button>
                    </div>
                </div>
                <div class="leaflet-lb-stage">
                    <img id="lbImage" src="" alt="">
                </div>
                <div class="leaflet-lb-foot">
                    <a class="leaflet-lb-open" id="lbOpen" href="#" target="_blank" rel="noopener">Buka di tab baru</a>
                    <span class="leaflet-lb-hint">Gunakan tombol &#10094; &#10095; untuk berpindah leaflet</span>
                </div>
            </div>
        </div>
        <script>
            window.INSTAGI_LEAFLETS = <?= json_encode(
                array_map(static function ($lf) {
                    return [
                        'title'    => $lf['judul'],
                        'sub'      => $lf['subjudul'],
                        'full'     => $lf['full'],
                        'download' => $lf['unduh'],
                    ];
                }, $leaflets),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP
            ) ?>;
        </script>
        <?php endif; ?>

        <?php if ($dbmp_pdf !== null): ?>
        <!-- ===== DBMP — DAFTAR BAHAN MAKANAN PENUKAR (1 berkas PDF) ===== -->
        <section class="dbmp-section" id="dbmp">
            <h2 class="dbmp-heading">Daftar Bahan Makanan Penukar (DBMP)</h2>
            <p class="dbmp-intro">
                Panduan menukar bahan makanan dengan nilai gizi setara, disusun per golongan pangan
                untuk membantu menyusun menu diet sehat dan seimbang.
            </p>

            <article class="dbmp-card">
                <div class="dbmp-cover">
                    <?php if ($dbmp_cover !== null): ?>
                    <img src="<?= htmlspecialchars($dbmp_cover) ?>"
                         alt="Sampul Daftar Bahan Makanan Penukar (DBMP)"
                         loading="lazy" decoding="async">
                    <?php else: ?>
                    <div class="dbmp-cover-fallback" aria-hidden="true">DBMP</div>
                    <?php endif; ?>
                    <span class="dbmp-badge"><?= (int) instagi_dbmp_jumlah_halaman() ?> halaman</span>
                </div>
                <div class="dbmp-body">
                    <span class="dbmp-tag">Panduan Diet</span>
                    <h3 class="dbmp-title">Daftar Bahan Makanan Penukar</h3>
                    <p class="dbmp-sub">Satu berkas PDF — 8 golongan bahan makanan</p>
                    <p class="dbmp-desc">
                        Memuat ukuran rumah tangga (URT), pengertian 1 satuan penukar, serta daftar
                        bahan makanan per golongan: karbohidrat, protein hewani, protein nabati,
                        sayuran, buah &amp; gula, susu, minyak &amp; lemak, hingga makanan tanpa kalori.
                    </p>
                    <div class="dbmp-actions">
                        <a class="dbmp-btn dbmp-btn-view"
                           href="<?= htmlspecialchars($dbmp_pdf) ?>"
                           target="_blank" rel="noopener">
                            Lihat PDF
                        </a>
                        <a class="dbmp-btn dbmp-btn-download"
                           href="<?= htmlspecialchars($dbmp_pdf) ?>"
                           download="DBMP-InStaGi-Daftar-Bahan-Makanan-Penukar.pdf">
                            Unduh PDF
                        </a>
                    </div>
                </div>
            </article>
        </section>
        <?php endif; ?>

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

    <?php if (!empty($leaflets)): ?>
    <script>
        /* ===== Galeri & pratinjau leaflet informasi gizi ===== */
        (function () {
            var data = window.INSTAGI_LEAFLETS || [];
            var box = document.getElementById('leafletLightbox');
            if (!data.length || !box) { return; }

            var elImage   = document.getElementById('lbImage');
            var elTitle   = document.getElementById('lbTitle');
            var elSub     = document.getElementById('lbSub');
            var elCounter = document.getElementById('lbCounter');
            var elDown    = document.getElementById('lbDownload');
            var elOpen    = document.getElementById('lbOpen');
            var current   = 0;
            var lastFocus = null;

            function render(i) {
                if (i < 0) { i = data.length - 1; }
                if (i >= data.length) { i = 0; }
                current = i;

                var item = data[i];
                elImage.src = item.full;
                elImage.alt = 'Leaflet ' + item.title;
                elTitle.textContent = item.title;
                elSub.textContent = item.sub || '';
                elCounter.textContent = (i + 1) + ' / ' + data.length;
                elDown.href = item.full;
                elDown.setAttribute('download', item.download || '');
                elOpen.href = item.full;
            }

            function open(i) {
                lastFocus = document.activeElement;
                render(i);
                box.hidden = false;
                document.body.classList.add('leaflet-open');
                box.querySelector('.leaflet-lb-close').focus();
            }

            function close() {
                box.hidden = true;
                document.body.classList.remove('leaflet-open');
                elImage.src = '';
                if (lastFocus && typeof lastFocus.focus === 'function') { lastFocus.focus(); }
            }

            // Buka dari kartu (thumbnail) maupun tombol "Lihat"
            document.querySelectorAll('.leaflet-thumb, .leaflet-btn-view').forEach(function (el) {
                el.addEventListener('click', function () {
                    open(parseInt(el.getAttribute('data-index'), 10) || 0);
                });
            });

            box.querySelectorAll('[data-lb-close]').forEach(function (el) {
                el.addEventListener('click', close);
            });
            box.querySelector('[data-lb-prev]').addEventListener('click', function () { render(current - 1); });
            box.querySelector('[data-lb-next]').addEventListener('click', function () { render(current + 1); });

            // Navigasi keyboard
            document.addEventListener('keydown', function (e) {
                if (box.hidden) { return; }
                if (e.key === 'Escape')     { close(); }
                if (e.key === 'ArrowLeft')  { render(current - 1); }
                if (e.key === 'ArrowRight') { render(current + 1); }
            });

            // Geser (swipe) di perangkat sentuh
            var startX = null;
            box.addEventListener('touchstart', function (e) {
                startX = e.changedTouches[0].clientX;
            }, { passive: true });
            box.addEventListener('touchend', function (e) {
                if (startX === null) { return; }
                var dx = e.changedTouches[0].clientX - startX;
                if (Math.abs(dx) > 50) { render(dx < 0 ? current + 1 : current - 1); }
                startX = null;
            }, { passive: true });
        })();
    </script>
    <?php endif; ?>
</body>

</html>