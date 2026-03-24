<?php

namespace App\Services;

use App\Support\AuditLogger;
use PDO;

class ServicoAuditLogService
{
    private $auditLogger;

    public function __construct(PDO $pdo)
    {
        $this->auditLogger = new AuditLogger($pdo);
    }

    public function logSaveFromCurrentFlow(
        int $servicoId,
        bool $isUpdate,
        array $dados,
        array $itensProduto,
        array $itensMicro,
        array $parcelas,
        $clienteId,
        string $dataServico
    ): void {
        $snapshot = [
            'servico' => [
                'id_servico' => $servicoId,
                'cliente_id' => $clienteId,
                'data_servico' => $dataServico,
                'condicao_pagamento' => (string) ($dados['condicao_pagamento'] ?? 'vista'),
                'subtotal_produtos' => (float) ($dados['subtotal_produtos'] ?? 0),
                'subtotal_microservicos' => (float) ($dados['subtotal_microservicos'] ?? 0),
                'desconto_total' => (float) ($dados['desconto_total'] ?? 0),
                'frete_total' => (float) ($dados['frete_total'] ?? 0),
                'total_geral' => (float) ($dados['total_geral'] ?? 0),
            ],
            'itens_produto' => $itensProduto,
            'itens_microservico' => $itensMicro,
            'parcelas' => $parcelas,
            'origem_fluxo' => 'servicos/salvar.php',
        ];

        if ($isUpdate) {
            $this->auditLogger->logUpdate('servico', 'servicos_pedidos', $servicoId, [], $snapshot);
            return;
        }

        $this->auditLogger->logCreate('servico', 'servicos_pedidos', $servicoId, $snapshot);
    }
}
