<?php
include '../includes/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    render_security_error(405, 'method_not_allowed', 'Metodo POST obrigatorio para esta operacao.');
}

$conteudo = file_get_contents('php://input');
$dados = json_decode($conteudo ?: '', true);

if (!is_array($dados)) {
    \App\Views\ApiResponse::send(400, ['status' => false, 'mensagem' => 'JSON invalido.']);
}

require_valid_csrf(is_string($dados['csrf_token'] ?? null) ? $dados['csrf_token'] : null);

$nome = trim((string) ($dados['nome'] ?? ''));
$custo = compra_decimal($dados['custo'] ?? 0);
$preco1 = compra_decimal($dados['preco1'] ?? 0);
$grupo = trim((string) ($dados['grupo'] ?? ''));
$subgrupo = trim((string) ($dados['subgrupo'] ?? ''));
$marca = trim((string) ($dados['marca'] ?? ''));
$observacoes = trim((string) ($dados['observacoes'] ?? ''));

if ($nome === '') {
    \App\Views\ApiResponse::send(422, ['status' => false, 'mensagem' => 'Nome do produto obrigatorio.']);
}

if ($custo <= 0) {
    \App\Views\ApiResponse::send(422, ['status' => false, 'mensagem' => 'Custo inicial obrigatorio.']);
}

if ($preco1 <= 0) {
    $preco1 = $custo;
}

$repository = new \App\Repositories\ProdutoRepository($pdo);

try {
    $produto = [
        'nome' => $nome,
        'custo' => round($custo, 4),
        'preco1' => round($preco1, 2),
        'preco2' => round($preco1, 2),
        'qtdLoja' => 0,
        'qtdEstoque' => 0,
        'controle_estoque' => 1,
        'estoque_minimo' => 0,
        'ponto_compra' => 0,
        'grupo' => $grupo === '' ? null : $grupo,
        'subgrupo' => $subgrupo === '' ? null : $subgrupo,
        'marca' => $marca === '' ? null : $marca,
        'observacoes' => $observacoes === '' ? null : $observacoes,
    ];

    $produtoId = $repository->create($produto);

    \App\Views\ApiResponse::send(201, [
        'status' => true,
        'mensagem' => 'Produto salvo com sucesso.',
        'produto' => [
            'id' => $produtoId,
            'nome' => $produto['nome'],
            'custo' => $produto['custo'],
            'preco1' => $produto['preco1'],
            'qtdLoja' => 0,
            'qtdEstoque' => 0,
        ],
    ]);
} catch (Throwable $e) {
    error_log('Erro ao salvar produto rapido para compra: ' . $e->getMessage());

    \App\Views\ApiResponse::send(500, [
        'status' => false,
        'mensagem' => 'Erro ao salvar produto. Tente novamente.',
    ]);
}

function compra_decimal($value): float
{
    $text = trim((string) $value);
    if ($text === '') {
        return 0.0;
    }

    if (strpos($text, ',') !== false) {
        $text = str_replace('.', '', $text);
        $text = str_replace(',', '.', $text);
    }

    return is_numeric($text) ? (float) $text : 0.0;
}
