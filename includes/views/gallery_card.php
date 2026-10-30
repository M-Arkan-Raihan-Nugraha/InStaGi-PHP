<?php
/**
 * Partial SATU BAGIAN galeri di halaman Informasi Gizi
 * -----------------------------------------------------------------------------
 * Sebelumnya markup kartu (gambar + tag + judul + ringkasan + tombol Lihat/Unduh)
 * disalin untuk tiap galeri. Sekarang cukup satu tempat ini; halaman Informasi
 * Gizi memanggilnya empat kali (leaflet, contoh menu, resep, panduan).
 *
 * CARA PAKAI — isi variabel berikut sebelum `require`:
 *
 *   $kartu_id     = 'resep';                       // id bagian (untuk tautan #resep)
 *   $kartu_judul  = 'Kumpulan Resep';
 *   $kartu_intro  = 'Lembar resep bergambar dari InStaGi ...';
 *   $kartu_daftar = $reseps;                       // hasil instagi_galeri_daftar_tampil()
 *   $kartu_alt    = 'Resep';                       // awalan teks alternatif gambar
 *   $kartu_kosong = 'Belum ada resep yang tersedia.';
 *   require __DIR__ . '/includes/galeri_kartu.php';
 *
 * Semua teks dicetak lewat htmlspecialchars(), jadi data dari modul tetap aman.
 */

$kartu_id     = isset($kartu_id) ? $kartu_id : 'galeri';
$kartu_judul  = isset($kartu_judul) ? $kartu_judul : 'Galeri';
$kartu_intro  = isset($kartu_intro) ? $kartu_intro : '';
$kartu_daftar = isset($kartu_daftar) && is_array($kartu_daftar) ? $kartu_daftar : [];
$kartu_alt    = isset($kartu_alt) ? $kartu_alt : '';
$kartu_kosong = isset($kartu_kosong) ? $kartu_kosong : 'Belum ada materi yang tersedia.';
?>
        <section class="info-section" id="<?= htmlspecialchars($kartu_id, ENT_QUOTES, 'UTF-8') ?>">
            <h2><?= htmlspecialchars($kartu_judul, ENT_QUOTES, 'UTF-8') ?></h2>
            <?php if ($kartu_intro !== ''): ?>
            <p class="info-intro"><?= htmlspecialchars($kartu_intro, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <?php if (!empty($kartu_daftar)): ?>
                <div class="info-grid">
                    <?php foreach ($kartu_daftar as $kt): ?>
                    <article class="info-card">
                        <a href="<?= htmlspecialchars($kt['full'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                            <img src="<?= htmlspecialchars($kt['thumb'], ENT_QUOTES, 'UTF-8') ?>"
                                 alt="<?= htmlspecialchars(trim($kartu_alt . ' ' . $kt['judul']), ENT_QUOTES, 'UTF-8') ?>"
                                 decoding="async">
                        </a>
                        <div class="info-card-body">
                            <span class="info-tag"><?= htmlspecialchars($kt['tag'], ENT_QUOTES, 'UTF-8') ?></span>
                            <h3><?= htmlspecialchars($kt['judul'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="info-desc"><?= htmlspecialchars($kt['ringkas'], ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="info-card-actions">
                                <a href="<?= htmlspecialchars($kt['full'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Lihat</a>
                                <a class="alt" href="<?= htmlspecialchars($kt['full'], ENT_QUOTES, 'UTF-8') ?>"
                                   download="<?= htmlspecialchars($kt['unduh'], ENT_QUOTES, 'UTF-8') ?>">Unduh</a>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="info-empty"><?= htmlspecialchars($kartu_kosong, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </section>
