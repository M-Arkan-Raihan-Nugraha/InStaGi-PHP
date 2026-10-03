-- =============================================================================
-- InStaGi — Skema Database
-- -----------------------------------------------------------------------------
-- File ini OPSIONAL. Tabel `imt_history` sudah dibuat OTOMATIS oleh aplikasi
-- saat pertama kali dibuka (lihat includes/db.php). Gunakan file ini hanya
-- bila Anda lebih suka membuat tabel secara manual lewat phpMyAdmin.
--
-- PENTING (nama tabel versi lama):
--   Bila di database Anda sudah ada tabel lama `bmi_history` berisi data,
--   TIDAK perlu memindahkan apa pun secara manual — aplikasi akan otomatis
--   me-rename `bmi_history` menjadi `imt_history` (RENAME TABLE, data ikut
--   pindah) saat pertama kali dibuka. Bila ingin melakukannya sendiri:
--       RENAME TABLE `bmi_history` TO `imt_history`;
--
-- Cara pakai:
--   1. Buat database lewat cPanel (mis. namauser_instagi).
--   2. Buka phpMyAdmin → pilih database itu → tab "Import" → pilih file ini.
--   3. Isi kredensial di includes/db_credentials.php sesuai database tersebut.
-- =============================================================================

CREATE TABLE IF NOT EXISTS `imt_history` (
    `id`            INT(11)      NOT NULL AUTO_INCREMENT,
    `tanggal`       DATE         NOT NULL,
    `nama`          VARCHAR(255) NOT NULL,
    `usia`          INT(3)       NOT NULL,
    `jenis_kelamin` VARCHAR(20)  NOT NULL,
    `no_hp`         VARCHAR(20)  NOT NULL,
    `berat_badan`   DECIMAL(5,1) NOT NULL,
    `tinggi_badan`  INT(5)       NOT NULL,
    `aktivitas`     DECIMAL(4,3) NOT NULL,
    `kalori`        INT(11)      NOT NULL,
    `imt`           DECIMAL(4,1) NOT NULL,
    `status_gizi`   VARCHAR(50)  NOT NULL,
    `saran`         TEXT,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Opsional (disarankan bila data sudah banyak): percepat ORDER BY tanggal.
-- ALTER TABLE `imt_history` ADD INDEX `idx_tanggal` (`tanggal`);
