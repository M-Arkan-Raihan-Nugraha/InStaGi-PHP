# InStaGi V3 — Roadmap Upgrade (Hasil Audit)

Dokumen ini dihasilkan dari audit menyeluruh atas seluruh file di `V3 PHP Native/`
(PHP 8.5, lint bersih). Isinya: apa yang **masih bisa di-upgrade**, mengapa penting,
di mana lokasinya, dan contoh perbaikan. **Belum ada kode aplikasi yang diubah.**

Legenda prioritas:
- **P0** = keamanan / risiko langsung → kerjakan lebih dulu
- **P1** = korektnes data & performa
- **P2** = UX, aksesibilitas, kualitas kode
- **P3** = fitur opsional (nilai tambah)

> Catatan: README sudah mendaftarkan 6 item keamanan sebagai "belum dikerjakan"
> (CSRF, `session_regenerate_id`, cookie flags, rate-limit, validasi rentang,
> rotasi password DB). Audit ini mengonfirmasi semuanya **masih benar-benar belum ada**
> dan menambahkan temuan baru di luar daftar tersebut.

---

## Ringkasan temuan baru (di luar daftar README)

| # | Temuan | Lokasi |
|---|--------|--------|
| A | Kredensial DB asli dalam plaintext (host, user, password) | `includes/db_credentials.php:18-22` |
| B | Hash admin + username produksi ikut tersimpan | `includes/auth_config.php:17-18` |
| C | Detail error SQL bocor ke respons client | `api/delete_record.php:55,80`, `api/update_record.php:69`, `imt.php:105` |
| D | `login.php` redirect sukses tanpa `exit` | `login.php:44` |
| E | CDN tanpa SRI + versi usang | `admin.php:38,39,138,139`, `result.php:266` |
| F | Tidak ada `.htaccess` di root (proteksi hanya di subfolder) | root |
| G | ~~`migrate.php` masih ada di root~~ **SUDAH DIHAPUS** — tabel kini dibuat otomatis di `includes/db.php` | — |
| H | **IMT dibulatkan sebelum diklasifikasi** → salah kategori di batas | `includes/gizi.php:130-131` |
| I | Nama & saran ter-escape ganda masuk pesan WhatsApp | `result.php:23,27,39` |
| J | Nama file PDF ikut ter-escape | `result.php:299` |
| K | Nomor WA hardcoded & duplikat | `result.php:36,163` |
| L | Tidak ada index pada `bmi_history` (selalu ORDER BY tanggal) | `includes/db.php` (setup tabel) |
| M | Admin memuat seluruh tabel ke browser (client-side DataTables) | `admin.php:15` |
| N | Export CSV buffered (seluruh hasil ke memori) | `api/export_csv.php:67` |
| O | Tombol hapus per-baris tidak pernah dirender (dead code) | `admin.php:121` vs `:338` |
| P | `colspan="11"` padahal tabel 15 kolom | `admin.php:127` |
| Q | Respons API selalu HTTP 200 walau tidak diizinkan | `api/*.php` |
| R | Bukan git repo; tanpa CI/unit test | root |

---

## P0 — Keamanan

### 1. Rotasi kredensial DB & admin
- **Masalah:** `includes/db_credentials.php` menyimpan password DB asli
  dalam plaintext (nilainya sengaja **tidak ditulis** di dokumen ini). Karena file
  ini pernah ikut tersalin / terdistribusi, password harus dianggap **bocor**.
- **Aksi:**
  1. Ganti password DB via cPanel/hosting.
  2. Ganti password admin (`php -r "echo password_hash('...', PASSWORD_DEFAULT);"`).
  3. Simpan nilai baru **hanya** via environment variable (`INSTAGI_DB_*`,
     `INSTAGI_ADMIN_*`); kosongkan nilai di file agar tidak ada rahasia di repo.
  4. Tambahkan `includes/db_credentials.php` & `includes/auth_config.php` ke `.gitignore`
     (atau sediakan versi `.example`).

### 2. CSRF token pada operasi tulis
- **Masalah:** `api/delete_record.php`, `api/update_record.php`, `api/export_csv.php`
  hanya memverifikasi `$_SESSION['loggedin']`. Cookie sesi ikut terkirim dari situs lain
  → CSRF.
- **Contoh pendekatan (token per-sesi):**
  ```php
  // includes/csrf.php
  function instagi_csrf_token(): string {
      if (empty($_SESSION['csrf'])) {
          $_SESSION['csrf'] = bin2hex(random_bytes(32));
      }
      return $_SESSION['csrf'];
  }
  function instagi_csrf_check(): bool {
      return isset($_POST['csrf'], $_SESSION['csrf'])
          && hash_equals($_SESSION['csrf'], $_POST['csrf']);
  }
  ```
  - Render token di `admin.php` (mis. `<meta name="csrf-token" content="...">`),
    sertakan di setiap `$.ajax` (header `X-CSRF-Token` atau field `csrf`).
  - Tolak (403) bila token tidak cocok.

### 3. Session hardening
- **`login.php`** — setelah kredensial benar:
  ```php
  session_regenerate_id(true);
  $_SESSION['loggedin'] = true;
  $_SESSION['username'] = $username;
  header('location: admin.php');
  exit;   // <-- tambahkan exit (temuan D)
  ```
- **Cookie flags** (set sebelum `session_start()` pada tiap entry point, atau sekali
  di `includes/config.php`):
  ```php
  ini_set('session.cookie_httponly', '1');
  ini_set('session.cookie_samesite', 'Lax');   // 'Strict' bila tidak ada alur cross-site
  ini_set('session.use_strict_mode', '1');
  if (!empty($_SERVER['HTTPS'])) {
      ini_set('session.cookie_secure', '1');
  }
  ```
  Karena `session_start()` dipanggil di banyak file, **taruh konfigurasi ini di
  `includes/config.php`** lalu `session_start()` terpusat.

### 4. Rate-limit / lockout login
- Simpan hitungan gagal per-IP + per-username di tabel atau file cache; kunci sementara
  (mis. 5 gagal → tunggu 15 menit). Sertakan delay konstan untuk mencegah user enumeration.

### 5. Buang detail error dari respons client (temuan C)
- **Sekarang:** `'Gagal menghapus data: ' . $stmt->error`
- **Perbaikan:** catat detail ke `error_log()`, kirim pesan generik ke client:
  ```php
  error_log('[InStaGi] delete gagal: ' . $stmt->error);
  $response['message'] = 'Gagal menghapus data. Silakan coba lagi.';
  ```
  Terapkan di `api/delete_record.php:55,80`, `api/update_record.php:69`, `imt.php:105`.

### 6. `.htaccess` root (temuan F) — **SUDAH DIKERJAKAN**
- `/.htaccess` di root sudah ada: `Options -Indexes`, menolak akses ke
  `includes/`, `logs/`, file `.md`/`.sql`, file titik, plus security header.
- Skrip migrasi terpisah sudah **dihapus**; pembuatan tabel dilakukan otomatis
  dan aman di `includes/db.php`.
- **Penting untuk non-Apache (Nginx/LiteSpeed tanpa AllowOverride):** `.htaccess`
  diabaikan. Pastikan setara dengan blok server-level, atau pindahkan kredensial &
  log **ke luar web root**.

### 7. Security header
- Tambah di `includes/config.php` (atau `/.htaccess`):
  ```
  X-Frame-Options: SAMEORIGIN
  X-Content-Type-Options: nosniff
  Referrer-Policy: strict-origin-when-cross-origin
  Content-Security-Policy: default-src 'self'; img-src 'self' data:; script-src 'self' https://cdn.datatables.net https://code.jquery.com https://cdnjs.cloudflare.com
  ```

### 8. SRI + update CDN (temuan E)
- Tambah atribut `integrity` + `crossorigin="anonymous"` pada semua `<script>`/`<link>`
  eksternal, dan naikkan versi:
  - jQuery 3.5.1 → 3.7.1
  - DataTables 1.11.5 / responsive 2.2.9 → DataTables 2.x
  - html2pdf 0.10.1 → versi terbaru

---

## P1 — Korektnes data

### 9. Klasifikasi IMT harus pakai nilai mentah (temuan H)
- **Masalah:** `instagi_hitung_semua()` menghitung `imt = round(bb/tb², 1)` lalu
  mengklasifikasi dari nilai yang **sudah dibulatkan**. Contoh: IMT asli `18,47`
  → dibulatkan `18,5` → masuk "normal", padahal seharusnya underweight.
- **Perbaikan:** klasifikasi memakai nilai presisi, pembulatan hanya untuk tampilan:
  ```php
  $imt_raw = $bb_kg / ($tb_meter * $tb_meter);
  $kategori = instagi_kategori_gizi($imt_raw);      // pakai nilai mentah
  'imt' => round($imt_raw, 1),                       // simpan untuk tampilan
  ```
  Perhatikan juga **celah ambang**: saat ini `<=18.49`, lalu `18.5–24.9`, `25–27`, `>27`.
  Nilai `24.95` (setelah presisi) jatuh ke mana? Definisikan ulang batas secara
  eksklusif (mis. `<18.5`, `18.5–<25`, `25–<27`, `>=27`) agar tidak ada celah/overlap.

### 10. Escaping ganda pada WhatsApp & PDF (temuan I, J)
- `$nama`/`$saran` sudah di-`htmlspecialchars` (untuk HTML), lalu dipakai lagi di
  `urlencode()` pesan WA → teks WA rusak (`A &amp; B`).
- **Perbaikan:** simpan nilai mentah terpisah:
  ```php
  $nama_raw  = $result['nama'];
  $saran_raw = $result['saran'];
  $nama = htmlspecialchars($nama_raw);   // untuk HTML
  $saran = htmlspecialchars($saran_raw); // untuk HTML
  $whatsapp_message = urlencode("Halo, saya *{$nama_raw}* ... saran: *{$saran_raw}*");
  ```
  Nama file PDF juga pakai nilai mentah (`$nama_raw`), bukan yang ter-escape.

### 11. Validasi rentang input
- Tambah batas wajar (di `imt.php` **dan** `api/update_record.php` supaya konsisten):
  usia 1–120, TB 50–250 cm, BB 5–300 kg, `no_hp` format Indonesia, `aktivitas` harus
  salah satu nilai yang diizinkan (1.2/1.375/1.55/1.725/1.9).
- Jangan hanya mengandalkan atribut HTML; **validasi di server adalah otoritatif**.

### 12. Rumus kalori
- Pertimbangkan beralih dari **Harris-Benedict (1919)** ke **Mifflin-St Jeor**
  (lebih akurat untuk populasi modern):
  - Pria: `10·BB + 6.25·TB − 5·Usia + 5`
  - Wanita: `10·BB + 6.25·TB − 5·Usia − 161`
- Karena `includes/gizi.php` adalah satu sumber kebenaran, perubahan ini otomatis
  konsisten di input maupun edit.

---

## P1 — Performa & database

### 13. Index pada `bmi_history` (temuan L)
```sql
ALTER TABLE bmi_history ADD INDEX idx_tanggal (tanggal);
-- bila sering difilter per status:
ALTER TABLE bmi_history ADD INDEX idx_status (status_gizi);
```
Tambahkan lewat phpMyAdmin (idempoten: cek `INFORMATION_SCHEMA.STATISTICS` lebih dulu),
atau tambahkan ke blok `CREATE TABLE` di `includes/db.php`.

### 14. Server-side processing di admin (temuan M)
- Saat ini `admin.php:15` mengambil **semua** baris, dikirim ke browser, diproses
  DataTables di klien. Untuk ribuan baris jadi berat.
- **Perbaikan:** DataTables server-side + endpoint `api/datatable.php`
  (LIMIT/OFFSET, pencarian, sorting) agar hanya 10–25 baris per halaman yang dikirim.

### 15. Streaming export CSV (temuan N)
- Gunakan `mysqli::query(..., MYSQLI_USE_RESULT)` dan tulis per baris (sudah dilakukan),
  atau `unbuffered` + `flush()` berkala untuk dataset besar.

### 16. Rotasi log
- `logs/php-error.log` tumbuh tanpa batas. Tambahkan rotasi (logrotate di server,
  atau cek ukuran di `includes/config.php` dan arsipkan bila > mis. 5 MB).

### 17. Optimasi aset gambar
- Gambar leaflet asli ~2,7 MB, thumbnail ~1,2 MB total. Konversi ke **WebP** +
  `srcset`/`sizes`; tambah `width`/`height` untuk mencegah layout shift.

---

## P2 — UX, aksesibilitas, kualitas kode

### 18. Tombol hapus per-baris (temuan O)
- Handler `$('#historyTable tbody').on('click', '.delete-btn', ...)` ada, tapi tidak ada
  tombol `.delete-btn` di baris tabel (`admin.php:121` hanya tombol Edit).
- **Pilihan:** render tombol Hapus per baris, **atau** hapus handler dead-code bila
  memang sengaja hanya mengandalkan bulk delete.

### 19. `colspan` empty state (temuan P)
- `admin.php:127` → ubah `colspan="11"` menjadi `colspan="15"` (jumlah kolom sebenarnya).

### 20. Aksesibilitas modal edit
- Tambah `role="dialog"`, `aria-modal="true"`, focus-trap, tutup dengan `Esc`,
  dan kembalikan fokus ke tombol pemicu setelah ditutup.

### 21. Kode status HTTP pada API (temuan Q)
- Kirim `http_response_code(401)` / `403` saat tidak terautentikasi / tidak diizinkan,
  bukan selalu 200. Front-end bisa membedakan sesi kedaluwarsa (arahkan ke login).

### 22. `logout.php` lengkap
- Hapus juga cookie sesi:
  ```php
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
      $p = session_get_cookie_params();
      setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
  }
  session_destroy();
  ```

### 23. Unit test untuk `includes/gizi.php`
- Fungsi murni (IMT, kategori, kalori) sangat mudah diuji. Tambah PHPUnit dengan
  kasus batas: 18.49/18.5, 24.9/25, 27/27.01, dan verifikasi konsistensi
  `imt.php` ↔ `api/update_record.php`.

### 24. Konsistensi UI
- `index.html` (statis) terpisah dari halaman PHP lain → pertimbangkan partial
  header/footer bersama agar perubahan tampilan konsisten.

---

## P2 — DevOps

- **Inisialisasi git** + `.gitignore` (kredensial, `logs/`, `assets/leaflet/thumbs/` bila
  dapat dibangun ulang) + riwayat perubahan yang bisa dilacak.
- **CI** sederhana: `php -l` semua file + PHPUnit (temuan 23).
- **`.env.example`** yang mendokumentasikan `INSTAGI_*` tanpa nilai rahasia.
- Opsional: konfigurasi Docker/dev-setup untuk reproduksibilitas.

---

## P3 — Fitur (opsional)

- **Grafik tren IMT per responden** (Chart.js) memanfaatkan `bmi_history` yang sudah ada.
- **Multi-user admin + peran** (admin/operator) dan **audit log** perubahan data.
- **Ekspor PDF/Excel server-side** (menggantikan html2pdf client-side) dan **impor massal**.
- **Filter & pencarian server-side** (status gizi, rentang tanggal) — terkait item 14.
- **Anti-spam pada `imt.php`** (form publik): honeypot + rate-limit per-IP.
- **Verifikasi format No. HP** Indonesia.

---

## Rekomendasi urutan eksekusi

1. **Rotasi kredensial** + `.htaccess` root *(cepat, dampak besar)*
2. **CSRF + session hardening** (`regenerate_id`, cookie flags, rate-limit, `exit`)
3. **Buang detail error** dari respons client
4. **Perbaikan korektnes**: klasifikasi IMT pakai nilai mentah, escaping WA/PDF, validasi rentang
5. **Index DB + server-side pagination + SRI/update CDN**
6. Bersih-bersih kode (tombol hapus, colspan, aksesibilitas) + unit test + git/CI

---

*Dokumen ini tidak mengubah kode aplikasi. Setiap item menyertakan lokasi presisi agar
dapat dikerjakan bertahap tanpa mengubah perilaku/skema secara destruktif.*
