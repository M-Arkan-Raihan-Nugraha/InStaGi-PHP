<?php
/**
 * Partial Modal Lightbox Terpadu InStaGi
 * -----------------------------------------------------------------------------
 * Dipakai oleh halaman Informasi Gizi dan Bestie InStaGi untuk menampilkan
 * pratinjau flyer mitra, leaflet edukasi, kumpulan resep, panduan diet,
 * dan tips kebugaran dalam modal resolusi tinggi yang responsif di semua perangkat.
 */
require_once __DIR__ . '/icon.php';
?>
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

<script>
(function() {
    'use strict';
    var modal = document.getElementById('bestie-modal');
    if (!modal) return;
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

    // Tangani seluruh trigger lightbox dengan event delegation
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

    window.instagiLightbox = {
        open: openModal,
        close: closeModal
    };
})();
</script>

