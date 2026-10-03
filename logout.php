<?php
// --- LOGIC UNTUK LOGOUT ---
// Session aman (lihat includes/session.php) — sekaligus memastikan
// session_start() memakai parameter cookie yang sama seperti saat login.
require_once 'includes/session.php';

// Hapus semua variabel session
$_SESSION = array();

// Hapus cookie session di browser juga (bukan hanya di server),
// agar tidak ada sisa session id yang bisa dipakai ulang.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        [
            'expires'  => time() - 42000,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]
    );
}

// Hancurkan session
session_destroy();

// Arahkan kembali ke halaman login
header("location: login.php");
exit;
