<?php
// backend/config/session.php
// Call configureSession() BEFORE session_start() in every auth endpoint.
// Ensures the session cookie is sent correctly when Vite proxies requests
// to the PHP built-in dev server on port 8081.

function configureSession(): void
{
    // Allow the session cookie to be attached to cross-port proxy requests
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',       // empty = current domain only
        'secure' => true,     // Required for SameSite=None
        'httponly' => true,
        'samesite' => 'None',   // Required for cross-domain cookies
    ]);

    session_name('SMART_SESS');
}
