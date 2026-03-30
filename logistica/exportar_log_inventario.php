<?php
include '../includes/db.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    render_security_error(400, 'invalid_inventory_log', 'Relatorio de inventario invalido.');
}

$repository = new \App\Repositories\InventarioLogRepository($pdo);
$service = new \App\Services\InventarioLogService($repository);
$detalhe = $service->getReportById($id);

if (!$detalhe || !empty($detalhe['error']) || !is_array($detalhe['report'] ?? null)) {
    render_security_error(404, 'inventory_log_not_found', 'Relatorio de inventario nao encontrado.');
}

$fileName = (string) (($detalhe['meta']['nome_arquivo'] ?? '') !== '' ? $detalhe['meta']['nome_arquivo'] : ('inventario_log_' . $id . '.json'));
$payload = json_encode($detalhe['report'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if ($payload === false) {
    render_security_error(500, 'inventory_log_export_error', 'Nao foi possivel exportar o relatorio.');
}

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $fileName) . '"');
echo $payload;
exit;
