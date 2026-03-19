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

    public function listWithCliente(array $filters = [])
    {
        $sql = 'SELECT
                v.id_venda,
                v.data_venda,
                c.nome_cliente AS cliente,
                v.condicao_pagamento,
                v.subtotal,
                v.desconto_total,
                v.frete_total,
                v.total_geral
            FROM vendas v
            LEFT JOIN clientes c ON c.id_cliente = v.id_cliente';

        [$whereClause, $params] = $this->buildFilterClause($filters);

        $sql .= $whereClause;
        $sql .= ' ORDER BY v.data_venda DESC, v.id_venda DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findCompleteById(int $vendaId): ?array
    {
        $stmtVenda = $this->pdo->prepare('SELECT
                v.id_venda,
                v.data_venda,
                v.subtotal,
                v.desconto_total,
                v.frete_total,
                v.total_geral,
                v.condicao_pagamento,
                c.id_cliente,
                c.nome_cliente,
                c.telefone_contato,
                c.cpf_cnpj,
                c.email_contato,
                c.endereco
            FROM vendas v
            LEFT JOIN clientes c ON c.id_cliente = v.id_cliente
            WHERE v.id_venda = :id_venda
            LIMIT 1');
        $stmtVenda->execute([':id_venda' => $vendaId]);
        $venda = $stmtVenda->fetch(PDO::FETCH_ASSOC);

        if (!$venda) {
            return null;
        }

        $stmtItens = $this->pdo->prepare('SELECT
                vi.id_venda_item,
                vi.id_produto,
                p.nome AS produto_nome,
                vi.quantidade,
                vi.valor_unitario,
                vi.desconto_valor,
                vi.frete_valor,
                vi.total_item
            FROM venda_itens vi
            LEFT JOIN produtos p ON p.id = vi.id_produto
            WHERE vi.id_venda = :id_venda
            ORDER BY vi.id_venda_item ASC');
        $stmtItens->execute([':id_venda' => $vendaId]);
        $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

        $stmtParcelas = $this->pdo->prepare('SELECT
                id_venda_parcela,
                numero_parcela,
                vencimento,
                valor_parcela,
                tipo_pagamento,
                qtd_parcelas,
                total_parcelas
            FROM venda_parcelas
            WHERE id_venda = :id_venda
            ORDER BY numero_parcela ASC, id_venda_parcela ASC');
        $stmtParcelas->execute([':id_venda' => $vendaId]);
        $parcelas = $stmtParcelas->fetchAll(PDO::FETCH_ASSOC);

        return [
            'venda' => $venda,
            'itens' => $itens,
            'parcelas' => $parcelas,
        ];
    }


    public function getSerieFaturamento(array $filters = [], string $agrupamento = 'dia'): array
    {
        $agrupamento = $agrupamento === 'mes' ? 'mes' : 'dia';

        $periodoExpr = $agrupamento === 'mes'
            ? 'DATE_FORMAT(v.data_venda, "%Y-%m")'
            : 'DATE(v.data_venda)';

        $sql = "SELECT
                {$periodoExpr} AS periodo,
                COALESCE(SUM(v.total_geral), 0) AS faturamento,
                COUNT(v.id_venda) AS quantidade_vendas
            FROM vendas v
            LEFT JOIN clientes c ON c.id_cliente = v.id_cliente";

        [$whereClause, $params] = $this->buildFilterClause($filters);

        $sql .= $whereClause;
        $sql .= " GROUP BY periodo ORDER BY periodo ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getResumoKpis(array $filters = []): array
    {
        $sql = 'SELECT
                COUNT(v.id_venda) AS total_vendas,
                COALESCE(SUM(v.total_geral), 0) AS faturamento_bruto,
                COALESCE(SUM(CASE WHEN v.condicao_pagamento = "vista" THEN v.total_geral ELSE 0 END), 0) AS total_vista,
                COALESCE(SUM(CASE WHEN v.condicao_pagamento = "parcelado" THEN v.total_geral ELSE 0 END), 0) AS total_parcelado
            FROM vendas v
            LEFT JOIN clientes c ON c.id_cliente = v.id_cliente';

        [$whereClause, $params] = $this->buildFilterClause($filters);

        $sql .= $whereClause;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $totalVendas = (int) ($result['total_vendas'] ?? 0);
        $faturamentoBruto = (float) ($result['faturamento_bruto'] ?? 0);
        $totalVista = (float) ($result['total_vista'] ?? 0);
        $totalParcelado = (float) ($result['total_parcelado'] ?? 0);

        return [
            'total_vendas' => $totalVendas,
            'faturamento_bruto' => $faturamentoBruto,
            'ticket_medio' => $totalVendas > 0 ? $faturamentoBruto / $totalVendas : 0.0,
            'total_vista' => $totalVista,
            'total_parcelado' => $totalParcelado,
        ];
    }

    private function buildFilterClause(array $filters): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['data_inicial'])) {
            $conditions[] = 'v.data_venda >= :data_inicial';
            $params[':data_inicial'] = $filters['data_inicial'];
        }

        if (!empty($filters['data_final'])) {
            $conditions[] = 'v.data_venda <= :data_final';
            $params[':data_final'] = $filters['data_final'];
        }

        if (!empty($filters['nome_cliente'])) {
            $conditions[] = 'c.nome_cliente LIKE :nome_cliente';
            $params[':nome_cliente'] = '%' . $filters['nome_cliente'] . '%';
        }

        if (!empty($filters['condicao_pagamento']) && in_array($filters['condicao_pagamento'], ['vista', 'parcelado'], true)) {
            $conditions[] = 'v.condicao_pagamento = :condicao_pagamento';
            $params[':condicao_pagamento'] = $filters['condicao_pagamento'];
        }

        if (($filters['valor_minimo'] ?? '') !== '' && is_numeric((string) $filters['valor_minimo'])) {
            $conditions[] = 'v.total_geral >= :valor_minimo';
            $params[':valor_minimo'] = (float) $filters['valor_minimo'];
        }

        if (($filters['valor_maximo'] ?? '') !== '' && is_numeric((string) $filters['valor_maximo'])) {
            $conditions[] = 'v.total_geral <= :valor_maximo';
            $params[':valor_maximo'] = (float) $filters['valor_maximo'];
        }

        $whereClause = empty($conditions) ? '' : ' WHERE ' . implode(' AND ', $conditions);

        return [$whereClause, $params];
    }
}
