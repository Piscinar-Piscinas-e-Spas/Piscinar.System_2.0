<?php
include '../includes/db.php';
require_login();

$idVenda = (int) ($_GET['id'] ?? 0);
if ($idVenda <= 0) {
    header('Location: ' . app_url('vendas/listar.php'));
    exit;
}

header('Location: ' . app_url('vendas/detalhes.php?id=' . $idVenda));
exit;
