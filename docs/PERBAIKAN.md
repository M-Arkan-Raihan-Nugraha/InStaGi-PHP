# Riwayat Perbaikan InStaGi V3

Dokumen ini memindahkan seluruh catatan perbaikan yang dulu menumpuk di `README.md`.
Folder V3 adalah salinan dari **V2 PHP Native** yang disiapkan khusus untuk perbaikan.

> **Prinsip yang dipegang:** **rumus perhitungan tidak diubah.** IMT, kalori
> (Harris-Benedict × faktor aktivitas), ambang klasifikasi, dan pembulatan tetap
> persis seperti aslinya. Yang diperbaiki hanya bug, keamanan, korektnes data,
> dan penamaan tampilan.

---

## 1. Perbaikan V2 → V3

### Kritis

| # | Masalah | Perbaikan |
|---|---------|-----------|
| 1 | `ALTER TABLE ... ADD COLUMN IF NOT EXISTS` **tidak didukung MySQL** (hanya MariaDB) → `mysqli_sql_exception` fatal di setiap halaman | Sintaks MariaDB **dihapus**; pembuatan tabel/kolom kini memakai `IF NOT EXISTS` + pengecekan `INFORMATION_SCHEMA` (aman di MySQL 8) |
| 2 | `error_log` menunjuk `includes/logs/` (tidak ada) → logging gagal senyap | Path diperbaiki ke `dirname(__DIR__).'/logs/'` + folder dibuat otomatis |
| 3 | `$koneksi->connect_error` tak pernah tereksekusi (mysqli sudah exception) → halaman putih | Koneksi dibungkus `try/catch`, `mysqli_report(MYSQLI_REPORT_OFF)`; error tampil sebagai pesan ramah / JSON sesuai konteks |
| 4 | Kredensial DB & admin hardcoded di file yang ikut disalin | Dipindah ke `includes/db_credentials.php` & `includes/auth_config.php`, bisa dioverride via environment variable, dan diblokir dari web |

### Data & konsistensi

| # | Masalah | Perbaikan |
|---|---------|-----------|
| 5 | Kalori di `imt.php` dibulatkan ke ratusan, tapi di `api/update_record.php` tidak → **edit data mengubah nilai kalori**; teks `saran` juga berbeda | Dibuat `includes/gizi.php` sebagai satu sumber kebenaran, dipakai kedua file |
| 6 | Input di-`htmlspecialchars` **sebelum disimpan** → `A & B` tersimpan `A &amp; B` (rusak & makin rusak saat ditampilkan) | Data disimpan mentah; escaping hanya saat menampilkan. Data lama yang sudah rusak perlu diperbaiki manual bila diperlukan |
| 7 | Redirect `login.php` di `api/export_csv.php` salah (→ `api/login.php`, 404) | Diperbaiki menjadi `../login.php` |
| 8 | `fputcsv()` memicu deprecation di PHP 8.4+; CSV tanpa BOM tampil rusak di Excel | Parameter eksplisit + BOM UTF-8 + proteksi CSV injection (cell berawalan `= + - @`) |

### Fitur baru

| # | Fitur | Keterangan |
|---|-------|-----------|
| 9 | **Galeri leaflet informasi gizi** di `result.php` | 8 leaflet InStaGi (Gizi Seimbang, Karbohidrat, Protein, Lemak & Kolesterol, Vitamin & Serat, Natrium, Gula, Purin). Thumbnail 600×900 (~1,2 MB total) untuk galeri, gambar asli untuk unduhan. Klik → pratinjau besar (lightbox) dengan navigasi tombol/panah keyboard/geser layar sentuh, plus tombol **Unduh Gambar** |
| 10 | **Galeri contoh menu sesuai kebutuhan kalori** di `result.php` | 5 leaflet InStaGi "Contoh Menu" (1.400 · 1.500 · 1.700 · 2.000 · 2.400 kkal). Menu yang **paling dekat** dengan estimasi kebutuhan kalori pengguna ditandai otomatis. Thumbnail 600×900 untuk galeri, gambar asli untuk unduhan; pratinjau lightbox sama seperti galeri leaflet |

### Lain-lain

| # | Masalah | Perbaikan |
|---|---------|-----------|
| 11 | Folder `includes/` & `logs/` bisa diakses langsung dari browser | Ditambahkan `.htaccess` (deny all) |
| 12 | Koneksi gagal → pengguna hanya melihat halaman putih | Pesan error ramah (HTML) atau JSON, detail tetap masuk log |

### Leaflet Informasi Gizi

Gambar leaflet disimpan di `assets/leaflet/` dan **tidak** ikut dalam `#printable-area`,
sehingga tombol "Unduh PDF" pada halaman hasil tidak terpengaruh.

Menambah/mengganti leaflet:

1. Taruh gambar baru di `assets/leaflet/` (nama file = slug, mis. `sumber_zat_besi.jpeg`).
2. Daftarkan di `includes/leaflet.php` pada `instagi_leaflet_list()`
   (kunci array = slug, isi `judul`, `subjudul`, `ringkas`, `tag`, `unduh`).
3. Buat thumbnail: `php includes/leaflet_build_thumbs.php`
   (butuh ekstensi GD; folder `thumbs/` dibuat otomatis).
4. Buka `result.php` — kartu muncul otomatis.

Leaflet yang file gambarnya tidak ditemukan akan **dilewati tanpa error**; bila folder
`thumbs/` belum ada, galeri otomatis memakai gambar ukuran penuh.

### Contoh Menu Sesuai Kebutuhan Kalori

Gambar contoh menu disimpan di `assets/menu/` dan juga **tidak** ikut dalam
`#printable-area`, sehingga PDF hasil analisis tidak terpengaruh.

Galeri menampilkan 5 pilihan menu (1.400 · 1.500 · 1.700 · 2.000 · 2.400 kkal).
Menu yang **paling dekat** dengan `kalori_harian` pengguna diberi tanda
*"Sesuai kebutuhan Anda"*. Bila jarak dua menu sama, dipilih kalori yang lebih kecil.

Menambah/mengganti menu:

1. Taruh gambar baru di `assets/menu/` dengan nama pola `<kkal>kkal.jpeg`
   (mis. `1800kkal.jpeg` → kunci `1800`).
2. Daftarkan di `includes/menu.php` pada `instagi_menu_list()`
   (kunci array = total kkal; isi `judul`, `subjudul`, `ringkas`, `tag`, `unduh`).
3. Buat thumbnail: `php includes/menu_build_thumbs.php`
   (butuh ekstensi GD; folder `thumbs/` dibuat otomatis).
4. Buka `result.php` — kartu muncul otomatis dan penanda menu terdekat ikut menyesuaikan.

Menu yang file gambarnya tidak ditemukan akan **dilewati tanpa error**; bila folder
`thumbs/` belum ada, galeri otomatis memakai gambar ukuran penuh.

### DBMP — Daftar Bahan Makanan Penukar

DBMP disajikan sebagai **satu berkas PDF** gabungan 8 halaman golongan bahan makanan
(`assets/dbmp/dbmp-lengkap.pdf`), dengan gambar sampul ringan (`assets/dbmp/dbmp-cover.jpg`).
Bagian ini juga berada di luar `#printable-area`, jadi tidak ikut ke dalam PDF hasil analisis.

- Data & pencarian berkas: `includes/dbmp.php`
- Tampilan: blok `<!-- ===== DBMP ... -->` di `result.php`; gaya di `css/result.css`
- Bila `dbmp-lengkap.pdf` belum ada, bagian DBMP **otomatis tidak ditampilkan** (tanpa error).

Memperbarui DBMP cukup dengan mengganti `assets/dbmp/dbmp-lengkap.pdf`
(dan `dbmp-cover.jpg`) — tidak perlu mengubah kode.

**Tombol "Lihat PDF"** membuka pratinjau **di dalam halaman** (lightbox berisi `<iframe>`),
bukan langsung mengunduh. Ini disengaja: sebagian peramban/situs tidak punya penampil PDF
bawaan sehingga PDF "Lihat" akan terunduh. Tombol **Unduh PDF** tetap mengunduh berkas.
Agar server tidak memaksa unduh, `.htaccess` mengirim `Content-Disposition: inline` untuk `.pdf`.

### Tata letak hasil (`result.php`)

Urutan blok di halaman hasil:

1. Kartu ringkasan hasil + saran (di dalam `#printable-area`)
2. **Tiga tombol aksi:** Hubungi WhatsApp · Simpan ke PDF · Hitung Ulang / Kembali
3. **Leaflet Informasi Gizi** (galeri, ringkas)
4. **Contoh Menu Sesuai Kebutuhan Kalori** (galeri, menu terdekat ditandai)
5. **DBMP — Daftar Bahan Makanan Penukar** (satu berkas PDF)

Leaflet, contoh menu, & DBMP sengaja diletakkan **setelah** tombol aksi dan **di luar**
`#printable-area`, supaya tombol "Simpan ke PDF" hanya mencetak kartu hasil — bukan
leaflet/contoh menu/DBMP. Ketiganya juga disembunyikan saat mencetak lewat aturan
`@media print` di `css/result.css`.

Agar halaman tidak memanjang ke bawah, kartu leaflet **dan** kartu contoh menu memakai
thumbnail pendek (180 px; 140 px di tablet, 120 px di ponsel) dan deskripsi dibatasi
**maksimal 2 baris** (`-webkit-line-clamp: 2`).

---

## 2. Paket Keamanan P0

Semua perbaikan di bawah ini **tidak mengubah satu pun rumus perhitungan**
(`includes/gizi.php` tidak disentuh; `imt.php` & `api/update_record.php` tetap
memakai `instagi_hitung_semua()` apa adanya).

| # | Masalah sebelumnya | Perbaikan |
|---|--------------------|-----------|
| S1 | **CSRF tidak ada** pada delete/update/export/form input | `includes/session.php` menyediakan `instagi_csrf_token()` / `instagi_csrf_check()`. Token diverifikasi di `api/delete_record.php`, `api/update_record.php` (**HTTP 403** bila tidak sah), `login.php`, dan form publik `imt.php`. `admin.php` mengirim token otomatis lewat `$.ajaxSetup` (`X-CSRF-Token`) |
| S2 | Session fixation | `session_regenerate_id(true)` dipanggil tepat setelah login berhasil |
| S3 | Cookie session tanpa proteksi | Cookie sesi kini `HttpOnly` + `SameSite=Lax` + `Secure` (otomatis bila HTTPS), `use_strict_mode=1`, nama khusus `INSTAGISESSID`. Diatur terpusat di `includes/session.php` |
| S4 | Brute force bebas | Pembatas percobaan login: maks. 5 gagal → jeda 15 menit (berbasis sesi). Sisa percobaan ditampilkan ke pengguna |
| S5 | **Detail error SQL bocor ke klien** (`$stmt->error`) | Detail hanya masuk `error_log` server; klien menerima pesan umum. Berlaku di `imt.php`, `api/delete_record.php`, `api/update_record.php` |
| S6 | `login.php` sukses redirect **tanpa `exit`** | Ditambahkan `exit` (dan pesan error kini di-escape) |
| S7 | Respons API selalu **HTTP 200** | Status benar: **401** (belum login), **403** (CSRF), **405** (metode salah), **500** (gagal query), **503** (DB tak terjangkau) |
| S8 | Cek login dilakukan **setelah** koneksi DB | Login/CSRF diperiksa lebih dulu → permintaan tak sah tidak menyentuh database |
| S9 | `logout.php` tidak menghapus cookie sesi | Cookie sesi dihapus di browser + `session_destroy()` |
| S10 | Skrip pembuatan tabel bisa diakses siapa pun dari web | Tidak ada lagi skrip migrasi terpisah; pembuatan tabel terjadi otomatis di dalam `includes/db.php` saat aplikasi dijalankan, dan `.htaccess` root memblokir file sensitif (`.md`, `.log`, `.sql`, file titik) |
| S11 | Tanpa security header | `.htaccess` root menambahkan `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, `Options -Indexes` |
| S12 | Escaping ganda pada pesan WhatsApp & nama file PDF | Pesan WA memakai nilai **mentah** + `http_build_query()` (`A & B` tidak lagi jadi `A &amp; B`); nama file PDF dibersihkan dari karakter ilegal |
| S13 | Empty-state tabel `colspan="11"` padahal 14 kolom | Diperbaiki menjadi `colspan="14"` |
| S14 | Folder **`.git/` bisa diakses publik** (`.git/config` → HTTP 200) sehingga riwayat & konfigurasi repo berpotensi terbaca bila folder ikut ter-upload | `.htaccess` menambahkan `RedirectMatch 404` untuk `.git`, `.svn`, `.hg` (aturan `<FilesMatch "^\.">` saja tidak cukup, karena berkas di dalam `.git/` tidak berawalan titik) |
| S15 | PDF DBMP **terunduh** padahal diminta "Lihat" | `.htaccess` mengirim `Content-Disposition: inline` untuk `.pdf`; tampilan memakai lightbox `<iframe>` sehingga tidak bergantung pada penampil PDF peramban |

---

## 3. Konsistensi Penamaan: IMT (bukan BMI)

Istilah yang dipakai di seluruh **tampilan** adalah **IMT (Indeks Massa Tubuh)** —
padanan resmi Indonesia untuk *BMI*. Rename sudah dilakukan, tetapi beberapa
nama teknis **sengaja dipertahankan** agar data & URL yang sudah beredar tidak rusak:

| Bagian | Status | Alasan |
|--------|--------|--------|
| Teks, judul, label, kolom CSV, header tabel | **IMT** | Yang dibaca pengguna |
| Kelas CSS (`imt-bar-container`, `imt-marker`, …) | **IMT** | Internal, aman diganti |
| Kunci session (`$_SESSION['imt_result']`) | **IMT** | Internal, aman diganti |
| Nama file unduhan CSV (`data_responden_imt_*.csv`) | **IMT** | Yang dilihat pengguna |
| Tabel database `imt_history` | **imt_history** | Sudah di-rename dari `bmi_history`; tabel lama dipindahkan otomatis lewat `RENAME TABLE` di `includes/db.php`, jadi data tidak hilang |
| File `imt.php`, `css/imt.css` | **IMT** | Sudah di-rename; nama lama `bmi.php`/`css/bmi.css` dialihkan otomatis (301) agar tautan/bookmark lama tidak rusak |
| Rumus & ambang batas di `includes/gizi.php` | **tidak disentuh** | Sesuai permintaan; hanya teks saran yang dirapikan |

> Bila kelak ingin menuntaskan rename tabel, lakukan sebagai langkah terpisah:
> buat tabel `imt_history` → salin data → tambahkan view/alias → baru ubah kode.

### Inkonsistensi lain yang ikut dirapikan

| # | Temuan | Perbaikan |
|---|--------|-----------|
| 1 | `admin.php` menghasilkan class `obesitas`, sedangkan `gizi.php` memakai `obese` — dua nama untuk satu kategori | Disatukan menjadi **`obese`** (CSS juga disesuaikan) |
| 2 | `css/admin.css` punya selector mati `.status.berat-badan-berlebih` — tak pernah dihasilkan kode | Dihapus |
| 3 | Header tabel admin "**Gender**", padahal form & CSV memakai "Jenis Kelamin" | Diseragamkan menjadi "Jenis Kelamin" |
| 4 | Nomor WA ditulis **dua kali** dengan format berbeda (`6281224948388` & `081224948388`) | Satu sumber di `includes/config.php` (`INSTAGI_WA_NUMBER`, bisa dioverride env); versi `08…` diturunkan otomatis |
| 5 | `index.html`: alt `Instagi Logo` (kapital salah) | `InStaGi Logo` |
| 6 | `index.html` & `result.php`: kata "anda" di tengah kalimat | "Anda" |
| 7 | Typo teks saran: *"Berat badan **ada masih dibawah** normal,"*, *"**memalui**"*, koma-splice | Dirapikan (hanya teks `'saran'`; rumus & ambang batas tidak berubah) |

---

## 4. Perbaikan Frontend (prioritas tinggi)

Empat perbaikan yang paling berdampak pada pengalaman pengguna.

### 1. Validasi input di `imt.php` (sebelumnya kosong)

| Kolom | Sebelum | Sesudah |
|-------|---------|---------|
| Usia | bebas (`999`, `-5` diterima) | `min="1" max="120" step="1" inputmode="numeric"` |
| Berat badan | bebas (`5000` diterima) | `min="2" max="500" step="0.1" inputmode="decimal"` |
| Tinggi badan | `INT` di DB, tapi tanpa batas & tanpa `step` | `min="40" max="250" step="1" inputmode="numeric"` |
| Nama | tanpa batas | `maxlength="100" autocomplete="name"` |
| No. HP | tanpa pola | `pattern` 8–20 digit (boleh `+ ( ) - spasi`), `inputmode="tel"` |

Ditambahkan pula **petunjuk kecil** (`.field-hint`) di bawah tiap kolom, dan
**validasi di sisi server** (bukan hanya peramban) dengan pesan yang jelas.

> **Catatan Tinggi Badan:** kolom `tinggi_badan` di database bertipe `INT(5)`.
> Bila pengguna memasukkan TB desimal (mis. `165,5`), MySQL akan membulatkannya
> menjadi `166` sehingga nilai yang **tersimpan** berbeda dari yang **dihitung**.
> Karena itu TB kini diwajibkan bilangan bulat (dan desimal ditolak dengan pesan
> jelas), agar data yang tersimpan selalu sama dengan yang dihitung.
> **Skema database tidak diubah.**

### 2. Pengaman klik-ganda (anti-duplikat)

Sebelumnya `imt.php` **tidak punya JavaScript sama sekali**. Bila pengguna
mengeklik tombol "Hitung IMT & Simpan" dua kali dengan cepat, form terkirim dua
kali dan **dua baris data kembar** tersimpan ke `imt_history`.

Sekarang dilindungi dua lapis:

1. **Sisi peramban** — tombol langsung dinonaktifkan dan berubah menjadi
   "Menghitung..." saat form dikirim; kiriman kedua diabaikan. Tombol dipulihkan
   otomatis bila pengguna kembali ke halaman (tombol *back*).
2. **Sisi server** — ada penanda `submitted`; kiriman tanpa penanda ditolak
   (berguna bila JavaScript dimatikan).

### 3. Pratinjau tautan (meta description + Open Graph)

`index.php` (halaman utama), `imt.php`, dan `result.php` kini punya
`meta description`, `theme-color`, dan **Open Graph + Twitter Card** lengkap,
sehingga saat tautan dibagikan lewat WhatsApp/Facebook/X akan muncul judul,
deskripsi, dan gambar pratinjau (bukan tautan polos).

Gambar pratinjau `assets/og-image.jpg` (1200×630) dibuat dari logo InStaGi.

**Alamat Open Graph dibuat DINAMIS** (`og:url`/`og:image` mengikuti domain yang
sedang dipakai). Karena itu halaman utama dipindah dari `index.html` ke
`index.php` — kalau domain hosting berganti (mis. dari `*.iceiy.com` ke
`*.aeonfree.com`), pratinjau tautan tetap benar tanpa mengedit kode. Berkas
`index.html` lama otomatis dialihkan (301) ke halaman utama.

### 4. Optimasi logo (berat & tata letak)

| Sebelum | Sesudah |
|---------|---------|
| `logo.png` 1024×1024, **242 KB** | 512×466, **66 KB** (−73%) |

Ruang putih di sekeliling logo dipangkas, ukuran diperkecil, dan dikompres.
Semua tag `<img>` logo (termasuk header PDF) kini punya atribut
`width`/`height` sehingga halaman **tidak bergeser (CLS)** saat logo dimuat.

### Tambahan kecil (sekalian)

- `prefers-reduced-motion` — hormati pengguna yang membatasi animasi.
- `:focus-visible` — penanda fokus jelas untuk pengguna keyboard.

---

## 4b. `ERR_SSL_PROTOCOL_ERROR` lewat DNS AdGuard — DIAGNOSIS & PERBAIKAN

**Gejala.** Saat `instagi.iceiy.com` dibuka dari perangkat yang memakai **DNS
AdGuard**, muncul *"Situs ini tidak dapat menyediakan sambungan aman —
ERR_SSL_PROTOCOL_ERROR"*. Dari jaringan/DNS lain, situs normal.

**Penyebab (sudah diverifikasi, bukan dugaan).** AdGuard DNS **memblokir**
domain ini dan mengarahkannya ke alamat *sinkhole* `94.140.14.33` yang **tidak
melayani TLS sama sekali** — karena itu jabat tangan SSL gagal.

Bukti pengukuran:

| Sumber resolusi | `instagi.iceiy.com` → |
|-----------------|------------------------|
| Cloudflare DoH (`1.1.1.1`) | `185.27.134.181` (server asli) |
| Google DoH (`8.8.8.8`) | `185.27.134.181` |
| AdGuard **unfiltered** (`unfiltered.adguard-dns.com`) | `185.27.134.181` |
| AdGuard **filtered** (`dns.adguard-dns.com`) | `94.140.14.33` ← sinkhole |

Resolusi yang sama, hanya berbeda lapisan filter → blokir datang dari
**AdGuard**, bukan dari server, sertifikat, maupun kode aplikasi.

Pengujian langsung memperkuat: koneksi ke IP asli `185.27.134.181` dengan SNI
benar menghasilkan **HTTP 200 + sertifikat sah** (ZeroSSL, `CN=iceiy.com`,
mencakup `*.iceiy.com`), sedangkan koneksi ke IP AdGuard gagal TLS
(`tlsv1 alert internal error`) tanpa sertifikat.

Blokir ini bersifat **intermiten** (terukur ±2–3 dari 10 resolusi) — sesuai
keluhan "kadang muncul, kadang tidak".

**Perbaikan yang diterapkan di sisi aplikasi (agar tahan pindah domain):**

1. `.htaccess` dibuat **domain-agnostik** — aturan kanonik & redirect tidak lagi
   menuliskan `instagi.iceiy.com` secara tetap, tetapi memakai `%{HTTP_HOST}`.
   Untuk iceiy.com hasilnya identik; bila pindah ke `*.aeonfree.com` situs tidak
   lagi terlempar balik ke iceiy.com.
2. Halaman utama `index.html` → **`index.php`**, agar `og:url`/`og:image`
   **dinamis** mengikuti domain aktif (helper `instagi_base_url()` di
   `includes/config.php`). `index.html` lama dialihkan 301 ke halaman utama.
3. Catatan lengkap ditulis di akhir `.htaccess` dan di
   `docs/DEPLOY-AEONFREE.md` §8 (Troubleshooting).

**Catatan penting:** blokir ini berada di **sisi AdGuard**, jadi tidak bisa
"diperbaiki" dari kode. Tindakan yang benar (di luar kode):

- **Laporkan salah-blokir** ke AdGuard: <https://reports.adguard.com/en/website_report.html>
- Di perangkat terdampak: matikan proteksi DNS AdGuard, atau ganti DNS ke
  `1.1.1.1` / `8.8.8.8`.
- Atau **pindah ke domain yang tidak diblokir** (mis. subdomain `*.aeonfree.com`).
  Aplikasi sudah disiapkan agar perpindahan ini tidak merusak apa pun.

---

## 5. Verifikasi

- `php -l` pada **seluruh** file `.php` → bersih.
- JS inline (`admin.php`, `result.php`) → `node --check` OK.
- Uji render `result.php`: kelas `imt-*` terpakai, sisa `bmi-*` = 0; nomor WA
  `wa.me/6281224948388` + tampilan `081224948388` berasal dari satu sumber;
  nama `Budi & Santoso` → `&amp;` di HTML tetapi `%26` di URL WA (escaping ganda beres).
- Semua route merespons sesuai harapan: `200 / 302 / 401 / 403 / 503`.
- Perbaikan sebelumnya juga diverifikasi end-to-end lewat Apache + mod_php
  (session, `.htaccess`, redirect, pembuatan tabel otomatis) dengan MySQL lokal:
  **36/36 pengujian lulus** — alur input → hasil → admin → API (get/update/export/delete),
  proteksi folder sensitif, dan perbaikan data lama.
- **Perbaikan frontend (bagian 4)** diuji end-to-end dengan PHP dev server + MySQL
  lokal (database uji terpisah, lalu dihapus):
  - Validasi server: usia `999`/`-5`, BB `5000`, TB `999`/`20`, TB desimal → semua
    **ditolak** dengan pesan yang tepat; input wajar (`bb 60.5`, `tb 165`) **diterima**.
  - Penjaga klik-ganda: kiriman tanpa penanda `submitted` ditolak; database berisi
    **tepat 2 baris** (tidak ada duplikat) setelah 7 percobaan pengiriman.
  - CSRF salah → ditolak.
  - JS anti-klik-ganda diuji statis dengan Node: **7/7 lulus** (kiriman ke-1 lolos,
    ke-2 dicegah, tombol pulih saat kembali, validasi peramban dihormati).
  - Pola `no_hp` divalidasi di mode regex HTML5 modern (`v`) → valid.
  - Rumus: `includes/gizi.php` **tidak berubah** (diff kosong); baris rumus asli
    `round($bb_kg / ($tb_meter * $tb_meter), 1)` dan `round($tdee + $kalori_adj)`
    masih utuh.
  - Ukuran terkirim: `logo.png` 66.431 B, `og-image.jpg` 56.879 B.

---

## 6. Yang Masih Direkomendasikan (belum dikerjakan)

Lihat [`ROADMAP-UPGRADE.md`](ROADMAP-UPGRADE.md) untuk daftar lengkap beserta lokasi
kodenya. Yang paling penting:

1. **Rotasi password DB & admin** — password lama pernah tersimpan plaintext dan
   ikut tersalin, jadi harus dianggap bocor. (Wajib, manual, di hosting.)
2. ~~**Validasi rentang input** (usia/TB/BB) di server~~ — **sudah dikerjakan**
   (lihat bagian 4 di atas).
3. **Klasifikasi IMT memakai nilai mentah** — saat ini IMT dibulatkan lebih dulu
   sebelum diklasifikasi (`includes/gizi.php`), sehingga nilai tepat di batas bisa
   masuk kategori yang kurang tepat. **Belum diubah karena menyentuh rumus.**
4. SRI pada CDN + naikkan versi jQuery/DataTables.
5. Index pada `imt_history` dan server-side pagination di `admin.php`.
6. **Duplikasi CSS** — `:root`, `.main-footer`, `.container`, `box-shadow`, dan
   `border-radius: 12px` disalin identik di kelima file CSS. Bisa dipindahkan ke
   satu `css/base.css` bersama (aman, hanya gaya).
7. **`css/result.css`** — ada selector kembar: `.imt-segments-wrapper` (2×) dan
   `@media (max-width: 768px)` (2×). Bisa digabung tanpa mengubah tampilan.
8. **SRI (`integrity`) pada skrip CDN** — `html2pdf.js`, jQuery, dan DataTables
   dimuat dari CDN tanpa pengaman integritas.
