#!/usr/bin/env bash
# Uji fitur baru InStaGi: rename menu, hapus Login Admin, background lightblue,
# akun berpassword, grafik Monitoring, layanan WhatsApp, Bestie InStaGi.
BASE="http://127.0.0.1:8099"
MYSQL=/d/SMAKZIE/PRODUKTIF/XAMPP/mysql/bin/mysql.exe
DB=instagi_v3_uji
WORK="C:/Users/Pongo/AppData/Local/hermes/cache/scratch/e2e"
mkdir -p "$WORK"; rm -f "$WORK"/ck*.txt
LULUS=0; GAGAL=0
hitung() { if [ "$2" = "$3" ]; then LULUS=$((LULUS+1)); echo "  OK    $1"; else GAGAL=$((GAGAL+1)); echo "  GAGAL $1 :: harap=[$3] dapat=[$2]"; fi; }
ada()    { if echo "$2" | grep -qF "$3"; then LULUS=$((LULUS+1)); echo "  OK    $1"; else GAGAL=$((GAGAL+1)); echo "  GAGAL $1 :: tidak menemukan [$3]"; fi; }
tiada()  { if echo "$2" | grep -qF "$3"; then GAGAL=$((GAGAL+1)); echo "  GAGAL $1 :: seharusnya TIDAK ada [$3]"; else LULUS=$((LULUS+1)); echo "  OK    $1"; fi; }
csrf_form() { echo "$1" | grep -o 'name="csrf_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//'; }
hash_ada() { "$MYSQL" -u root -N -B -e "SELECT IF(password_hash IS NULL OR password_hash='', 'NULL', 'ADA') FROM $DB.responden WHERE nama='$1';" 2>/dev/null | tr -d '\r' | head -1; }
HARI=$(date +%F)

echo "=== A. BERANDA: menu, layanan, bestie, tanpa Login Admin ==="
curl -s -o "$WORK/ck_index.html" -c "$WORK/ck_a.txt" -b "$WORK/ck_a.txt" "$BASE/index.php"
IDX=$(cat "$WORK/ck_index.html")
ada "kartu (1) Cek Status Gizi"        "$IDX" ">Cek Status Gizi<"
ada "kartu (2) Informasi Gizi"         "$IDX" ">Informasi Gizi<"
ada "kartu (3) Monitoring Status Gizi" "$IDX" ">Monitoring Status Gizi<"
ada "tanpa kartu Login Admin"          "$(echo "$IDX" | grep -c 'Login Admin'; true)" "0"
ada "tanpa tautan ke login.php"        "$(echo "$IDX" | grep -c 'href="login.php"'; true)" "0"
ada "bagian Layanan Kami"              "$IDX" "Layanan Kami"
ada "layanan Konsultasi Gizi"          "$IDX" "Konsultasi Gizi"
ada "layanan Katering Gizi"            "$IDX" "Katering Gizi"
ada "bagian Bestie InStaGi"            "$IDX" "Bestie InStaGi"
ada "tombol WhatsApp kerja sama"       "$IDX" "Jadi Bestie InStaGi"
JAJAK=$(echo "$IDX" | grep -o 'https://wa.me/[0-9]*' | sort -u | wc -l)
hitung "jumlah nomor WhatsApp unik di beranda = 1 (satu nomor, tiga layanan)" "$JAJAK" "1"
NAVBLOCK=$(echo "$IDX" | sed -n '/<nav class="site-nav-links"/,/<\/nav>/p')
ada "navigasi memuat Cek Status Gizi"        "$NAVBLOCK" "Cek Status Gizi"
ada "navigasi memuat Informasi Gizi"         "$NAVBLOCK" "Informasi Gizi"
ada "navigasi memuat Monitoring Status Gizi" "$NAVBLOCK" "Monitoring Status Gizi"
ada "navigasi TIDAK memuat Login Admin" "$(echo "$IDX" | grep -o 'Login Admin' | wc -l; true)" "0"

echo
echo "=== B. BACKGROUND LIGHTBLUE (dipusatkan di css/base.css) ==="
cd "D:/Codingan/Instagi/V3 PHP Native"
ada "css/base.css memakai #ADD8E6 (lightblue)" "$(grep -c '#ADD8E6' css/base.css; true)" "1"
ada "layout_head.php memuat /css/base.css untuk semua halaman" "$(grep -c '/css/base.css' includes/views/layout_head.php; true)" "1"
for f in index imt result informasi monitoring admin login; do
  ada "$f.php memakai partial layout_head.php" "$(grep -Eq 'includes/(views/)?layout_head.php' $f.php && echo YA || echo TIDAK)" "YA"
  tiada "$f.php tidak lagi menyalin blok <head> sendiri" "$(grep -c '<!DOCTYPE html>' $f.php; true)" "1"
done
ada "tidak ada sisa warna lama #CFE8FF" "$(grep -rl '#CFE8FF' css/ 2>/dev/null | wc -l; true)" "0"

echo
echo "=== C. FORM: AKUN AMAN (OPSIONAL) ==="
curl -s -o "$WORK/ck_imt.html" -c "$WORK/ck_b.txt" -b "$WORK/ck_b.txt" "$BASE/imt.php"
ISIAN=$(cat "$WORK/ck_imt.html")
CSRF=$(csrf_form "$ISIAN")
ada "kotak centang akun aman ada" "$ISIAN" 'name="buat_akun"'
ada "kolom password akun ada"     "$ISIAN" 'id="akunAmanPass"'
token_imt() { # $1 = berkas jar -> cetak token CSRF untuk sesi jar itu
  curl -s -o "$WORK/ck_tok.html" -c "$1" -b "$1" "$BASE/imt.php"
  csrf_form "$(cat "$WORK/ck_tok.html")"
}
kirim_imt() { # $1 = jar, $2 = token sesi jar itu, lalu nama usia jk hp bb tb [opsi]
  local jar="$1" csrf="$2"; shift 2
  curl -s -o "$WORK/ck_kirim.html" -w '%{http_code}' -c "$jar" -b "$jar" "$BASE/imt.php" \
    --data-urlencode "csrf_token=$csrf" --data "submitted=1" --data-urlencode "tanggal_input=$HARI" \
    --data-urlencode "nama=$1" --data "usia=$2" --data "jenis_kelamin=$3" \
    --data-urlencode "no_hp=$4" --data "bb=$5" --data "tb=$6" --data "aktivitas=1.55" "${@:7}"
}
CSRF_C=$(token_imt "$WORK/ck_b.txt")
KODE=$(kirim_imt "$WORK/ck_b.txt" "$CSRF_C" "Uji Pendek" 30 Laki-laki 081200000001 60 165 --data "buat_akun=1" --data "password=pendek" --data "password_ulang=pendek")
ada "password 6 karakter ditolak" "$(cat "$WORK/ck_kirim.html")" "Password minimal 8 karakter."
KODE=$(kirim_imt "$WORK/ck_b.txt" "$CSRF_C" "Uji Beda" 30 Laki-laki 081200000002 60 165 --data "buat_akun=1" --data "password=rahasia123" --data "password_ulang=rahasia124")
ada "password tidak sama ditolak" "$(cat "$WORK/ck_kirim.html")" "tidak sama"
KODE=$(kirim_imt "$WORK/ck_b.txt" "$CSRF_C" "Uji Kosong" 30 Laki-laki 081200000003 60 165 --data "buat_akun=1")
ada "centang tanpa password ditolak" "$(cat "$WORK/ck_kirim.html")" "Isi password akun"
KODE=$(kirim_imt "$WORK/ck_b.txt" "$CSRF_C" "Budi Aman" 30 Laki-laki 081200000010 70 172 --data "buat_akun=1" --data "password=rahasia123" --data "password_ulang=rahasia123")
hitung "input dengan akun aman -> 302" "$KODE" "302"
hitung "akun Budi Aman tersimpan berpassword" "$(hash_ada 'Budi Aman')" "ADA"
CSRF_D=$(token_imt "$WORK/ck_c.txt")
KODE=$(kirim_imt "$WORK/ck_c.txt" "$CSRF_D" "Sinta Lama" 28 Perempuan 081200000011 55 158)
hitung "input tanpa akun aman -> 302" "$KODE" "302"
hitung "akun Sinta Lama tanpa password" "$(hash_ada 'Sinta Lama')" "NULL"

echo
echo "=== D. PASSWORD TIDAK BISA DITIMPA DARI FORM PUBLIK ==="
CSRF_E=$(token_imt "$WORK/ck_d.txt")
KODE=$(kirim_imt "$WORK/ck_d.txt" "$CSRF_E" "Budi Aman" 30 Laki-laki 081200000010 68 172 --data "buat_akun=1" --data "password=passwordlain" --data "password_ulang=passwordlain")
hitung "kiriman form publik kedua tetap tersimpan (302)" "$KODE" "302"
hitung "password akun TIDAK ikut tertimpa" "$("$MYSQL" -u root -N -B -e "SELECT COUNT(*) FROM $DB.imt_history WHERE nama='Budi Aman';" 2>/dev/null | tr -d '\r')" "2"
# Verifikasi lewat Monitoring: password LAMA harus tetap berlaku, yang baru tidak.
rm -f "$WORK/ck_e.txt"
CSRF_M=$(csrf_form "$(curl -s -c "$WORK/ck_e.txt" -b "$WORK/ck_e.txt" "$BASE/monitoring.php")")
KELUAR=$(curl -s -L -c "$WORK/ck_e.txt" -b "$WORK/ck_e.txt" "$BASE/monitoring.php" \
  --data-urlencode "csrf_token=$CSRF_M" --data "action=login" --data-urlencode "nama=Budi Aman" \
  --data-urlencode "hp=081200000010" --data-urlencode "password=rahasia123")
ada "password LAMA masih berlaku (tidak tertimpa form publik)" "$KELUAR" "Riwayat Pengukuran"
rm -f "$WORK/ck_e2.txt"
CSRF_M2=$(csrf_form "$(curl -s -c "$WORK/ck_e2.txt" -b "$WORK/ck_e2.txt" "$BASE/monitoring.php")")
KELUAR=$(curl -s -c "$WORK/ck_e2.txt" -b "$WORK/ck_e2.txt" "$BASE/monitoring.php" \
  --data-urlencode "csrf_token=$CSRF_M2" --data "action=login" --data-urlencode "nama=Budi Aman" \
  --data-urlencode "hp=081200000010" --data-urlencode "password=passwordlain")
ada "password dari form publik TIDAK berlaku" "$KELUAR" "Password tidak cocok"

echo
echo "=== E. MONITORING: LOGIN BERPASSWORD ==="
rm -f "$WORK/ck_f.txt"
CSRF_M=$(csrf_form "$(curl -s -c "$WORK/ck_f.txt" -b "$WORK/ck_f.txt" "$BASE/monitoring.php")")
ada "form masuk punya kolom password" "$(curl -s -c "$WORK/ck_f.txt" -b "$WORK/ck_f.txt" "$BASE/monitoring.php")" 'name="password"'
KELUAR=$(curl -s -c "$WORK/ck_f.txt" -b "$WORK/ck_f.txt" "$BASE/monitoring.php" \
  --data-urlencode "csrf_token=$CSRF_M" --data "action=login" --data-urlencode "nama=Budi Aman" --data-urlencode "hp=081200000010")
ada "login tanpa password ditolak + diberi tahu" "$KELUAR" "Akun ini memakai password"
KELUAR=$(curl -s -c "$WORK/ck_f.txt" -b "$WORK/ck_f.txt" "$BASE/monitoring.php" \
  --data-urlencode "csrf_token=$CSRF_M" --data "action=login" --data-urlencode "nama=Budi Aman" --data-urlencode "hp=081200000010" --data-urlencode "password=salahbanget")
ada "password salah ditolak" "$KELUAR" "Password tidak cocok"
KELUAR=$(curl -s -L -c "$WORK/ck_f.txt" -b "$WORK/ck_f.txt" "$BASE/monitoring.php" \
  --data-urlencode "csrf_token=$CSRF_M" --data "action=login" --data-urlencode "nama=Budi Aman" --data-urlencode "hp=081200000010" --data-urlencode "password=rahasia123")
ada "password benar -> masuk (follow redirect)" "$KELUAR" "Riwayat Pengukuran"
ada "akun aman ditandai di halaman" "$KELUAR" "Akun aman"
ada "grafik Monitoring dirender" "$KELUAR" 'id="grafikImt"'
ada "data grafik ikut dikirim ke Chart.js" "$KELUAR" '"imt":'
ada "garis panduan kategori ada di skrip grafik" "$KELUAR" "garisPanduan"
ada "ringkasan diperluas (rata-rata IMT)" "$KELUAR" "Rata-rata IMT"
ada "bagian Keamanan Akun ada" "$KELUAR" "Keamanan Akun"

echo
echo "=== F. AKUN LAMA (TANPA PASSWORD) TETAP BISA MASUK, LALU DIPASANG PASSWORD ==="
rm -f "$WORK/ck_g.txt"
CSRF_M=$(csrf_form "$(curl -s -c "$WORK/ck_g.txt" -b "$WORK/ck_g.txt" "$BASE/monitoring.php")")
KELUAR=$(curl -s -L -c "$WORK/ck_g.txt" -b "$WORK/ck_g.txt" "$BASE/monitoring.php" \
  --data-urlencode "csrf_token=$CSRF_M" --data "action=login" --data-urlencode "nama=Sinta Lama" --data-urlencode "hp=081200000011")
ada "akun lama masuk tanpa password (kompatibel)" "$KELUAR" "Riwayat Pengukuran"
ada "akun lama ditandai 'Belum memakai password'" "$KELUAR" "Belum memakai password"
KELUAR=$(curl -s -c "$WORK/ck_g.txt" -b "$WORK/ck_g.txt" "$BASE/monitoring.php" \
  --data-urlencode "csrf_token=$CSRF_M" --data "action=password" --data-urlencode "password_baru=sinta1234" --data-urlencode "password_ulang=sinta1234")
ada "pasang password dari Monitoring berhasil" "$KELUAR" "Password akun berhasil disimpan"
hitung "akun Sinta Lama kini berpassword" "$(hash_ada 'Sinta Lama')" "ADA"
KELUAR=$(curl -s -c "$WORK/ck_g.txt" -b "$WORK/ck_g.txt" "$BASE/monitoring.php" \
  --data-urlencode "csrf_token=$CSRF_M" --data "action=password" --data-urlencode "password_lama=SALAH" --data-urlencode "password_baru=sinta5678" --data-urlencode "password_ulang=sinta5678")
ada "ganti password dengan password lama salah ditolak" "$KELUAR" "Password lama tidak cocok."
KELUAR=$(curl -s -c "$WORK/ck_g.txt" -b "$WORK/ck_g.txt" "$BASE/monitoring.php" \
  --data-urlencode "csrf_token=$CSRF_M" --data "action=password" --data-urlencode "password_lama=sinta1234" --data-urlencode "password_baru=sinta5678" --data-urlencode "password_ulang=sinta5678")
ada "ganti password dengan password lama benar berhasil" "$KELUAR" "Password akun berhasil disimpan"
rm -f "$WORK/ck_h.txt"
CSRF_M=$(csrf_form "$(curl -s -c "$WORK/ck_h.txt" -b "$WORK/ck_h.txt" "$BASE/monitoring.php")")
KELUAR=$(curl -s -L -c "$WORK/ck_h.txt" -b "$WORK/ck_h.txt" "$BASE/monitoring.php" \
  --data-urlencode "csrf_token=$CSRF_M" --data "action=login" --data-urlencode "nama=Sinta Lama" --data-urlencode "hp=081200000011" --data-urlencode "password=sinta5678")
ada "password BARU dipakai untuk masuk" "$KELUAR" "Riwayat Pengukuran"

echo
echo "=== G. HALAMAN LAIN: tanpa tautan Login Admin ==="
for h in informasi.php monitoring.php result.php index.php imt.php; do
  ada "$h tidak memuat 'Login Admin'" "$(curl -s "$BASE/$h" | grep -o 'Login Admin' | wc -l; true)" "0"
done
ada "login.php tetap bisa dibuka langsung (pintu admin)" "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/login.php")" "200"
ada "login.php TIDAK memasang tautan Login Admin di navigasinya" "$(curl -s "$BASE/login.php" | sed -n '/site-nav-links/,/<\/nav>/p' | grep -c 'Login Admin'; true)" "0"

echo
echo "=== H. ALAMAT RAPI (tanpa .php) & ALAMAT LAMA TETAP HIDUP ==="
for u in / /imt /informasi /monitoring /login; do
  ada "GET $u -> 200" "$(curl -s -o /dev/null -w '%{http_code}' "$BASE$u")" "200"
done
ada "alamat rapi /imt menampilkan judul halaman yang benar" "$(curl -s "$BASE/imt" | grep -o '<title>[^<]*')" "InStaGi - Cek Status Gizi"
for u in /imt.php /informasi.php /monitoring.php /login.php; do
  ada "alamat lama $u tetap 200 (tautan/bookmark lama tidak mati)" "$(curl -s -o /dev/null -w '%{http_code}' "$BASE$u")" "200"
done
ada "aset /css/base.css tidak tertelan aturan rewrite" "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/css/base.css")" "200"
ada "API /api/get_record.php tetap menolak tanpa login (tidak tertelan rewrite)" "$(curl -s "$BASE/api/get_record.php")" "Akses tidak diizinkan"
ada "/imt/ (berslash) dialihkan 301 ke /imt" "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/imt/")" "301"
ada "tidak ada lagi tautan .php di beranda (menu & kartu)" "$(grep -o 'href="[a-z_]*\.php"' index.php | wc -l; true)" "0"
ada "canonical memakai alamat rapi" "$(curl -s "$BASE/imt" | grep -o 'rel="canonical" href="[^"]*')" "/imt"

echo
echo "=== I. KONTEN INFORMASI GIZI, IKON VEKTOR, GAYA MONITORING ==="
INFO=$(curl -s "$BASE/informasi")
ada "halaman Informasi memuat 5 bagian galeri" "$(echo "$INFO" | grep -o 'class="info-section"' | wc -l)" "5"
ada "bagian Kumpulan Resep ada" "$(echo "$INFO" | grep -c 'id="resep"')" "1"
ada "bagian Panduan & Edukasi ada" "$(echo "$INFO" | grep -c 'id="panduan"')" "1"
ada "kartu galeri (leaflet+menu+resep+panduan+dbmp)" "$(echo "$INFO" | grep -o 'class="info-card"' | wc -l)" "36"
ada "tautan lompat antar bagian" "$(echo "$INFO" | grep -o 'class="info-jump"' | wc -l)" "1"
ada "kartu resep memakai thumbnail assets/resep/thumbs" "$(echo "$INFO" | grep -c 'assets/resep/thumbs/')" "15"
ada "kartu panduan memakai thumbnail assets/panduan/thumbs" "$(echo "$INFO" | grep -c 'assets/panduan/thumbs/')" "7"
ada "berkas modul resep & panduan ada" "$(ls includes/content/resep.php includes/content/panduan.php | wc -l)" "2"
# Ikon: SVG menggantikan emoji
for u in / /imt /informasi /monitoring; do
  H=$(curl -s "$BASE$u")
  ada "$u memakai ikon vektor (svg)" "$(echo "$H" | grep -o '<svg' | wc -l | awk '{print ($1>0)?"ADA":"TIDAK"}')" "ADA"
  ada "$u bebas emoji" "$(echo "$H" | grep -c '📊\|📄\|📈\|🛠\|🔒\|⚠️\|❤️\|🥗\|🍱')" "0"
done
ada "logo beranda diperkecil (maks 170px)" "$(grep -c 'max-height: 170px' css/index.css)" "1"
MON=$(curl -s "$BASE/monitoring")
ada "Monitoring: badge 'Akun aman' sudah dihapus" "$(echo "$MON" | grep -c 'Akun aman')" "0"
ada "Monitoring: judul bagian pakai gaya halaman hasil" "$(grep -c 'Judul bagian Monitoring memakai gaya judul halaman hasil' css/pages.css)" "1"
ada "Monitoring: tombol 'Cek Status Gizi Baru' ada" "$(grep -c 'Cek Status Gizi Baru' monitoring.php)" "1"

echo
echo "=============================================="
echo "LULUS: $LULUS | GAGAL: $GAGAL"
[ "$GAGAL" -eq 0 ] || exit 1
