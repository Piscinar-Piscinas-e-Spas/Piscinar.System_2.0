<?php
require_once __DIR__ . '/includes/auth.php';

auth_clear_session();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

session_regenerate_id(true);
$_SESSION = [];

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Location: ' . app_url('login.php?reason=logged_out'));
exit;
