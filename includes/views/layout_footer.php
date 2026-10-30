<?php
/**
 * Partial BAWAH halaman InStaGi: footer + penutup dokumen.
 * -----------------------------------------------------------------------------
 * Dipakai bersama oleh semua halaman supaya teks footer (dan penutup
 * </body></html>) hanya ditulis di satu tempat.
 *
 * Cara pakai: letakkan di akhir halaman, SETELAH seluruh isi & <script> halaman:
 *   require __DIR__ . '/includes/layout_footer.php';
 *
 * (Skrip halaman diletakkan sebelum baris ini — urutan skrip tidak berubah,
 * hanya posisinya relatif terhadap footer yang tidak terlihat.)
 */
?>
<?php require_once __DIR__ . '/icon.php'; ?>
    <footer class="main-footer">
        <p>Copyright © 2026 InStaGi | Created With <span class="ikon-hati"><?= instagi_ikon('hati') ?></span> · <a href="/login" class="footer-admin-link">Admin</a></p>
    </footer>
</body>
</html>
