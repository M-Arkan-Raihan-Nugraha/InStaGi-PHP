# Skrip uji InStaGi (dijalankan manual)

Folder ini menyimpan skrip pengujian yang dipakai untuk memverifikasi aplikasi
**secara nyata** (bukan sekadar membaca kode). Isinya ikut tersimpan di git supaya
tidak hilang.

| Berkas | Kegunaan |
|--------|----------|
| `Unit/test_validation.php` | Uji unit `includes/services/validation.php` tanpa database (71 kasus: input sah, ditolak, pesan, format No. HP, aktivitas, tanggal, fuzz) |
| `Unit/test_nutrition.php` | Uji unit perhitungan rumus IMT, kalori, dan ambang batas gizi (14 kasus) |
| `Unit/test_bestie.php` | Uji unit data etalase mitra Bestie InStaGi dan integritas berkas WebP (73 kasus) |
| `Unit/test_gallery.php` | Uji unit seluruh materi galeri informasi gizi (Leaflet, Menu, Resep, Panduan, Tips Bugar, DBMP) dan keterhubungan aset WebP (180 kasus) |
| `E2E/test_e2e.sh` | Uji end-to-end: input form → hasil → login admin → API (get/update/delete/export) → Monitoring, **termasuk memaksa `prepare()` gagal** untuk membuktikan penjaga JSON 500. **Membuat ulang (DROP + CREATE) database uji**, jadi jalankan ini lebih dulu sebelum `test_features.sh`. |
| `E2E/test_features.sh` | Uji fitur permukaan: menu baru, tanpa Login Admin, background lightblue, akun berpassword (buat/pasang/ganti), grafik Monitoring, layanan WhatsApp, Bestie InStaGi, **alamat rapi tanpa `.php`** (bagian H). Bersifat stateful — jalankan setelah `test_e2e.sh`. |
| `dev_router.php` | Peniru bagian 0d `.htaccess` untuk server bawaan PHP (`php -S`), supaya alamat rapi juga bisa diuji lokal. Produksi tidak memakainya. |

## Cara menjalankan (Windows + XAMPP)

```bash
# 1) Nyalakan MariaDB khusus proyek (port 3306)
/d/SMAKZIE/PRODUKTIF/XAMPP/mysql/bin/mysqld.exe \
  --defaults-file="D:/SMAKZIE/PRODUKTIF/XAMPP/instagi-db/my.ini" --console

# 2) Nyalakan server PHP dengan database UJI + admin uji (jangan DB produksi!)
#    PENTING: pakai dev_router.php supaya alamat rapi (/imt, /monitoring, …) ikut
#    berlaku — server bawaan PHP tidak membaca .htaccess.
cd "D:/Codingan/Instagi/V3 PHP Native"
INSTAGI_DB_HOST=127.0.0.1 INSTAGI_DB_USER=root INSTAGI_DB_PASS= \
INSTAGI_DB_NAME=instagi_v3_uji INSTAGI_DB_PORT=3306 \
INSTAGI_ADMIN_USER=admin_uji \
INSTAGI_ADMIN_HASH='<hash bcrypt dari password uji>' \
/d/SMAKZIE/PRODUKTIF/XAMPP/php/php -S 127.0.0.1:8099 tests/dev_router.php

# 3) Jalankan uji (dari Git Bash), urutannya penting:
php tests/Unit/test_validation.php
php tests/Unit/test_nutrition.php
php tests/Unit/test_bestie.php
php tests/Unit/test_gallery.php
bash tests/E2E/test_e2e.sh      # membuat ulang database uji sendiri
bash tests/E2E/test_features.sh # stateful: jalankan SETELAH test_e2e.sh
```

### Menguji `.htaccess` dengan Apache sungguhan (opsional, lebih kuat)

Server bawaan PHP mengabaikan `.htaccess`, jadi aturan alamat rapi di produksi
sebaiknya sekali waktu diuji dengan Apache asli:

```bash
# 1) Konfigurasi kecil (port 8080, AllowOverride All, DocumentRoot = proyek)
#    contoh lengkap ada di catatan ronde 9 docs/PERBAIKAN.md
# 2) Jalankan
/d/SMAKZIE/PRODUKTIF/XAMPP/apache/bin/httpd.exe -f "<berkas-conf>" -t   # cek sintaks
/d/SMAKZIE/PRODUKTIF/XAMPP/apache/bin/httpd.exe -f "<berkas-conf>"
# 3) Uji (aturan HTTPS di .htaccess dilewati dengan header ini)
curl -s -H "X-Forwarded-Proto: https" -o /dev/null -w '%{http_code}\n' http://127.0.0.1:8080/imt
```

Catatan: konfigurasi itu memasang `SetEnv INSTAGI_DB_*` ke database UJI.

Catatan penting:

- **Jangan** arahkan `INSTAGI_DB_*` ke database produksi — `uji_e2e.sh` menghapus
  dan membuat ulang database ujinya (`DROP DATABASE instagi_v3_uji`).
- Hash admin uji dibuat dengan
  `php -r 'echo password_hash("uji12345", PASSWORD_DEFAULT);'` dan **wajib
  dikutip tunggal** di bash (tanda `$` pada hash bcrypt akan mengembang).
- Lingkungan uji selalu dibersihkan setelah selesai: hentikan server PHP
  (`taskkill /F /PID <pid>` — `//PID` gagal di Git Bash), lalu
  `mysqladmin -u root shutdown`.
- Skrip memakai satu folder kerja bergaya Windows
  (`C:/Users/.../hermes/cache/scratch/e2e`) untuk cookie dan berkas keluaran —
  `curl` versi Windows tidak memahami jalur gaya POSIX seperti `/tmp`.
