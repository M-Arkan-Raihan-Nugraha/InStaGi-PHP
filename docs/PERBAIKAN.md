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

---

## 7. Ronde Perbaikan Kritis (17 Okt 2026)

Semua perubahan di bawah ini **tidak menyentuh `includes/gizi.php`** (rumus IMT,
kalori Harris-Benedict, dan ambang klasifikasi tetap persis seperti semula —
diverifikasi: `diff` terhadap HEAD kosong). Backup berkas sebelum perubahan ada
di `_backup-v3/kritis-20261017/` (di luar paket upload).

### K1 — Jalur API bisa "mati" dengan halaman kosong (paling kritis)

**Masalah.** `includes/db.php` sengaja mematikan mode exception mysqli, sehingga
`prepare()` yang gagal mengembalikan `false`. Di `api/get_record.php`,
`api/update_record.php`, dan `api/delete_record.php` nilai itu langsung dipakai
(`$stmt->bind_param(...)`), sehingga PHP melempar fatal
*"Call to a member function bind_param() on bool"*. Karena `display_errors` mati,
pengguna/admin hanya melihat halaman putih — dan karena responsnya bukan JSON,
antarmuka admin menampilkan *"Error parsing server response"*. `imt.php` sudah
memakai penjaga ini sejak awal; ketiga endpoint API belum.

**Perbaikan.** Penjaga `if ($stmt === false) { error_log(...); http_response_code(500); JSON pesan ramah }`
di **semua** pemanggilan `prepare()` pada ketiga endpoint (5 titik), dengan pola
dan gaya yang sama seperti `imt.php`. Detail teknis tetap hanya masuk log server.

### K2 — Satu sumber aturan validasi: `includes/validasi.php` (baru)

**Masalah.** Aturan validasi lengkap hanya ada di `imt.php`; `api/update_record.php`
hanya memeriksa `> 0`. Akibatnya jalur edit admin bisa menyimpan nilai yang tidak
mungkin diketik responden sendiri:

| Celah | Dampak |
|-------|--------|
| `aktivitas` tidak diperiksa | nilai apa pun (mis. `999`) tersimpan → kalori tidak masuk akal |
| `jenis_kelamin` tidak diperiksa | salah tulis satu huruf dianggap Perempuan oleh rumus |
| No. HP tidak mengikuti aturan login Monitoring | akun responden terbentuk tetapi **tidak pernah bisa dipakai masuk** (mis. `81234567890` tanpa awalan 0) |
| `tanggal` diambil apa adanya dari POST | kolom `readonly` hanya pengaman peramban → nilai bisa dipalsukan/kosong dan membuat penyimpanan gagal |

**Perbaikan.** Modul baru `includes/validasi.php` (prinsip sama dengan `gizi.php`:
satu sumber kebenaran) dipakai **kedua** jalur — `imt.php` dan `api/update_record.php`:

- `nama` 1–100 karakter; `usia` 1–120; `bb` 2–500 kg; `tb` 40–250 cm **dan wajib bilangan bulat**;
- `jenis_kelamin` wajib tepat `Laki-laki` / `Perempuan`;
- `aktivitas` wajib salah satu dari `1.2 · 1.375 · 1.55 · 1.725 · 1.9` (toleran terhadap penulisan `1.550` dari database);
- `no_hp` dinormalkan (satu fungsi dengan halaman Monitoring) dan wajib cocok `^0\d{7,14}$`;
- `tanggal` wajib tanggal nyata `YYYY-MM-DD`; pada form publik tanggal masa depan ditolak, sedangkan pada jalur admin tanggal rusak **tidak menolak** penyimpanan — nilai lama dipertahankan lewat `IFNULL(NULLIF(?, ''), tanggal)` supaya baris lama bertanggal rusak tidak menjadi mustahil diperbaiki;
- kiriman berbentuk array (mis. `no_hp[]=...`) tidak lagi memicu peringatan PHP *"Array to string conversion"*.

Petunjuk No. HP di `imt.php` ikut disesuaikan agar sama dengan aturan servernya.

### K3 — Halaman Monitoring: pembatas percobaan & pesan seragam

**Masalah.** Halaman Monitoring memakai prinsip "1 nama + 1 No. HP = 1 akun"
tanpa password dan tanpa batas percobaan: nama & nomor bisa dicoba sebanyak apa
pun sampai cocok, lalu riwayat gizi orang lain terbaca.

**Perbaikan.** Pembatas percobaan berbasis sesi (sama seperti login admin):
**maksimal 5 percobaan gagal → jeda 15 menit**, penghitung direset saat berhasil
masuk, dan pesan kegagalan diseragamkan ("Nama atau No. HP tidak cocok dengan
data kami") agar tidak memudahkan menebak akun mana yang terdaftar.

> Catatan: ini mengurangi penyalahgunaan, **bukan** menggantikan autentikasi.
> Perlindungan penuh (mis. PIN/password ringan) adalah keputusan produk —
> belum dikerjakan.

### K4 — Status HTTP API yang lebih tepat

| Endpoint | Sebelum | Sesudah |
|----------|---------|---------|
| `api/update_record.php` | selalu 200 (`success:false`) | **400** ID tidak sah · **422** input tidak lolos validasi · **404** ID tidak ada · **500** gagal query |
| `api/get_record.php`, `api/delete_record.php` | 200 walau query gagal | **500** + JSON pesan ramah |

### K5 — Inkonsistensi kecil yang ikut dirapikan

- `api/update_record.php`: bila tidak ada baris yang berubah, dipastikan dulu
  datanya masih ada — ID yang sudah dihapus kini menghasilkan pesan
  "Data tidak ditemukan atau sudah dihapus" alih-alih "berhasil diperbarui".

### Verifikasi ronde ini

| Uji | Hasil |
|-----|-------|
| `php -l` seluruh berkas PHP (28 berkas) | bersih |
| `diff includes/gizi.php` vs HEAD | identik — rumus utuh |
| Uji unit `includes/validasi.php` (68 kasus: sah/tolak/pesan/format HP/aktivitas/tanggal/fuzz) | **69/69 lulus**, tanpa warning PHP |
| Uji end-to-end dengan MariaDB 10.4 + server PHP lokal (input → hasil → login admin → API get/update/delete/export → Monitoring) | **61/61 lulus** |
| Paksa `prepare()` gagal (kolom `saran` di-rename **di DB uji**) | respons **JSON 500** dengan pesan ramah, tanpa bocoran detail SQL — bukan halaman kosong |

Skrip ujinya disimpan di `_backup-v3/uji-20261017/` (`uji_validasi.php`,
`uji_e2e.sh`, `hasil_e2e.txt`) dan bisa dijalankan ulang; `uji_e2e.sh` membuat
ulang database ujinya sendiri secara otomatis.

### Masih MENUNGGU KEPUTUSAN (tidak diubah karena menyentuh rumus/ambang)

1. **Celah ambang `instagi_kategori_gizi()`** — IMT mentah di antara `18,49–18,5`
   atau `24,9–25` jatuh ke cabang terakhir sehingga berlabel **Obesitas**
   (terbukti: `24.9001` → Obesitas). Saat ini **laten**, karena kedua pemanggil
   selalu mengirim nilai yang sudah dibulatkan 1 desimal dan grid 0,1 tidak punya
   nilai di dalam celah itu. Menjadi berbahaya bila item "klasifikasi pakai nilai
   mentah" dikerjakan apa adanya: 0,46% kombinasi BB/TB wajar (199 dari 42.761)
   akan salah dilabeli Obesitas.
2. **Pembulatan sebelum klasifikasi** — IMT mentah `18,47` → tersimpan `18,5` →
   "normal" (seharusnya underweight); `24,95` → `25,0` → "overweight".
   Perbaikan keduanya berarti mengubah ambang batas → butuh persetujuan pemilik
   proyek.

---

## 8. Ronde Permintaan Fitur & Tampilan (17 Okt 2026)

Delapan permintaan pemilik proyek, semuanya sudah dikerjakan. **Rumus di
`includes/gizi.php` tetap tidak disentuh** (diverifikasi dengan `diff`).

| # | Permintaan | Hasil |
|---|-----------|-------|
| 1 | Hapus "Login Admin" dari menu awal & navigasi | Kartu Login Admin di `index.php` dihapus; tautan di `includes/nav.php` dihapus; tautan "Login Admin" di footer `imt.php` ikut dihapus (pintu masuk admin tidak tampil untuk umum). Halaman admin tetap ada di `login.php`, dan tautan "Admin" hanya muncul SETELAH admin masuk |
| 2 | Samakan semua background menjadi lightblue | `--secondary-color` di kelima CSS (`index`, `imt`, `result`, `admin`, `login`) diseragamkan menjadi **`#ADD8E6` (lightblue)** — sebelumnya `#CFE8FF` |
| 3 | Rename menu | "Hitung IMT" → **Cek Status Gizi** · "Informasi" → **Informasi Gizi** · "Monitoring" → **Monitoring Status Gizi** (dipakai di navigasi, kartu menu beranda, judul halaman, dan tombol form) |
| 4 | Opsi pembuatan akun dengan password | Form Cek Status Gizi punya bagian **"Akun Monitoring (opsional)"**: centang "Simpan dengan akun aman" → muncul kolom Password + Ulangi (min. 8 karakter, di-hash `password_hash`). Riwayat jadi hanya bisa dibuka dengan password |
| 5 | Rapikan halaman Monitoring | Tata letak dibagi jelas: Ringkasan → Grafik → Riwayat → Keamanan Akun; ringkasan diperluas (jumlah, IMT terakhir, status, rata-rata, rentang IMT, perubahan IMT, tanggal) |
| 6 | Grafik statistik Monitoring | Grafik garis Chart.js (`cdn.jsdelivr.net`, versi mayor 4) berisi IMT per tanggal, titik berwarna sesuai status gizi, plus garis panduan 18,5 / 25 / 27 (digambar plugin kecil sendiri, bukan plugin tambahan). 12 pengukuran terakhir. Bila pustaka gagal dimuat, halaman tetap utuh dan riwayat tetap terbaca |
| 7 | Konsultasi gizi & katering gizi dengan tombol WhatsApp | Bagian **Layanan Kami** di beranda: kartu Konsultasi Gizi & Katering Gizi masing-masing dengan tombol WhatsApp berisi pesan siap kirim. Nomor diambil dari `includes/config.php` (`INSTAGI_WA_KONSULTASI`, `INSTAGI_WA_KATERING`) dan bisa dioverride lewat environment variable |
| 8 | Bestie InStaGi (wadah sponsor/kerja sama iklan) | Bagian **Bestie InStaGi** di beranda + tombol WhatsApp kerja sama (`INSTAGI_WA_KERJASAMA`). Logo mitra cukup diletakkan di `assets/bestie/` dan tampil otomatis; bila kosong muncul ajakan (bukan error) |
| 9 | Responsive cross-platform | Semua bagian baru memakai grid `auto-fit`, grafik `responsive` dengan tinggi tetap, tabel/riwayat tetap kartu; diuji lebar 360 / 414 / 768 / 1280 px tanpa geser horizontal |

### Keamanan akun (bagian dari permintaan no. 4)

- Akun **berpassword** tidak bisa dibajak lewat form publik: `imt.php` hanya
  boleh MENGISI password bila akun belum punya password — password yang sudah ada
  tidak pernah ditimpa dari form publik.
- Mengganti password akun yang sudah berpassword **wajib** memasukkan password
  lama.
- Masuk berhasil kini juga `session_regenerate_id(true)` (anti session fixation),
  sama seperti login admin.
- Pembatas percobaan (5 gagal → jeda 15 menit) tetap berlaku, sekarang menghitung
  juga percobaan dengan password salah.
- Lupa password: tidak ada email di aplikasi ini, jadi disediakan tautan
  WhatsApp di halaman Monitoring. Pemilik juga bisa mengosongkan password sebuah
  akun lewat phpMyAdmin bila perlu:
  `UPDATE responden SET password_hash = NULL WHERE nama = '...' AND no_hp = '...';`
- Kolom `password_hash` dibuat OTOMATIS oleh `includes/db.php` (cek
  `INFORMATION_SCHEMA`, aman di MySQL 8) — tidak ada langkah migrasi manual.

### Berkas yang berubah di ronde ini

`includes/config.php` (nomor WA per layanan + helper `instagi_wa_url()`),
`includes/db.php` (kolom `password_hash`), `includes/responden.php` (password,
login berpassword, ganti password, anti session fixation), `includes/validasi.php`
(`instagi_validasi_password()`), `includes/nav.php` (rename + hapus Login Admin),
`index.php` (rename kartu + Layanan + Bestie InStaGi), `imt.php` (akun aman +
rename), `monitoring.php` (rapikan + grafik + keamanan akun), `css/imt.css` &
`css/pages.css` (gaya baru), `css/*.css` (background lightblue),
`docs/schema.sql` (kolom `password_hash`), `assets/bestie/` (folder logo mitra).

---

## 9. Ronde Perapian Struktur & Alamat Rapi (17 Okt 2026)

### 9.1 Keputusan: rapi-in, BUKAN rombak ke MVC

Proyek ini **tidak** dipindah ke MVC/kerangka kerja. Alasannya: skala kecil (7
halaman, 2 tabel), hosting gratis tanpa SSH/Composer, rumus gizi wajib beku, dan
tidak ada satu pun bug yang diperbaiki oleh rombak MVC. Yang dikerjakan adalah
merapikan tata letak berkas + menghapus duplikasi, tanpa mengubah perilaku.
Rincian pertimbangannya ada di percakapan 17 Okt 2026 (perbandingan MVC penuh vs
MVC ringan vs rapi-in).

### 9.2 Duplikasi yang dihapus

- **`<head>` + navigasi** sebelumnya disalin di 7 halaman (±35 baris × 7).
  Sekarang satu berkas: `includes/layout_head.php` (isi `$judul`, `$deskripsi`,
  `$og_path`, `$css`, opsional `$css_ekstra` / `$meta_ekstra`, lalu `require`).
- **Footer + penutup dokumen**: `includes/layout_footer.php`.
- **Gaya dasar** yang dulu disalin 5×: `css/base.css` (`:root`, `body`,
  `.container`, `.main-footer`). Nilai khusus tiap halaman (max-width halaman
  admin, padding beranda, tebal huruf beranda, `margin-top:auto` di login) tetap
  di CSS halaman masing-masing.

### 9.3 Alamat rapi tanpa `.php` (tanpa router, tanpa memindahkan kode)

Bagian **0d** di `.htaccess` root: `/imt` → rewrite internal ke `imt.php`.

- Alamat LAMA (`imt.php`, `result.php`, …) tetap 200 — tautan lama, bookmark,
  dan formulir POST lama tidak rusak.
- Aman: hanya cocok SATU segmen `[a-zA-Z0-9_-]`, jadi `/api/…`, `/css/…`,
  `/assets/…`, `/docs/…` tidak tersentuh; ada penjaga `!-f` / `!-d`.
- `/imt/` (berslash) → 301 ke `/imt`.
- Tautan di dalam aplikasi, `form action`, dan `header("location: …")` sudah
  memakai alamat rapi (`/imt`, `/result`, `/monitoring`, `/login`, `/admin`,
  `/logout`).
- Ditambahkan `<link rel="canonical">` per halaman dan `og:url` ikut alamat rapi.
- Halaman yang tadinya belum punya meta Open Graph (informasi, monitoring,
  login) sekarang punya, karena dibuat di satu tempat.
- Bila kelak ingin alamat `.php` **dialihkan** (301) ke versi rapi, cukup satu
  baris di bagian 0d (sudah ditulis sebagai catatan di `.htaccess`).

### 9.4 Berkas baru/berubah di ronde ini

Baru: `includes/layout_head.php`, `includes/layout_footer.php`, `css/base.css`,
`docs/uji/dev-router.php`.

Berubah: `index.php`, `imt.php`, `result.php`, `informasi.php`, `monitoring.php`,
`admin.php`, `login.php`, `logout.php`, `includes/nav.php` (alamat rapi),
`includes/db.php` (tautan halaman galat → `/`), `css/{index,imt,result,admin,login}.css`
(blok duplikat dihapus), `.htaccess` (bagian 0d), `docs/uji/uji_{e2e,fitur}.sh`
(uji baru untuk alamat rapi + partial).

### 9.5 Verifikasi ronde ini

- `php -l` 31 berkas bersih.
- Uji unit validasi **69/69**, uji end-to-end **61/61**, uji fitur **84/84**.
- **Apache sungguhan** (2.4.58, konfigurasi uji `apache-uji.conf`, `.htaccess`
  dipakai apa adanya): `/`, `/imt`, `/informasi`, `/monitoring`, `/login` → 200;
  `/imt.php` → 200; `/imt/` → 301; `/tidak-ada` → 404; `/css/base.css` →
  200; `/api/get_record.php` → JSON penolakan (tidak tertelan rewrite).
- **Gaya terhitung** dibandingkan antara kode SEBELUM (dari git) dan SESUDAH:
  5 halaman (beranda, cek status gizi, informasi, monitoring, login)
  **identik** (latar `rgb(173,216,230)`, `.container` 800px/padding khusus,
  footer `rgb(240,240,240)` 14.4px, posisi footer sama).
- Perbandingan HTML sebelum/sesudah: isi halaman sama, perbedaan hanya (a) tautan
  `.php` → alamat rapi, (b) posisi footer pindah ke bawah skrip, (c) tambahan
  canonical/OG. Tidak ada isi yang hilang.
- Peramban sungguhan: formulir di `/imt` → POST `/imt` → mendarat di `/result`
  (IMT 24,2 tampil); masuk Monitoring lewat `/monitoring` berhasil dan grafik
  Chart.js tergambar (kanvas 766×320, 11.312 piksel); tanpa geser horizontal di
  lebar 360/414/1280 px.

### 9.6 Yang BELUM dikerjakan (lanjutan opsional)

- Memindahkan `includes/` ke subfolder (`core/`, `domain/`, `data/`, `layout/`)
  dan mengeluarkan ±450 baris logika `imt.php` & `monitoring.php` ke
  `controller/` — belum perlu selama halaman masih 7.
- Guard terpusat (login admin, CSRF, JSON) di satu `bootstrap.php`.
- Lapisan repositori untuk `imt_history`/`responden`.
- Pengalihan 301 dari `.php` ke alamat rapi (lihat catatan bagian 0d).

---

## 10. Ronde Konten Informasi Gizi, Ikon Vektor & Gaya Monitoring (17 Okt 2026)

### 10.1 Konten baru: 22 lembar materi (dinamai sesuai isinya)

Kedua puluh dua gambar yang diletakkan di folder akar proyek dipindahkan dan
dinamai ulang sesuai ISI gambar (bukan singkatan berkas asalnya). Penamaan
dilakukan setelah memeriksa setiap gambar satu per satu.

**Kumpulan Resep — `assets/resep/` (15 lembar, tiap lembar berisi 7 resep):**

| Berkas | Judul di lembar |
|---|---|
| `menu-ayam.jpeg` | Resep Menu Ayam |
| `menu-ikan.jpeg` | Resep Menu Ikan |
| `sup-sayuran-mix-protein.jpeg` | Sup Sayuran Mix Protein |
| `krim-sup.jpeg` | Krim Sup Ayam |
| `krim-sup-selain-ayam.jpeg` | Krim Sup Selain Ayam |
| `menu-bubur.jpeg` | Resep Menu Bubur |
| `bubur-bayi-kaya-protein.jpeg` | Bubur Bayi Kaya Protein (MPASI) |
| `nasi-nusantara.jpeg` | Resep Nasi Nusantara |
| `soto-nusantara.jpeg` | Resep Soto Nusantara |
| `sate-nusantara.jpeg` | Resep Sate Nusantara |
| `pepes-khas-nusantara.jpeg` | Resep Pepes Khas Nusantara |
| `salad-sayur-mix-protein.jpeg` | Salad Sayur Mix Protein |
| `menu-tinggi-zat-besi.jpeg` | Menu Tinggi Zat Besi |
| `gorengan-favorit.jpeg` | Gorengan Favorit |
| `jus-buah-mix-sayuran.jpeg` | Jus Buah Mix Sayuran |

**Panduan & Edukasi Gizi — `assets/panduan/` (7 lembar):**

| Berkas | Judul di lembar |
|---|---|
| `diet-diabetes-melitus-3j.jpeg` | Diet Diabetes Melitus (DM): Prinsip 3J |
| `diet-rendah-karbohidrat-tinggi-lemak-ppok.jpeg` | Diet Rendah Karbohidrat Tinggi Lemak untuk Pasien PPOK |
| `diet-tinggi-protein.jpeg` | Diet Tinggi Protein |
| `makanan-sumber-albumin.jpeg` | 16 Makanan Sumber Albumin |
| `makanan-tinggi-zat-besi.jpeg` | 15 Makanan Tinggi Zat Besi |
| `keamanan-pangan-rumah-tangga.jpeg` | Panduan Keamanan Pangan Tingkat Rumah Tangga |
| `prebiotik-dan-probiotik.jpeg` | Prebiotik dan Probiotik |

Halaman Informasi Gizi kini punya **lima bagian** (sebelumnya tiga):
Leaflet → Contoh Menu → **Kumpulan Resep** → **Panduan & Edukasi Gizi** → DBMP.
Ditambah baris tautan lompat antar bagian di bawah paragraf pengantar.

### 10.2 Berkas baru (modul & partial)

| Berkas | Guna |
|---|---|
| `includes/galeri_aset.php` | Perkakas bersama semua galeri: cari gambar/thumbnail, susun URL, saring entri yang gambarnya belum ada |
| `includes/resep.php` | Data 15 lembar resep (judul, ringkasan, tag, nama unduhan) |
| `includes/panduan.php` | Data 7 lembar panduan/edukasi |
| `includes/galeri_kartu.php` | **Partial satu bagian galeri** — markup kartu (gambar, tag, judul, ringkasan, Lihat/Unduh) kini ditulis SEKALI, dipakai 4 galeri |
| `includes/ikon.php` | Ikon vektor (SVG) pengganti emoji |
| `includes/galeri_build_thumbs.php` | Pembuat thumbnail untuk SEMUA galeri (leaflet, menu, resep, panduan) |

`informasi.php` menyusut 170 → 131 baris karena dua blok `foreach` yang sama dan
markup kartu yang berulang digantikan helper + partial.

### 10.3 Emoji dihapus, diganti ikon vektor

Permintaan: *"pastikan jangan ada pake emoji"*. Seluruh ikon antarmuka kini
berupa SVG di `includes/ikon.php` (warna mengikuti teks, ukuran mengikuti huruf):

| Sebelum (emoji) | Sekarang (ikon SVG) | Keterangan |
|---|---|---|
| 📊 kartu & menu "Cek Status Gizi" | `cek-gizi` | **orang kurus & orang gemuk** berdampingan |
| 📄 kartu & menu "Informasi Gizi" | `informasi` | **piring dengan sendok & garpu** |
| 📈 kartu & menu "Monitoring" | `monitoring` | grafik garis naik |
| 🛠️ tautan Admin | `admin` | pengatur (slider) |
| 🥗 kartu layanan Konsultasi Gizi | `konsultasi` | gelembung percakapan |
| 🍱 kartu layanan Katering Gizi | `katering` | tudung saji |
| 🔒 badge "Akun aman" | **badge dihapus** | sesuai permintaan |
| ⚠️ badge "Belum memakai password" | `perhatian` | segitiga peringatan |
| ❤️ footer "Created With" | `hati` | hati penuh berwarna |

Verifikasi: pencarian emoji di seluruh berkas PHP/CSS aplikasi dan pada HTML yang
dikirim ke peramban = **0 emoji**. Satu-satunya simbol tersisa adalah tanda centang
`✓` sebagai penanda daftar di `css/index.css` (bukan emoji, hanya glif huruf) —
sebutkan bila ingin ikut diganti.

### 10.4 Gaya teks Monitoring disamakan dengan halaman hasil

- Aturan `.mon-judul` yang tadinya membuat judul bagian **rata kiri dan lebih
  kecil** (1,25em) dihapus, sehingga judul bagian kini memakai gaya judul halaman
  hasil (`h1, h2` di `css/result.css`): **rata tengah, warna utama, 1,5em**.
  Yang tersisa hanya jarak antar bagian (`margin-top: 30px`).
- Kotak akun (`.account-box`) disamakan dengan kotak isi halaman hasil
  (`.result-container`): latar lightblue + **garis putus-putus** warna utama.
- Warna judul di dalam kotak (`.account-box h3`) disamakan menjadi warna utama.
- Terukur di peramban: judul halaman 32px dan judul bagian 24px, keduanya
  `rgb(231, 76, 60)` dan rata tengah — sama seperti halaman hasil.

### 10.5 Penyesuaian lain

- Tombol **"+ Ukur Status Gizi Baru" → "+ Cek Status Gizi Baru"** di Monitoring.
- Badge **"Akun aman (memakai password)" dihapus**; status password kini hanya
  ditandai badge peringatan bila akun belum memakai password.
- **Logo beranda diperkecil**: tinggi maksimum 400px → **170px** (ponsel: 250px →
  130px). Terukur 170px di peramban.

### 10.6 Verifikasi ronde ini

- `php -l` 38 berkas bersih.
- Uji unit validasi **69/69**, uji end-to-end **61/61**, uji fitur **84/84**.
- Halaman Informasi: 5 bagian, **36 kartu**, 35 gambar galeri, 5 tautan lompat;
  **0 geser horizontal**; thumbnail baru termuat (diuji dengan menggulir halaman).
- Monitoring (dua kondisi diuji): akun berpassword -> badge peringatan tidak
  muncul; akun tanpa password -> badge "Belum memakai password" muncul lengkap
  dengan ikon SVG. "Akun aman" tidak ada di HTML. Tombol "Cek Status Gizi Baru"
  ada. Emoji 0.
- Badge status di kartu Ringkasan diukur: tepi badge **17px di dalam** tepi kartu
  (tidak meluber); tinggi kartu baris pertama seragam (95px).

### 10.7 Cara menambah materi berikutnya

1. Simpan gambar ke `assets/resep/` atau `assets/panduan/` dengan nama sesuai isi.
2. Tambahkan entri di `instagi_resep_list()` (`includes/resep.php`) atau
   `instagi_panduan_list()` (`includes/panduan.php`).
3. Jalankan `php includes/galeri_build_thumbs.php` untuk membuat thumbnail.

Catatan: skrip lama `includes/menu_build_thumbs.php` dan
`includes/leaflet_build_thumbs.php` masih ada dan tetap berfungsi untuk
galerinya masing-masing; keduanya kini bisa digantikan oleh
`galeri_build_thumbs.php` yang menangani semua galeri (kandidat perapian
berikutnya).
