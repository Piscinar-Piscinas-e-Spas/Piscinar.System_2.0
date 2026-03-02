<?php
include '../includes/db.php';
require_once dirname(__DIR__) . '/config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: ' . app_url('clientes/listar.php?status=erro_id'));
    exit;
}

try {
    $stmt = $pdo->prepare('DELETE FROM clientes WHERE id_cliente = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        header('Location: ' . app_url('clientes/listar.php?status=excluido'));
        exit;
    }

    header('Location: ' . app_url('clientes/listar.php?status=nao_encontrado'));
    exit;
} catch (PDOException $e) {
    header('Location: ' . app_url('clientes/listar.php?status=erro_exclusao'));
    exit;
}
