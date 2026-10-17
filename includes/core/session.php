<?php
/**
 * Sesi & Keamanan (CSRF) InStaGi
 * -----------------------------------------------------------------------------
 * Menggantikan pemanggilan session_start() langsung di setiap halaman supaya
 * seluruh aplikasi memakai konfigurasi cookie sesi yang aman dan konsisten.
 *
 * Yang diatur di sini:
 *   1. Cookie sesi: HttpOnly + SameSite=Lax + Secure (otomatis bila HTTPS).
 *   2. session.use_strict_mode (menolak session id yang tidak dikenal).
 *   3. Nama sesi khusus (INSTAGISESSID) agar tidak bertabrakan dengan aplikasi
 *      lain di domain yang sama.
 *   4. Fungsi CSRF token untuk melindungi operasi tulis (POST).
 *
 * Cara pakai di halaman:  require_once 'includes/session.php';
 * (menggantikan: require_once 'includes/config.php'; + session_start();)
 *
 * CATATAN: file ini TIDAK menyentuh rumus perhitungan apa pun.
 */

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    // Deteksi HTTPS (langsung, atau lewat reverse proxy / load balancer).
    $is_https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);

    // Keamanan tambahan pada penanganan session id.
    @ini_set('session.use_strict_mode', '1');
    @ini_set('session.use_only_cookies', '1');

    session_set_cookie_params([
        'lifetime' => 0,          // berlaku sampai browser ditutup
        'path'     => '/',
        'domain'   => '',
        'secure'   => $is_https,  // hanya dikirim lewat HTTPS bila tersedia
        'httponly' => true,       // tidak bisa dibaca JavaScript (anti-XSS)
        'samesite' => 'Lax',      // tidak ikut pada permintaan lintas situs
    ]);

    session_name('INSTAGISESSID');
    session_start();
}

if (!function_exists('instagi_csrf_token')) {
    /**
     * Ambil (dan buat bila belum ada) CSRF token untuk sesi ini.
     */
    function instagi_csrf_token()
    {
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('instagi_csrf_check')) {
    /**
     * Bandingkan token yang dikirim klien dengan token di sesi (constant-time).
     */
    function instagi_csrf_check($token)
    {
        return is_string($token)
            && $token !== ''
            && !empty($_SESSION['csrf_token'])
            && is_string($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('instagi_csrf_from_request')) {
    /**
     * Ambil token CSRF dari body POST, lalu dari header (untuk AJAX).
     */
    function instagi_csrf_from_request()
    {
        if (isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])) {
            return $_POST['csrf_token'];
        }
        $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return is_string($header) ? $header : '';
    }
}

if (!function_exists('instagi_csrf_require_json')) {
    /**
     * Penjaga untuk endpoint API: hentikan dengan HTTP 403 bila token tidak sah.
     */
    function instagi_csrf_require_json()
    {
        if (instagi_csrf_check(instagi_csrf_from_request())) {
            return;
        }
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(403);
        }
        echo json_encode([
            'success' => false,
            'message' => 'Token keamanan tidak valid atau sudah kedaluwarsa. Silakan muat ulang halaman lalu coba lagi.',
        ]);
        exit;
    }
}

if (!function_exists('instagi_require_login_json')) {
    /**
     * Penjaga untuk endpoint API: hentikan dengan HTTP 401 bila belum login.
     */
    function instagi_require_login_json()
    {
        if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
            return;
        }
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(401);
        }
        echo json_encode([
            'success' => false,
            'message' => 'Akses tidak diizinkan. Silakan login terlebih dahulu.',
        ]);
        exit;
    }
}
