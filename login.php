<?php
require_once 'includes/config.php';

// --- LOGIC UNTUK LOGIN ---
session_start(); // Mulai session

// Jika user sudah login, arahkan ke halaman admin
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: admin.php");
    exit;
}

// Definisikan kredensial admin
define('ADMIN_USERNAME', 'instagiop');
// Ini adalah hash dari password 'instagi123'. Jangan ganti hash ini kecuali Anda tahu cara membuat hash baru.
define('ADMIN_PASSWORD_HASH', '$2y$10$rld16n09cQbVqtQn88WErOFEOHGK31z7os3OsenyDb2moBZ5bAQ82');

// Inisialisasi variabel
$username = "";
$password = "";
$error_message = "";

// Proses form saat data dikirim (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Ambil data dari form
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validasi kredensial menggunakan password_verify
    if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
        // Jika kredensial benar, mulai session baru
        // session_start(); // Session sudah dimulai di atas

        // Simpan data di session
        $_SESSION["loggedin"] = true;
        $_SESSION["username"] = $username;

        // Arahkan ke halaman admin
        header("location: admin.php");
    } else {
        // Jika kredensial salah, tampilkan pesan error
        $error_message = "Username atau password salah.";
    }
}
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
                <div class="error-message"><?= $error_message; ?></div>
            <?php endif; ?>
            <form action="login.php" method="post">
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
