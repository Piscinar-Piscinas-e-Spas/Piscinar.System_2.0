<?php

namespace App\Services;

use PDO;

class DocumentStockService
{
    private const ORIGIN_COLUMNS = [
        'loja' => 'qtdLoja',
        'estoque_auxiliar' => 'qtdEstoque',
    ];

    private const ORIGIN_LABELS = [
        'loja' => 'Loja',
        'estoque_auxiliar' => 'Estoque Auxiliar',
    ];

    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->ensureSchema();
    }

    public function applyDocumentStock(string $tipoOrigem, int $origemId, array $itens, int $usuarioId = 0): void
    {
        $stmtProduto = $this->pdo->prepare(
            'SELECT
                id,
                nome,
                COALESCE(qtdLoja, 0) AS qtdLoja,
                COALESCE(qtdEstoque, 0) AS qtdEstoque,
                COALESCE(custo, 0) AS custo,
                COALESCE(controle_estoque, 0) AS controle_estoque
             FROM produtos
             WHERE id = :id
             LIMIT 1
             FOR UPDATE'
        );

        $stmtAtualiza = $this->pdo->prepare(
            'UPDATE produtos
             SET qtdLoja = :qtdLoja,
                 qtdEstoque = :qtdEstoque
             WHERE id = :id'
        );

        $stmtMov = $this->pdo->prepare(
            'INSERT INTO estoque_movimentacoes (
                id_produto,
                tipo_origem,
                id_origem,
                id_origem_item,
                destino_estoque,
                quantidade,
                custo_unitario,
                saldo_anterior,
                saldo_posterior,
                observacao,
                id_usuario,
                created_at
            ) VALUES (
                :id_produto,
                :tipo_origem,
                :id_origem,
                :id_origem_item,
                :destino_estoque,
                :quantidade,
                :custo_unitario,
                :saldo_anterior,
                :saldo_posterior,
                :observacao,
                :id_usuario,
                NOW()
            )'
        );

        foreach ($itens as $item) {
            $produtoId = (int) ($item['produto_id'] ?? $item['id_produto'] ?? 0);
            $quantidade = round((float) ($item['quantidade'] ?? 0), 3);
            if ($produtoId <= 0 || $quantidade <= 0) {
                continue;
            }

            $origemEstoque = $this->normalizeOrigin($item['origem_estoque'] ?? null);
            $colunaSaldo = self::ORIGIN_COLUMNS[$origemEstoque];

            $stmtProduto->execute([':id' => $produtoId]);
            $produto = $stmtProduto->fetch(PDO::FETCH_ASSOC);
            if (!$produto) {
                throw new DocumentStockException('Produto invalido na operacao de estoque: ' . $produtoId . '.');
            }

            if ((int) ($produto['controle_estoque'] ?? 0) !== 1) {
                continue;
            }

            $saldoAnterior = (float) ($produto[$colunaSaldo] ?? 0);
            if ($saldoAnterior + 0.0001 < $quantidade) {
                throw new DocumentStockException(sprintf(
                    'Saldo insuficiente para o produto "%s" no estoque %s.',
                    (string) ($produto['nome'] ?? ('#' . $produtoId)),
                    self::ORIGIN_LABELS[$origemEstoque]
                ));
            }

            $novoSaldo = round($saldoAnterior - $quantidade, 3);
            $novoSaldoLoja = (float) ($produto['qtdLoja'] ?? 0);
            $novoSaldoEstoque = (float) ($produto['qtdEstoque'] ?? 0);
            if ($origemEstoque === 'loja') {
                $novoSaldoLoja = $novoSaldo;
            } else {
                $novoSaldoEstoque = $novoSaldo;
            }

            $stmtAtualiza->execute([
                ':id' => $produtoId,
                ':qtdLoja' => $novoSaldoLoja,
                ':qtdEstoque' => $novoSaldoEstoque,
            ]);

            $stmtMov->execute([
                ':id_produto' => $produtoId,
                ':tipo_origem' => $tipoOrigem,
                ':id_origem' => $origemId,
                ':id_origem_item' => $this->extractOriginItemId($item),
                ':destino_estoque' => $origemEstoque,
                ':quantidade' => $quantidade,
                ':custo_unitario' => (float) ($produto['custo'] ?? 0),
                ':saldo_anterior' => $saldoAnterior,
                ':saldo_posterior' => $novoSaldo,
                ':observacao' => $this->buildObservation($tipoOrigem, $origemId),
                ':id_usuario' => $usuarioId > 0 ? $usuarioId : null,
            ]);
        }
    }

    public function revertDocumentStock(string $tipoOrigem, int $origemId): void
    {
        $stmtMovimentos = $this->pdo->prepare(
            'SELECT
                id_estoque_movimentacao,
                id_produto,
                destino_estoque,
                quantidade
             FROM estoque_movimentacoes
             WHERE tipo_origem = :tipo_origem
               AND id_origem = :id_origem
             ORDER BY id_estoque_movimentacao DESC'
        );
        $stmtMovimentos->execute([
            ':tipo_origem' => $tipoOrigem,
            ':id_origem' => $origemId,
        ]);
        $movimentos = $stmtMovimentos->fetchAll(PDO::FETCH_ASSOC);

        if (!$movimentos) {
            return;
        }

        $stmtProduto = $this->pdo->prepare(
            'SELECT
                id,
                nome,
                COALESCE(qtdLoja, 0) AS qtdLoja,
                COALESCE(qtdEstoque, 0) AS qtdEstoque
             FROM produtos
             WHERE id = :id
             LIMIT 1
             FOR UPDATE'
        );

        $stmtAtualiza = $this->pdo->prepare(
            'UPDATE produtos
             SET qtdLoja = :qtdLoja,
                 qtdEstoque = :qtdEstoque
             WHERE id = :id'
        );

        foreach ($movimentos as $movimento) {
            $produtoId = (int) ($movimento['id_produto'] ?? 0);
            $quantidade = round((float) ($movimento['quantidade'] ?? 0), 3);
            if ($produtoId <= 0 || $quantidade <= 0) {
                continue;
            }

            $origemEstoque = $this->normalizeOrigin($movimento['destino_estoque'] ?? null);
            $stmtProduto->execute([':id' => $produtoId]);
            $produto = $stmtProduto->fetch(PDO::FETCH_ASSOC);
            if (!$produto) {
                throw new DocumentStockException('Produto nao encontrado durante a reversao do estoque.');
            }

            $novoSaldoLoja = (float) ($produto['qtdLoja'] ?? 0);
            $novoSaldoEstoque = (float) ($produto['qtdEstoque'] ?? 0);
            if ($origemEstoque === 'loja') {
                $novoSaldoLoja = round($novoSaldoLoja + $quantidade, 3);
            } else {
                $novoSaldoEstoque = round($novoSaldoEstoque + $quantidade, 3);
            }

            $stmtAtualiza->execute([
                ':id' => $produtoId,
                ':qtdLoja' => $novoSaldoLoja,
                ':qtdEstoque' => $novoSaldoEstoque,
            ]);
        }

        $this->deleteDocumentMovements($tipoOrigem, $origemId);
    }

    public function deleteDocumentMovements(string $tipoOrigem, int $origemId): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM estoque_movimentacoes
             WHERE tipo_origem = :tipo_origem
               AND id_origem = :id_origem'
        );
        $stmt->execute([
            ':tipo_origem' => $tipoOrigem,
            ':id_origem' => $origemId,
        ]);
    }

    private function extractOriginItemId(array $item): ?int
    {
        foreach (['id_origem_item', 'id_venda_item', 'id_item'] as $key) {
            $id = (int) ($item[$key] ?? 0);
            if ($id > 0) {
                return $id;
            }
        }

        return null;
    }

    private function normalizeOrigin($origem): string
    {
        $valor = trim((string) $origem);
        if (!isset(self::ORIGIN_COLUMNS[$valor])) {
            throw new DocumentStockException('Origem de estoque invalida para o item.');
        }

        return $valor;
    }

    private function buildObservation(string $tipoOrigem, int $origemId): string
    {
        if ($tipoOrigem === 'venda') {
            return 'Baixa via venda #' . $origemId;
        }

        if ($tipoOrigem === 'servico') {
            return 'Baixa via servico #' . $origemId;
        }

        return 'Baixa de estoque via documento #' . $origemId;
    }

    private function ensureSchema(): void
    {
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS estoque_movimentacoes (
                id_estoque_movimentacao INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                id_produto INT UNSIGNED NOT NULL,
                tipo_origem VARCHAR(40) NOT NULL,
                id_origem INT UNSIGNED NULL,
                id_origem_item INT UNSIGNED NULL,
                destino_estoque VARCHAR(40) NOT NULL,
                quantidade DECIMAL(12,3) NOT NULL DEFAULT 0,
                custo_unitario DECIMAL(12,4) NOT NULL DEFAULT 0,
                saldo_anterior DECIMAL(12,3) NOT NULL DEFAULT 0,
                saldo_posterior DECIMAL(12,3) NOT NULL DEFAULT 0,
                observacao VARCHAR(255) NULL,
                id_usuario INT UNSIGNED NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_mov_produto (id_produto),
                INDEX idx_mov_origem (tipo_origem, id_origem),
                INDEX idx_mov_data (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->ensureColumnExists('estoque_movimentacoes', 'tipo_origem', "ALTER TABLE estoque_movimentacoes ADD COLUMN tipo_origem VARCHAR(40) NOT NULL AFTER id_produto");
        $this->ensureColumnExists('estoque_movimentacoes', 'id_origem', "ALTER TABLE estoque_movimentacoes ADD COLUMN id_origem INT UNSIGNED NULL AFTER tipo_origem");
        $this->ensureColumnExists('estoque_movimentacoes', 'id_origem_item', "ALTER TABLE estoque_movimentacoes ADD COLUMN id_origem_item INT UNSIGNED NULL AFTER id_origem");
        $this->ensureColumnExists('estoque_movimentacoes', 'destino_estoque', "ALTER TABLE estoque_movimentacoes ADD COLUMN destino_estoque VARCHAR(40) NOT NULL DEFAULT 'loja' AFTER id_origem_item");
        $this->ensureColumnExists('estoque_movimentacoes', 'custo_unitario', "ALTER TABLE estoque_movimentacoes ADD COLUMN custo_unitario DECIMAL(12,4) NOT NULL DEFAULT 0 AFTER quantidade");
        $this->ensureColumnExists('estoque_movimentacoes', 'saldo_anterior', "ALTER TABLE estoque_movimentacoes ADD COLUMN saldo_anterior DECIMAL(12,3) NOT NULL DEFAULT 0 AFTER custo_unitario");
        $this->ensureColumnExists('estoque_movimentacoes', 'saldo_posterior', "ALTER TABLE estoque_movimentacoes ADD COLUMN saldo_posterior DECIMAL(12,3) NOT NULL DEFAULT 0 AFTER saldo_anterior");
        $this->ensureColumnExists('estoque_movimentacoes', 'observacao', "ALTER TABLE estoque_movimentacoes ADD COLUMN observacao VARCHAR(255) NULL AFTER saldo_posterior");
        $this->ensureColumnExists('estoque_movimentacoes', 'id_usuario', "ALTER TABLE estoque_movimentacoes ADD COLUMN id_usuario INT UNSIGNED NULL AFTER observacao");
        $this->ensureColumnExists('estoque_movimentacoes', 'created_at', "ALTER TABLE estoque_movimentacoes ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER id_usuario");
    }

    private function ensureColumnExists(string $table, string $column, string $alterSql): void
    {
        $stmt = $this->pdo->query(sprintf("SHOW COLUMNS FROM %s LIKE '%s'", $table, $column));
        $exists = $stmt && $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$exists) {
            $this->pdo->exec($alterSql);
        }
    }
}
