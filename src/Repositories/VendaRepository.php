<?php

namespace App\Repositories;

use App\Support\AuditLogger;
use PDO;
use Throwable;

class VendaRepository
{
    private $pdo;
    private $auditLogger;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->auditLogger = new AuditLogger($pdo);
    }

    public function create(array $venda, array $itens, array $parcelas)
    {
        $startedTransaction = !$this->pdo->inTransaction();

        try {
            if ($startedTransaction) {
                $this->pdo->beginTransaction();
            }

            $insertVenda = $this->pdo->prepare('INSERT INTO vendas (
                    id_cliente,
                    data_venda,
                    subtotal,
                    desconto_total,
                    frete_total,
                    total_geral,
                    condicao_pagamento,
                    created_at,
                    updated_at
                ) VALUES (
                    :id_cliente,
                    CURDATE(),
                    :subtotal,
                    :desconto_total,
                    :frete_total,
                    :total_geral,
                    :condicao_pagamento,
                    NOW(),
                    NOW()
                )');

            $insertVenda->execute([
                ':id_cliente' => $venda['cliente_id'],
                ':subtotal' => $venda['subtotal'],
                ':desconto_total' => $venda['desconto_total'],
                ':frete_total' => $venda['frete_total'],
                ':total_geral' => $venda['total_geral'],
                ':condicao_pagamento' => $venda['condicao_pagamento'],
            ]);

            $vendaId = (int) $this->pdo->lastInsertId();

            $insertItem = $this->pdo->prepare('INSERT INTO venda_itens (
                    id_venda,
                    id_produto,
                    quantidade,
                    valor_unitario,
                    desconto_valor,
                    frete_valor,
                    total_item,
                    created_at,
                    updated_at
                ) VALUES (
                    :id_venda,
                    :id_produto,
                    :quantidade,
                    :valor_unitario,
                    :desconto_valor,
                    :frete_valor,
                    :total_item,
                    NOW(),
                    NOW()
                )');

            foreach ($itens as $item) {
                $insertItem->execute([
                    ':id_venda' => $vendaId,
                    ':id_produto' => $item['produto_id'],
                    ':quantidade' => $item['quantidade'],
                    ':valor_unitario' => $item['valor_unitario'],
                    ':desconto_valor' => $item['desconto_valor'],
                    ':frete_valor' => $item['frete_valor'],
                    ':total_item' => $item['total_item'],
                ]);
            }

            $insertParcela = $this->pdo->prepare('INSERT INTO venda_parcelas (
                    id_venda,
                    numero_parcela,
                    vencimento,
                    valor_parcela,
                    tipo_pagamento,
                    qtd_parcelas,
                    total_parcelas,
                    created_at,
                    updated_at
                ) VALUES (
                    :id_venda,
                    :numero_parcela,
                    :vencimento,
                    :valor_parcela,
                    :tipo_pagamento,
                    :qtd_parcelas,
                    :total_parcelas,
                    NOW(),
                    NOW()
                )');

            foreach ($parcelas as $parcela) {
                $insertParcela->execute([
                    ':id_venda' => $vendaId,
                    ':numero_parcela' => $parcela['numero_parcela'],
                    ':vencimento' => $parcela['vencimento'],
                    ':valor_parcela' => $parcela['valor'],
                    ':tipo_pagamento' => $parcela['tipo_pagamento'],
                    ':qtd_parcelas' => $parcela['qtd_parcelas'],
                    ':total_parcelas' => $parcela['total_parcelas'],
                ]);
            }

            $this->auditLogger->logCreate('venda', 'vendas', $vendaId, [
                'id_cliente' => $venda['cliente_id'],
                'data_venda' => date('Y-m-d'),
                'subtotal' => $venda['subtotal'],
                'desconto_total' => $venda['desconto_total'],
                'frete_total' => $venda['frete_total'],
                'total_geral' => $venda['total_geral'],
                'condicao_pagamento' => $venda['condicao_pagamento'],
                'itens' => $itens,
                'parcelas' => $parcelas,
            ]);

            if ($startedTransaction) {
                $this->pdo->commit();
            }

            return $vendaId;
        } catch (Throwable $e) {
            if ($startedTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function listWithCliente()
    {
        $stmt = $this->pdo->query('SELECT
                v.id_venda,
                v.data_venda,
                c.nome_cliente AS cliente,
                v.condicao_pagamento,
                v.subtotal,
                v.desconto_total,
                v.frete_total,
                v.total_geral
            FROM vendas v
            LEFT JOIN clientes c ON c.id_cliente = v.id_cliente
            ORDER BY v.data_venda DESC, v.id_venda DESC');

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
