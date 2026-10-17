# Skrip uji InStaGi (dijalankan manual)

Folder ini menyimpan skrip pengujian yang dipakai untuk memverifikasi aplikasi
**secara nyata** (bukan sekadar membaca kode). Isinya ikut tersimpan di git supaya
tidak hilang.

| Berkas | Kegunaan |
|--------|----------|
| `uji_validasi.php` | Uji unit `includes/validasi.php` tanpa database (69 kasus: input sah, ditolak, pesan, format No. HP, aktivitas, tanggal, fuzz) |
| `uji_e2e.sh` | Uji end-to-end: input form → hasil → login admin → API (get/update/delete/export) → Monitoring, **termasuk memaksa `prepare()` gagal** untuk membuktikan penjaga JSON 500 |
| `uji_fitur.sh` | Uji fitur permukaan: menu baru, tanpa Login Admin, background lightblue, akun berpassword (buat/pasang/ganti), grafik Monitoring, layanan WhatsApp, Bestie InStaGi |

## Cara menjalankan (Windows + XAMPP)

```bash
# 1) Nyalakan MariaDB khusus proyek (port 3306)
/d/SMAKZIE/PRODUKTIF/XAMPP/mysql/bin/mysqld.exe \
  --defaults-file="D:/SMAKZIE/PRODUKTIF/XAMPP/instagi-db/my.ini" --console

# 2) Nyalakan server PHP dengan database UJI + admin uji (jangan DB produksi!)
cd "D:/Codingan/Instagi/V3 PHP Native"
INSTAGI_DB_HOST=127.0.0.1 INSTAGI_DB_USER=root INSTAGI_DB_PASS= \
INSTAGI_DB_NAME=instagi_v3_uji INSTAGI_DB_PORT=3306 \
INSTAGI_ADMIN_USER=admin_uji \
INSTAGI_ADMIN_HASH='<hash bcrypt dari password uji>' \
/d/SMAKZIE/PRODUKTIF/XAMPP/php/php -S 127.0.0.1:8099 -t "D:/Codingan/Instagi/V3 PHP Native"

# 3) Jalankan uji (dari Git Bash)
php docs/uji/uji_validasi.php
bash docs/uji/uji_e2e.sh      # membuat ulang database uji sendiri
bash docs/uji/uji_fitur.sh
```

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
