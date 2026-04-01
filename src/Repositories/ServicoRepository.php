<?php

namespace App\Repositories;

use App\Support\AuditLogger;
use PDO;

class ServicoRepository
{
    // Repository hibrido: ele ainda carrega alguns metodos do modelo antigo
    // (servicos + servico_*) e os metodos ativos de leitura no modelo atual
    // (servicos_pedidos + servicos_itens + servicos_parcelas).
    private $pdo;
    private $auditLogger;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->auditLogger = new AuditLogger($pdo);
    }

    public function createServico(array $servico): int
    {
        // Fluxo legado de persistencia singular. Vale manter comentado porque
        // quem entrar no projeto precisa perceber que existe esse caminho paralelo.
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
        // Persistencia legado dos produtos vinculados ao modelo singular.
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
        // Persistencia legado dos microservicos no modelo singular.
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
        // Persistencia legado das parcelas no modelo singular.
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
        // Mesmo no caminho legado, a auditoria tenta registrar o snapshot completo.
        $this->auditLogger->logCreate('servico', 'servicos', $servicoId, [
            'servico' => $servico,
            'produtos' => $produtos,
            'microservicos' => $microservicos,
            'parcelas' => $parcelas,
        ]);
    }

    public function listWithCliente(array $filters = []): array
    {
        // Listagem ativa do modulo de servicos baseada no schema atual.
        $sql = 'SELECT
                s.id_servico,
                s.data_servico,
                s.vendedor_id,
                s.vendedor_nome,
                s.condicao_pagamento,
                s.subtotal_produtos,
                s.subtotal_microservicos,
                s.desconto_total,
                s.frete_total,
                s.total_geral,
                c.nome_cliente
            FROM servicos_pedidos s
            LEFT JOIN clientes c ON c.id_cliente = s.cliente_id';

        [$whereClause, $params] = $this->buildFilterClause($filters);

        $sql .= $whereClause;
        $sql .= ' ORDER BY s.data_servico DESC, s.id_servico DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findCompleteById(int $servicoId): ?array
    {
        // Monta o servico completo para detalhe, dashboard e tela de edicao.
        $stmtServico = $this->pdo->prepare('SELECT
                s.id_servico,
                s.cliente_id,
                s.vendedor_id,
                s.vendedor_nome,
                s.data_servico,
                s.condicao_pagamento,
                s.subtotal_produtos,
                s.subtotal_microservicos,
                s.desconto_total,
                s.frete_total,
                s.total_geral,
                c.nome_cliente,
                c.telefone_contato,
                c.cpf_cnpj,
                c.email_contato,
                c.endereco
            FROM servicos_pedidos s
            LEFT JOIN clientes c ON c.id_cliente = s.cliente_id
            WHERE s.id_servico = :id_servico
            LIMIT 1');
        $stmtServico->execute([':id_servico' => $servicoId]);
        $servico = $stmtServico->fetch(PDO::FETCH_ASSOC);

        if (!$servico) {
            return null;
        }

        $stmtItens = $this->pdo->prepare('SELECT
                id_item,
                tipo_item,
                produto_id,
                descricao,
                quantidade,
                valor_unitario,
                desconto_valor,
                frete_valor,
                total_item
            FROM servicos_itens
            WHERE servico_id = :id_servico
            ORDER BY id_item ASC');
        $stmtItens->execute([':id_servico' => $servicoId]);
        $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

        $stmtParcelas = $this->pdo->prepare('SELECT
                id_parcela,
                numero_parcela,
                vencimento,
                tipo_pagamento,
                valor_parcela,
                qtd_parcelas,
                total_parcelas
            FROM servicos_parcelas
            WHERE servico_id = :id_servico
            ORDER BY numero_parcela ASC, id_parcela ASC');
        $stmtParcelas->execute([':id_servico' => $servicoId]);
        $parcelas = $stmtParcelas->fetchAll(PDO::FETCH_ASSOC);

        return [
            'servico' => $servico,
            'itens' => $itens,
            'parcelas' => $parcelas,
        ];
    }

    public function getResumoKpis(array $filters = []): array
    {
        // KPIs resumidos usados no dashboard/listagem de servicos.
        $sql = 'SELECT
                COUNT(s.id_servico) AS total_servicos,
                COALESCE(SUM(s.total_geral), 0) AS faturamento_bruto,
                COALESCE(SUM(CASE WHEN s.condicao_pagamento = "vista" THEN s.total_geral ELSE 0 END), 0) AS total_vista,
                COALESCE(SUM(CASE WHEN s.condicao_pagamento = "parcelado" THEN s.total_geral ELSE 0 END), 0) AS total_parcelado
            FROM servicos_pedidos s
            LEFT JOIN clientes c ON c.id_cliente = s.cliente_id';

        [$whereClause, $params] = $this->buildFilterClause($filters);
        $sql .= $whereClause;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $totalServicos = (int) ($result['total_servicos'] ?? 0);
        $faturamentoBruto = (float) ($result['faturamento_bruto'] ?? 0);
        $totalVista = (float) ($result['total_vista'] ?? 0);
        $totalParcelado = (float) ($result['total_parcelado'] ?? 0);

        return [
            'total_servicos' => $totalServicos,
            'faturamento_bruto' => $faturamentoBruto,
            'ticket_medio' => $totalServicos > 0 ? $faturamentoBruto / $totalServicos : 0.0,
            'total_vista' => $totalVista,
            'total_parcelado' => $totalParcelado,
        ];
    }

    private function buildFilterClause(array $filters): array
    {
        // Reaproveita o mesmo WHERE entre listagem e consultas de resumo.
        $conditions = [];
        $params = [];

        if (!empty($filters['data_inicial'])) {
            $conditions[] = 's.data_servico >= :data_inicial';
            $params[':data_inicial'] = $filters['data_inicial'];
        }

        if (!empty($filters['data_final'])) {
            $conditions[] = 's.data_servico <= :data_final';
            $params[':data_final'] = $filters['data_final'];
        }

        if (!empty($filters['nome_cliente'])) {
            $conditions[] = 'c.nome_cliente LIKE :nome_cliente';
            $params[':nome_cliente'] = '%' . $filters['nome_cliente'] . '%';
        }

        if (!empty($filters['condicao_pagamento']) && in_array($filters['condicao_pagamento'], ['vista', 'parcelado'], true)) {
            $conditions[] = 's.condicao_pagamento = :condicao_pagamento';
            $params[':condicao_pagamento'] = $filters['condicao_pagamento'];
        }

        if (($filters['valor_minimo'] ?? '') !== '' && is_numeric((string) $filters['valor_minimo'])) {
            $conditions[] = 's.total_geral >= :valor_minimo';
            $params[':valor_minimo'] = (float) $filters['valor_minimo'];
        }

        if (($filters['valor_maximo'] ?? '') !== '' && is_numeric((string) $filters['valor_maximo'])) {
            $conditions[] = 's.total_geral <= :valor_maximo';
            $params[':valor_maximo'] = (float) $filters['valor_maximo'];
        }

        $whereClause = empty($conditions) ? '' : ' WHERE ' . implode(' AND ', $conditions);

        return [$whereClause, $params];
    }
}
