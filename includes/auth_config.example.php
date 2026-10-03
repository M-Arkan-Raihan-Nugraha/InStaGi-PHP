<?php
/**
 * CONTOH — salin file ini menjadi auth_config.php lalu isi nilainya.
 *
 * Membuat hash password:
 *   php -r "echo password_hash('PasswordBaruAnda', PASSWORD_DEFAULT);"
 */
return [
    'username'      => 'admin',
    'password_hash' => '$2y$10$ganti.dengan.hash.dari.password_hash',
];
