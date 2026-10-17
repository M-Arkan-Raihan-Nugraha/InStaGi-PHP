# Spesifikasi Domain & Modul InStaGi (Language-Agnostic Specification)

Dokumen ini adalah **spesifikasi referensi teknis** untuk mempermudah pemeliharaan, integrasi, dan pembuatan ulang (*porting*) proyek InStaGi ke bahasa pemrograman atau framework lain (misal: Python/FastAPI/Django, TypeScript/Node.js/Next.js, Go, Rust, dsb.) dengan hasil perhitungan dan perilaku yang 100% identik.

---

## 1. Standar Nomenklatur & Konsistensi Istilah

Seluruh antarmuka, basis data, dan API InStaGi tunduk pada aturan penamaan berikut:

| Konsep | Istilah Resmi | Catatan |
|---|---|---|
| Indeks Massa Tubuh | **IMT** | Jangan gunakan singkatan bahasa Inggris "BMI" pada tampilan atau API baru. |
| Gender | **Jenis Kelamin** | Nilai baku: `"Laki-laki"` dan `"Perempuan"`. |
| Berat Badan | **BB** | Satuan: Kilogram (kg), presisi 1 desimal. |
| Tinggi Badan | **TB** | Satuan: Sentimeter (cm), bilangan bulat (*integer*). |
| Kebutuhan Energi | **Kalori Harian** | Satuan: kilokalori (kkal), dibulatkan ke ratusan terdekat. |

### Kategori & Kelas CSS Status Gizi

| Kategori Teks Resmi | Kode CSS / Slug | Ambang Batas IMT | Koreksi Kalori |
|---|---|---|---|
| `Berat badan kurang (underweight)` | `underweight` | `IMT < 18.5` | `+500 kkal` |
| `Berat badan normal (ideal)` | `normal` | `18.5 <= IMT < 25.0` | `0 kkal` |
| `Berat badan berlebih (overweight)` | `overweight` | `25.0 <= IMT <= 27.0` | `-500 kkal` |
| `Obesitas` | `obese` | `IMT > 27.0` | `-500 kkal` |

---

## 2. Rumus Medis & Logika Kalkulasi (`GiziService`)

### A. Indeks Massa Tubuh (IMT)
$$\text{TB}_{\text{meter}} = \frac{\text{TB}_{\text{cm}}}{100}$$
$$\text{IMT}_{\text{mentah}} = \frac{\text{BB}_{\text{kg}}}{(\text{TB}_{\text{meter}})^2}$$

> **Aturan Presisi:** Klasifikasi kategori gizi WAJIB memakai $\text{IMT}_{\text{mentah}}$. Pembulatan ke 1 angka di belakang koma (`round(imt, 1)`) hanya dilakukan untuk keperluan tampilan layar (*display*).

### B. Basal Metabolic Rate (BMR) — Harris-Benedict
- **Laki-laki:**
  $$\text{BMR} = 66.5 + (13.75 \times \text{BB}) + (5.003 \times \text{TB}) - (6.75 \times \text{Usia})$$
- **Perempuan:**
  $$\text{BMR} = 655.1 + (9.563 \times \text{BB}) + (1.850 \times \text{TB}) - (4.676 \times \text{Usia})$$

### C. Total Daily Energy Expenditure (TDEE) & Kalori Harian
$$\text{TDEE} = \text{BMR} \times \text{Aktivitas}$$

Faktor aktivitas yang sah:
- `1.200` : Sangat Ringan (Bedrest / jarang bergerak)
- `1.375` : Ringan (Olahraga ringan 1–3 hari/minggu)
- `1.550` : Sedang (Olahraga sedang 3–5 hari/minggu)
- `1.725` : Berat (Aktivitas fisik berat 6–7 hari/minggu)
- `1.900` : Sangat Berat (Atlet / pekerjaan fisik intensif 2x sehari)

$$\text{Kalori}_{\text{target}} = \text{TDEE} + \text{Koreksi Kalori}$$

**Aturan Pembulatan ke Ratusan Terdekat:**
- Sisa modulus 100 $< 50 \rightarrow$ bulatkan ke bawah (`floor(kal / 100) * 100`)
- Sisa modulus 100 $== 50 \rightarrow$ biarkan apa adanya (contoh: 1750 tetap 1750)
- Sisa modulus 100 $> 50 \rightarrow$ bulatkan ke atas (`ceil(kal / 100) * 100`)

### D. Berat Badan Ideal & Rentang Sehat
- **BB Ideal (Broca Modifikasi):**
  $$\text{BB}_{\text{ideal}} = (\text{TB} - 100) \times 0.9$$
- **Rentang BB Sehat (berdasarkan batas IMT normal 18.5 s.d. 24.9):**
  $$\text{BB}_{\text{sehat min}} = 18.5 \times (\text{TB}_{\text{meter}})^2$$
  $$\text{BB}_{\text{sehat max}} = 24.9 \times (\text{TB}_{\text{meter}})^2$$

---

## 3. Spesifikasi Validasi Input (`ValidationService`)

Semua input wajib melewati aturan berikut sebelum disimpan:

| Field | Tipe | Aturan Validasi |
|---|---|---|
| `nama` | String | Wajib diisi, maksimal 100 karakter, trim whitespace. |
| `usia` | Integer | Wajib diisi, rentang `1 <= usia <= 120`. |
| `jenis_kelamin` | String | Wajib salah satu: `"Laki-laki"` atau `"Perempuan"`. |
| `bb` | Float | Wajib diisi, rentang `2.0 <= bb <= 500.0` (boleh desimal). |
| `tb` | Integer | Wajib diisi, bilangan bulat (tanpa desimal), rentang `40 <= tb <= 250`. |
| `aktivitas` | Float | Wajib salah satu dari: `[1.2, 1.375, 1.55, 1.725, 1.9]`. |
| `no_hp` | String | Normalisasi digit: awali `0`, panjang 8–20 digit. Format regex: `/^0\d{7,19}$/`. |
| `tanggal` | Date | Format `YYYY-MM-DD`, tanggal kalender valid, tidak boleh tanggal masa depan. |

---

## 4. Model Entitas Data (Database Schema)

### Entitas: `responden`
Identitas akun unik berbasis Nama + Nomor HP:
- `id` : Integer (Auto Increment, PK)
- `nama` : String(255)
- `no_hp` : String(30)
- `password_hash` : String(255), NULLABLE (algoritma secure hash, misal bcrypt/argon2)
- `created_at` : Timestamp
- **Unique Constraint:** `(nama, no_hp)`

### Entitas: `imt_history`
Rekam jejak pengukuran status gizi:
- `id` : Integer (PK)
- `responden_id` : Integer, Foreign Key opsional ke `responden.id`
- `tanggal` : Date
- `nama` : String(255)
- `usia` : Integer
- `jenis_kelamin` : String(20)
- `no_hp` : String(20)
- `berat_badan` : Decimal(5,1)
- `tinggi_badan` : Integer
- `aktivitas` : Decimal(4,3)
- `kalori` : Integer
- `imt` : Decimal(4,1)
- `status_gizi` : String(50)
- `saran` : Text
- `created_at` : Timestamp

---

## 5. Panduan Implementasi di Bahasa Lain

Jika mengimplementasikan di bahasa lain:

```
src/ (atau app/)
├── domain/
│   ├── gizi.py (atau nutrition.ts / gizi.go)     <-- Rumus IMT, BMR, TDEE, Kategori
│   └── validator.py (atau validator.ts)          <-- Aturan batas data
├── models/
│   ├── responden.py (atau user.ts)               <-- Entitas akun
│   └── measurement.py (atau history.ts)          <-- Entitas rekam gizi
├── api/
│   ├── routes_calculator.py                      <-- Endpoint hitung gizi
│   ├── routes_monitoring.py                      <-- Endpoint riwayat & grafik
│   └── routes_admin.py                           <-- CRUD admin
```
Dokumen ini menjamin logika bisnis tidak terikat pada framework PHP, sehingga memudahkan tim pengembang masa depan untuk melakukan reimplementasi modern kapan pun dibutuhkan.
