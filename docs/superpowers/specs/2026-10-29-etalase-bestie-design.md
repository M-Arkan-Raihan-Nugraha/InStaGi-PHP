# Design Document: Etalase Bestie InStaGi & Relokasi ke Halaman Informasi Gizi

## Ringkasan Proyek
Memindahkan seksi kemitraan dan promosi "Bestie InStaGi" dari halaman beranda (`index.php`) ke bagian paling atas halaman Informasi Gizi (`informasi.php`), serta meningkatkan tampilannya menjadi etalase flyer interaktif berbasis kategori (*Travel & Hotel, Land & Property, Kuliner, Minimarket, Layanan Kesehatan*).

## Konteks & Tujuan
* Pengunjung InStaGi dapat melihat berbagai promosi dan kerja sama mitra di halaman Informasi Gizi.
* Tampil di posisi paling atas halaman `informasi.php`, sebelum gerbang pembatasan akses (*gate* Cek Status Gizi), sehingga dapat dilihat oleh semua pengunjung publik.
* Mengintegrasikan 11 berkas flyer WhatsApp yang ada di root ke folder terorganisir `assets/bestie/` dengan data yang terstruktur.
* Menyediakan filter kategori interaktif, kartu flyer informatif, tombol aksi WhatsApp langsung, serta modal lightbox untuk melihat flyer ukuran penuh.

## Struktur Aset & Berkas
1. **Aset Flyer (`assets/bestie/`)**:
   - `assets/bestie/spirit-holiday-bali-lombok.jpeg`
   - `assets/bestie/dago-wisata-bobotoh-singapore.jpeg`
   - `assets/bestie/rumah-tanah-ciranjang-cianjur.jpeg`
   - `assets/bestie/tanah-sawah-cigaok-sukabumi.jpeg`
   - `assets/bestie/tanah-kolam-sawah-cugenang-cianjur.jpeg`
   - `assets/bestie/rumah-tanah-jagapura-brebes.jpeg`
   - `assets/bestie/dapur-rifka-kuliner.jpeg`
   - `assets/bestie/marwa-fresh-markisa.jpeg`
   - `assets/bestie/dapur-ummi-frozen-risoles.jpeg`
   - `assets/bestie/pd-puspita-minimarket.jpeg`
   - `assets/bestie/klinik-sunat-hd.jpeg`

2. **Modul Logika & Data**:
   - `includes/content/bestie.php`: Berisi definisi fungsi `instagi_bestie_kategori()` dan `instagi_bestie_daftar()`.
   - `includes/bestie.php`: Forwarder kompatibilitas.

3. **Komponen Tampilan**:
   - `includes/views/bestie_etalase.php`: Partial view yang merender etalase Bestie (header, filter kategori, grid kartu flyer, dialog modal preview, dan kartu ajakan kerja sama).

4. **Halaman & Gaya**:
   - `informasi.php`: Menyertakan `bestie_etalase.php` di bagian atas container sebelum pengecekan `$akses_diizinkan`.
   - `index.php`: Membersihkan seksi Bestie lama.
   - `css/pages.css`: Styling modern etalase Bestie, pills filter, kartu flyer, tombol WhatsApp, dan modal lightbox.

## Rincian Data Flyer
* **Travel & Hotel (`travel`)**:
  1. *Spirit Holiday Indonesia*: Open Trip Overland Bali - Lombok (29 Des '26 - 4 Jan '27). Paket mulai 3,65 Juta. WA: `0812-1436-4730` / `0813-2244-2994`.
  2. *PT Dago Wisata Internasional*: Bobotoh Away Days Goes to Singapore (Persib vs Lion City Sailors). Promo mulai 6,99 Jt. WA: `0811-222-056` / `0811-205-005`.
* **Land & Property (`property`)**:
  3. *Rumah & Tanah Ciranjang Cianjur*: LT 329m², LB 250m², 4 KT, 3 KM, SHM, Rp 950 Juta Nego. Samping Puskesmas Ciranjang.
  4. *Tanah Sawah & Daratan Sukabumi*: Luas 3 Ha, air mandiri, tanah subur. Kp. Cigaok, Giri Jaya, Warung Kiara. WA: `0812-2494-8388`.
  5. *Tanah Daratan, Kolam & Sawah Cianjur*: Luas 1.600m², SHM, Rp 1,1 Jt/m² Nego. Desa Talaga, Cugenang.
  6. *Rumah & Tanah Kersana Brebes*: Luas 1.249m², SHM, Siap huni, harga nego. Desa Jagapura.
* **Kuliner (`kuliner`)**:
  7. *Dapur Rifka*: Minuman (Kopi, Alpukat Kocok, Lemon Sereh, Gogoma) & Makanan rumahan mulai 10K. Ciumbuleuit Bandung. WA: `0823-1897-4678`.
  8. *Marwa Fresh*: Homegrown Passion Fruit Drink (Markisa Asli Tanpa Pengawet). WA: `0812-2494-8388`.
  9. *Dapur Ummi*: Frozen Risoles isi 5 & 10 pcs (Savory & Sweet) mulai 10K. Cibadak Bandung. WA: `0896-0275-1277`.
* **Minimarket (`minimarket`)**:
  10. *PD Puspita*: Kebutuhan sehari-hari & sembako lengkap. Baros Arjasari Bandung. WA: `0877-1860-4058`.
* **Layanan Kesehatan (`kesehatan`)**:
  11. *Klinik Sunat HD*: Sunat Modern & Profesional 5.G Plus, hadiah remote control car. Bojongsoang Bandung. WA: `0813-2046-9003`.

## Interaksi UI & UX
* **Filter Kategori**: Tombol tab/pill aktif tanpa reload halaman menggunakan atribut `data-category`.
* **Kartu Flyer**: Menampilkan foto flyer proporsional, badge kategori warna-warni, judul mitra, ringkasan/lokasi, tombol "Lihat Flyer" (membuka modal zoom), dan tombol "WhatsApp" untuk menghubungi langsung.
* **Modal Lightbox**: Menampilkan flyer resolusi penuh dengan tombol tutup dan klik di luar untuk menutup.
