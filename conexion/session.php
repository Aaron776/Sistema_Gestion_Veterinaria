<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_secure' => false,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}
