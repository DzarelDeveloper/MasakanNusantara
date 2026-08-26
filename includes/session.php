<?php
/**
 * Shared session bootstrap. Sets cookie flags (HttpOnly, SameSite, and
 * Secure when served over HTTPS) before starting the session — must be
 * required and called in place of a bare session_start().
 */

function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $https,
    ]);

    session_start();
}
