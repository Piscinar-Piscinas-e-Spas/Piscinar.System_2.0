<?php
include '../includes/db.php';
require_once dirname(__DIR__) . '/config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$controller = new \App\Controllers\ProdutoController($pdo);

try {
    $status = $controller->delete($id);
} catch (\Throwable $e) {
    $status = 'erro_exclusao';
}

header('Location: ' . app_url('produtos/listar.php?status=' . $status));
exit;
