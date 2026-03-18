<?php
include '../includes/db.php';
require_login();

header('Location: ' . app_url('index.php'));
exit;
