#!/usr/bin/env bash
# Uji end-to-end InStaGi V3 (server PHP lokal + MariaDB uji lokal).
# Menjalankan alur nyata: input -> hasil -> login admin -> API (get/update/delete/export)
# Termasuk uji paksa kegagalan prepare() untuk membuktikan penjaga baru bekerja.

BASE="http://127.0.0.1:8099"
MYSQL=/d/SMAKZIE/PRODUKTIF/XAMPP/mysql/bin/mysql.exe
DB=instagi_v3_uji
WORK="C:/Users/Pongo/AppData/Local/hermes/cache/scratch/e2e"
JAR="$WORK/cookies.txt"
mkdir -p "$WORK"
rm -f "$JAR"
LULUS=0; GAGAL=0

# Reset database uji supaya jumlah baris yang diperiksa pasti (dijalankan ulang aman).
"$MYSQL" -u root -e "DROP DATABASE IF EXISTS $DB; CREATE DATABASE $DB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
echo "(database uji direset)"

hitung() { if [ "$2" = "$3" ]; then LULUS=$((LULUS+1)); echo "  OK    $1"; else GAGAL=$((GAGAL+1)); echo "  GAGAL $1 :: harap=[$3] dapat=[$2]"; fi; }
ada()    { if echo "$2" | grep -qF "$3"; then LULUS=$((LULUS+1)); echo "  OK    $1"; else GAGAL=$((GAGAL+1)); echo "  GAGAL $1 :: tidak menemukan [$3] di: $(echo "$2" | head -c 200)"; fi; }
tiada()  { if echo "$2" | grep -qF "$3"; then GAGAL=$((GAGAL+1)); echo "  GAGAL $1 :: seharusnya TIDAK ada [$3]"; else LULUS=$((LULUS+1)); echo "  OK    $1"; fi; }
baris()  { "$MYSQL" -u root -N -B -e "SELECT COUNT(*) FROM $DB.imt_history;" 2>/dev/null | tr -d '\r'; }
csrf_form() { echo "$1" | grep -o 'name="csrf_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//'; }
csrf_meta() { echo "$1" | grep -o 'name="csrf-token" content="[^"]*"' | head -1 | sed 's/.*content="//;s/"//'; }

echo "=== A. HALAMAN PUBLIK & FORM ==="
KODE=$(curl -s -o $WORK/a_imt.html -w '%{http_code}' -c "$JAR" -b "$JAR" "$BASE/imt.php")
hitung "GET /imt.php -> 200" "$KODE" "200"
CSRF=$(csrf_form "$(cat $WORK/a_imt.html)")
if [ -n "$CSRF" ]; then LULUS=$((LULUS+1)); echo "  OK    token CSRF form ditemukan"; else GAGAL=$((GAGAL+1)); echo "  GAGAL token CSRF form tidak ditemukan"; fi
ada "petunjuk No. HP baru tampil" "$(cat $WORK/a_imt.html)" "8&ndash;15 digit"

echo
echo "=== B. INPUT SAH (form publik) ==="
HARI=$(date +%F)
KODE=$(curl -s -o $WORK/b_hasil.txt -w '%{http_code}' -c "$JAR" -b "$JAR" -X POST "$BASE/imt.php" \
  --data-urlencode "csrf_token=$CSRF" --data "submitted=1" \
  --data-urlencode "tanggal_input=$HARI" --data-urlencode "nama=Budi & Santoso" \
  --data "usia=30" --data "jenis_kelamin=Laki-laki" --data-urlencode "no_hp=081234567890" \
  --data "bb=60.5" --data "tb=165" --data "aktivitas=1.55")
hitung "POST input sah -> 302 (redirect ke hasil)" "$KODE" "302"
hitung "jumlah baris tersimpan = 1" "$(baris)" "1"
KODE=$(curl -s -o $WORK/b_result.html -w '%{http_code}' -c "$JAR" -b "$JAR" "$BASE/result.php")
hitung "GET /result.php -> 200" "$KODE" "200"
ada "hasil menampilkan nama apa adanya (escaping saat tampil)" "$(cat $WORK/b_result.html)" "Budi &amp; Santoso"
ada "hasil menampilkan status gizi" "$(cat $WORK/b_result.html)" "Berat badan normal"
ada "pesan WhatsApp memakai & mentah (tidak ganda)" "$(cat $WORK/b_result.html)" "Budi+%26+Santoso"
ada "kartu akun Monitoring muncul" "$(cat $WORK/b_result.html)" "/monitoring"

echo
echo "=== C. INPUT TIDAK SAH HARUS DITOLAK (tanpa menambah data) ==="
uji_tolak() {
  local judul="$1" pesan="$2"; shift 2
  local keluar
  keluar=$(curl -s -c "$JAR" -b "$JAR" -X POST "$BASE/imt.php" \
    --data-urlencode "csrf_token=$CSRF" --data "submitted=1" \
    --data-urlencode "tanggal_input=$HARI" --data-urlencode "nama=Uji Tolak" \
    --data "usia=30" --data "jenis_kelamin=Laki-laki" --data-urlencode "no_hp=081234567890" \
    --data "bb=60" --data "tb=165" --data "aktivitas=1.55" "$@")
  ada "$judul" "$keluar" "$pesan"
  hitung "  (data tidak bertambah)" "$(baris)" "1"
}
uji_tolak "aktivitas 999 ditolak"            "Tingkat Aktivitas Fisik tidak valid."   --data "aktivitas=999"
uji_tolak "aktivitas 1.3 ditolak"            "Tingkat Aktivitas Fisik tidak valid."   --data "aktivitas=1.3"
uji_tolak "jenis_kelamin 'X' ditolak"        "Jenis Kelamin tidak valid."             --data "jenis_kelamin=X"
uji_tolak "No. HP tanpa awalan 0 ditolak"    "No. HP tidak valid."                    --data-urlencode "no_hp=81234567890"
uji_tolak "TB desimal ditolak"               "harus bilangan bulat"                    --data "tb=165.5"
uji_tolak "usia 999 ditolak"                 "Usia harus antara 1 sampai 120 tahun."  --data "usia=999"
uji_tolak "BB 5000 ditolak"                  "Berat Badan harus antara 2 sampai 500 kg." --data "bb=5000"
uji_tolak "tanggal masa depan ditolak"       "Tanggal tidak valid."                   --data-urlencode "tanggal_input=2030-01-01"
KELUAR=$(curl -s -c "$JAR" -b "$JAR" -X POST "$BASE/imt.php" \
  --data-urlencode "csrf_token=$CSRF" --data-urlencode "nama=Klik Ganda" --data "usia=30" \
  --data "jenis_kelamin=Laki-laki" --data-urlencode "no_hp=081234567890" \
  --data "bb=60" --data "tb=165" --data "aktivitas=1.55" --data-urlencode "tanggal_input=$HARI")
ada "kiriman tanpa penanda 'submitted' ditolak (anti klik-ganda)" "$KELUAR" "Formulir sudah dikirim"
hitung "  (data tetap 1 baris)" "$(baris)" "1"

echo
echo "=== D. LOGIN ADMIN ==="
curl -s -o $WORK/d_login.html -c "$JAR" -b "$JAR" "$BASE/login.php"
CSRF_L=$(csrf_form "$(cat $WORK/d_login.html)")
KODE=$(curl -s -o /dev/null -w '%{http_code}' -c "$JAR" -b "$JAR" -X POST "$BASE/login.php" \
  --data-urlencode "csrf_token=$CSRF_L" --data "username=admin_uji" --data "password=uji12345")
hitung "login benar -> 302 ke admin" "$KODE" "302"
KODE=$(curl -s -o $WORK/d_admin.html -w '%{http_code}' -c "$JAR" -b "$JAR" "$BASE/admin.php")
hitung "GET /admin.php -> 200" "$KODE" "200"
ada "data tampil di tabel admin" "$(cat $WORK/d_admin.html)" "Budi &amp; Santoso"
CSRF_A=$(csrf_meta "$(cat $WORK/d_admin.html)")
if [ -n "$CSRF_A" ]; then LULUS=$((LULUS+1)); echo "  OK    token CSRF admin ditemukan"; else GAGAL=$((GAGAL+1)); echo "  GAGAL token CSRF admin tidak ditemukan"; fi

echo
echo "=== E. API ADMIN ==="
ID=$("$MYSQL" -u root -N -B -e "SELECT id FROM $DB.imt_history ORDER BY id LIMIT 1;" | tr -d '\r')
KELUAR=$(curl -s -c "$JAR" -b "$JAR" -H "X-CSRF-Token: $CSRF_A" "$BASE/api/get_record.php?id=$ID")
ada "get_record mengembalikan JSON sukses" "$KELUAR" '"success":true'
ada "get_record berisi data" "$KELUAR" '"nama":"Budi & Santoso"'
KODE=$(curl -s -o $WORK/e_update.json -w '%{http_code}' -c "$JAR" -b "$JAR" -H "X-CSRF-Token: $CSRF_A" \
  -X POST "$BASE/api/update_record.php" --data "id=$ID" --data-urlencode "tanggal=$HARI" \
  --data-urlencode "nama=Budi & Santoso" --data "usia=31" --data "jenis_kelamin=Laki-laki" \
  --data-urlencode "no_hp=081234567890" --data "berat_badan=61.2" --data "tinggi_badan=165" --data "aktivitas=1.55")
hitung "update sah -> 200" "$KODE" "200"
ada "update sah -> JSON sukses" "$(cat $WORK/e_update.json)" '"success":true'
hitung "perubahan tersimpan (usia 31)" "$("$MYSQL" -u root -N -B -e "SELECT usia FROM $DB.imt_history WHERE id=$ID;" | tr -d '\r')" "31"

KODE=$(curl -s -o $WORK/e_tolak.json -w '%{http_code}' -c "$JAR" -b "$JAR" -H "X-CSRF-Token: $CSRF_A" \
  -X POST "$BASE/api/update_record.php" --data "id=$ID" --data-urlencode "tanggal=$HARI" \
  --data-urlencode "nama=Budi" --data "usia=31" --data "jenis_kelamin=Laki-laki" \
  --data-urlencode "no_hp=081234567890" --data "berat_badan=61.2" --data "tinggi_badan=165" --data "aktivitas=999")
hitung "update aktivitas 999 -> HTTP 422" "$KODE" "422"
ada "update ditolak -> pesan jelas (bukan halaman kosong)" "$(cat $WORK/e_tolak.json)" "Tingkat Aktivitas Fisik tidak valid."
hitung "  (data tidak berubah: aktivitas tetap 1.550)" "$("$MYSQL" -u root -N -B -e "SELECT aktivitas FROM $DB.imt_history WHERE id=$ID;" | tr -d '\r')" "1.550"

KODE=$(curl -s -o $WORK/e_404.json -w '%{http_code}' -c "$JAR" -b "$JAR" -H "X-CSRF-Token: $CSRF_A" \
  -X POST "$BASE/api/update_record.php" --data "id=999999" --data-urlencode "tanggal=$HARI" \
  --data-urlencode "nama=Hantu" --data "usia=31" --data "jenis_kelamin=Laki-laki" \
  --data-urlencode "no_hp=081234567890" --data "berat_badan=61.2" --data "tinggi_badan=165" --data "aktivitas=1.55")
hitung "update ID tidak ada -> HTTP 404" "$KODE" "404"
ada "update ID tidak ada -> pesan jelas" "$(cat $WORK/e_404.json)" "tidak ditemukan"

KODE=$(curl -s -o $WORK/e_nologin.json -w '%{http_code}' -X POST "$BASE/api/update_record.php" --data "id=$ID" --data "aktivitas=1.55")
hitung "update tanpa login -> HTTP 401" "$KODE" "401"
KODE=$(curl -s -o $WORK/e_nocsrf.json -w '%{http_code}' -c "$JAR" -b "$JAR" -X POST "$BASE/api/update_record.php" --data "id=$ID" --data "aktivitas=1.55")
hitung "update tanpa token CSRF -> HTTP 403" "$KODE" "403"
KODE=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/api/get_record.php?id=1")
hitung "get_record tanpa login -> HTTP 401" "$KODE" "401"

echo
echo "=== F. PENJAGA prepare() GAGAL (inti perbaikan kritis) ==="
# Rusak skema secara sengaja (hanya di DB uji): ganti nama kolom 'saran'.
"$MYSQL" -u root -e "ALTER TABLE $DB.imt_history CHANGE saran saran_rusak TEXT;" 2>&1 | head -2
KODE=$(curl -s -o $WORK/f_update.json -w '%{http_code}' -c "$JAR" -b "$JAR" -H "X-CSRF-Token: $CSRF_A" \
  -X POST "$BASE/api/update_record.php" --data "id=$ID" --data-urlencode "tanggal=$HARI" \
  --data-urlencode "nama=Budi" --data "usia=31" --data "jenis_kelamin=Laki-laki" \
  --data-urlencode "no_hp=081234567890" --data "berat_badan=61.2" --data "tinggi_badan=165" --data "aktivitas=1.55")
hitung "prepare gagal -> HTTP 500 (bukan halaman kosong)" "$KODE" "500"
ada "prepare gagal -> tetap JSON dengan pesan ramah" "$(cat $WORK/f_update.json)" '"success":false'
tiada "prepare gagal -> TIDAK membocorkan detail SQL" "$(cat $WORK/f_update.json)" "saran_rusak"
"$MYSQL" -u root -e "ALTER TABLE $DB.imt_history CHANGE saran_rusak saran TEXT;" 2>/dev/null
KODE=$(curl -s -o $WORK/f_delete.json -w '%{http_code}' -c "$JAR" -b "$JAR" -H "X-CSRF-Token: $CSRF_A" \
  -X POST "$BASE/api/delete_record.php" --data "ids[]=999999")
hitung "hapus ID tidak ada -> 200 dengan pesan" "$KODE" "200"

echo
echo "=== G. EXPORT CSV ==="
curl -s -o $WORK/g_export.csv -c "$JAR" -b "$JAR" "$BASE/api/export_csv.php"
ada "CSV memuat BOM UTF-8" "$(head -c 3 $WORK/g_export.csv | od -An -tx1 | tr -d ' \n')" "efbbbf"
ada "CSV memuat header Indonesia" "$(cat $WORK/g_export.csv)" "Berat Badan (kg)"
ada "CSV memuat data" "$(cat $WORK/g_export.csv)" "Budi & Santoso"
KODE=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/api/export_csv.php")
hitung "export tanpa login -> 302 ke halaman login" "$KODE" "302"

echo
echo "=== H. MONITORING (akun responden) ==="
KODE=$(curl -s -o $WORK/h_mon.html -w '%{http_code}' -c "$JAR" -b "$JAR" "$BASE/monitoring.php")
hitung "GET /monitoring.php -> 200" "$KODE" "200"
CSRF_M=$(csrf_form "$(cat $WORK/h_mon.html)")
# Akun sudah otomatis aktif karena submit form IMT tadi (dicek lebih dulu).
ada "riwayat akun tampil" "$(cat $WORK/h_mon.html)" "Budi &amp; Santoso"

KELUAR=$(curl -s -L -c "$JAR" -b "$JAR" "$BASE/monitoring.php" \
  --data-urlencode "csrf_token=$CSRF_M" --data "action=logout")
ada "keluar dari akun responden" "$KELUAR" "Masuk ke akun Anda"

# Coba masuk dengan kredensial SALAH 5x -> harus dijeda (perbaikan baru).
for i in 1 2 3 4 5; do
  KELUAR=$(curl -s -c "$JAR" -b "$JAR" -X POST "$BASE/monitoring.php" \
    --data-urlencode "csrf_token=$CSRF_M" --data "action=login" --data-urlencode "nama=Salah" --data-urlencode "hp=081234567890")
done
ada "5 percobaan masuk gagal -> dijeda 15 menit" "$KELUAR" "Terlalu banyak percobaan masuk"
KELUAR=$(curl -s -c "$JAR" -b "$JAR" -X POST "$BASE/monitoring.php" \
  --data-urlencode "csrf_token=$CSRF_M" --data "action=login" --data-urlencode "nama=Salah" --data-urlencode "hp=081234567890")
ada "percobaan ke-6 tetap ditolak (jeda berlaku)" "$KELUAR" "Terlalu banyak percobaan masuk"

# Sesi baru (cookie jar baru) = penghitung percobaan kembali bersih.
JAR2="$WORK/cookies2.txt"; rm -f "$JAR2"
curl -s -o $WORK/h_mon2.html -c "$JAR2" -b "$JAR2" "$BASE/monitoring.php"
CSRF_M2=$(csrf_form "$(cat $WORK/h_mon2.html)")
KELUAR=$(curl -s -L -c "$JAR2" -b "$JAR2" "$BASE/monitoring.php" \
  --data-urlencode "csrf_token=$CSRF_M2" --data "action=login" --data-urlencode "nama=  budi & santoso  " --data-urlencode "hp=+62 812-3456-7890")
ada "masuk dengan nama+HP benar berhasil (format +62 dinormalkan)" "$KELUAR" "Riwayat Pengukuran"
ada "riwayat menampilkan data pengukuran" "$KELUAR" "081234567890"

# Nama tidak dikenal (sesi bersih, belum masuk) -> pesan seragam.
JAR3="$WORK/cookies3.txt"; rm -f "$JAR3"
CSRF_M3=$(csrf_form "$(curl -s -c "$JAR3" -b "$JAR3" "$BASE/monitoring.php")")
KELUAR=$(curl -s -c "$JAR3" -b "$JAR3" "$BASE/monitoring.php" \
  --data-urlencode "csrf_token=$CSRF_M3" --data "action=login" --data-urlencode "nama=Akun Ngawur" --data-urlencode "hp=081299999999")
ada "nama tidak dikenal -> pesan seragam (tidak membocorkan akun)" "$KELUAR" "Nama atau No. HP tidak cocok dengan data kami"

echo
echo "=============================================="
echo "LULUS: $LULUS | GAGAL: $GAGAL"
[ "$GAGAL" -eq 0 ] || exit 1
