<?php
/**
 * InStaGi Application Bootstrap
 * -----------------------------------------------------------------------------
 * Menyediakan satu titik inisialisasi terpusat untuk aplikasi.
 * Memuat konfigurasi dasar, penanganan sesi aman, dan service inti.
 * Memudahkan pengembangan modular, CLI, testing, dan porting ke arsitektur lain.
 */

// 1. Core
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/session.php';

// 2. Services (Domain Logic & Validasi)
require_once __DIR__ . '/services/gizi.php';
require_once __DIR__ . '/services/validasi.php';
require_once __DIR__ . '/services/responden.php';
require_once __DIR__ . '/services/format.php';

// 3. Views Helper
require_once __DIR__ . '/views/ikon.php';
