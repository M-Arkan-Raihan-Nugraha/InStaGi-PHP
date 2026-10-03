# Panduan Deploy ke Hosting AeonFree

AeonFree adalah hosting gratis berbasis **cPanel** dengan **PHP 8.2**, **MySQL 8.0**,
dan **dukungan penuh `.htaccess`** — cocok untuk proyek ini tanpa perlu ubah kode.

> ✅ **Tidak ada langkah migrasi.** Aplikasi membuat **tabel otomatis** saat pertama
> kali dibuka (lihat `includes/db.php`). Anda hanya perlu membuat *database*-nya di
> cPanel lalu mengisi kredensial. Tidak butuh SSH/terminal sama sekali.

---

## Ringkasan Alur

```
Daftar → cPanel → Buat database & user → Isi db_credentials.php
      → Upload file → Buka aplikasi (tabel dibuat otomatis) → Ganti password admin
      → Uji coba
```

Perkiraan waktu: **15–25 menit** (terbanyak menunggu proses upload gambar leaflet).

---

## 1. Daftar & Buka cPanel

1. Buka <https://aeonfree.com> → **Get Started** → daftar (gratis, tanpa kartu kredit).
2. Setelah akun aktif, buka **cPanel** dan catat:
   - **cPanel username** (mis. `if0_12345678`) — dipakai sebagai awalan nama database.
   - **Domain/subdomain** yang diberikan (mis. `instagi.aeonfree.com`) atau
     domain sendiri yang sudah diarahkan ke nameserver AeonFree.
3. Di cPanel → **PHP Settings** (atau *Select PHP Version*) → pilih **PHP 8.x**
   (8.1 ke atas). Proyek ini sudah kompatibel; jangan pilih PHP 5.x.

---

## 2. Buat Database MySQL

1. cPanel → **MySQL® Databases**.
2. **Create New Database** → mis. `instagi`. Sistem akan memberi nama lengkap
   seperti `if0_12345678_instagi`. **Catat nama lengkapnya.**
3. **Add New User** → buat user + password kuat. Catat
   nama lengkapnya (mis. `if0_12345678_admin`).
4. **Add User To Database** → pilih user & database tadi → centang **ALL PRIVILEGES** → *Make Changes*.
5. Catat **host database**. Di hosting berbasis cPanel/IfastNet biasanya **bukan**
   `localhost`, melainkan sesuatu seperti `sql300.aeonfree.com` atau
   `sql1XX.epizy.com`. **Host yang benar tertulis di halaman MySQL Databases**
   (kolom *Hostname*). Kalau tidak terlihat, tanyakan di forum AeonFree.

---

## 3. Isi Kredensial di Proyek

Edit **`includes/db_credentials.php`** (sebelum atau sesudah upload):

```php
return [
    'host' => 'sqlXXX.aeonfree.com',          // <-- HOST dari langkah 2.5
    'user' => 'if0_12345678_admin',           // <-- user lengkap (ada awalan)
    'pass' => 'PasswordKuatAnda',
    'name' => 'if0_12345678_instagi',         // <-- nama DB lengkap (ada awalan)
    'port' => 3306,
];
```

Lalu edit **`includes/auth_config.php`** untuk akun admin:

```php
return [
    'username'      => 'instagiop',
    'password_hash' => '<hash-baru>',   // lihat cara membuat di bawah
];
```

Membuat hash password baru (jalankan di komputer yang ada PHP-nya):

```bash
php -r "echo password_hash('PasswordBaruAnda', PASSWORD_DEFAULT);"
```

> **Alternatif lebih aman (tanpa menaruh rahasia di file):** biarkan nilai di
> kedua file itu kosong dan isi lewat environment variable di cPanel →
> *MultiPHP INI Editor* atau `.htaccess` `SetEnv`:
> ```
> INSTAGI_DB_HOST, INSTAGI_DB_USER, INSTAGI_DB_PASS, INSTAGI_DB_NAME, INSTAGI_DB_PORT
> INSTAGI_ADMIN_USER, INSTAGI_ADMIN_HASH
> ```
> Kode sudah membaca env var ini lebih dulu daripada nilai di file.

---

## 4. Upload File

**Cara A — File Manager (cPanel):**
1. Kompres folder proyek menjadi `.zip`.
2. cPanel → **File Manager** → masuk ke **`htdocs`** (atau folder root
   subdomain/domain Anda).
3. Upload `.zip` → klik kanan → **Extract**.
4. Pastikan `index.html`, `imt.php`, `login.php`, dll. berada **langsung** di dalam
   `htdocs/`, bukan di dalam subfolder ekstra seperti `htdocs/V3 PHP Native/`.

**Cara B — FTP (disarankan, lebih andal untuk file besar):**
Gunakan FileZilla dengan detail FTP dari cPanel, upload ke folder root yang sama.

**Yang TIDAK perlu di-upload:**
- `logs/php-error.log` — hapus dulu; folder `logs/` akan dibuat otomatis.
- File `*.md` di `docs/` boleh di-upload (sudah diblokir `.htaccess`) atau tidak,
  sama saja — dokumentasi tidak dibutuhkan di server.

**Tips:** total aset gambar ~4 MB (leaflet + thumbnail). Jika File Manager
terputus, gunakan FTP.

**Izin file (bila perlu, via File Manager → Permissions):**
- Folder → `755`
- File `.php` / `.html` / `.css` → `644`

---

## 5. Buka Aplikasi (tabel dibuat otomatis)

Tidak ada langkah migrasi manual. Cukup buka:

1. **`https://<domain-anda>/imt.php`** — saat request pertama, `includes/db.php`
   otomatis membuat tabel `bmi_history` bila belum ada (aman diulang).
2. Bila kredensial di `includes/db_credentials.php` salah, akan muncul halaman
   **"Layanan sedang tidak tersedia" (503)** — perbaiki kredensial lalu muat ulang.
3. Untuk memastikan koneksi & tabel beres, login admin lalu buka **`/admin.php`**
   (daftar riwayat tampil kosong = siap dipakai).

> **Opsional:** bila lebih suka menyiapkan tabel manual, import
> **`docs/schema.sql`** lewat phpMyAdmin (tab *Import*). Hasilnya sama dengan
> pembuatan otomatis.

---

## 6. Uji Coba

| Halaman | Yang diharapkan |
|---------|-----------------|
| `/` | Landing page tampil, logo & tombol berfungsi |
| `/imt.php` | Form bisa diisi & dihitung, hasil tersimpan, redirect ke `result.php` |
| `/result.php` | Hasil IMT, bar IMT, saran, tombol WhatsApp, galeri leaflet, tombol PDF |
| `/login.php` | Login admin berhasil |
| `/admin.php` | Tabel data tampil, edit & hapus berfungsi |
| Export CSV | File `data_responden_imt_*.csv` terunduh, terbuka rapi di Excel |

---

## 7. Setelah Deploy — Wajib & Disarankan

**Wajib:**
1. **Ganti password admin** (dari hash contoh) — lihat langkah 3.
2. **Ganti password database** bila password lama pernah tersimpan di file yang
   ikut tersalin/terdistribusi — perlakukan sebagai bocor.

**Disarankan:**
3. Aktifkan **SSL** (cPanel → *SSL/TLS Status* → Run AutoSSL). Cookie sesi akan
   otomatis menjadi `Secure` saat HTTPS aktif.
   > **Sudah otomatis:** `.htaccess` root sudah memaksa alamat kanonik —
   > semua permintaan (`http://`, `www.`, host lain) dialihkan (301) ke
   > **`https://instagi.iceiy.com/`** dengan jalur & query tetap utuh.
   > Jadi cukup pastikan SSL aktif; pengalihan HTTP→HTTPS sudah ditangani.
4. Batasi ukuran `logs/php-error.log` (hapus berkala bila sudah besar).

---

## 8. Troubleshooting

| Gejala | Kemungkinan penyebab & solusi |
|--------|-------------------------------|
| **HTTP 500 / halaman putih** | Lihat `logs/php-error.log`. Sering karena `db_credentials.php` belum diisi. |
| **"Layanan sedang tidak tersedia" (503)** | Koneksi DB gagal: host/user/pass/name salah, atau user belum diberi privilege ke database. |
| **Tabel kosong padahal data lama ada** | Data lama dibuat dengan skema berbeda. Aplikasi membuat tabel baru yang benar secara otomatis; data lama perlu diimpor manual bila diperlukan. |
| **Halaman tampil sebagai kode / terunduh** | PHP tidak aktif atau versi PHP salah. Set **PHP 8.x** di cPanel → PHP Settings. |
| **`db_credentials.php` bisa dibuka di browser** | `.htaccess` diabaikan (server bukan Apache/LiteSpeed). Hubungi support atau pindahkan file kredensial ke luar web root. |
| **Gambar leaflet tidak muncul** | Pastikan `assets/leaflet/` dan `assets/leaflet/thumbs/` ikut ter-upload dan izinnya `755`/`644`. |
| **PDF DBMP tidak muncul / tidak bisa dibuka** | Pastikan `assets/dbmp/dbmp-lengkap.pdf` (dan `dbmp-cover.jpg`) ikut ter-upload dengan izin `644`. |
| **Upload terputus** | Pakai FTP, bukan File Manager. |
| **Excel menampilkan CSV berantakan** | Seharusnya tidak — file sudah memuat BOM UTF-8. Pastikan yang diunduh adalah hasil export dari aplikasi (bukan file lama). |

---

## 9. Catatan Kompatibilitas

- **PHP 8.2** (AeonFree): proyek ini sudah lolos `php -l` dan tidak memakai API
  yang dihapus di PHP 8. Fitur yang dipakai: `mysqli`, `password_hash`,
  `random_bytes`, `hash_equals` — semuanya tersedia.
- **MySQL 8.0**: skema memakai `utf8mb4` + `InnoDB`, didukung penuh. Sintaks
  `ADD COLUMN IF NOT EXISTS` yang bermasalah **sudah dihapus** dari kode.
- **.htaccess**: didukung penuh. File sudah memakai `<IfModule>` sehingga aman
  walau modul tertentu tidak aktif.
- **Ekstensi PHP yang dibutuhkan:** `mysqli` (wajib), `gd` (hanya untuk membuat
  thumbnail leaflet — thumbnail sudah disertakan, jadi tidak wajib di server).
- **Tidak butuh** Composer, Node.js, atau SSH.
