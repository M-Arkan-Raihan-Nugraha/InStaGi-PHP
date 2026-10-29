# Etalase Bestie InStaGi Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Memindahkan seksi Bestie InStaGi dari beranda (`index.php`) ke bagian teratas halaman Informasi Gizi (`informasi.php`), membangun etalase flyer interaktif berbasis multi-kategori (Travel & Hotel, Land & Property, Kuliner, Minimarket, Layanan Kesehatan), dan menerapkan 11 flyer mitra ke dalam sistem.

**Architecture:** Menggunakan arsitektur modular PHP native khas InStaGi dengan pemisahan berkas data (`includes/content/bestie.php`), wrapper pemanggilan (`includes/bestie.php`), komponen view reusable (`includes/views/bestie_etalase.php`), penataan aset terstandarisasi (`assets/bestie/`), serta CSS responsif dan interaksi filter/modal ringan tanpa dependensi eksternal.

**Tech Stack:** PHP 7.4+ / PHP 8+, HTML5 Semantic, CSS3 (CSS Variables, Flexbox/Grid), Vanilla JavaScript (kategori filter & modal lightbox).

## Global Constraints

- Kompatibilitas PHP native tanpa dependensi pihak ketiga baru.
- Halaman `informasi.php` menampilkan etalase Bestie sebelum gerbang pembatasan akses status gizi (`!$akses_diizinkan`).
- Bersih dari file sementara di root project (memindahkan 11 file `WhatsApp Image *.jpeg` ke `assets/bestie/`).
- Teks aman dengan `htmlspecialchars()` untuk seluruh data dinamis.
- Responsive mobile-first (iPhone SE hingga desktop lebar).

---

### Task 1: Pemindahan & Penataan Aset Flyer Mitra

**Files:**
- Move & Rename:
  - `WhatsApp Image 2026-10-26 at 06.02.41.jpeg` -> `assets/bestie/spirit-holiday-bali-lombok.jpeg`
  - `WhatsApp Image 2026-10-26 at 06.03.44.jpeg` -> `assets/bestie/rumah-tanah-ciranjang-cianjur.jpeg`
  - `WhatsApp Image 2026-10-26 at 06.03.45 (1).jpeg` -> `assets/bestie/tanah-sawah-cigaok-sukabumi.jpeg`
  - `WhatsApp Image 2026-10-26 at 06.03.45.jpeg` -> `assets/bestie/tanah-kolam-sawah-cugenang-cianjur.jpeg`
  - `WhatsApp Image 2026-10-26 at 06.03.46.jpeg` -> `assets/bestie/rumah-tanah-jagapura-brebes.jpeg`
  - `WhatsApp Image 2026-10-26 at 06.47.27.jpeg` -> `assets/bestie/dapur-rifka-kuliner.jpeg`
  - `WhatsApp Image 2026-10-26 at 06.55.04.jpeg` -> `assets/bestie/marwa-fresh-markisa.jpeg`
  - `WhatsApp Image 2026-10-26 at 07.42.37.jpeg` -> `assets/bestie/dapur-ummi-frozen-risoles.jpeg`
  - `WhatsApp Image 2026-10-26 at 07.44.21.jpeg` -> `assets/bestie/dago-wisata-bobotoh-singapore.jpeg`
  - `WhatsApp Image 2026-10-26 at 09.54.36.jpeg` -> `assets/bestie/pd-puspita-minimarket.jpeg`
  - `WhatsApp Image 2026-10-26 at 10.03.42.jpeg` -> `assets/bestie/klinik-sunat-hd.jpeg`

- [ ] **Step 1: Pindahkan dan beri nama terstruktur untuk ke-11 file gambar flyer**
- [ ] **Step 2: Verifikasi seluruh berkas berada di `assets/bestie/` dan dapat dibaca**
- [ ] **Step 3: Commit perubahan aset**

---

### Task 2: Modul Data Bestie (`includes/content/bestie.php` & `includes/bestie.php`)

**Files:**
- Create: `includes/content/bestie.php`
- Create: `includes/bestie.php`
- Test: `tests/Unit/uji_bestie.php`

**Interfaces:**
- Produces:
  - `instagi_bestie_kategori(): array<string, string>`
  - `instagi_bestie_daftar(): array<int, array{id: string, kategori: string, kategori_label: string, judul: string, subjudul: string, deskripsi: string, lokasi: string, harga: string, wa: string, wa_text: string, gambar: string}>`

- [ ] **Step 1: Tulis unit test `tests/Unit/uji_bestie.php` yang memverifikasi kategori dan integritas berkas gambar flyer**
- [ ] **Step 2: Jalankan test dan pastikan gagal sebelum implementasi**
- [ ] **Step 3: Implementasikan `includes/content/bestie.php` dan `includes/bestie.php`**
- [ ] **Step 4: Jalankan unit test dan pastikan lolos (PASS)**
- [ ] **Step 5: Commit modul data Bestie**

---

### Task 3: Komponen View Etalase (`includes/views/bestie_etalase.php`)

**Files:**
- Create: `includes/views/bestie_etalase.php`
- Modify: `includes/views/ikon.php` (menambahkan ikon kategori jika diperlukan)

**Interfaces:**
- Consumes: `instagi_bestie_kategori()`, `instagi_bestie_daftar()`, `instagi_wa_url()`
- Produces: HTML section `#bestie` dengan filter buttons, grid kartu flyer, dialog modal zoom, dan form kontak/sponsor.

- [ ] **Step 1: Rancang markup semantic untuk filter kategori, kartu flyer, tombol WhatsApp, dan lightbox modal**
- [ ] **Step 2: Sertakan script vanilla JS terenkapsulasi untuk filter kategori aktif (`data-category`) dan interaksi modal**
- [ ] **Step 3: Uji render partial view secara terisolasi dengan CLI php**
- [ ] **Step 4: Commit komponen view etalase**

---

### Task 4: Integrasi di `informasi.php` & Pembersihan `index.php`

**Files:**
- Modify: `informasi.php`
- Modify: `index.php`

- [ ] **Step 1: Muat modul `bestie.php` di `informasi.php` dan panggil `includes/views/bestie_etalase.php` sebelum blok pembatasan akses**
- [ ] **Step 2: Tambahkan jump anchor `#bestie` pada menu navigasi lompat cepat `informasi.php` jika diperlukan**
- [ ] **Step 3: Bersihkan seksi Bestie lama dan pemindaian logo dari `index.php`**
- [ ] **Step 4: Uji sintaks kedua file dengan `php -l`**
- [ ] **Step 5: Commit perubahan integrasi halaman**

---

### Task 5: Penyesuaian Styling di `css/pages.css`

**Files:**
- Modify: `css/pages.css`

- [ ] **Step 1: Tambahkan gaya CSS untuk etalase Bestie: tabs/pills filter, grid flyer responsif, kartu flyer bertema InStaGi, badge kategori, tombol aksi WA & zoom**
- [ ] **Step 2: Tambahkan gaya modal preview gambar flyer dengan transisi halus dan responsif di layar sentuh**
- [ ] **Step 3: Uji tampilan di berbagai ukuran viewport (desktop, tablet, mobile)**
- [ ] **Step 4: Commit perubahan styling**

---

### Task 6: Verifikasi End-to-End & Uji Regresi

**Files:**
- Run test suite: `tests/Unit/uji_gizi.php`, `tests/Unit/uji_validasi.php`, `tests/Unit/uji_bestie.php`
- Run linting / syntax check: `tools/` and all php files.

- [ ] **Step 1: Jalankan semua unit test**
- [ ] **Step 2: Jalankan web server lokal php built-in untuk memverifikasi halaman `http://localhost:8080/informasi.php` dan `http://localhost:8080/index.php`**
- [ ] **Step 3: Periksa interaktivitas: filter kategori berfungsi menyaring kartu, modal zoom terbuka dan tertutup dengan benar, link WA valid**
- [ ] **Step 4: Pastikan status git bersih dan commit final**
