<?php
include '../includes/db.php';
require_login();

if (!function_exists('normalize_compra_dashboard_date')) {
    function normalize_compra_dashboard_date(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        foreach (['d/m/Y', 'Y-m-d'] as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date instanceof \DateTime && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return '';
    }
}

$filters = [
    'data_inicial' => normalize_compra_dashboard_date($_GET['data_inicial'] ?? ''),
    'data_final' => normalize_compra_dashboard_date($_GET['data_final'] ?? ''),
    'nome_fornecedor' => trim((string) ($_GET['nome_fornecedor'] ?? '')),
    'condicao_pagamento' => trim((string) ($_GET['condicao_pagamento'] ?? '')),
    'numero_nota' => trim((string) ($_GET['numero_nota'] ?? '')),
    'valor_minimo' => trim((string) ($_GET['valor_minimo'] ?? '')),
    'valor_maximo' => trim((string) ($_GET['valor_maximo'] ?? '')),
];

$repository = new \App\Repositories\CompraEntradaRepository($pdo);
$serieDia = $repository->getSerieCompras($filters, 'dia');
$serieMes = $repository->getSerieCompras($filters, 'mes');
$usarMes = count($serieDia) > 31;
$serie = $usarMes ? $serieMes : $serieDia;

\App\Views\ApiResponse::send(200, [
    'status' => true,
    'agrupamento' => $usarMes ? 'mes' : 'dia',
    'labels' => array_map(static fn (array $row): string => (string) ($row['periodo'] ?? ''), $serie),
    'series' => [
        'total_compras' => array_map(static fn (array $row): float => (float) ($row['total_compras'] ?? 0), $serie),
        'quantidade_compras' => array_map(static fn (array $row): int => (int) ($row['quantidade_compras'] ?? 0), $serie),
    ],
]);
