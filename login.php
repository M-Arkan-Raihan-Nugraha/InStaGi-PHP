<?php
// Session aman (HttpOnly/SameSite/Secure + strict mode) + helper CSRF.
require_once 'includes/session.php';

// --- LOGIC UNTUK LOGIN ---

// Jika user sudah login, arahkan ke halaman admin
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: admin.php");
    exit;
}

// Definisikan kredensial admin (dibaca dari includes/auth_config.php).
// Dapat di-override lewat environment variable untuk produksi.
$auth_config_file = __DIR__ . '/includes/auth_config.php';
$auth_config = is_file($auth_config_file) ? require $auth_config_file : [];
define('ADMIN_USERNAME', getenv('INSTAGI_ADMIN_USER') ?: ($auth_config['username'] ?? ''));
// Nilai default adalah hash dari password 'instagi123'.
// Untuk mengganti password, lihat petunjuk di includes/auth_config.php.
define('ADMIN_PASSWORD_HASH', getenv('INSTAGI_ADMIN_HASH') ?: ($auth_config['password_hash'] ?? ''));

// --- Pembatas percobaan login (anti brute-force) ---
// Berbasis sesi: maksimal 5 percobaan gagal, lalu jeda 15 menit.
define('INSTAGI_LOGIN_MAX_ATTEMPTS', 5);
define('INSTAGI_LOGIN_LOCKOUT_SECONDS', 900);

// Inisialisasi variabel
$username = "";
$password = "";
$error_message = "";

if (!isset($_SESSION['login_attempts']) || !is_array($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = ['count' => 0, 'last' => 0];
}

// Proses form saat data dikirim (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Verifikasi token CSRF terlebih dahulu.
    if (!instagi_csrf_check($_POST['csrf_token'] ?? '')) {
        $error_message = "Sesi form tidak valid. Silakan muat ulang halaman lalu coba lagi.";
    } else {
        $attempts = $_SESSION['login_attempts'];
        $locked_until = (int) $attempts['last'] + INSTAGI_LOGIN_LOCKOUT_SECONDS;
        $is_locked = ((int) $attempts['count'] >= INSTAGI_LOGIN_MAX_ATTEMPTS) && (time() < $locked_until);

        if ($is_locked) {
            $sisa = (int) ceil(($locked_until - time()) / 60);
            $error_message = "Terlalu banyak percobaan gagal. Coba lagi dalam {$sisa} menit.";
        } else {
            // Ambil data dari form
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            // Validasi kredensial menggunakan password_verify
            if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
                // Cegah session fixation: ganti ID sesi setelah login berhasil.
                session_regenerate_id(true);

                // Reset penghitung percobaan gagal.
                $_SESSION['login_attempts'] = ['count' => 0, 'last' => 0];

                // Simpan data di session
                $_SESSION["loggedin"] = true;
                $_SESSION["username"] = $username;

                // Arahkan ke halaman admin (WAJIB exit agar eksekusi berhenti).
                header("location: admin.php");
                exit;
            } else {
                // Catat percobaan gagal.
                $attempts['count'] = (int) $attempts['count'] + 1;
                $attempts['last'] = time();
                $_SESSION['login_attempts'] = $attempts;

                // Jika sudah melewati batas, mulai periode jeda.
                if ($attempts['count'] >= INSTAGI_LOGIN_MAX_ATTEMPTS) {
                    $error_message = "Terlalu banyak percobaan gagal. Coba lagi dalam 15 menit.";
                } else {
                    $sisa = INSTAGI_LOGIN_MAX_ATTEMPTS - (int) $attempts['count'];
                    $error_message = "Username atau password salah. Sisa percobaan: {$sisa}.";
                }
            }
        }
    }
}

// Token CSRF untuk form di bawah.
$csrf_token = instagi_csrf_token();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InStaGi - Login Admin</title>
    <link rel="shortcut icon" href="assets/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <main class="main-content">
        <div class="login-container">
            <h1>Login Admin</h1>
            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <form action="login.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autocomplete="username" placeholder="Masukkan Username">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Masukkan Password">
                </div>
                <button type="submit" class="submit-btn">Login</button>
            </form>
        </div>
    </main>
    <footer class="main-footer">
        <p>Copyright © 2026 InStaGi | Created With ❤️</p>
    </footer>
</body>
</html>
