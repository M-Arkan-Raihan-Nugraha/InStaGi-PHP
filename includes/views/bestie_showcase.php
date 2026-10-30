<?php
/**
 * Komponen Etalase Flyer Bestie InStaGi
 * -----------------------------------------------------------------------------
 * Menampilkan etalase multi-kategori mitra InStaGi (Travel & Hotel, Land & Property,
 * Kuliner, Minimarket, Layanan Kesehatan), filter interaktif, modal pratinjau, dan
 * form ajakan kemitraan.
 */
require_once __DIR__ . '/../content/bestie.php';
require_once __DIR__ . '/icon.php';
if (!function_exists('instagi_wa_url')) {
    require_once __DIR__ . '/../core/config.php';
}

$bestie_kategori = instagi_bestie_kategori();
$bestie_items    = instagi_bestie_daftar();
$wa_bestie_mitra = instagi_wa_url(
    'Halo Tim InStaGi, saya ingin bermitra / memasang iklan flyer usaha saya di Bestie InStaGi. Mohon informasi syarat dan prosedurnya.',
    defined('INSTAGI_WA_KERJASAMA') ? INSTAGI_WA_KERJASAMA : null
);

// Kelompokkan flyer berdasarkan kategori agar masing-masing memiliki carousel
$bestie_per_kategori = [];
foreach ($bestie_items as $item) {
    $cat = $item['kategori'] ?? 'lainnya';
    if (!isset($bestie_per_kategori[$cat])) {
        $bestie_per_kategori[$cat] = [];
    }
    $bestie_per_kategori[$cat][] = $item;
}
?>
<section class="bestie-section" id="bestie" aria-labelledby="bestie-heading">
    <div class="bestie-header">
        <h2 id="bestie-heading">Bestie InStaGi</h2>
        <p class="bestie-intro">
            Etalase kemitraan dan promosi resmi untuk sahabat InStaGi. Temukan penawaran pilihan dari aneka kategori: 
            <strong>Travel &amp; Hotel</strong>, <strong>Land &amp; Property</strong>, <strong>Kuliner</strong>, 
            <strong>Minimarket</strong>, dan <strong>Layanan Kesehatan</strong> terpercaya.
        </p>
    </div>

    <!-- ===== FILTER KATEGORI ===== -->
    <div class="bestie-filter-wrapper">
        <div class="bestie-filters" role="tablist" aria-label="Filter Kategori Bestie InStaGi">
            <?php foreach ($bestie_kategori as $slug => $label): ?>
                <?php
                if ($slug !== 'all' && empty($bestie_per_kategori[$slug])) {
                    continue;
                }
                $jumlah_kategori = ($slug === 'all') ? count($bestie_items) : count($bestie_per_kategori[$slug]);
                ?>
                <button type="button"
                        class="bestie-filter-btn<?= $slug === 'all' ? ' is-active' : '' ?>"
                        data-filter="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>"
                        role="tab"
                        aria-selected="<?= $slug === 'all' ? 'true' : 'false' ?>">
                    <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="bestie-filter-count"><?= $jumlah_kategori ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ===== CAROUSEL HORIZONTAL SCROLL PER KATEGORI ===== -->
    <div class="bestie-carousel-container" id="bestie-carousel-container">
        <?php foreach ($bestie_per_kategori as $cat_slug => $cat_items): ?>
            <?php
            $cat_label = $bestie_kategori[$cat_slug] ?? ucfirst($cat_slug);
            ?>
            <section class="bestie-cat-group" data-category="<?= htmlspecialchars($cat_slug, ENT_QUOTES, 'UTF-8') ?>">
                <div class="bestie-cat-header">
                    <div class="bestie-cat-title-wrap">
                        <span class="bestie-cat-indicator bestie-badge-<?= htmlspecialchars($cat_slug, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></span>
                        <h3 class="bestie-cat-title"><?= htmlspecialchars($cat_label, ENT_QUOTES, 'UTF-8') ?></h3>
                        <span class="bestie-cat-count"><?= count($cat_items) ?> Mitra</span>
                    </div>
                    <div class="bestie-nav-arrows" aria-label="Navigasi carousel <?= htmlspecialchars($cat_label, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="button" class="bestie-arrow-btn prev" data-target="carousel-track-<?= htmlspecialchars($cat_slug, ENT_QUOTES, 'UTF-8') ?>" aria-label="Geser ke kiri">
                            <?= instagi_ikon('panah-kiri') ?>
                        </button>
                        <button type="button" class="bestie-arrow-btn next" data-target="carousel-track-<?= htmlspecialchars($cat_slug, ENT_QUOTES, 'UTF-8') ?>" aria-label="Geser ke kanan">
                            <?= instagi_ikon('panah-kanan') ?>
                        </button>
                    </div>
                </div>

                <div class="bestie-track-wrapper">
                    <div class="bestie-carousel-track" id="carousel-track-<?= htmlspecialchars($cat_slug, ENT_QUOTES, 'UTF-8') ?>" role="region" aria-label="Koleksi <?= htmlspecialchars($cat_label, ENT_QUOTES, 'UTF-8') ?>" tabindex="0">
                        <?php foreach ($cat_items as $item): ?>
                            <?php
                            $bestie_thumb = function_exists('instagi_galeri_thumb_url')
                                ? instagi_galeri_thumb_url('bestie', $item['id'])
                                : '/assets/bestie/' . $item['gambar'];
                            ?>
                            <article class="bestie-card">
                                <div class="bestie-card-thumb"
                                     data-full="/assets/bestie/<?= htmlspecialchars($item['gambar'], ENT_QUOTES, 'UTF-8') ?>"
                                     data-title="<?= htmlspecialchars($item['judul'], ENT_QUOTES, 'UTF-8') ?>"
                                     data-download="<?= htmlspecialchars($item['gambar'], ENT_QUOTES, 'UTF-8') ?>"
                                     data-download-text="Unduh Flyer">
                                    <img src="<?= htmlspecialchars($bestie_thumb, ENT_QUOTES, 'UTF-8') ?>"
                                         alt="<?= htmlspecialchars($item['judul'] . ' - ' . $item['subjudul'], ENT_QUOTES, 'UTF-8') ?>"
                                         loading="lazy"
                                         decoding="async">
                                    <span class="bestie-badge bestie-badge-<?= htmlspecialchars($item['kategori'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($item['kategori_label'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <button type="button"
                                            class="bestie-thumb-zoom-btn"
                                            data-full="/assets/bestie/<?= htmlspecialchars($item['gambar'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-title="<?= htmlspecialchars($item['judul'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-download="<?= htmlspecialchars($item['gambar'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-download-text="Unduh Flyer"
                                            aria-label="Lihat ukuran penuh <?= htmlspecialchars($item['judul'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= instagi_ikon('zoom') ?>
                                    </button>
                                </div>
                                <div class="bestie-card-body">
                                    <span class="bestie-card-sub"><?= htmlspecialchars($item['subjudul'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <h4 class="bestie-card-title"><?= htmlspecialchars($item['judul'], ENT_QUOTES, 'UTF-8') ?></h4>
                                    <p class="bestie-card-desc"><?= htmlspecialchars($item['deskripsi'], ENT_QUOTES, 'UTF-8') ?></p>

                                    <div class="bestie-card-meta">
                                        <?php if (!empty($item['lokasi'])): ?>
                                            <div class="bestie-meta-item">
                                                <span class="bestie-meta-ico" aria-hidden="true"><?= instagi_ikon('lokasi') ?></span>
                                                <span class="bestie-meta-text"><?= htmlspecialchars($item['lokasi'], ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($item['harga'])): ?>
                                            <div class="bestie-meta-item bestie-meta-price">
                                                <strong><?= htmlspecialchars($item['harga'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="bestie-card-actions">
                                        <button type="button"
                                                class="bestie-btn-view"
                                                data-full="/assets/bestie/<?= htmlspecialchars($item['gambar'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-title="<?= htmlspecialchars($item['judul'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-download="<?= htmlspecialchars($item['gambar'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-download-text="Unduh Flyer">
                                            <span class="bestie-btn-ico" aria-hidden="true"><?= instagi_ikon('zoom') ?></span>
                                            <span>Lihat Flyer</span>
                                        </button>
                                        <?php if (!empty($item['wa'])): ?>
                                            <a href="<?= htmlspecialchars(instagi_wa_url($item['wa_text'], $item['wa']), ENT_QUOTES, 'UTF-8') ?>"
                                               target="_blank"
                                               rel="noopener"
                                               class="bestie-btn-wa">
                                                <span class="bestie-btn-ico" aria-hidden="true"><?= instagi_ikon('whatsapp') ?></span>
                                                <span>WhatsApp</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endforeach; ?>
    </div>

    <!-- Pesan jika hasil filter kosong (fallback) -->
    <div id="bestie-empty-msg" class="bestie-empty-state" style="display:none;">
        <p>Belum ada mitra pada kategori ini.</p>
    </div>

    <!-- ===== AJAKAN KEMITRAAN / SPONSOR ===== -->
    <div class="bestie-cta-card">
        <div class="bestie-cta-icon" aria-hidden="true">
            <?= instagi_ikon('hati') ?>
        </div>
        <div class="bestie-cta-text">
            <h3>Ingin Memasang Flyer atau Bermitra di Bestie InStaGi?</h3>
            <p>
                Perluas jangkauan bisnis Anda ke masyarakat dan keluarga peduli pola hidup sehat. 
                Kami membuka kesempatan kolaborasi untuk bidang kuliner, travel, properti, minimarket, serta fasilitas kesehatan.
            </p>
            <div class="bestie-cta-actions">
                <a href="<?= htmlspecialchars($wa_bestie_mitra, ENT_QUOTES, 'UTF-8') ?>"
                   target="_blank"
                   rel="noopener"
                   class="btn-cta-wa">
                    <span class="site-nav-ico" aria-hidden="true"><?= instagi_ikon('whatsapp') ?></span>
                    <span>Hubungi Kemitraan via WhatsApp</span>
                </a>
                <a href="mailto:instagiadmin@gmail.com" class="btn-cta-email">
                    <span class="site-nav-ico" aria-hidden="true"><?= instagi_ikon('email') ?></span>
                    <span>instagiadmin@gmail.com</span>
                </a>
            </div>
        </div>
    </div>

    <!-- ===== MODAL LIGHTBOX PRATINJAU MATERI & FLYER ===== -->
    <div id="bestie-modal" class="bestie-modal info-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="bestie-modal-title">
        <div class="bestie-modal-backdrop" id="bestie-modal-backdrop"></div>
        <div class="bestie-modal-box">
            <button type="button" class="bestie-modal-close" id="bestie-modal-close" aria-label="Tutup pratinjau">
                <?= instagi_ikon('tutup') ?>
            </button>
            <div class="bestie-modal-header">
                <h4 id="bestie-modal-title" class="bestie-modal-title">Pratinjau Materi</h4>
            </div>
            <div class="bestie-modal-body">
                <img id="bestie-modal-img" src="" alt="Pratinjau Materi InStaGi" decoding="async">
            </div>
            <div class="bestie-modal-footer">
                <a id="bestie-modal-tab" href="" target="_blank" rel="noopener" class="bestie-modal-btn">
                    <span>Buka Ukuran Asli</span>
                </a>
                <a id="bestie-modal-dl" href="" download class="bestie-modal-btn alt">
                    <span id="bestie-modal-dl-text">Unduh Berkas</span>
                </a>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    'use strict';
    // 1. Filter Kategori
    var filterBtns = document.querySelectorAll('.bestie-filter-btn');
    var catGroups = document.querySelectorAll('.bestie-cat-group');
    var emptyMsg = document.getElementById('bestie-empty-msg');

    filterBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var filter = this.getAttribute('data-filter');

            filterBtns.forEach(function(b) {
                b.classList.remove('is-active');
                b.setAttribute('aria-selected', 'false');
            });
            this.classList.add('is-active');
            this.setAttribute('aria-selected', 'true');

            var visibleCount = 0;
            catGroups.forEach(function(group) {
                var cat = group.getAttribute('data-category');
                if (filter === 'all' || cat === filter) {
                    group.style.display = '';
                    visibleCount++;
                } else {
                    group.style.display = 'none';
                }
            });

            if (emptyMsg) {
                emptyMsg.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        });
    });

    // 2. Navigasi Panah Carousel Horizontal
    var arrowBtns = document.querySelectorAll('.bestie-arrow-btn');
    arrowBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            var track = document.getElementById(targetId);
            if (!track) return;
            var isPrev = this.classList.contains('prev');
            var scrollAmount = track.clientWidth * 0.75;
            track.scrollBy({
                left: isPrev ? -scrollAmount : scrollAmount,
                behavior: 'smooth'
            });
        });
    });

    function updateArrowState(track) {
        var id = track.id;
        var prevBtn = document.querySelector('.bestie-arrow-btn.prev[data-target="' + id + '"]');
        var nextBtn = document.querySelector('.bestie-arrow-btn.next[data-target="' + id + '"]');
        if (prevBtn) {
            prevBtn.disabled = track.scrollLeft <= 5;
        }
        if (nextBtn) {
            var maxScroll = track.scrollWidth - track.clientWidth - 5;
            nextBtn.disabled = track.scrollLeft >= maxScroll;
        }
    }

    var tracks = document.querySelectorAll('.bestie-carousel-track');
    tracks.forEach(function(track) {
        track.addEventListener('scroll', function() {
            updateArrowState(this);
        }, { passive: true });
        updateArrowState(track);
    });

    window.addEventListener('resize', function() {
        tracks.forEach(updateArrowState);
    });

    // 3. Modal Lightbox Terpadu (Bestie InStaGi & Informasi Gizi)
    var modal = document.getElementById('bestie-modal');
    var modalImg = document.getElementById('bestie-modal-img');
    var modalTitle = document.getElementById('bestie-modal-title');
    var modalTab = document.getElementById('bestie-modal-tab');
    var modalDl = document.getElementById('bestie-modal-dl');
    var modalDlText = document.getElementById('bestie-modal-dl-text');
    var closeBtn = document.getElementById('bestie-modal-close');
    var backdrop = document.getElementById('bestie-modal-backdrop');

    function openModal(fullSrc, title, downloadName, downloadText) {
        if (!modal || !modalImg || !fullSrc) return;
        modalImg.src = fullSrc;
        modalImg.alt = title || 'Materi InStaGi';
        if (modalTitle) modalTitle.textContent = title || 'Pratinjau Materi';
        if (modalTab) modalTab.href = fullSrc;
        if (modalDl) {
            modalDl.href = fullSrc;
            var dlName = downloadName || (fullSrc ? fullSrc.split('/').pop().split('?')[0] : '');
            if (dlName) {
                modalDl.setAttribute('download', dlName);
            } else {
                modalDl.removeAttribute('download');
            }
        }
        if (modalDlText) {
            modalDlText.textContent = downloadText || 'Unduh Berkas';
        }
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('bestie-modal-active');
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('bestie-modal-active');
    }

    // Tangani semua pemicu lightbox pada Bestie maupun Galeri Informasi Gizi (Event Delegation)
    document.addEventListener('click', function(e) {
        var trigger = e.target.closest('.bestie-btn-view, .bestie-thumb-zoom-btn, .bestie-card-thumb, .info-btn-view, .info-thumb-zoom-btn, .info-card-thumb');
        if (!trigger) return;

        // Cegah klik ganda jika tombol zoom di dalam thumbnail ditekan
        if (trigger.classList.contains('bestie-card-thumb') || trigger.classList.contains('info-card-thumb')) {
            if (e.target.closest('.bestie-thumb-zoom-btn, .info-thumb-zoom-btn')) return;
        }

        e.preventDefault();
        e.stopPropagation();
        var src = trigger.getAttribute('data-full');
        var title = trigger.getAttribute('data-title');
        var downloadName = trigger.getAttribute('data-download');
        var downloadText = trigger.getAttribute('data-download-text');
        openModal(src, title, downloadName, downloadText);
    });

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('is-open')) {
            closeModal();
        }
    });

    // Expose fungsi agar bisa dipanggil secara programatis bila diperlukan
    window.instagiLightbox = {
        open: openModal,
        close: closeModal
    };
})();
</script>

