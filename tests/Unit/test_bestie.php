<?php
/**
 * Uji Unit Modul Bestie InStaGi
 */
require_once __DIR__ . '/../../includes/content/bestie.php';

$total = 0;
$lulus = 0;

function cek($kondisi, $nama) {
    global $total, $lulus;
    $total++;
    if ($kondisi) {
        $lulus++;
        echo "  [OK] $nama\n";
    } else {
        echo "  [GAGAL] $nama\n";
    }
}

echo "== 1. Pengujian Kategori Bestie (instagi_bestie_kategori) ==\n";
$kategori = instagi_bestie_kategori();
cek(is_array($kategori), 'instagi_bestie_kategori mengembalikan array');
cek(isset($kategori['all']), 'Tersedia kategori all');
cek(isset($kategori['travel']), 'Tersedia kategori travel');
cek(isset($kategori['property']), 'Tersedia kategori property');
cek(isset($kategori['kuliner']), 'Tersedia kategori kuliner');
cek(isset($kategori['minimarket']), 'Tersedia kategori minimarket');
cek(isset($kategori['kesehatan']), 'Tersedia kategori kesehatan');

echo "\n== 2. Pengujian Daftar Flyer Bestie (instagi_bestie_daftar) ==\n";
$items = instagi_bestie_daftar();
cek(is_array($items), 'instagi_bestie_daftar mengembalikan array');
cek(count($items) === 12, 'Jumlah flyer tepat 12 item');

$kategori_terisi = [];
$assets_dir = dirname(dirname(__DIR__)) . '/assets/bestie';

foreach ($items as $idx => $item) {
    $judul = isset($item['judul']) ? $item['judul'] : "Item #$idx";
    cek(!empty($item['id']), "[$judul] ID tidak kosong");
    cek(!empty($item['kategori']) && isset($kategori[$item['kategori']]), "[$judul] Kategori valid (" . ($item['kategori'] ?? '') . ")");
    cek(!empty($item['judul']), "[$judul] Judul tidak kosong");
    cek(!empty($item['gambar']), "[$judul] Berkas gambar didefinisikan");

    $berkas_lengkap = $assets_dir . '/' . $item['gambar'];
    cek(file_exists($berkas_lengkap), "[$judul] Berkas gambar fisik ada di $berkas_lengkap");

    if (!empty($item['kategori'])) {
        $kategori_terisi[$item['kategori']] = true;
    }
}

cek(isset($kategori_terisi['travel']), 'Kategori travel terisi flyer');
cek(isset($kategori_terisi['property']), 'Kategori property terisi flyer');
cek(isset($kategori_terisi['kuliner']), 'Kategori kuliner terisi flyer');
cek(isset($kategori_terisi['minimarket']), 'Kategori minimarket terisi flyer');
cek(isset($kategori_terisi['kesehatan']), 'Kategori kesehatan terisi flyer');

echo "\n== 3. Pengujian Index Slug Bestie (instagi_bestie_list) ==\n";
$bestie_list = instagi_bestie_list();
cek(is_array($bestie_list), 'instagi_bestie_list mengembalikan array');
cek(count($bestie_list) === count($items), 'Jumlah item list sama dengan jumlah item daftar');
cek(isset($bestie_list['spirit-holiday-bali-lombok']), 'Kunci slug spirit-holiday-bali-lombok ada');
cek(isset($bestie_list['klinik-sunat-hd']), 'Kunci slug klinik-sunat-hd ada');
cek(isset($bestie_list['gastio-bite-siomay-ayam']), 'Kunci slug gastio-bite-siomay-ayam ada');

echo "\nTotal uji: $total | Lulus: $lulus | Gagal: " . ($total - $lulus) . "\n";
exit($total === $lulus ? 0 : 1);

