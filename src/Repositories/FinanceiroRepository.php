<?php

namespace App\Repositories;

use App\Support\AuditLogger;
use DateTimeImmutable;
use PDO;

class FinanceiroRepository
{
    private PDO $pdo;
    private AuditLogger $auditLogger;

    /** @var array<string, array<string, string>> */
    private array $sources = [
        'vendas' => [
            'table' => 'venda_parcelas',
            'id_column' => 'id_venda_parcela',
            'document_table' => 'vendas',
            'parcela_fk_column' => 'id_venda',
            'document_id_column' => 'id_venda',
            'document_date_column' => 'data_venda',
            'party_join' => 'LEFT JOIN clientes c ON c.id_cliente = d.id_cliente',
            'party_expr' => 'COALESCE(NULLIF(TRIM(c.nome_cliente), ""), CONCAT("Cliente #", LPAD(d.id_cliente, 6, "0")))',
            'type_column' => 'tipo_pagamento',
            'document_prefix' => 'Venda',
            'status_prefix' => 'receita',
        ],
        'servicos' => [
            'table' => 'servicos_parcelas',
            'id_column' => 'id_parcela',
            'document_table' => 'servicos_pedidos',
            'parcela_fk_column' => 'servico_id',
            'document_id_column' => 'id_servico',
            'document_date_column' => 'data_servico',
            'party_join' => 'LEFT JOIN clientes c ON c.id_cliente = d.cliente_id',
            'party_expr' => 'COALESCE(NULLIF(TRIM(c.nome_cliente), ""), CONCAT("Cliente servico #", LPAD(d.id_servico, 6, "0")))',
            'type_column' => 'tipo_pagamento',
            'document_prefix' => 'Servico',
            'status_prefix' => 'receita',
        ],
        'compras' => [
            'table' => 'compra_parcelas',
            'id_column' => 'id_compra_parcela',
            'document_table' => 'compra_entradas',
            'parcela_fk_column' => 'id_compra_entrada',
            'document_id_column' => 'id_compra_entrada',
            'document_date_column' => 'data_entrada',
            'party_join' => 'LEFT JOIN fornecedores f ON f.id_fornecedor = d.id_fornecedor',
            'party_expr' => 'COALESCE(NULLIF(TRIM(f.nome_fantasia), ""), NULLIF(TRIM(f.razao_social), ""), CONCAT("Fornecedor #", LPAD(d.id_fornecedor, 6, "0")))',
            'type_column' => 'tipo_pagamento_previsto',
            'document_prefix' => 'Compra',
            'status_prefix' => 'despesa',
        ],
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->auditLogger = new AuditLogger($pdo);
        $this->ensurePaymentColumns();
    }

    public function getHistoricalSeriesBySource(): array
    {
        $series = [];

        foreach ($this->sources as $sourceKey => $config) {
            $sql = sprintf(
                'SELECT YEAR(vencimento) AS ano, MONTH(vencimento) AS mes, COALESCE(SUM(valor_parcela), 0) AS total
                 FROM %s
                 GROUP BY YEAR(vencimento), MONTH(vencimento)
                 ORDER BY ano ASC, mes ASC',
                $config['table']
            );

            $stmt = $this->pdo->query($sql);
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

            $mapped = [];
            foreach ($rows as $row) {
                $year = (int) ($row['ano'] ?? 0);
                $month = (int) ($row['mes'] ?? 0);
                if ($year <= 0 || $month < 1 || $month > 12) {
                    continue;
                }

                if (!isset($mapped[$year])) {
                    $mapped[$year] = [];
                }

                $mapped[$year][$month] = (float) ($row['total'] ?? 0);
            }

            $series[$sourceKey] = $mapped;
        }

        return $series;
    }

    public function getCurrentMonthCashKpis(?DateTimeImmutable $today = null): array
    {
        $today = $today ?? new DateTimeImmutable('today');
        $month = (int) $today->format('n');
        $year = (int) $today->format('Y');
        $todaySql = $today->format('Y-m-d');

        $receitasPrevistas = $this->sumByMonthAndYear('vendas', $month, $year)
            + $this->sumByMonthAndYear('servicos', $month, $year);
        $receitasRecebidas = $this->sumByMonthAndYear('vendas', $month, $year, $todaySql)
            + $this->sumByMonthAndYear('servicos', $month, $year, $todaySql);
        $despesasPrevistas = $this->sumByMonthAndYear('compras', $month, $year);
        $despesasPagas = $this->sumByMonthAndYear('compras', $month, $year, $todaySql);

        return [
            'month' => $month,
            'year' => $year,
            'receitas_previstas' => $receitasPrevistas,
            'receitas_recebidas' => $receitasRecebidas,
            'receitas_a_receber' => max($receitasPrevistas - $receitasRecebidas, 0),
            'despesas_previstas' => $despesasPrevistas,
            'despesas_pagas' => $despesasPagas,
            'despesas_a_pagar' => max($despesasPrevistas - $despesasPagas, 0),
            'saldo_projetado' => $receitasPrevistas - $despesasPrevistas,
        ];
    }

    public function getAvailableFluxoYears(): array
    {
        $years = [];

        foreach ($this->sources as $config) {
            $sql = sprintf('SELECT DISTINCT YEAR(vencimento) AS ano FROM %s WHERE vencimento IS NOT NULL ORDER BY ano DESC', $config['table']);
            $stmt = $this->pdo->query($sql);
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

            foreach ($rows as $row) {
                $year = (int) ($row['ano'] ?? 0);
                if ($year > 0) {
                    $years[$year] = $year;
                }
            }
        }

        rsort($years, SORT_NUMERIC);

        return array_values($years);
    }

    public function getFluxoCaixaRows(array $filters): array
    {
        $month = max(1, min(12, (int) ($filters['mes'] ?? date('n'))));
        $year = max(2000, (int) ($filters['ano'] ?? date('Y')));
        $sourceFilter = (string) ($filters['origem'] ?? 'todas');
        $statusFilter = (string) ($filters['status'] ?? 'todas');

        $queries = [];
        $params = [
            ':mes' => $month,
            ':ano' => $year,
        ];

        foreach ($this->sources as $sourceKey => $config) {
            if ($sourceFilter !== 'todas' && $sourceFilter !== $sourceKey) {
                continue;
            }

            $queries[] = sprintf(
                "SELECT
                    CAST('%s' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS origem,
                    CAST('%s' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci AS categoria,
                    p.%s AS parcela_id,
                    d.%s AS documento_id,
                    p.numero_parcela,
                    p.qtd_parcelas,
                    p.vencimento,
                    p.valor_parcela,
                    p.data_pagamento,
                    CONVERT(p.%s USING utf8mb4) COLLATE utf8mb4_unicode_ci AS tipo_pagamento,
                    CONVERT(%s USING utf8mb4) COLLATE utf8mb4_unicode_ci AS contraparte,
                    CONVERT(CONCAT('%s #', LPAD(d.%s, 6, '0')) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS documento_codigo,
                    d.%s AS data_documento
                FROM %s p
                INNER JOIN %s d ON d.%s = p.%s
                %s
                WHERE MONTH(p.vencimento) = :mes
                  AND YEAR(p.vencimento) = :ano",
                $sourceKey,
                $config['status_prefix'],
                $config['id_column'],
                $config['document_id_column'],
                $config['type_column'],
                $config['party_expr'],
                $config['document_prefix'],
                $config['document_id_column'],
                $config['document_date_column'],
                $config['table'],
                $config['document_table'],
                $config['document_id_column'],
                $config['parcela_fk_column'],
                $config['party_join']
            );
        }

        if (empty($queries)) {
            return [];
        }

        $sql = 'SELECT * FROM (' . implode(' UNION ALL ', $queries) . ') fluxo WHERE 1 = 1';

        if ($statusFilter === 'pagas') {
            $sql .= ' AND fluxo.data_pagamento IS NOT NULL';
        } elseif ($statusFilter === 'abertas') {
            $sql .= ' AND fluxo.data_pagamento IS NULL';
        }

        $sql .= ' ORDER BY fluxo.vencimento ASC, fluxo.origem ASC, fluxo.documento_id ASC, fluxo.numero_parcela ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function summarizeFluxoCaixa(array $rows): array
    {
        $summary = [
            'receitas_previstas' => 0.0,
            'receitas_recebidas' => 0.0,
            'despesas_previstas' => 0.0,
            'despesas_pagas' => 0.0,
        ];

        foreach ($rows as $row) {
            $amount = (float) ($row['valor_parcela'] ?? 0);
            $isPaid = !empty($row['data_pagamento']);
            $category = (string) ($row['categoria'] ?? '');

            if ($category === 'receita') {
                $summary['receitas_previstas'] += $amount;
                if ($isPaid) {
                    $summary['receitas_recebidas'] += $amount;
                }
                continue;
            }

            if ($category === 'despesa') {
                $summary['despesas_previstas'] += $amount;
                if ($isPaid) {
                    $summary['despesas_pagas'] += $amount;
                }
            }
        }

        $summary['receitas_a_receber'] = max($summary['receitas_previstas'] - $summary['receitas_recebidas'], 0);
        $summary['despesas_a_pagar'] = max($summary['despesas_previstas'] - $summary['despesas_pagas'], 0);
        $summary['saldo_projetado'] = $summary['receitas_previstas'] - $summary['despesas_previstas'];

        return $summary;
    }

    public function updateParcelaPayment(string $sourceKey, int $parcelaId, ?string $paymentDate): void
    {
        if (!isset($this->sources[$sourceKey])) {
            throw new \InvalidArgumentException('Origem financeira invalida.');
        }

        $config = $this->sources[$sourceKey];
        $paymentDate = $paymentDate !== null && trim($paymentDate) !== '' ? trim($paymentDate) : null;

        if ($paymentDate !== null) {
            $date = DateTimeImmutable::createFromFormat('Y-m-d', $paymentDate);
            if (!$date || $date->format('Y-m-d') !== $paymentDate) {
                throw new \InvalidArgumentException('Data de pagamento invalida.');
            }
        }

        $before = $this->fetchParcelaSnapshot($sourceKey, $parcelaId);
        if ($before === null) {
            throw new \RuntimeException('Parcela nao encontrada.');
        }

        $sql = sprintf(
            'UPDATE %s SET data_pagamento = :data_pagamento WHERE %s = :id',
            $config['table'],
            $config['id_column']
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':data_pagamento', $paymentDate, $paymentDate === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':id', $parcelaId, PDO::PARAM_INT);
        $stmt->execute();

        $after = $this->fetchParcelaSnapshot($sourceKey, $parcelaId);
        if ($after !== null) {
            $this->auditLogger->logUpdate('parcela_financeira', $config['table'], $parcelaId, $before, $after);
        }
    }

    public function getSourceLabels(): array
    {
        return [
            'vendas' => 'Vendas',
            'servicos' => 'Servicos',
            'compras' => 'Compras',
        ];
    }

    private function sumByMonthAndYear(string $sourceKey, int $month, int $year, ?string $paidUntil = null): float
    {
        $config = $this->sources[$sourceKey];
        $sql = sprintf(
            'SELECT COALESCE(SUM(valor_parcela), 0) AS total
             FROM %s
             WHERE MONTH(vencimento) = :mes
               AND YEAR(vencimento) = :ano',
            $config['table']
        );

        if ($paidUntil !== null) {
            $sql .= ' AND data_pagamento IS NOT NULL AND data_pagamento <= :data_pagamento';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':mes', $month, PDO::PARAM_INT);
        $stmt->bindValue(':ano', $year, PDO::PARAM_INT);
        if ($paidUntil !== null) {
            $stmt->bindValue(':data_pagamento', $paidUntil, PDO::PARAM_STR);
        }
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return (float) ($row['total'] ?? 0);
    }

    private function ensurePaymentColumns(): void
    {
        $definitions = [
            'venda_parcelas' => 'ALTER TABLE venda_parcelas ADD COLUMN data_pagamento DATE NULL AFTER total_parcelas',
            'servicos_parcelas' => 'ALTER TABLE servicos_parcelas ADD COLUMN data_pagamento DATE NULL AFTER total_parcelas',
            'compra_parcelas' => 'ALTER TABLE compra_parcelas ADD COLUMN data_pagamento DATE NULL AFTER total_parcelas',
        ];

        foreach ($definitions as $table => $sql) {
            if ($this->tableExists($table) && !$this->columnExists($table, 'data_pagamento')) {
                $this->pdo->exec($sql);
            }
        }
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name = :table_name'
        );
        $stmt->execute([':table_name' => $table]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return (int) ($row['total'] ?? 0) > 0;
    }

    private function columnExists(string $table, string $column): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = :table_name
               AND column_name = :column_name'
        );
        $stmt->execute([
            ':table_name' => $table,
            ':column_name' => $column,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return (int) ($row['total'] ?? 0) > 0;
    }

    private function fetchParcelaSnapshot(string $sourceKey, int $parcelaId): ?array
    {
        if (!isset($this->sources[$sourceKey])) {
            return null;
        }

        $config = $this->sources[$sourceKey];
        $sql = sprintf(
            'SELECT * FROM %s WHERE %s = :id LIMIT 1',
            $config['table'],
            $config['id_column']
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $parcelaId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}
