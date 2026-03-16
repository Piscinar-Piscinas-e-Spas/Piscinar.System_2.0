<?php
include '../includes/db.php';
require_once dirname(__DIR__) . '/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    render_security_error(405, 'method_not_allowed', 'Metodo POST obrigatorio para esta operacao.');
}

require_valid_csrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$controller = new \App\Controllers\ProdutoController($pdo);

try {
    $status = $controller->delete($id);
} catch (\Throwable $e) {
    $status = 'erro_exclusao';
}

header('Location: ' . app_url('produtos/listar.php?status=' . $status));
exit;
