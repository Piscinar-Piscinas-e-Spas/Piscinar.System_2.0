<?php

namespace App\Repositories;

use App\Support\AuditLogger;
use PDO;

class ServicoRepository
{
    private $pdo;
    private $auditLogger;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->auditLogger = new AuditLogger($pdo);
    }

    public function createServico(array $servico): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO servicos (
                id_cliente,
                data_servico,
                condicao_pagamento,
                subtotal_produtos,
                subtotal_microservicos,
                desconto_total,
                frete_total,
                total_geral,
                created_at,
                updated_at
            ) VALUES (
                :id_cliente,
                CURDATE(),
                :condicao_pagamento,
                :subtotal_produtos,
                :subtotal_microservicos,
                :desconto_total,
                :frete_total,
                :total_geral,
                NOW(),
                NOW()
            )');

        $stmt->execute([
            ':id_cliente' => $servico['cliente_id'],
            ':condicao_pagamento' => $servico['condicao_pagamento'],
            ':subtotal_produtos' => $servico['subtotal_produtos'],
            ':subtotal_microservicos' => $servico['subtotal_microservicos'],
            ':desconto_total' => $servico['desconto_total'],
            ':frete_total' => $servico['frete_total'],
            ':total_geral' => $servico['total_geral'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function createServicoProduto(int $servicoId, array $item): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO servico_produtos (
                id_servico,
                id_produto,
                descricao,
                quantidade,
                valor_unitario,
                desconto_valor,
                frete_valor,
                total_item,
                created_at,
                updated_at
            ) VALUES (
                :id_servico,
                :id_produto,
                :descricao,
                :quantidade,
                :valor_unitario,
                :desconto_valor,
                :frete_valor,
                :total_item,
                NOW(),
                NOW()
            )');

        $stmt->execute([
            ':id_servico' => $servicoId,
            ':id_produto' => $item['produto_id'],
            ':descricao' => $item['descricao'],
            ':quantidade' => $item['quantidade'],
            ':valor_unitario' => $item['valor_unitario'],
            ':desconto_valor' => $item['desconto_valor'],
            ':frete_valor' => $item['frete_valor'],
            ':total_item' => $item['total_item'],
        ]);
    }

    public function createServicoMicroservico(int $servicoId, array $item): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO servico_microservicos (
                id_servico,
                descricao,
                quantidade,
                valor_unitario,
                desconto_valor,
                frete_valor,
                total_item,
                created_at,
                updated_at
            ) VALUES (
                :id_servico,
                :descricao,
                :quantidade,
                :valor_unitario,
                :desconto_valor,
                :frete_valor,
                :total_item,
                NOW(),
                NOW()
            )');

        $stmt->execute([
            ':id_servico' => $servicoId,
            ':descricao' => $item['descricao'],
            ':quantidade' => $item['quantidade'],
            ':valor_unitario' => $item['valor_unitario'],
            ':desconto_valor' => $item['desconto_valor'],
            ':frete_valor' => $item['frete_valor'],
            ':total_item' => $item['total_item'],
        ]);
    }

    public function createServicoParcela(int $servicoId, array $parcela): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO servico_parcelas (
                id_servico,
                numero_parcela,
                vencimento,
                valor_parcela,
                tipo_pagamento,
                qtd_parcelas,
                total_parcelas,
                created_at,
                updated_at
            ) VALUES (
                :id_servico,
                :numero_parcela,
                :vencimento,
                :valor_parcela,
                :tipo_pagamento,
                :qtd_parcelas,
                :total_parcelas,
                NOW(),
                NOW()
            )');

        $stmt->execute([
            ':id_servico' => $servicoId,
            ':numero_parcela' => $parcela['numero_parcela'],
            ':vencimento' => $parcela['vencimento'],
            ':valor_parcela' => $parcela['valor'],
            ':tipo_pagamento' => $parcela['tipo_pagamento'],
            ':qtd_parcelas' => $parcela['qtd_parcelas'],
            ':total_parcelas' => $parcela['total_parcelas'],
        ]);
    }

    public function logCreate(int $servicoId, array $servico, array $produtos, array $microservicos, array $parcelas): void
    {
        $this->auditLogger->logCreate('servico', 'servicos', $servicoId, [
            'servico' => $servico,
            'produtos' => $produtos,
            'microservicos' => $microservicos,
            'parcelas' => $parcelas,
        ]);
    }
}
