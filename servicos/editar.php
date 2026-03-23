<?php
include '../includes/db.php';
require_login();

$idServico = (int) ($_GET['id'] ?? $_GET['id_servico'] ?? $_GET['servico_id'] ?? 0);
if ($idServico <= 0) {
    header('Location: ' . app_url('servicos/listar.php'));
    exit;
}

header('Location: ' . app_url('servicos/nova.php?id=' . $idServico));
exit;
