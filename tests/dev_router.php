<?php
/**
 * Router KECIL untuk server pengembangan bawaan PHP (`php -S`).
 * -----------------------------------------------------------------------------
 * Kenapa perlu? Server bawaan PHP tidak membaca berkas .htaccess, sehingga
 * aturan "alamat rapi" (bagian 0d di .htaccess root) tidak ikut jalan dan
 * alamat seperti http://127.0.0.1:8099/imt akan menghasilkan 404.
 *
 * Berkas ini MENIRU aturan .htaccess itu untuk keperluan uji lokal saja.
 * Produksi TIDAK memakainya (di Apache aturannya sudah di .htaccess).
 *
 * Pakai:
 *   php -S 127.0.0.1:8099 docs/uji/dev-router.php
 * (dijalankan dari folder akar proyek)
 *
 * Berkas ini berada di docs/ yang pada hosting sudah diblokir dari akses web,
 * jadi tidak membuka celah apa pun di produksi.
 */

$akar  = dirname(__DIR__, 2);                               // folder akar proyek
$jalur = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$jalur = rawurldecode($jalur);

// 0) Halaman utama: "/" -> index.php (meniru DirectoryIndex Apache).
if ($jalur === '/') {
    require $akar . '/index.php';
    return true;
}

// 1) Berkas/folder yang benar-benar ada (css, assets, api, docs, ...) → layani apa adanya.
if (is_file($akar . $jalur) || is_dir($akar . $jalur)) {
    return false;
}

// 2) /imt/  ->  /imt  (301), meniru aturan .htaccess.
if (preg_match('#^/([a-zA-Z0-9_-]+)/$#', $jalur, $cocok)) {
    header('Location: /' . $cocok[1], true, 301);
    exit;
}

// 3) /imt  ->  imt.php  (internal), hanya bila berkasnya ada.
if (preg_match('#^/([a-zA-Z0-9_-]+)$#', $jalur, $cocok)) {
    $tujuan = $akar . '/' . $cocok[1] . '.php';
    if (is_file($tujuan)) {
        $_SERVER['SCRIPT_NAME']     = '/' . $cocok[1] . '.php';
        $_SERVER['SCRIPT_FILENAME'] = $tujuan;
        $_SERVER['PHP_SELF']        = '/' . $cocok[1] . '.php';
        require $tujuan;
        return true;
    }
}

// 4) Selain itu: TIRU Apache — alamat yang tidak punya berkas tujuan = 404.
//    (Tanpa ini, server bawaan PHP menyajikan index.php untuk alamat apa pun,
//    sehingga galat 404 justru tersembunyi saat pengujian lokal.)
http_response_code(404);
header('Content-Type: text/html; charset=UTF-8');
echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>404</title></head>'
   . '<body><h1>404 - Halaman tidak ditemukan</h1>'
   . '<p>Alamat <code>' . htmlspecialchars($jalur, ENT_QUOTES, 'UTF-8') . '</code> tidak ada.</p>'
   . '<p><a href="/">Kembali ke halaman utama</a></p></body></html>';
return true;
