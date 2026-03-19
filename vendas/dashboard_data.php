<?php
include '../includes/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    render_security_error(405, 'method_not_allowed', 'Metodo GET obrigatorio para esta operacao.');
}

$filtros = [
    'data_inicial' => trim((string) ($_GET['data_inicial'] ?? '')),
    'data_final' => trim((string) ($_GET['data_final'] ?? '')),
    'nome_cliente' => trim((string) ($_GET['nome_cliente'] ?? '')),
    'condicao_pagamento' => trim((string) ($_GET['condicao_pagamento'] ?? '')),
    'valor_minimo' => trim((string) ($_GET['valor_minimo'] ?? '')),
    'valor_maximo' => trim((string) ($_GET['valor_maximo'] ?? '')),
];

$agrupamento = 'dia';
$dataInicial = DateTime::createFromFormat('Y-m-d', $filtros['data_inicial']);
$dataFinal = DateTime::createFromFormat('Y-m-d', $filtros['data_final']);

if ($dataInicial instanceof DateTime && $dataFinal instanceof DateTime) {
    $intervalo = (int) $dataInicial->diff($dataFinal)->format('%r%a');
    if ($intervalo > 62) {
        $agrupamento = 'mes';
    }
}

$repository = new \App\Repositories\VendaRepository($pdo);
$serie = $repository->getSerieFaturamento($filtros, $agrupamento);

$labels = [];
$faturamento = [];
$quantidadeVendas = [];

foreach ($serie as $ponto) {
    $periodo = (string) ($ponto['periodo'] ?? '');
    $dataPeriodo = $agrupamento === 'mes'
        ? DateTime::createFromFormat('Y-m', $periodo)
        : DateTime::createFromFormat('Y-m-d', $periodo);

    $labels[] = $dataPeriodo instanceof DateTime
        ? ($agrupamento === 'mes' ? $dataPeriodo->format('m/Y') : $dataPeriodo->format('d/m/Y'))
        : $periodo;

    $faturamento[] = round((float) ($ponto['faturamento'] ?? 0), 2);
    $quantidadeVendas[] = (int) ($ponto['quantidade_vendas'] ?? 0);
}

\App\Views\ApiResponse::send(200, [
    'status' => true,
    'agrupamento' => $agrupamento,
    'labels' => $labels,
    'series' => [
        'faturamento' => $faturamento,
        'quantidade_vendas' => $quantidadeVendas,
    ],
]);
