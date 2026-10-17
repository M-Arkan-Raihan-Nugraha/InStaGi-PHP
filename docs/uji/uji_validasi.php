<?php
/**
 * Uji mandiri includes/validasi.php — dijalankan dari CLI (tanpa DB).
 * Skrip ini hanya untuk pengujian; disimpan di luar folder proyek.
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = 'D:/Codingan/Instagi/V3 PHP Native';
require_once $root . '/includes/validasi.php';

$lulus = 0; $gagal = 0;
function cek($judul, $harusnya, $benar) {
    global $lulus, $gagal;
    if ($harusnya === $benar) { $lulus++; echo "  OK   $judul\n"; }
    else { $gagal++; echo "  GAGAL $judul -> diharapkan: " . var_export($harusnya, true) . " | dapat: " . var_export($benar, true) . "\n"; }
}

$hari_ini = date('Y-m-d');

// Data dasar yang VALID (gaya form publik imt.php)
$ok = [
    'nama' => 'Budi & Santoso', 'usia' => '30', 'jenis_kelamin' => 'Laki-laki',
    'bb' => '60.5', 'tb' => '165', 'no_hp' => '081234567890',
    'aktivitas' => '1.55', 'tanggal_input' => $hari_ini,
];

echo "== 1. Input yang SAH harus diterima ==\n";
$v = instagi_validasi_input($ok);
cek('data lengkap valid', true, $v['valid']);
cek('nama tersimpan apa adanya (A & B tidak di-escape)', 'Budi & Santoso', $v['data']['nama']);
cek('bb desimal diterima', 60.5, $v['data']['bb']);
cek('tb jadi bilangan bulat', 165, $v['data']['tb']);
cek('hp_norm = digit saja', '081234567890', $v['data']['hp_norm']);
cek('aktivitas 1.55 sah', 1.55, $v['data']['aktivitas']);
cek('tanggal terpakai', $hari_ini, $v['data']['tanggal']);

echo "\n== 2. Variasi format No. HP ==\n";
foreach ([
    '+62 812-2494-8388' => '081224948388',
    '0812 2494 8388'    => '081224948388',
    '6281224948388'     => '081224948388',
    '(0812) 2494-8388'  => '081224948388',
    '0812345678'        => '0812345678',
] as $masuk => $harap) {
    $d = $ok; $d['no_hp'] = $masuk;
    $v = instagi_validasi_input($d);
    cek("hp '$masuk' -> $harap", $harap, $v['valid'] ? $v['data']['hp_norm'] : ('DITOLAK: ' . $v['error']));
}

echo "\n== 3. Input yang HARUS ditolak ==\n";
$kasus_tolak = [
    'usia 999'                    => ['usia' => '999', 'pesan' => 'Usia harus antara 1 sampai 120 tahun.'],
    'usia -5'                     => ['usia' => '-5', 'pesan' => 'Usia harus antara 1 sampai 120 tahun.'],
    'bb 5000'                     => ['bb' => '5000', 'pesan' => 'Berat Badan harus antara 2 sampai 500 kg.'],
    'bb 1'                        => ['bb' => '1', 'pesan' => 'Berat Badan harus antara 2 sampai 500 kg.'],
    'tb 20'                       => ['tb' => '20', 'pesan' => 'Tinggi Badan harus antara 40 sampai 250 cm.'],
    'tb 999'                      => ['tb' => '999', 'pesan' => 'Tinggi Badan harus antara 40 sampai 250 cm.'],
    'tb desimal 165.5'            => ['tb' => '165.5', 'pesan' => 'Tinggi Badan dalam cm harus bilangan bulat (tanpa koma), mis. 165.'],
    'aktivitas 999'              => ['aktivitas' => '999', 'pesan' => 'Tingkat Aktivitas Fisik tidak valid. Pilih salah satu pilihan yang tersedia.'],
    'aktivitas 1.3 (bukan pilihan)' => ['aktivitas' => '1.3', 'pesan' => 'Tingkat Aktivitas Fisik tidak valid. Pilih salah satu pilihan yang tersedia.'],
    'aktivitas kosong'           => ['aktivitas' => '', 'pesan' => 'Tingkat Aktivitas Fisik tidak valid. Pilih salah satu pilihan yang tersedia.'],
    'jenis_kelamin "X"'          => ['jenis_kelamin' => 'X', 'pesan' => 'Jenis Kelamin tidak valid. Pilih Laki-laki atau Perempuan.'],
    'jenis_kelamin "laki-laki"'  => ['jenis_kelamin' => 'laki-laki', 'pesan' => 'Jenis Kelamin tidak valid. Pilih Laki-laki atau Perempuan.'],
    'nama kosong'                => ['nama' => '', 'pesan' => 'Nama Lengkap wajib diisi.'],
    'hp tanpa awalan 0'          => ['no_hp' => '81234567890', 'pesan' => 'No. HP tidak valid. Masukkan 8-15 digit dan boleh diawali 0 atau +62 (mis. 081234567890).'],
    'hp terlalu pendek'          => ['no_hp' => '0812345', 'pesan' => 'No. HP tidak valid. Masukkan 8-15 digit dan boleh diawali 0 atau +62 (mis. 081234567890).'],
    'hp terlalu panjang (18 digit)' => ['no_hp' => '081234567890123456', 'pesan' => 'No. HP tidak valid. Masukkan 8-15 digit dan boleh diawali 0 atau +62 (mis. 081234567890).'],
    'hp bukan angka'             => ['no_hp' => 'nomor saya', 'pesan' => 'No. HP tidak valid. Masukkan 8-15 digit dan boleh diawali 0 atau +62 (mis. 081234567890).'],
    'tanggal ngawur 2026-13-45'  => ['tanggal_input' => '2026-13-45', 'pesan' => 'Tanggal tidak valid. Muat ulang halaman lalu coba lagi.'],
    'tanggal 2026-02-31'         => ['tanggal_input' => '2026-02-31', 'pesan' => 'Tanggal tidak valid. Muat ulang halaman lalu coba lagi.'],
    'tanggal masa depan'         => ['tanggal_input' => '2027-01-01', 'pesan' => 'Tanggal tidak valid. Muat ulang halaman lalu coba lagi.'],
];
foreach ($kasus_tolak as $judul => $ubah) {
    $pesan_harap = $ubah['pesan']; unset($ubah['pesan']);
    $d = array_merge($ok, $ubah);
    $v = instagi_validasi_input($d);
    cek($judul, false, $v['valid']);
    if (!$v['valid']) { cek("  pesan: $judul", $pesan_harap, $v['error']); }
}

echo "\n== 4. Nama > 100 karakter ==\n";
$d = $ok; $d['nama'] = str_repeat('A', 101);
$v = instagi_validasi_input($d);
cek('nama 101 karakter ditolak', 'Nama Lengkap maksimal 100 karakter.', $v['error']);
$d['nama'] = str_repeat('A', 100);
cek('nama 100 karakter diterima', true, instagi_validasi_input($d)['valid']);

echo "\n== 5. Tanggal kosong = hari ini (JS mati / atribut form tidak jalan) ==\n";
$d = $ok; $d['tanggal_input'] = '';
$v = instagi_validasi_input($d);
cek('tanggal kosong -> hari ini', $hari_ini, $v['valid'] ? $v['data']['tanggal'] : ('DITOLAK: ' . $v['error']));

echo "\n== 6. Gaya API admin (berat_badan / tinggi_badan / tanggal) ==\n";
$api = [
    'id' => '7', 'nama' => 'Sinta', 'usia' => '28', 'jenis_kelamin' => 'Perempuan',
    'berat_badan' => '52.4', 'tinggi_badan' => '158', 'no_hp' => '0812-3456-7890',
    'aktivitas' => '1.375', 'tanggal' => '2026-10-01',
];
$v = instagi_validasi_input($api, ['konteks_api' => true]);
cek('gaya API valid', true, $v['valid']);
cek('berat_badan terbaca', 52.4, $v['data']['bb']);
cek('tinggi_badan terbaca', 158, $v['data']['tb']);
cek('tanggal API terpakai', '2026-10-01', $v['data']['tanggal']);

echo "\n== 7. API: tanggal rusak TIDAK menolak, tapi dikosongkan (pertahankan tanggal lama) ==\n";
$api_rusak = array_merge($api, ['tanggal' => '0000-00-00']);
$v = instagi_validasi_input($api_rusak, ['konteks_api' => true]);
cek('baris bertanggal rusak tetap bisa disimpan', true, $v['valid']);
cek("tanggal dikosongkan ('' = pakai tanggal lama)", '', $v['data']['tanggal']);

echo "\n== 8. API: tanggal masa depan dibiarkan (admin merapikan data lama) ==\n";
$api_depan = array_merge($api, ['tanggal' => '2030-05-05']);
$v = instagi_validasi_input($api_depan, ['konteks_api' => true]);
cek('tanggal masa depan tetap tersimpan di konteks API', '2030-05-05', $v['valid'] ? $v['data']['tanggal'] : 'DITOLAK');

echo "\n== 9. Aktivitas dari database ('1.550') tetap dikenali ==\n";
$d = $ok; $d['aktivitas'] = '1.550';
cek("'1.550' -> 1.55", 1.55, instagi_validasi_input($d)['data']['aktivitas']);
$d['aktivitas'] = '1.2';   cek("'1.2' -> 1.2 (tepat)", 1.2,   instagi_validasi_input($d)['data']['aktivitas']);
$d['aktivitas'] = '1.725'; cek("'1.725' -> 1.725",   1.725, instagi_validasi_input($d)['data']['aktivitas']);
$d['aktivitas'] = '1.9';   cek("'1.9' -> 1.9",       1.9,   instagi_validasi_input($d)['data']['aktivitas']);

echo "\n== 10. Fuzz: nilai aneh tidak boleh memicu warning/notice PHP ==\n";
$aneh = [
    [], ['nama' => null], ['nama' => []], ['usia' => 'abc'], ['bb' => 'NaN'],
    ['aktivitas' => ['x']], ['no_hp' => ['0812']], ['tanggal_input' => '2026-1-1'],
    ['nama' => "  Budi  ", 'usia' => ' 30 ', 'tb' => '165.000', 'bb' => '60.50'],
];
$bunyi_aneh = 0;
foreach ($aneh as $i => $d) {
    $v = instagi_validasi_input($d);
    if (!is_array($v) || !isset($v['valid'])) { $bunyi_aneh++; echo "  bentuk kembalian salah pada kasus #$i\n"; }
}
cek('semua masukan aneh ditangani tanpa error', 0, $bunyi_aneh);
$d = ['nama' => "  Budi  ", 'usia' => ' 30 ', 'tb' => '165.000', 'bb' => '60.50',
      'jenis_kelamin' => 'Perempuan', 'no_hp' => '0812345678', 'aktivitas' => '1.2', 'tanggal_input' => $hari_ini];
$v = instagi_validasi_input($d);
cek('spasi di nama dirapikan', 'Budi', $v['data']['nama']);
cek("'165.000' dianggap bilangan bulat", 165, $v['data']['tb']);

echo "\n=========================================\n";
echo "LULUS: $lulus | GAGAL: $gagal\n";
exit($gagal === 0 ? 0 : 1);
