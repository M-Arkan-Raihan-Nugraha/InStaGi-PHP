<?php
/**
 * Komponen Etalase Flyer Bestie InStaGi
 * -----------------------------------------------------------------------------
 * Menampilkan etalase multi-kategori mitra InStaGi (Travel & Hotel, Land & Property,
 * Kuliner, Minimarket, Layanan Kesehatan), filter interaktif, modal pratinjau, dan
 * form ajakan kemitraan.
 */
require_once __DIR__ . '/../bestie.php';
require_once __DIR__ . '/ikon.php';
if (!function_exists('instagi_wa_url')) {
    require_once __DIR__ . '/../core/config.php';
}

$bestie_kategori = instagi_bestie_kategori();
$bestie_items    = instagi_bestie_daftar();
$wa_bestie_mitra = instagi_wa_url(
    'Halo Tim InStaGi, saya ingin bermitra / memasang iklan flyer usaha saya di Bestie InStaGi. Mohon informasi syarat dan prosedurnya.',
    defined('INSTAGI_WA_KERJASAMA') ? INSTAGI_WA_KERJASAMA : null
);
?>
<section class="bestie-section" id="bestie" aria-labelledby="bestie-heading">
    <div class="bestie-header">
        <span class="bestie-pill-tag">Sponsor &amp; Mitra Terpercaya</span>
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
                <button type="button"
                        class="bestie-filter-btn<?= $slug === 'all' ? ' is-active' : '' ?>"
                        data-filter="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>"
                        role="tab"
                        aria-selected="<?= $slug === 'all' ? 'true' : 'false' ?>">
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ===== GRID KARTU FLYER ===== -->
    <div class="bestie-grid" id="bestie-grid">
        <?php foreach ($bestie_items as $item): ?>
            <article class="bestie-card" data-category="<?= htmlspecialchars($item['kategori'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="bestie-card-thumb" data-full="assets/bestie/<?= htmlspecialchars($item['gambar'], ENT_QUOTES, 'UTF-8') ?>" data-title="<?= htmlspecialchars($item['judul'], ENT_QUOTES, 'UTF-8') ?>">
                    <img src="assets/bestie/<?= htmlspecialchars($item['gambar'], ENT_QUOTES, 'UTF-8') ?>"
                         alt="<?= htmlspecialchars($item['judul'] . ' - ' . $item['subjudul'], ENT_QUOTES, 'UTF-8') ?>"
                         loading="lazy"
                         decoding="async">
                    <span class="bestie-badge bestie-badge-<?= htmlspecialchars($item['kategori'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($item['kategori_label'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <button type="button"
                            class="bestie-thumb-zoom-btn"
                            data-full="assets/bestie/<?= htmlspecialchars($item['gambar'], ENT_QUOTES, 'UTF-8') ?>"
                            data-title="<?= htmlspecialchars($item['judul'], ENT_QUOTES, 'UTF-8') ?>"
                            aria-label="Lihat ukuran penuh <?= htmlspecialchars($item['judul'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= instagi_ikon('zoom') ?>
                    </button>
                </div>
                <div class="bestie-card-body">
                    <span class="bestie-card-sub"><?= htmlspecialchars($item['subjudul'], ENT_QUOTES, 'UTF-8') ?></span>
                    <h3 class="bestie-card-title"><?= htmlspecialchars($item['judul'], ENT_QUOTES, 'UTF-8') ?></h3>
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
                                data-full="assets/bestie/<?= htmlspecialchars($item['gambar'], ENT_QUOTES, 'UTF-8') ?>"
                                data-title="<?= htmlspecialchars($item['judul'], ENT_QUOTES, 'UTF-8') ?>">
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

    <!-- ===== MODAL LIGHTBOX PRATINJAU FLYER ===== -->
    <div id="bestie-modal" class="bestie-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="bestie-modal-title">
        <div class="bestie-modal-backdrop" id="bestie-modal-backdrop"></div>
        <div class="bestie-modal-box">
            <button type="button" class="bestie-modal-close" id="bestie-modal-close" aria-label="Tutup pratinjau flyer">
                <?= instagi_ikon('tutup') ?>
            </button>
            <div class="bestie-modal-header">
                <h4 id="bestie-modal-title" class="bestie-modal-title">Pratinjau Flyer</h4>
            </div>
            <div class="bestie-modal-body">
                <img id="bestie-modal-img" src="" alt="Pratinjau Flyer Mitra" decoding="async">
            </div>
            <div class="bestie-modal-footer">
                <a id="bestie-modal-tab" href="" target="_blank" rel="noopener" class="bestie-modal-btn">
                    <span>Buka Ukuran Asli</span>
                </a>
                <a id="bestie-modal-dl" href="" download class="bestie-modal-btn alt">
                    <span>Unduh Flyer</span>
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
    var cards = document.querySelectorAll('.bestie-card');
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
            cards.forEach(function(card) {
                var cat = card.getAttribute('data-category');
                if (filter === 'all' || cat === filter) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (emptyMsg) {
                emptyMsg.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        });
    });

    // 2. Modal Lightbox
    var modal = document.getElementById('bestie-modal');
    var modalImg = document.getElementById('bestie-modal-img');
    var modalTitle = document.getElementById('bestie-modal-title');
    var modalTab = document.getElementById('bestie-modal-tab');
    var modalDl = document.getElementById('bestie-modal-dl');
    var closeBtn = document.getElementById('bestie-modal-close');
    var backdrop = document.getElementById('bestie-modal-backdrop');

    function openModal(fullSrc, title) {
        if (!modal || !modalImg) return;
        modalImg.src = fullSrc;
        modalImg.alt = title || 'Flyer Bestie InStaGi';
        if (modalTitle) modalTitle.textContent = title || 'Pratinjau Flyer';
        if (modalTab) modalTab.href = fullSrc;
        if (modalDl) modalDl.href = fullSrc;
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

    document.querySelectorAll('.bestie-btn-view, .bestie-thumb-zoom-btn').forEach(function(el) {
        el.addEventListener('click', function(e) {
            e.stopPropagation();
            var src = this.getAttribute('data-full');
            var title = this.getAttribute('data-title');
            openModal(src, title);
        });
    });

    document.querySelectorAll('.bestie-card-thumb').forEach(function(thumb) {
        thumb.addEventListener('click', function(e) {
            if (e.target.closest('.bestie-thumb-zoom-btn')) return;
            var src = this.getAttribute('data-full');
            var title = this.getAttribute('data-title');
            openModal(src, title);
        });
    });

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('is-open')) {
            closeModal();
        }
    });
})();
</script>

