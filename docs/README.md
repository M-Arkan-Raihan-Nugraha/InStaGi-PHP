# Dokumentasi InStaGi V3 (PHP Native)

Folder ini berisi seluruh dokumentasi teknis proyek. **README di root sengaja
dibiarkan singkat** (deskripsi proyek + cara menjalankan) agar tampilan repo bersih.

| Dokumen | Isi |
|---------|-----|
| [`PERBAIKAN.md`](PERBAIKAN.md) | Riwayat lengkap perbaikan V2 → V3: bug kritis, korektnes data, paket keamanan P0 (S1–S13), dan konsistensi penamaan IMT |
| [`ROADMAP-UPGRADE.md`](ROADMAP-UPGRADE.md) | Hasil audit: apa yang **masih bisa** di-upgrade (P0–P3), lengkap dengan lokasi presisi di kode |
| [`DEPLOY-AEONFREE.md`](DEPLOY-AEONFREE.md) | Panduan unggah ke hosting **AeonFree** langkah demi langkah (cPanel, MySQL; tabel dibuat otomatis) |
| [`schema.sql`](schema.sql) | Skema tabel `imt_history` untuk dibuat manual lewat phpMyAdmin |

## Ringkas

- **Stack:** HTML5 + CSS3 (vanilla) · PHP Native · MySQL (mysqli).
- **Rumus perhitungan** (IMT, kalori Harris-Benedict, ambang klasifikasi) ada di
  **satu tempat**: `includes/gizi.php`. Jangan menduplikasinya.
- **Istilah di tampilan = IMT** (Indeks Massa Tubuh), bukan "BMI". Berkas form
  sudah di-rename menjadi `imt.php` (CSS: `css/imt.css`); nama lama `bmi.php`
  dialihkan otomatis (301). Tabel database juga sudah bernama `imt_history`;
  tabel lama `bmi_history` dipindahkan otomatis (RENAME TABLE, data ikut).
  Lihat "Konsistensi Penamaan" di [`PERBAIKAN.md`](PERBAIKAN.md).
- **Kredensial** ada di `includes/db_credentials.php` dan `includes/auth_config.php`
  (diblokir dari web). Sebaiknya diisi lewat environment variable di produksi.
