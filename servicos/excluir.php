<?php
include '../includes/db.php';
require_login();

header('Location: ' . app_url('servicos/listar.php'));
exit;
