<?php
require_once __DIR__ . '/includes/auth.php';

auth_clear_session();
header('Location: ' . app_url('login.php?reason=logged_out'));
exit;
