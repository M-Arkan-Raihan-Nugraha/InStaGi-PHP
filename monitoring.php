<?php
/**
 * Halaman Monitoring InStaGi.
 * -----------------------------------------------------------------------------
 * Menampilkan RIWAYAT pengukuran milik akun responden sendiri.
 *
 * Akun responden memakai prinsip "1 nama + 1 nomor HP = 1 akun" (tanpa password).
 * Akun dibuat otomatis saat responden mengisi form di imt.php. Di halaman ini
 * responden bisa "login" memakai Nama + No. HP untuk melihat riwayatnya.
 *
 * CATATAN: halaman ini TIDAK menyentuh rumus perhitungan (includes/gizi.php).
 */
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/responden.php';

$nav_active = 'monitoring';
$instagi_base = htmlspecialchars(instagi_base_url(), ENT_QUOTES, 'UTF-8');

$login_error = '';

// --- Pembatas percobaan masuk (anti tebak nama + No. HP) ---------------------
// Halaman ini memakai prinsip "1 nama + 1 nomor HP = 1 akun" tanpa password,
// jadi tanpa pembatas, nama & nomor bisa dicoba sebanyak apa pun sampai cocok
// dan riwayat gizi orang lain terbaca. Aturan sama dengan login admin:
// maksimal 5 percobaan gagal, lalu jeda 15 menit.
if (!isset($_SESSION['responden_attempts']) || !is_array($_SESSION['responden_attempts'])) {
    $_SESSION['responden_attempts'] = ['count' => 0, 'last' => 0];
}
$RESPONDEN_MAX_GAGAL   = 5;
$RESPONDEN_JEJAK_DETIK = 900; // 15 menit

// --- Proses aksi (login / logout responden) ---
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
            $hp_norm    = instagi_norm_hp($hp_input);

            // Periksa apakah sedang dalam masa jeda.
            $percobaan    = $_SESSION['responden_attempts'];
            $buka_pada    = (int) $percobaan['last'] + $RESPONDEN_JEJAK_DETIK;
            $sedang_dijeda = ((int) $percobaan['count'] >= $RESPONDEN_MAX_GAGAL) && (time() < $buka_pada);

            if ($sedang_dijeda) {
                $sisa = (int) ceil(($buka_pada - time()) / 60);
                $login_error = "Terlalu banyak percobaan masuk. Coba lagi dalam {$sisa} menit.";
            } elseif ($nama_input === '' || $hp_norm === '') {
                $login_error = 'Nama dan No. HP wajib diisi.';
            } elseif (!preg_match('/^0\d{7,14}$/', $hp_norm)) {
                $login_error = 'Format No. HP tidak valid. Contoh: 081234567890.';
            } elseif (instagi_responden_login($koneksi, $nama_input, $hp_input)) {
                // Berhasil: bersihkan penghitung & lanjutkan ke halaman riwayat.
                $_SESSION['responden_attempts'] = ['count' => 0, 'last' => 0];
                $koneksi->close();
                header('location: monitoring.php');
                exit;
            } else {
                // Gagal: catat. Pesan sengaja tetap sama untuk semua kegagalan
                // agar tidak memudahkan menebak nama/nomor mana yang terdaftar.
                $percobaan['count'] = (int) $percobaan['count'] + 1;
                $percobaan['last']  = time();
                $_SESSION['responden_attempts'] = $percobaan;

                if ($percobaan['count'] >= $RESPONDEN_MAX_GAGAL) {
                    $login_error = 'Terlalu banyak percobaan masuk. Coba lagi dalam 15 menit.';
                } else {
                    $login_error = 'Nama atau No. HP tidak cocok dengan data kami. '
                        . 'Pastikan sama seperti saat mengisi form IMT.';
                }
            }
        }
    }
}

// --- Ambil data riwayat bila responden sudah login ---
$responden = instagi_responden_saat_ini();
$riwayat = [];
$stat = ['jumlah' => 0, 'imt_terakhir' => null, 'status_terakhir' => null, 'tanggal_terakhir' => null];

if ($responden) {
    $rid = (int) $responden['id'];
    $stmt = $koneksi->prepare(
        "SELECT tanggal, berat_badan, tinggi_badan, imt, status_gizi, kalori, saran
         FROM imt_history WHERE responden_id = ? ORDER BY tanggal DESC, id DESC"
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
        $stat['jumlah'] = count($riwayat);
        $stat['imt_terakhir'] = $riwayat[0]['imt'];
        $stat['status_terakhir'] = $riwayat[0]['status_gizi'];
        $stat['tanggal_terakhir'] = $riwayat[0]['tanggal'];
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
    <meta name="description" content="Lihat riwayat pengukuran status gizi (IMT) milik akun Anda dari waktu ke waktu melalui InStaGi.">
    <meta name="theme-color" content="#e74c3c">
</head>
<body>
    <?php require __DIR__ . '/includes/nav.php'; ?>

    <div class="container">
        <h1>Monitoring Status Gizi</h1>

        <?php if (!$responden): ?>
            <!-- ===== Form akun responden (Nama + No. HP) ===== -->
            <div class="account-box">
                <h3>Masuk ke akun Anda</h3>
                <p>Masukkan <strong>Nama</strong> dan <strong>No. HP</strong> yang sama seperti saat mengisi form IMT.
                   Setiap nama + nomor HP adalah satu akun, sehingga Anda hanya melihat riwayat milik Anda sendiri.</p>

                <?php if ($login_error): ?>
                    <div class="error-message" style="margin:12px 0;"><?= htmlspecialchars($login_error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form method="post" action="monitoring.php" class="account-form">
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
                    <button type="submit" class="btn-primary">Masuk</button>
                </form>
                <p style="margin-top:12px;font-size:0.9em;">Belum punya akun? Akun dibuat otomatis saat Anda
                   <a href="imt.php">menghitung IMT</a>.</p>
            </div>
        <?php else: ?>
            <!-- ===== Ringkasan akun ===== -->
            <div class="account-box">
                <h3>Akun: <?= htmlspecialchars($responden['nama'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p>No. HP: <?= htmlspecialchars($responden['no_hp'], ENT_QUOTES, 'UTF-8') ?></p>
                <div class="account-actions">
                    <a class="btn-primary" href="imt.php">+ Ukur IMT Baru</a>
                    <form method="post" action="monitoring.php" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="btn-secondary">Keluar</button>
                    </form>
                </div>
            </div>

            <?php if ($stat['jumlah'] > 0): ?>
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
                        <span class="mon-stat-label">Tanggal Terakhir</span>
                        <span class="mon-stat-value" style="font-size:1.1em;">
                            <?= htmlspecialchars(date('d-m-Y', strtotime($stat['tanggal_terakhir']))) ?>
                        </span>
                    </div>
                </div>

                <h2 style="text-align:left;">Riwayat Pengukuran</h2>
                <div class="mon-list">
                    <?php foreach ($riwayat as $r): ?>
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
                    <p><a class="btn-primary" href="imt.php">Mulai Hitung IMT</a></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <footer class="main-footer">
        <p>Copyright © 2026 InStaGi | Created With ❤️</p>
    </footer>
</body>
</html>
