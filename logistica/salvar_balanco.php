<?php
include '../includes/db.php';
require_login();
require_valid_csrf();

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    header('Location: ' . app_url('logistica/inventario.php'));
    exit;
}

$modo = (string) ($_POST['local'] ?? 'loja');
$modo = $modo === 'barracao' ? 'barracao' : 'loja';
$coluna = $modo === 'barracao' ? 'qtdEstoque' : 'qtdLoja';

$itens = $_POST['itens'] ?? [];
$busca = trim((string) ($_POST['busca'] ?? ''));
$grupo = trim((string) ($_POST['grupo'] ?? ''));
$pagina = max(1, (int) ($_POST['pagina'] ?? 1));
$porPagina = max(1, (int) ($_POST['por_pagina'] ?? 50));

$updates = [];
if (is_array($itens)) {
    foreach ($itens as $id => $quantidade) {
        $produtoId = (int) $id;
        if ($produtoId <= 0 || $quantidade === '' || $quantidade === null) {
            continue;
        }

        $updates[$produtoId] = max(0, (int) $quantidade);
    }
}

$redirectParams = [
    'local' => $modo,
    'busca' => $busca,
    'grupo' => $grupo,
    'pagina' => $pagina,
    'por_pagina' => $porPagina,
];

if ($updates === []) {
    header('Location: ' . app_url('logistica/inventario.php?' . http_build_query($redirectParams + ['status' => 'balanco_vazio'])));
    exit;
}

try {
    $stmtAtual = $pdo->prepare("UPDATE produtos SET {$coluna} = :quantidade WHERE id = :id");

    foreach ($updates as $produtoId => $quantidade) {
        $stmtAtual->execute([
            ':quantidade' => $quantidade,
            ':id' => $produtoId,
        ]);
    }

    header('Location: ' . app_url('logistica/inventario.php?' . http_build_query($redirectParams + ['status' => 'balanco_salvo'])));
    exit;
} catch (Throwable $e) {
    header('Location: ' . app_url('logistica/inventario.php?' . http_build_query($redirectParams + ['status' => 'balanco_erro'])));
    exit;
}
