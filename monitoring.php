<?php
/**
 * Halaman Monitoring Status Gizi InStaGi.
 * -----------------------------------------------------------------------------
 * Menampilkan RIWAYAT + GRAFIK perkembangan pengukuran milik akun responden.
 *
 * Dua jenis akun:
 *   - tanpa password : masuk dengan Nama + No. HP (cara lama tetap jalan).
 *   - berpassword    : masuk wajib memakai password. Password bisa dibuat saat
 *                      mengisi form Cek Status Gizi, atau dipasang dari halaman
 *                      ini (lihat "Keamanan Akun").
 *
 * CATATAN: halaman ini TIDAK menyentuh rumus perhitungan (includes/gizi.php).
 * Garis panduan pada grafik hanya untuk TAMPILAN; ambang klasifikasi yang
 * sebenarnya tetap di includes/gizi.php dan tidak diubah di sini.
 */
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/responden.php';
require_once 'includes/validasi.php';

$nav_active = 'monitoring';
$instagi_base = htmlspecialchars(instagi_base_url(), ENT_QUOTES, 'UTF-8');

$login_error     = '';
$pesan_sukses    = '';
$pesan_keamanan  = '';

// --- Pembatas percobaan masuk -------------------------------------------------
// Tanpa pembatas, nama & nomor bisa dicoba sebanyak apa pun sampai cocok lalu
// riwayat gizi orang lain terbaca. Aturan sama dengan login admin:
// maksimal 5 percobaan gagal, lalu jeda 15 menit.
if (!isset($_SESSION['responden_attempts']) || !is_array($_SESSION['responden_attempts'])) {
    $_SESSION['responden_attempts'] = ['count' => 0, 'last' => 0];
}
$RESPONDEN_MAX_GAGAL   = 5;
$RESPONDEN_JEJAK_DETIK = 900; // 15 menit

// --- Proses aksi (login / logout / password) ---------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!instagi_csrf_check($_POST['csrf_token'] ?? '')) {
        $login_error = 'Sesi form tidak valid. Silakan muat ulang halaman lalu coba lagi.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'logout') {
            instagi_responden_logout();
            $koneksi->close();
            header('location: monitoring.php');
            exit;
        }

        if ($action === 'login') {
            $nama_input = trim($_POST['nama'] ?? '');
            $hp_input   = trim($_POST['hp'] ?? '');
            $pass_input = (string) ($_POST['password'] ?? '');
            $hp_norm    = instagi_norm_hp($hp_input);

            // Periksa apakah sedang dalam masa jeda.
            $percobaan     = $_SESSION['responden_attempts'];
            $buka_pada     = (int) $percobaan['last'] + $RESPONDEN_JEJAK_DETIK;
            $sedang_dijeda = ((int) $percobaan['count'] >= $RESPONDEN_MAX_GAGAL) && (time() < $buka_pada);

            if ($sedang_dijeda) {
                $sisa = (int) ceil(($buka_pada - time()) / 60);
                $login_error = "Terlalu banyak percobaan masuk. Coba lagi dalam {$sisa} menit.";
            } elseif ($nama_input === '' || $hp_norm === '') {
                $login_error = 'Nama dan No. HP wajib diisi.';
            } elseif (!preg_match('/^0\d{7,14}$/', $hp_norm)) {
                $login_error = 'Format No. HP tidak valid. Contoh: 081234567890.';
            } else {
                $hasil_login = instagi_responden_login($koneksi, $nama_input, $hp_input, $pass_input);

                if ($hasil_login['ok']) {
                    // Berhasil: bersihkan penghitung & lanjutkan ke halaman riwayat.
                    $_SESSION['responden_attempts'] = ['count' => 0, 'last' => 0];
                    $koneksi->close();
                    header('location: monitoring.php');
                    exit;
                }

                // Gagal: catat. Pesan dibedakan hanya sejauh yang sudah diketahui
                // pengguna sendiri (mis. akun ini memang pakai password).
                $percobaan['count'] = (int) $percobaan['count'] + 1;
                $percobaan['last']  = time();
                $_SESSION['responden_attempts'] = $percobaan;

                if ($percobaan['count'] >= $RESPONDEN_MAX_GAGAL) {
                    $login_error = 'Terlalu banyak percobaan masuk. Coba lagi dalam 15 menit.';
                } elseif ($hasil_login['alasan'] === 'password_kosong') {
                    $login_error = 'Akun ini memakai password. Masukkan password Anda.';
                } elseif ($hasil_login['alasan'] === 'password_salah') {
                    $login_error = 'Password tidak cocok. Silakan coba lagi.';
                } else {
                    $login_error = 'Nama atau No. HP tidak cocok dengan data kami. '
                        . 'Pastikan sama seperti saat mengisi form Cek Status Gizi.';
                }
            }
        }

        if ($action === 'password') {
            $sudah_masuk = instagi_responden_saat_ini();
            if (!$sudah_masuk) {
                $pesan_keamanan = 'Silakan masuk ke akun Anda terlebih dahulu.';
            } else {
                $lama = (string) ($_POST['password_lama'] ?? '');
                $baru = (string) ($_POST['password_baru'] ?? '');
                $ulang = (string) ($_POST['password_ulang'] ?? '');

                $salah = instagi_validasi_password($baru, $ulang);
                if ($salah !== '') {
                    $pesan_keamanan = $salah;
                } else {
                    $salah = instagi_responden_ganti_password($koneksi, (int) $sudah_masuk['id'], $lama, $baru);
                    if ($salah === '') {
                        $pesan_sukses = 'Password akun berhasil disimpan. Simpan baik-baik dan jangan dibagikan.';
                    } else {
                        $pesan_keamanan = $salah;
                    }
                }
            }
        }
    }
}

// --- Ambil data riwayat bila responden sudah login ---------------------------
$responden = instagi_responden_saat_ini();
$riwayat   = [];
$akun_punya_password = false;
$stat = [
    'jumlah' => 0, 'imt_terakhir' => null, 'status_terakhir' => null,
    'tanggal_terakhir' => null, 'imt_rata' => null, 'imt_min' => null,
    'imt_max' => null, 'selisih_imt' => null, 'selisih_bb' => null, 'arah' => '',
];

if ($responden) {
    $rid = (int) $responden['id'];

    // Status keamanan akun (sudah pakai password atau belum).
    $stmt = $koneksi->prepare("SELECT password_hash FROM responden WHERE id = ? LIMIT 1");
    if ($stmt !== false) {
        $stmt->bind_param('i', $rid);
        $stmt->execute();
        $res = $stmt->get_result();
        $baris_akun = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        $akun_punya_password = !empty($baris_akun['password_hash']);
    }

    // Riwayat diambil dari yang TERLAMA ke terbaru supaya grafik tersusun
    // kronologis; daftar tampilan dibalik belakangan.
    $stmt = $koneksi->prepare(
        "SELECT tanggal, berat_badan, tinggi_badan, imt, status_gizi, kalori, saran
         FROM imt_history WHERE responden_id = ? ORDER BY tanggal ASC, id ASC"
    );
    if ($stmt !== false) {
        $stmt->bind_param('i', $rid);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $riwayat[] = $row;
        }
        $stmt->close();
    }

    if (!empty($riwayat)) {
        $imt_nilai = [];
        $bb_nilai  = [];
        foreach ($riwayat as $r) {
            $imt_nilai[] = (float) $r['imt'];
            $bb_nilai[]  = (float) $r['berat_badan'];
        }
        $terakhir = $riwayat[count($riwayat) - 1];

        $stat['jumlah']           = count($riwayat);
        $stat['imt_terakhir']     = $terakhir['imt'];
        $stat['status_terakhir']  = $terakhir['status_gizi'];
        $stat['tanggal_terakhir'] = $terakhir['tanggal'];
        $stat['imt_rata']         = round(array_sum($imt_nilai) / count($imt_nilai), 1);
        $stat['imt_min']          = min($imt_nilai);
        $stat['imt_max']          = max($imt_nilai);

        if (count($riwayat) >= 2) {
            $sebelumnya = $riwayat[count($riwayat) - 2];
            $stat['selisih_imt'] = round((float) $terakhir['imt'] - (float) $sebelumnya['imt'], 1);
            $stat['selisih_bb']  = round((float) $terakhir['berat_badan'] - (float) $sebelumnya['berat_badan'], 1);
            $stat['arah'] = ($stat['selisih_imt'] > 0) ? 'naik' : (($stat['selisih_imt'] < 0) ? 'turun' : 'stabil');
        }
    }
}

$koneksi->close();

// Kelas CSS status dari teks status gizi (sama dengan admin.php).
if (!function_exists('instagi_status_class')) {
    function instagi_status_class($status_gizi_db)
    {
        if (strpos($status_gizi_db, '(underweight)') !== false) {
            return 'underweight';
        }
        if (strpos($status_gizi_db, '(ideal)') !== false) {
            return 'normal';
        }
        if (strpos($status_gizi_db, '(overweight)') !== false) {
            return 'overweight';
        }
        if ($status_gizi_db === 'Obesitas') {
            return 'obese';
        }
        return '';
    }
}

// Warna titik grafik per status gizi (samakan dengan warna kartu status di CSS).
if (!function_exists('instagi_warna_status')) {
    function instagi_warna_status($status_gizi_db)
    {
        switch (instagi_status_class($status_gizi_db)) {
            case 'underweight': return '#3498db';
            case 'normal':      return '#00b894';
            case 'overweight':  return '#e67e22';
            case 'obese':       return '#e74c3c';
            default:            return '#95a5a6';
        }
    }
}

// --- Data grafik (maksimal 12 pengukuran terakhir agar tetap terbaca) --------
$grafik = ['label' => [], 'imt' => [], 'warna' => []];
foreach (array_slice($riwayat, -12) as $r) {
    $grafik['label'][] = date('d/m/y', strtotime($r['tanggal']));
    $grafik['imt'][]   = (float) $r['imt'];
    $grafik['warna'][] = instagi_warna_status((string) $r['status_gizi']);
}

// Garis panduan grafik. HANYA untuk tampilan (bukan sumber klasifikasi).
$grafik_ambang = [
    ['nilai' => 18.5, 'label' => '18,5', 'warna' => '#3498db'],
    ['nilai' => 25.0, 'label' => '25',   'warna' => '#e67e22'],
    ['nilai' => 27.0, 'label' => '27',   'warna' => '#e74c3c'],
];

$riwayat_tampil = array_reverse($riwayat); // terbaru di atas
$csrf_token = instagi_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InStaGi - Monitoring Status Gizi</title>
    <link rel="shortcut icon" href="assets/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/imt.css">
    <link rel="stylesheet" href="css/result.css">
    <link rel="stylesheet" href="css/nav.css">
    <link rel="stylesheet" href="css/pages.css">
    <meta name="description" content="Lihat riwayat dan grafik perkembangan status gizi (IMT) milik akun Anda dari waktu ke waktu melalui InStaGi.">
    <meta name="theme-color" content="#e74c3c">
</head>
<body>
    <?php require __DIR__ . '/includes/nav.php'; ?>

    <div class="container">
        <h1>Monitoring Status Gizi</h1>

        <?php if (!$responden): ?>
            <!-- ===== Masuk ke akun ===== -->
            <div class="account-box">
                <h3>Masuk ke akun Anda</h3>
                <p>Masukkan <strong>Nama</strong> dan <strong>No. HP</strong> yang sama seperti saat mengisi
                   form Cek Status Gizi. Setiap nama + nomor HP adalah satu akun, sehingga Anda hanya melihat
                   riwayat milik Anda sendiri.</p>

                <?php if ($login_error): ?>
                    <div class="error-message" style="margin:12px 0;"><?= htmlspecialchars($login_error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form method="post" action="monitoring.php" class="account-form-3">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <label for="nama">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" maxlength="100" autocomplete="name" required>
                    </div>
                    <div class="form-group">
                        <label for="hp">No. HP</label>
                        <input type="tel" id="hp" name="hp" inputmode="tel" autocomplete="tel"
                               placeholder="081234567890" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password <span class="label-opsional">(bila akun Anda memakai password)</span></label>
                        <input type="password" id="password" name="password" autocomplete="current-password"
                               placeholder="Kosongkan bila belum memakai password">
                    </div>
                    <div class="form-group form-group-tombol">
                        <button type="submit" class="btn-primary">Masuk</button>
                    </div>
                </form>

                <p class="catatan-akun">
                    Belum punya akun? Akun dibuat otomatis saat Anda
                    <a href="imt.php">cek status gizi</a>.
                    Lupa password? Hubungi kami lewat
                    <a href="<?= htmlspecialchars(instagi_wa_url('Halo InStaGi, saya lupa password akun Monitoring saya. Nama saya: ', INSTAGI_WA_KONSULTASI), ENT_QUOTES, 'UTF-8') ?>"
                       target="_blank" rel="noopener">WhatsApp</a>.
                </p>
            </div>

        <?php else: ?>
            <!-- ===== Ringkasan akun ===== -->
            <div class="account-box">
                <h3>Akun: <?= htmlspecialchars($responden['nama'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p>No. HP: <?= htmlspecialchars($responden['no_hp'], ENT_QUOTES, 'UTF-8') ?></p>
                <p>
                   <?php if ($akun_punya_password): ?>
                       <span class="badge-aman">🔒 Akun aman (memakai password)</span>
                   <?php else: ?>
                       <span class="badge-perhatian">⚠️ Belum memakai password</span>
                   <?php endif; ?>
                </p>
                <div class="account-actions">
                    <a class="btn-primary" href="imt.php">+ Ukur Status Gizi Baru</a>
                    <form method="post" action="monitoring.php" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="btn-secondary">Keluar</button>
                    </form>
                </div>
            </div>

            <?php if ($pesan_sukses): ?>
                <div class="message success-message"><?= htmlspecialchars($pesan_sukses, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($stat['jumlah'] > 0): ?>
                <h2 class="mon-judul">Ringkasan</h2>
                <div class="mon-summary">
                    <div class="mon-stat">
                        <span class="mon-stat-label">Jumlah Pengukuran</span>
                        <span class="mon-stat-value"><?= (int) $stat['jumlah'] ?></span>
                    </div>
                    <div class="mon-stat">
                        <span class="mon-stat-label">IMT Terakhir</span>
                        <span class="mon-stat-value"><?= htmlspecialchars($stat['imt_terakhir']) ?></span>
                    </div>
                    <div class="mon-stat">
                        <span class="mon-stat-label">Status Terakhir</span>
                        <span class="mon-stat-value" style="font-size:1em;">
                            <span class="status <?= htmlspecialchars(instagi_status_class((string) $stat['status_terakhir'])) ?>">
                                <?= htmlspecialchars($stat['status_terakhir']) ?>
                            </span>
                        </span>
                    </div>
                    <div class="mon-stat">
                        <span class="mon-stat-label">Rata-rata IMT</span>
                        <span class="mon-stat-value"><?= htmlspecialchars($stat['imt_rata']) ?></span>
                    </div>
                    <div class="mon-stat">
                        <span class="mon-stat-label">IMT Terendah &ndash; Tertinggi</span>
                        <span class="mon-stat-value" style="font-size:1.15em;">
                            <?= htmlspecialchars($stat['imt_min']) ?> &ndash; <?= htmlspecialchars($stat['imt_max']) ?>
                        </span>
                    </div>
                    <div class="mon-stat">
                        <span class="mon-stat-label">Perubahan IMT Terakhir</span>
                        <span class="mon-stat-value" style="font-size:1.15em;">
                            <?php if ($stat['selisih_imt'] === null): ?>
                                <span class="mon-naik-turun stabil">&ndash;</span>
                            <?php else: ?>
                                <span class="mon-naik-turun <?= htmlspecialchars($stat['arah']) ?>">
                                    <?= ($stat['selisih_imt'] > 0 ? '+' : '') . htmlspecialchars($stat['selisih_imt']) ?>
                                </span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="mon-stat">
                        <span class="mon-stat-label">Tanggal Terakhir</span>
                        <span class="mon-stat-value" style="font-size:1.1em;">
                            <?= htmlspecialchars(date('d-m-Y', strtotime($stat['tanggal_terakhir']))) ?>
                        </span>
                    </div>
                </div>

                <!-- ===== Grafik perkembangan IMT ===== -->
                <h2 class="mon-judul">Grafik Perkembangan IMT</h2>
                <div class="mon-chart-card">
                    <div class="mon-chart-wrap">
                        <canvas id="grafikImt" role="img"
                                aria-label="Grafik perkembangan nilai IMT Anda dari waktu ke waktu"></canvas>
                    </div>
                    <p class="mon-chart-fallback" id="grafikFallback" hidden>
                        Grafik tidak dapat dimuat (perpustakaan grafik tidak tersedia atau koneksi terbatas).
                        Nilai IMT tiap pengukuran tetap bisa dibaca pada daftar riwayat di bawah.
                    </p>
                    <p class="mon-chart-catatan">
                        Garis putus-putus adalah batas kategori: <strong>18,5</strong> (kurus/normal),
                        <strong>25</strong> (normal/gemuk), dan <strong>27</strong> (gemuk/obesitas).
                        Warna titik mengikuti status gizi saat itu.
                        <?php if ($stat['jumlah'] > 12): ?>Ditampilkan 12 pengukuran terakhir.<?php endif; ?>
                    </p>
                </div>

                <h2 class="mon-judul">Riwayat Pengukuran</h2>
                <div class="mon-list">
                    <?php foreach ($riwayat_tampil as $r): ?>
                    <article class="mon-card">
                        <div class="mon-card-head">
                            <span class="mon-date"><?= htmlspecialchars(date('d-m-Y', strtotime($r['tanggal']))) ?></span>
                            <span class="status <?= htmlspecialchars(instagi_status_class((string) $r['status_gizi'])) ?>">
                                <?= htmlspecialchars($r['status_gizi']) ?>
                            </span>
                        </div>
                        <div class="mon-grid">
                            <div><span>IMT</span><br><strong><?= htmlspecialchars($r['imt']) ?></strong></div>
                            <div><span>Berat Badan</span><br><strong><?= htmlspecialchars($r['berat_badan']) ?> kg</strong></div>
                            <div><span>Tinggi Badan</span><br><strong><?= htmlspecialchars($r['tinggi_badan']) ?> cm</strong></div>
                            <div><span>Kalori</span><br><strong><?= htmlspecialchars($r['kalori']) ?> kkal</strong></div>
                        </div>
                        <?php if (!empty($r['saran'])): ?>
                        <div class="mon-saran"><?= htmlspecialchars($r['saran']) ?></div>
                        <?php endif; ?>
                    </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="mon-empty">
                    <p>Belum ada riwayat pengukuran untuk akun ini.</p>
                    <p><a class="btn-primary" href="imt.php">Mulai Cek Status Gizi</a></p>
                </div>
            <?php endif; ?>

            <!-- ===== Keamanan akun (password) ===== -->
            <h2 class="mon-judul">Keamanan Akun</h2>
            <div class="account-box">
                <?php if ($akun_punya_password): ?>
                    <h3>Ganti password</h3>
                    <p>Akun Anda sudah memakai password. Untuk menggantinya, masukkan password lama Anda.</p>
                <?php else: ?>
                    <h3>Pasang password (disarankan)</h3>
                    <p>Akun Anda saat ini hanya dilindungi Nama + No. HP, sehingga siapa pun yang tahu keduanya
                       bisa melihat riwayat. Pasang password agar riwayat Anda lebih aman.</p>
                <?php endif; ?>

                <?php if ($pesan_keamanan): ?>
                    <div class="error-message" style="margin:12px 0;"><?= htmlspecialchars($pesan_keamanan, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form method="post" action="monitoring.php" class="account-form-3">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="password">
                    <?php if ($akun_punya_password): ?>
                        <div class="form-group">
                            <label for="password_lama">Password Lama</label>
                            <input type="password" id="password_lama" name="password_lama" autocomplete="current-password" required>
                        </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="password_baru">Password Baru</label>
                        <input type="password" id="password_baru" name="password_baru" minlength="8"
                               autocomplete="new-password" required>
                        <small class="field-hint">Minimal 8 karakter</small>
                    </div>
                    <div class="form-group">
                        <label for="password_ulang">Ulangi Password Baru</label>
                        <input type="password" id="password_ulang" name="password_ulang" minlength="8"
                               autocomplete="new-password" required>
                    </div>
                    <div class="form-group form-group-tombol">
                        <button type="submit" class="btn-primary">Simpan Password</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <footer class="main-footer">
        <p>Copyright © 2026 InStaGi | Created With ❤️</p>
    </footer>

    <?php if ($responden && $stat['jumlah'] > 0): ?>
    <!-- Grafik memakai Chart.js dari CDN; bila gagal dimuat, halaman tetap utuh
         dan daftar riwayat tetap bisa dibaca (lihat grafikFallback). -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            var data = <?= json_encode($grafik, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
            var ambang = <?= json_encode($grafik_ambang, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
            var kanvas = document.getElementById('grafikImt');
            var fallback = document.getElementById('grafikFallback');

            if (!kanvas || typeof Chart === 'undefined' || !data.label.length) {
                if (fallback) { fallback.hidden = false; }
                return;
            }

            // Garis panduan kategori (tampilan saja).
            var garisPanduan = {
                id: 'garisPanduan',
                afterDatasetsDraw: function (chart, args, opsi) {
                    var area = chart.chartArea;
                    var skala = chart.scales.y;
                    (opsi.lines || []).forEach(function (l) {
                        var y = skala.getPixelForValue(l.nilai);
                        if (y < area.top || y > area.bottom) { return; }
                        var ctx = chart.ctx;
                        ctx.save();
                        ctx.setLineDash([6, 6]);
                        ctx.strokeStyle = l.warna || '#999';
                        ctx.lineWidth = 1.2;
                        ctx.beginPath();
                        ctx.moveTo(area.left, y);
                        ctx.lineTo(area.right, y);
                        ctx.stroke();
                        ctx.setLineDash([]);
                        ctx.fillStyle = l.warna || '#999';
                        ctx.font = '11px sans-serif';
                        ctx.fillText(l.label, area.left + 6, y - 4);
                        ctx.restore();
                    });
                }
            };

            new Chart(kanvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: data.label,
                    datasets: [{
                        label: 'IMT',
                        data: data.imt,
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.10)',
                        borderWidth: 2,
                        tension: 0.25,
                        fill: true,
                        pointBackgroundColor: data.warna,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) { return 'IMT: ' + ctx.parsed.y; }
                            }
                        },
                        garisPanduan: { lines: ambang }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            suggestedMin: Math.max(10, Math.floor(Math.min.apply(null, data.imt) - 2)),
                            suggestedMax: Math.ceil(Math.max.apply(null, data.imt) + 2),
                            title: { display: true, text: 'IMT' }
                        },
                        x: { title: { display: true, text: 'Tanggal pengukuran' } }
                    }
                },
                plugins: [garisPanduan]
            });
        })();
    </script>
    <?php endif; ?>
</body>
</html>
