<?php
/**
 * Modul Akun Responden InStaGi
 * -----------------------------------------------------------------------------
 * Sistem "1 nama + 1 nomor HP = 1 akun".
 *
 * Akun dibuat OTOMATIS saat responden mengisi form IMT (imt.php) — tidak ada
 * halaman registrasi terpisah. Akun dipakai oleh halaman Monitoring agar setiap
 * responden hanya melihat RIWAYAT MILIKNYA SENDIRI.
 *
 * Login responden = Nama + No. HP (tanpa password), sesuai permintaan.
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

if (!function_exists('instagi_responden_cari')) {
    /**
     * Cari akun responden berdasarkan nama + nomor HP (sudah dinormalisasi).
     * Pencocokan nama mengikuti collation kolom (utf8mb4_general_ci = tidak
     * membedakan huruf besar/kecil).
     *
     * @return array|null Baris {id, nama, no_hp} atau null.
     */
    function instagi_responden_cari($koneksi, $nama, $hp_norm)
    {
        $nama = trim((string) $nama);
        $stmt = $koneksi->prepare("SELECT id, nama, no_hp FROM responden WHERE nama = ? AND no_hp = ? LIMIT 1");
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

if (!function_exists('instagi_responden_buat')) {
    /**
     * Buat akun responden baru. Bila gagal karena sudah ada (race), cari ulang.
     *
     * @return int|null ID responden, atau null bila gagal.
     */
    function instagi_responden_buat($koneksi, $nama, $hp_norm)
    {
        $nama = trim((string) $nama);
        $stmt = $koneksi->prepare("INSERT INTO responden (nama, no_hp) VALUES (?, ?)");
        if ($stmt === false) {
            return null;
        }
        $stmt->bind_param('ss', $nama, $hp_norm);
        $ok = $stmt->execute();
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
     * @return int|null
     */
    function instagi_responden_find_or_create($koneksi, $nama, $hp_norm)
    {
        $row = instagi_responden_cari($koneksi, $nama, $hp_norm);
        if ($row) {
            return (int) $row['id'];
        }
        return instagi_responden_buat($koneksi, $nama, $hp_norm);
    }
}

if (!function_exists('instagi_responden_login')) {
    /**
     * Login responden (Nama + No. HP). Bila cocok, simpan ke session dan
     * klaim riwayat lama yang belum tertaut ke akun mana pun.
     *
     * @return bool true bila akun ditemukan.
     */
    function instagi_responden_login($koneksi, $nama, $hp_input)
    {
        $nama = trim((string) $nama);
        $hp_norm = instagi_norm_hp($hp_input);
        if ($nama === '' || $hp_norm === '') {
            return false;
        }

        $row = instagi_responden_cari($koneksi, $nama, $hp_norm);
        if (!$row) {
            return false;
        }
        $id = (int) $row['id'];

        $_SESSION['responden'] = [
            'id'    => $id,
            'nama'  => $row['nama'],
            'no_hp' => $row['no_hp'],
        ];

        // Klaim riwayat lama (responden_id NULL) yang nama & nomor HP-nya sama,
        // supaya data sebelum fitur akun ini tetap muncul di Monitoring.
        // Nomor dibandingkan juga dalam bentuk digit-saja (mis. data lama
        // tersimpan "0812-3456-7890" sementara login memakai "081234567890"),
        // termasuk bentuk internasional 62...
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

        return true;
    }
}

if (!function_exists('instagi_responden_set_session')) {
    /** Simpan sesi responden (dipakai setelah submit form IMT). */
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
