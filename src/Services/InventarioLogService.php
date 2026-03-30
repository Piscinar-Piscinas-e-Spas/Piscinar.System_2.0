<?php

namespace App\Services;

use App\Repositories\InventarioLogRepository;
use RuntimeException;

class InventarioLogService
{
    private $repository;

    public function __construct(InventarioLogRepository $repository)
    {
        $this->repository = $repository;
    }

    public function recordBalanceEvent(array $context, array $itens): array
    {
        $storageDir = $this->resolveStorageDir();
        $timestamp = new \DateTimeImmutable('now', new \DateTimeZone('America/Sao_Paulo'));
        $logDatePath = $timestamp->format('Y/m');
        $targetDir = $storageDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $logDatePath);

        if (!is_dir($targetDir) && !mkdir($targetDir, 0770, true) && !is_dir($targetDir)) {
            throw new RuntimeException('Nao foi possivel criar o diretorio de logs de inventario.');
        }

        $quantidadeItens = count($itens);
        $quantidadeAlterados = 0;
        $somaDiferencasPositivas = 0.0;
        $somaDiferencasNegativas = 0.0;

        foreach ($itens as $item) {
            $diferenca = (float) ($item['diferenca'] ?? 0);
            if (abs($diferenca) > 0.0001) {
                $quantidadeAlterados++;
            }

            if ($diferenca > 0) {
                $somaDiferencasPositivas += $diferenca;
            } elseif ($diferenca < 0) {
                $somaDiferencasNegativas += $diferenca;
            }
        }

        $fileName = sprintf(
            'inventario_%s_%s_%s.json',
            $context['local'],
            $timestamp->format('Ymd_His'),
            bin2hex(random_bytes(4))
        );

        $payload = [
            'identificador' => $fileName,
            'usuario' => [
                'id' => $context['usuario_id'],
                'nome' => $context['usuario_nome'],
            ],
            'data_hora' => $timestamp->format('Y-m-d H:i:s'),
            'local' => $context['local'],
            'coluna_afetada' => $context['coluna'],
            'filtros' => [
                'busca' => $context['busca'],
                'grupo' => $context['grupo'],
                'pagina' => $context['pagina'],
                'por_pagina' => $context['por_pagina'],
            ],
            'resumo' => [
                'total_itens' => $quantidadeItens,
                'itens_alterados' => $quantidadeAlterados,
                'soma_diferencas_positivas' => round($somaDiferencasPositivas, 3),
                'soma_diferencas_negativas' => round($somaDiferencasNegativas, 3),
            ],
            'itens' => array_values($itens),
        ];

        $absolutePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;
        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($encoded === false) {
            throw new RuntimeException('Nao foi possivel serializar o relatorio de inventario.');
        }

        if (file_put_contents($absolutePath, $encoded, LOCK_EX) === false) {
            throw new RuntimeException('Nao foi possivel gravar o relatorio de inventario.');
        }

        try {
            $logId = $this->repository->create([
                'local_inventario' => $context['local'],
                'usuario_id' => $context['usuario_id'],
                'usuario_nome' => $context['usuario_nome'],
                'quantidade_itens' => $quantidadeItens,
                'quantidade_itens_alterados' => $quantidadeAlterados,
                'caminho_arquivo' => $absolutePath,
                'nome_arquivo' => $fileName,
            ]);
        } catch (\Throwable $e) {
            @unlink($absolutePath);
            throw $e;
        }

        return [
            'id' => $logId,
            'path' => $absolutePath,
        ];
    }

    public function getReportById(int $logId): ?array
    {
        $registro = $this->repository->findById($logId);
        if (!$registro) {
            return null;
        }

        $path = (string) ($registro['caminho_arquivo'] ?? '');
        if ($path === '' || !is_file($path) || !is_readable($path)) {
            return [
                'meta' => $registro,
                'report' => null,
                'error' => 'Arquivo de relatorio nao encontrado ou sem permissao de leitura.',
            ];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return [
            'meta' => $registro,
            'report' => is_array($decoded) ? $decoded : null,
            'error' => is_array($decoded) ? null : 'Arquivo de relatorio invalido.',
        ];
    }

    public function listReports(array $filters): array
    {
        return $this->repository->search($filters);
    }

    private function resolveStorageDir(): string
    {
        $configured = trim((string) getenv('INVENTARIO_LOG_STORAGE_PATH'));
        if ($configured !== '') {
            return rtrim($configured, "\\/");
        }

        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'inventario_logs';
    }
}
