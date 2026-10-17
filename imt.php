<?php
// Session aman + helper CSRF (sekaligus memuat includes/config.php).
require_once 'includes/session.php';

// --- LOGIC PHP UNTUK KALKULATOR IMT ---
// Sisipkan file koneksi untuk menghubungkan ke database.
require_once 'includes/db.php';

// Modul perhitungan gizi (satu sumber kebenaran untuk IMT/kalori/saran).
require_once 'includes/gizi.php';

// Modul akun responden (1 nama + 1 nomor HP = 1 akun) untuk menu Monitoring.
require_once 'includes/responden.php';

// Modul validasi input (satu sumber aturan; dipakai juga oleh API admin
// api/update_record.php agar aturan form publik dan edit admin sama).
require_once 'includes/validasi.php';

$nav_active = 'hitung';

// Inisialisasi variabel hasil agar tidak error saat halaman pertama kali dibuka
$nama = '';
$usia = '';
$jenis_kelamin = '';
$bb = '';
$tb = '';
$no_hp = '';
$tanggal_input = date('Y-m-d'); // Pre-fill dengan tanggal hari ini

// Bila responden sudah "login" (akun Monitoring), isi otomatis nama & No. HP
// agar tidak perlu mengetik ulang. Nilai dari POST tetap menang.
$responden_aktif = instagi_responden_saat_ini();
if ($responden_aktif) {
    $nama  = (string) $responden_aktif['nama'];
    $no_hp = (string) $responden_aktif['no_hp'];
}

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
    // 1. Ambil data dari form APA ADANYA (mentah) untuk mengisi ulang form bila
    //    ada yang tidak valid. CATATAN: data disimpan mentah ke database, TIDAK
    //    di-htmlspecialchars di sini. Escaping hanya dilakukan saat MENAMPILKAN
    //    data (result.php / admin.php). Jika di-escape di sini, nama seperti
    //    "A & B" akan tersimpan sebagai "A &amp; B" dan rusak saat ditampilkan.
    $nama = trim($_POST['nama'] ?? '');
    $usia = $_POST['usia'] ?? '';
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
    $bb = $_POST['bb'] ?? '';
    $tb = $_POST['tb'] ?? '';
    $no_hp = trim($_POST['no_hp'] ?? '');
    $tanggal_input = trim($_POST['tanggal_input'] ?? '');
    if ($tanggal_input === '') {
        $tanggal_input = date('Y-m-d');
    }

    // 2. Validasi input
    //    Termasuk token CSRF: form ini publik, jadi submit lintas situs ditolak.
    //    'submitted' = penjaga klik-ganda: bila tombol terklik dua kali, kiriman
    //    kedua (yang tidak punya penanda) ditolak sehingga data TIDAK tersimpan dua kali.
    if (!instagi_csrf_check($_POST['csrf_token'] ?? '')) {
        $error_message = 'Sesi form tidak valid. Silakan muat ulang halaman lalu coba lagi.';
    } elseif (empty($_POST['submitted'])) {
        $error_message = 'Formulir sudah dikirim. Silakan tunggu sebentar atau muat ulang halaman.';
    } else {
        // Aturan validasi dipakai bersama dengan api/update_record.php
        // (satu sumber: includes/validasi.php) supaya hasil input pengguna dan
        // hasil edit admin selalu tunduk pada aturan yang sama.
        // >>> RUMUS TIDAK DIUBAH: validasi hanya memeriksa input.
        $validasi = instagi_validasi_input($_POST);

        // 2b. Akun aman (opsional): bila pengguna mencentang "Simpan dengan akun
        //     aman", passwordnya diperiksa lebih dulu. Ini hanya soal keamanan
        //     akses riwayat di halaman Monitoring — tidak menyentuh rumus apa pun.
        $password_error = '';
        $password_hash  = '';
        if (!empty($_POST['buat_akun'])) {
            $pass1 = (string) ($_POST['password'] ?? '');
            $pass2 = (string) ($_POST['password_ulang'] ?? '');
            $password_error = instagi_validasi_password($pass1, $pass2);
            if ($password_error === '' && $pass1 === '') {
                $password_error = 'Isi password akun (minimal 8 karakter), atau hilangkan centang "Simpan dengan akun aman".';
            }
        }

        if ($password_error !== '') {
            $error_message = $password_error;
        } elseif (!$validasi['valid']) {
            $error_message = $validasi['error'];
        } else {
            $data          = $validasi['data'];
            $nama          = $data['nama'];
            $usia          = $data['usia'];
            $jenis_kelamin = $data['jenis_kelamin'];
            $bb            = $data['bb'];
            $tb            = $data['tb'];
            $no_hp         = $data['no_hp'];
            $tanggal_input = $data['tanggal'];
            $aktivitas     = $data['aktivitas'];
            $hp_norm       = $data['hp_norm'];

            // Cek integritas akun: jika akun sudah terdaftar dan ber-password,
            // verifikasi bahwa pengirim adalah pemilik akun yang sah.
            $akun_terdaftar = instagi_responden_cari($koneksi, $nama, $hp_norm);
            $sesi_responden = instagi_responden_saat_ini();
            $sedang_login   = $sesi_responden && ((int) $sesi_responden['id'] === (int) ($akun_terdaftar['id'] ?? 0));

            if ($akun_terdaftar && instagi_responden_punya_password($akun_terdaftar) && !$sedang_login) {
                $pass_input = (string) ($_POST['password'] ?? '');
                if ($pass_input === '' || !password_verify($pass_input, (string) $akun_terdaftar['password_hash'])) {
                    $error_message = 'Akun atas nama dan No. HP ini telah dilindungi password. Silakan centang "Simpan dengan akun aman" lalu masukkan password akun Anda, atau masuk terlebih dahulu melalui menu Monitoring.';
                }
            }

            if ($error_message === '') {
                if (!empty($_POST['buat_akun'])) {
                    $password_hash = instagi_hash_password((string) ($_POST['password'] ?? ''));
                }
                // 3. Lakukan Perhitungan jika data valid
                //    Semua rumus ada di includes/gizi.php agar identik dengan
                //    api/update_record.php (tidak lagi duplikat & tidak lagi berbeda).
                //    Nilai dipakai dari hasil validasi (sudah dirapikan & dipastikan sah).
                $hasil = instagi_hitung_semua($bb, $tb, $usia, $jenis_kelamin, $aktivitas);

                $imt            = $hasil['imt'];
                $status_gizi    = $hasil['status_gizi'];
                $status_class   = $hasil['status_class'];
                $saran          = $hasil['saran'];
                $kalori_harian  = $hasil['kalori_harian'];
                $bb_ideal       = $hasil['bb_ideal'];
                $bb_sehat_min   = $hasil['bb_sehat_min'];
                $bb_sehat_max   = $hasil['bb_sehat_max'];

        // 4. Akun responden (1 nama + 1 nomor HP = 1 akun) dibuat otomatis di
        //    sini, lalu ID-nya ikut disimpan agar menu Monitoring Status Gizi
        //    bisa menampilkan riwayat milik akun ini.
        //    Bila pengguna memilih "akun aman", akun dibuat/dilengkapi password
        //    sekaligus (password TIDAK ditimpa bila akun sudah punya password).
        //    ($hp_norm sudah dihitung & diperiksa di includes/validasi.php.)
        $responden_id = instagi_responden_find_or_create($koneksi, $nama, $hp_norm, $password_hash !== '' ? $password_hash : null);

        // 5. Simpan data ke database
        $stmt = $koneksi->prepare(
            "INSERT INTO imt_history (responden_id, tanggal, nama, usia, jenis_kelamin, no_hp, berat_badan, tinggi_badan, aktivitas, kalori, imt, status_gizi, saran) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        // Penjaga: bila prepare gagal (mis. tabel belum ada), tampilkan pesan
        // error yang jelas alih-alih fatal "call to a member function on bool".
        if ($stmt === false) {
            error_log('[InStaGi] imt.php prepare gagal: ' . $koneksi->error);
            $error_message = 'Gagal menyiapkan penyimpanan data. Silakan coba beberapa saat lagi.';
            $koneksi->close();
        } else {
            $stmt->bind_param("ississdddidss", $responden_id, $tanggal_input, $nama, $usia, $jenis_kelamin, $no_hp, $bb, $tb, $aktivitas, $kalori_harian, $imt, $status_gizi, $saran);

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

                // Tandai akun responden aktif agar menu Monitoring langsung siap.
                if (!empty($responden_id)) {
                    instagi_responden_set_session($responden_id, $nama, $hp_norm);
                }

                $stmt->close();
                $koneksi->close();
                header("location: /result"); // Redirect ke halaman hasil (alamat rapi)
                exit;

            } else {
                // Detail teknis hanya ke log server, TIDAK ditampilkan ke pengguna.
                error_log('[InStaGi] imt.php execute gagal: ' . $stmt->error);
                $error_message = "Gagal menyimpan data ke database. Silakan coba beberapa saat lagi.";
                $stmt->close();
                $koneksi->close(); // Close connection on error
            }
        }
            } // akhir if ($error_message === '')
        }     // akhir else validasi: data valid -> sudah dihitung & disimpan
    }         // akhir pemeriksaan validasi input
} else {
    $koneksi->close(); // Close connection if form is not submitted
}
?>

<?php
// Judul, deskripsi, alamat rapi (tanpa .php), dan daftar CSS halaman ini.
$judul     = 'InStaGi - Cek Status Gizi';
$deskripsi = 'Masukkan nama, usia, jenis kelamin, berat dan tinggi badan untuk menghitung IMT (Indeks Massa Tubuh), status gizi, serta estimasi kebutuhan kalori harian Anda.';
$og_path   = '/imt';
$css       = ['imt.css', 'nav.css'];
require __DIR__ . '/includes/layout_head.php';
?>
    <div class="container">
        <div style="text-align: center;">
            <img src="assets/logo.png" alt="InStaGi Logo" width="512" height="466" style="max-width: 150px; height: auto; margin-bottom: 10px;">
        </div>
        <h1>Cek Status Gizi Anda</h1>

        <?php if ($error_message): ?>
            <div class="message error-message"><?= $error_message ?></div>
        <?php endif; ?>


        <form action="/imt" method="POST" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(instagi_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="submitted" value="1">
            <div class="form-group full-width">
                <label for="tanggal_input">Tanggal Input</label>
                <input type="date" id="tanggal_input" name="tanggal_input" value="<?= htmlspecialchars($tanggal_input) ?>" readonly>
            </div>
            
            <div class="form-group">
                <label for="nama">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($nama) ?>"
                       autocomplete="name" maxlength="100" required>
            </div>
            <div class="form-group">
                <label for="usia">Usia (Tahun)</label>
                <small class="field-hint">1&ndash;120 tahun</small>
                <input type="number" id="usia" name="usia" value="<?= htmlspecialchars($usia) ?>"
                       min="1" max="120" step="1" inputmode="numeric" required>
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
                <small class="field-hint">8&ndash;15 digit (boleh diawali 0 atau +62)</small>
                <input type="tel" id="no_hp" name="no_hp" value="<?= htmlspecialchars($no_hp) ?>"
                       inputmode="tel" autocomplete="tel" pattern="[0-9+\(\)\- ]{8,20}"
                       title="Masukkan nomor HP Indonesia yang valid, mis. 081234567890 atau +6281234567890" required>
            </div>
            <div class="form-group">
                <label for="bb">Berat Badan (kg)</label>
                <small class="field-hint">2&ndash;500 kg, boleh desimal (mis. 52,5)</small>
                <input type="number" step="0.1" id="bb" name="bb" value="<?= htmlspecialchars($bb) ?>"
                       min="2" max="500" inputmode="decimal" required>
            </div>
            <div class="form-group">
                <label for="tb">Tinggi Badan (cm)</label>
                <small class="field-hint">40&ndash;250 cm (bilangan bulat, mis. 165)</small>
                <input type="number" step="1" id="tb" name="tb" value="<?= htmlspecialchars($tb) ?>"
                       min="40" max="250" inputmode="numeric" required>
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
            <!-- ===== AKUN AMAN (OPSIONAL) ===== -->
            <fieldset class="akun-aman full-width">
                <legend>Akun Monitoring (opsional)</legend>
                <label class="akun-aman-cek" for="buat_akun">
                    <input type="checkbox" id="buat_akun" name="buat_akun" value="1" <?= !empty($_POST['buat_akun']) ? 'checked' : '' ?>>
                    <span>Simpan dengan akun aman (pakai password)</span>
                </label>
                <small class="field-hint">
                    Tanpa password, riwayat Anda hanya dilindungi Nama + No. HP.
                    Dengan password, hanya Anda yang bisa membukanya di menu <strong>Monitoring Status Gizi</strong>.
                </small>

                <div class="akun-aman-pass" id="akunAmanPass" <?= empty($_POST['buat_akun']) ? 'hidden' : '' ?>>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <small class="field-hint">Minimal 8 karakter</small>
                        <input type="password" id="password" name="password" minlength="8"
                               autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label for="password_ulang">Ulangi Password</label>
                        <small class="field-hint">Harus sama dengan di atas</small>
                        <input type="password" id="password_ulang" name="password_ulang" minlength="8"
                               autocomplete="new-password">
                    </div>
                </div>
            </fieldset>

            <button type="submit" class="submit-btn">Cek Status Gizi &amp; Simpan</button>
        </form>

    </div>

    <script>
    // ===================================================================
    // Pengaman klik-ganda pada tombol "Hitung IMT & Simpan".
    // Tanpa ini, klik cepat dua kali dapat mengirim form dua kali dan
    // menyimpan DUA baris data yang sama ke database.
    // ===================================================================
    (function () {
        var form = document.querySelector('form.form-grid');
        if (!form) { return; }
        var btn = form.querySelector('.submit-btn');
        if (!btn) { return; }

        var original = btn.textContent;
        var terkirim = false;

        form.addEventListener('submit', function (e) {
            // Validasi bawaan peramban (min/max/pattern) dijalankan lebih dulu.
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                return; // biarkan peramban menampilkan pesan
            }
            if (terkirim) {
                e.preventDefault(); // kiriman kedua diabaikan
                return;
            }
            terkirim = true;
            btn.disabled = true;
            btn.classList.add('is-loading');
            btn.textContent = 'Menghitung...';
        });

        // Pulihkan tombol bila pengguna kembali ke halaman ini (bfcache).
        window.addEventListener('pageshow', function () {
            if (terkirim) {
                terkirim = false;
                btn.disabled = false;
                btn.classList.remove('is-loading');
                btn.textContent = original;
            }
        });

        // ===================================================================
        // Kolom password "akun aman": hanya ditampilkan (dan diwajibkan) bila
        // kotak centangnya dipilih, supaya pengguna yang tidak ingin memakai
        // password tidak terganggu.
        // ===================================================================
        var cekAkun = document.getElementById('buat_akun');
        var kotakPass = document.getElementById('akunAmanPass');
        if (cekAkun && kotakPass) {
            var sinkronAkun = function () {
                kotakPass.hidden = !cekAkun.checked;
                ['password', 'password_ulang'].forEach(function (id) {
                    var el = document.getElementById(id);
                    if (!el) { return; }
                    if (cekAkun.checked) { el.setAttribute('required', 'required'); }
                    else { el.removeAttribute('required'); }
                });
            };
            cekAkun.addEventListener('change', sinkronAkun);
            sinkronAkun();
        }
    })();
    </script>
<?php require __DIR__ . '/includes/layout_footer.php'; ?>
