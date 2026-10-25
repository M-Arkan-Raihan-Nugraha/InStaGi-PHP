<?php
/**
 * Modul Akun Responden InStaGi
 * -----------------------------------------------------------------------------
 * Sistem "1 nama + 1 nomor HP = 1 akun".
 *
 * Akun dibuat OTOMATIS saat responden mengisi form Cek Status Gizi (imt.php) —
 * tidak ada halaman registrasi terpisah. Akun dipakai oleh halaman Monitoring
 * agar setiap responden hanya melihat RIWAYAT MILIKNYA SENDIRI.
 *
 * DUA TINGKAT KEAMANAN AKUN:
 *   1. Akun lama / tanpa password -> masuk dengan Nama + No. HP (cara lama tetap
 *      jalan supaya data yang sudah ada tidak terkunci).
 *   2. Akun berpassword        -> masuk WAJIB memakai password (password_hash,
 *      algoritma default PHP). Password hanya bisa:
 *        - dibuat saat mengisi form Cek Status Gizi, atau
 *        - dipasang/diganti dari halaman Monitoring (mengganti password akun
 *          yang sudah berpassword WAJIB tahu password lama).
 *      Jadi akun yang sudah berpassword TIDAK bisa dibajak lewat form publik.
 *
 * CATATAN: file ini TIDAK menyentuh rumus perhitungan (includes/gizi.php).
 */

if (!function_exists('instagi_norm_hp')) {
    /**
     * Normalisasi nomor HP menjadi bentuk digit saja, dengan awalan 0.
     * Contoh: "+62 812-2494-8388" -> "081224948388".
     */
    function instagi_norm_hp($hp)
    {
        $d = preg_replace('/\D+/', '', (string) $hp);
        if ($d === '') {
            return '';
        }
        // 62xxxxxxxxxx -> 0xxxxxxxxxx
        if (strpos($d, '62') === 0 && strlen($d) > 2) {
            $d = '0' . substr($d, 2);
        }
        return $d;
    }
}

if (!function_exists('instagi_hash_password')) {
    /**
     * Buat hash password memakai algoritma default PHP.
     * Dipakai agar seluruh aplikasi memakai cara hashing yang sama.
     */
    function instagi_hash_password($password)
    {
        return password_hash((string) $password, PASSWORD_DEFAULT);
    }
}

if (!function_exists('instagi_responden_cari')) {
    /**
     * Cari akun responden berdasarkan nama + nomor HP (sudah dinormalisasi).
     * Pencocokan nama mengikuti collation kolom (utf8mb4_general_ci = tidak
     * membedakan huruf besar/kecil).
     *
     * @return array|null Baris {id, nama, no_hp, password_hash} atau null.
     */
    function instagi_responden_cari($koneksi, $nama, $hp_norm)
    {
        $nama = trim((string) $nama);
        $stmt = $koneksi->prepare("SELECT id, nama, no_hp, password_hash FROM responden WHERE nama = ? AND no_hp = ? LIMIT 1");
        if ($stmt === false) {
            // Kolom password_hash bisa belum ada (host tanpa hak ALTER) ->
            // coba sekali lagi tanpa kolom itu agar login cara lama tetap jalan.
            $stmt = $koneksi->prepare("SELECT id, nama, no_hp FROM responden WHERE nama = ? AND no_hp = ? LIMIT 1");
        }
        if ($stmt === false) {
            return null;
        }
        $stmt->bind_param('ss', $nama, $hp_norm);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ?: null;
    }
}

if (!function_exists('instagi_responden_punya_password')) {
    /** Apakah baris akun ini sudah memakai password? */
    function instagi_responden_punya_password($row)
    {
        return is_array($row) && !empty($row['password_hash']);
    }
}

if (!function_exists('instagi_responden_buat')) {
    /**
     * Buat akun responden baru (opsional langsung dengan password).
     * Bila gagal karena sudah ada (race), cari ulang.
     *
     * @param string|null $password_hash Hash password, atau null bila tanpa password.
     * @return int|null ID responden, atau null bila gagal.
     */
    function instagi_responden_buat($koneksi, $nama, $hp_norm, $password_hash = null)
    {
        $nama = trim((string) $nama);
        $tanpa_password = ($password_hash === null || $password_hash === '');

        $stmt = $tanpa_password
            ? $koneksi->prepare("INSERT INTO responden (nama, no_hp) VALUES (?, ?)")
            : $koneksi->prepare("INSERT INTO responden (nama, no_hp, password_hash) VALUES (?, ?, ?)");

        if ($stmt === false) {
            return null;
        }

        if ($tanpa_password) {
            $stmt->bind_param('ss', $nama, $hp_norm);
            $ok = $stmt->execute();
        } else {
            $stmt->bind_param('sss', $nama, $hp_norm, $password_hash);
            $ok = $stmt->execute();
            if (!$ok) {
                // Kolom password_hash bisa belum ada (host tanpa hak ALTER).
                // Akun tetap dibuat tanpa password supaya data tidak ikut gagal.
                error_log('[InStaGi] responden: gagal menyimpan password, lanjut tanpa password - ' . $koneksi->error);
                $stmt->close();
                $stmt = $koneksi->prepare("INSERT INTO responden (nama, no_hp) VALUES (?, ?)");
                if ($stmt === false) {
                    return null;
                }
                $stmt->bind_param('ss', $nama, $hp_norm);
                $ok = $stmt->execute();
            }
        }

        $id = $ok ? (int) $koneksi->insert_id : 0;
        $stmt->close();
        if ($id > 0) {
            return $id;
        }
        // Mungkin sudah dibuat oleh request lain -> ambil yang ada.
        $row = instagi_responden_cari($koneksi, $nama, $hp_norm);
        return $row ? (int) $row['id'] : null;
    }
}

if (!function_exists('instagi_responden_find_or_create')) {
    /**
     * Ambil ID akun responden; buat bila belum ada.
     *
     * ATURAN KEAMANAN: password hanya boleh DIISI saat akun belum punya
     * password. Bila akun sudah berpassword, password yang dikirim dari form
     * publik DIABAIKAN (tidak boleh menimpa) supaya akun tidak bisa diambil
     * alih hanya dengan tahu nama + nomor HP.
     *
     * @return int|null
     */
    function instagi_responden_find_or_create($koneksi, $nama, $hp_norm, $password_hash = null)
    {
        $row = instagi_responden_cari($koneksi, $nama, $hp_norm);

        if ($row) {
            $id = (int) $row['id'];
            if ($password_hash !== null && $password_hash !== '' && !instagi_responden_punya_password($row)) {
                instagi_responden_simpan_password($koneksi, $id, $password_hash);
            }
            return $id;
        }

        return instagi_responden_buat($koneksi, $nama, $hp_norm, $password_hash);
    }
}

if (!function_exists('instagi_responden_cari_kontak')) {
    /**
     * Cari akun responden berdasarkan No. HP atau Email (tanpa nama).
     *
     * @return array|null Baris {id, nama, no_hp, password_hash} atau null.
     */
    function instagi_responden_cari_kontak($koneksi, $kontak_input, $password = null)
    {
        $raw = trim((string) $kontak_input);
        if ($raw === '') {
            return null;
        }

        $is_email = (bool) filter_var($raw, FILTER_VALIDATE_EMAIL);
        $hp_norm  = $is_email ? strtolower($raw) : instagi_norm_hp($raw);
        $hp_intl  = (!$is_email && strpos($hp_norm, '0') === 0) ? '62' . substr($hp_norm, 1) : $hp_norm;

        // Ambil semua responden yang no_hp-nya cocok
        $stmt = $koneksi->prepare(
            "SELECT id, nama, no_hp, password_hash FROM responden 
             WHERE no_hp = ? OR no_hp = ? OR no_hp = ?
                OR REGEXP_REPLACE(no_hp, '[^0-9]', '') IN (?, ?)
             ORDER BY id DESC"
        );
        if ($stmt === false) {
            return null;
        }
        $stmt->bind_param('sssss', $raw, $hp_norm, $hp_intl, $hp_norm, $hp_intl);
        $stmt->execute();
        $res = $stmt->get_result();
        $candidates = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        if (empty($candidates)) {
            return null;
        }

        // Bila ada password, cari kandidat yang password-nya cocok
        if ($password !== null && $password !== '') {
            foreach ($candidates as $cand) {
                if (!empty($cand['password_hash']) && password_verify((string) $password, (string) $cand['password_hash'])) {
                    return $cand;
                }
            }
        }

        return $candidates[0];
    }
}

if (!function_exists('instagi_responden_login_by_contact')) {
    /**
     * Login responden cukup dengan No. HP atau Email dan Password (tanpa nama).
     *
     * @return array{ok:bool,alasan:string,punya_password:bool}
     */
    function instagi_responden_login_by_contact($koneksi, $kontak_input, $password = null)
    {
        $kontak = trim((string) $kontak_input);
        if ($kontak === '') {
            return ['ok' => false, 'alasan' => 'tidak_ada', 'punya_password' => false];
        }

        $row = instagi_responden_cari_kontak($koneksi, $kontak, $password);
        if (!$row) {
            return ['ok' => false, 'alasan' => 'tidak_ada', 'punya_password' => false];
        }

        $punya_password = instagi_responden_punya_password($row);

        if ($punya_password) {
            if ($password === null || $password === '') {
                return ['ok' => false, 'alasan' => 'password_kosong', 'punya_password' => true];
            }
            if (!password_verify((string) $password, (string) $row['password_hash'])) {
                return ['ok' => false, 'alasan' => 'password_salah', 'punya_password' => true];
            }
        }

        $id = (int) $row['id'];

        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_regenerate_id(true);
        }

        $_SESSION['responden'] = [
            'id'    => $id,
            'nama'  => $row['nama'],
            'no_hp' => $row['no_hp'],
        ];

        // Klaim riwayat lama yang no_hp-nya cocok
        $is_email = (bool) filter_var($kontak, FILTER_VALIDATE_EMAIL);
        $hp_norm  = $is_email ? strtolower($kontak) : instagi_norm_hp($kontak);
        $hp_intl  = (!$is_email && strpos($hp_norm, '0') === 0) ? '62' . substr($hp_norm, 1) : $hp_norm;

        $stmt = $koneksi->prepare(
            "UPDATE imt_history SET responden_id = ?
             WHERE responden_id IS NULL
               AND (
                     no_hp = ? OR no_hp = ? OR no_hp = ?
                     OR REGEXP_REPLACE(no_hp, '[^0-9]', '') IN (?, ?)
                   )"
        );
        if ($stmt !== false) {
            $stmt->bind_param('isssss', $id, $kontak, $hp_norm, $hp_intl, $hp_norm, $hp_intl);
            $stmt->execute();
            $stmt->close();
        }

        return ['ok' => true, 'alasan' => 'ok', 'punya_password' => $punya_password];
    }
}

if (!function_exists('instagi_responden_login')) {
    /**
     * Login responden. Mendukung pemanggilan baru (kontak, password) maupun
     * cara lama (nama, no_hp, password).
     *
     * @return array{ok:bool,alasan:string,punya_password:bool}
     */
    function instagi_responden_login($koneksi, $arg1, $arg2 = null, $arg3 = null)
    {
        // Bila dipanggil dengan 2 parameter: instagi_responden_login($koneksi, $kontak, $password)
        if ($arg3 === null && ($arg2 === null || !preg_match('/^0\d{7,14}$/', instagi_norm_hp($arg2)))) {
            return instagi_responden_login_by_contact($koneksi, $arg1, $arg2);
        }

        $nama = trim((string) $arg1);
        $hp_input = $arg2;
        $password = $arg3;
        $hp_norm = instagi_norm_hp($hp_input);
        if ($nama === '' || $hp_norm === '') {
            return ['ok' => false, 'alasan' => 'tidak_ada', 'punya_password' => false];
        }

        $row = instagi_responden_cari($koneksi, $nama, $hp_norm);
        if (!$row) {
            return ['ok' => false, 'alasan' => 'tidak_ada', 'punya_password' => false];
        }

        $punya_password = instagi_responden_punya_password($row);

        if ($punya_password) {
            if ($password === null || $password === '') {
                return ['ok' => false, 'alasan' => 'password_kosong', 'punya_password' => true];
            }
            if (!password_verify((string) $password, (string) $row['password_hash'])) {
                return ['ok' => false, 'alasan' => 'password_salah', 'punya_password' => true];
            }
        }

        $id = (int) $row['id'];

        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_regenerate_id(true);
        }

        $_SESSION['responden'] = [
            'id'    => $id,
            'nama'  => $row['nama'],
            'no_hp' => $row['no_hp'],
        ];

        $raw_hp  = trim((string) $hp_input);
        $hp_intl = (strpos($hp_norm, '0') === 0) ? '62' . substr($hp_norm, 1) : $hp_norm;
        $stmt = $koneksi->prepare(
            "UPDATE imt_history SET responden_id = ?
             WHERE responden_id IS NULL AND nama = ?
               AND (
                     no_hp = ? OR no_hp = ?
                     OR REGEXP_REPLACE(no_hp, '[^0-9]', '') IN (?, ?)
                   )"
        );
        if ($stmt !== false) {
            $stmt->bind_param('isssss', $id, $nama, $raw_hp, $hp_norm, $hp_norm, $hp_intl);
            $stmt->execute();
            $stmt->close();
        }

        return ['ok' => true, 'alasan' => 'ok', 'punya_password' => $punya_password];
    }
}

if (!function_exists('instagi_responden_simpan_password')) {
    /**
     * Simpan password baru untuk sebuah akun (hash sudah dihitung).
     *
     * @return bool
     */
    function instagi_responden_simpan_password($koneksi, $id, $password_hash)
    {
        $stmt = $koneksi->prepare("UPDATE responden SET password_hash = ? WHERE id = ?");
        if ($stmt === false) {
            return false;
        }
        $id = (int) $id;
        $hash = (string) $password_hash;
        $stmt->bind_param('si', $hash, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return (bool) $ok;
    }
}

if (!function_exists('instagi_responden_ganti_password')) {
    /**
     * Ganti/pasang password akun. Bila akun SUDAH punya password, password lama
     * wajib benar (mencegah pengambilalihan akun oleh orang yang hanya tahu
     * nama + No. HP).
     *
     * @return string Pesan kesalahan, atau '' bila berhasil.
     */
    function instagi_responden_ganti_password($koneksi, $id, $lama, $baru)
    {
        $id = (int) $id;
        $stmt = $koneksi->prepare("SELECT password_hash FROM responden WHERE id = ? LIMIT 1");
        if ($stmt === false) {
            return 'Gagal membaca data akun. Silakan coba beberapa saat lagi.';
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if (!$row) {
            return 'Akun tidak ditemukan.';
        }

        if (!empty($row['password_hash'])) {
            if ($lama === null || $lama === '') {
                return 'Masukkan password lama untuk mengganti password.';
            }
            if (!password_verify((string) $lama, (string) $row['password_hash'])) {
                return 'Password lama tidak cocok.';
            }
        }

        if (!instagi_responden_simpan_password($koneksi, $id, instagi_hash_password($baru))) {
            return 'Gagal menyimpan password baru. Silakan coba beberapa saat lagi.';
        }
        return '';
    }
}

if (!function_exists('instagi_responden_set_session')) {
    /** Simpan sesi responden (dipakai setelah submit form Cek Status Gizi). */
    function instagi_responden_set_session($id, $nama, $hp_norm)
    {
        $_SESSION['responden'] = [
            'id'    => (int) $id,
            'nama'  => (string) $nama,
            'no_hp' => (string) $hp_norm,
        ];
    }
}

if (!function_exists('instagi_responden_saat_ini')) {
    /** Ambil data responden yang sedang login, atau null. */
    function instagi_responden_saat_ini()
    {
        return (isset($_SESSION['responden']) && is_array($_SESSION['responden']))
            ? $_SESSION['responden']
            : null;
    }
}

if (!function_exists('instagi_responden_logout')) {
    /** Keluarkan responden (tidak menyentuh sesi admin). */
    function instagi_responden_logout()
    {
        unset($_SESSION['responden']);
    }
}
