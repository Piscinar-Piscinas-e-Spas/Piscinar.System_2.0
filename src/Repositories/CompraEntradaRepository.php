<?php

namespace App\Repositories;

use App\Support\AuditLogger;
use PDO;
use Throwable;

class CompraEntradaRepository
{
    private $pdo;
    private $auditLogger;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->ensureSchema();
        $this->auditLogger = new AuditLogger($pdo);
    }

    public function create(array $cabecalho, array $itens, array $parcelas, int $usuarioId): int
    {
        $startedTransaction = !$this->pdo->inTransaction();

        try {
            if ($startedTransaction) {
                $this->pdo->beginTransaction();
            }

            $stmtCabecalho = $this->pdo->prepare(
                'INSERT INTO compra_entradas (
                    id_fornecedor,
                    numero_nota,
                    data_emissao,
                    data_entrada,
                    condicao_pagamento,
                    subtotal_itens,
                    valor_frete,
                    valor_desconto,
                    valor_outras_despesas,
                    total_nota,
                    observacoes,
                    id_usuario_lancamento,
                    status,
                    created_at,
                    updated_at
                ) VALUES (
                    :id_fornecedor,
                    :numero_nota,
                    :data_emissao,
                    :data_entrada,
                    :condicao_pagamento,
                    :subtotal_itens,
                    :valor_frete,
                    :valor_desconto,
                    :valor_outras_despesas,
                    :total_nota,
                    :observacoes,
                    :id_usuario_lancamento,
                    :status,
                    NOW(),
                    NOW()
                )'
            );

            $stmtCabecalho->execute($cabecalho);
            $compraId = (int) $this->pdo->lastInsertId();

            $stmtItem = $this->pdo->prepare(
                'INSERT INTO compra_entrada_itens (
                    id_compra_entrada,
                    id_produto,
                    descricao_snapshot,
                    quantidade_total,
                    quantidade_loja,
                    quantidade_estoque_auxiliar,
                    custo_unitario,
                    subtotal_item,
                    created_at
                ) VALUES (
                    :id_compra_entrada,
                    :id_produto,
                    :descricao_snapshot,
                    :quantidade_total,
                    :quantidade_loja,
                    :quantidade_estoque_auxiliar,
                    :custo_unitario,
                    :subtotal_item,
                    NOW()
                )'
            );

            $stmtParcela = $this->pdo->prepare(
                'INSERT INTO compra_parcelas (
                    id_compra_entrada,
                    numero_parcela,
                    vencimento,
                    valor_parcela,
                    tipo_pagamento_previsto,
                    qtd_parcelas,
                    total_parcelas,
                    created_at
                ) VALUES (
                    :id_compra_entrada,
                    :numero_parcela,
                    :vencimento,
                    :valor_parcela,
                    :tipo_pagamento_previsto,
                    :qtd_parcelas,
                    :total_parcelas,
                    NOW()
                )'
            );

            $stmtProduto = $this->pdo->prepare(
                'SELECT
                    id,
                    nome,
                    COALESCE(qtdLoja, 0) AS qtdLoja,
                    COALESCE(qtdEstoque, 0) AS qtdEstoque,
                    COALESCE(custo, 0) AS custo
                FROM produtos
                WHERE id = :id
                LIMIT 1
                FOR UPDATE'
            );

            $stmtAtualizaProduto = $this->pdo->prepare(
                'UPDATE produtos
                SET
                    qtdLoja = :qtdLoja,
                    qtdEstoque = :qtdEstoque,
                    custo = :custo
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

            $itensPersistidos = [];

            foreach ($itens as $item) {
                $itemInsert = $item;
                $itemInsert['id_compra_entrada'] = $compraId;
                $stmtItem->execute($itemInsert);
                $compraItemId = (int) $this->pdo->lastInsertId();

                $stmtProduto->execute([':id' => $item['id_produto']]);
                $produtoAtual = $stmtProduto->fetch(PDO::FETCH_ASSOC);

                if (!$produtoAtual) {
                    throw new \RuntimeException('Produto nao encontrado para a entrada.');
                }

                $saldoLojaAnterior = (float) $produtoAtual['qtdLoja'];
                $saldoEstoqueAnterior = (float) $produtoAtual['qtdEstoque'];
                $saldoTotalAnterior = $saldoLojaAnterior + $saldoEstoqueAnterior;
                $custoAtual = (float) $produtoAtual['custo'];
                $quantidadeEntrada = (float) $item['quantidade_total'];
                $novoSaldoLoja = $saldoLojaAnterior + (float) $item['quantidade_loja'];
                $novoSaldoEstoque = $saldoEstoqueAnterior + (float) $item['quantidade_estoque_auxiliar'];
                $novoSaldoTotal = $saldoTotalAnterior + $quantidadeEntrada;

                $novoCusto = $quantidadeEntrada > 0
                    ? round(
                        (($saldoTotalAnterior * $custoAtual) + ($quantidadeEntrada * (float) $item['custo_unitario']))
                        / max($novoSaldoTotal, 1),
                        4
                    )
                    : $custoAtual;

                $stmtAtualizaProduto->execute([
                    ':id' => $item['id_produto'],
                    ':qtdLoja' => $novoSaldoLoja,
                    ':qtdEstoque' => $novoSaldoEstoque,
                    ':custo' => $novoCusto,
                ]);

                if ((float) $item['quantidade_loja'] > 0) {
                    $stmtMov->execute([
                        ':id_produto' => $item['id_produto'],
                        ':tipo_origem' => 'compra_entrada',
                        ':id_origem' => $compraId,
                        ':id_origem_item' => $compraItemId,
                        ':destino_estoque' => 'loja',
                        ':quantidade' => $item['quantidade_loja'],
                        ':custo_unitario' => $item['custo_unitario'],
                        ':saldo_anterior' => $saldoLojaAnterior,
                        ':saldo_posterior' => $novoSaldoLoja,
                        ':observacao' => 'Entrada via nota ' . $cabecalho['numero_nota'],
                        ':id_usuario' => $usuarioId,
                    ]);
                }

                if ((float) $item['quantidade_estoque_auxiliar'] > 0) {
                    $stmtMov->execute([
                        ':id_produto' => $item['id_produto'],
                        ':tipo_origem' => 'compra_entrada',
                        ':id_origem' => $compraId,
                        ':id_origem_item' => $compraItemId,
                        ':destino_estoque' => 'estoque_auxiliar',
                        ':quantidade' => $item['quantidade_estoque_auxiliar'],
                        ':custo_unitario' => $item['custo_unitario'],
                        ':saldo_anterior' => $saldoEstoqueAnterior,
                        ':saldo_posterior' => $novoSaldoEstoque,
                        ':observacao' => 'Entrada via nota ' . $cabecalho['numero_nota'],
                        ':id_usuario' => $usuarioId,
                    ]);
                }

                $itensPersistidos[] = $item + ['id_compra_entrada_item' => $compraItemId];
            }

            foreach ($parcelas as $parcela) {
                $stmtParcela->execute([
                    ':id_compra_entrada' => $compraId,
                    ':numero_parcela' => $parcela['numero_parcela'],
                    ':vencimento' => $parcela['vencimento'],
                    ':valor_parcela' => $parcela['valor'],
                    ':tipo_pagamento_previsto' => $parcela['tipo_pagamento'],
                    ':qtd_parcelas' => $parcela['qtd_parcelas'],
                    ':total_parcelas' => $parcela['total_parcelas'],
                ]);
            }

            $this->auditLogger->logCreate('compra_entrada', 'compra_entradas', $compraId, [
                'cabecalho' => $cabecalho,
                'itens' => $itensPersistidos,
                'parcelas' => $parcelas,
            ]);

            if ($startedTransaction) {
                $this->pdo->commit();
            }

            return $compraId;
        } catch (Throwable $e) {
            if ($startedTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    private function ensureSchema(): void
    {
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS compra_entradas (
                id_compra_entrada INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                id_fornecedor INT UNSIGNED NOT NULL,
                numero_nota VARCHAR(60) NOT NULL,
                data_emissao DATE NOT NULL,
                data_entrada DATE NOT NULL,
                condicao_pagamento VARCHAR(20) NOT NULL DEFAULT 'vista',
                subtotal_itens DECIMAL(12,2) NOT NULL DEFAULT 0,
                valor_frete DECIMAL(12,2) NOT NULL DEFAULT 0,
                valor_desconto DECIMAL(12,2) NOT NULL DEFAULT 0,
                valor_outras_despesas DECIMAL(12,2) NOT NULL DEFAULT 0,
                total_nota DECIMAL(12,2) NOT NULL DEFAULT 0,
                observacoes TEXT NULL,
                id_usuario_lancamento INT UNSIGNED NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'confirmada',
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_compra_fornecedor (id_fornecedor),
                INDEX idx_compra_data_entrada (data_entrada),
                INDEX idx_compra_nota (numero_nota)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS compra_entrada_itens (
                id_compra_entrada_item INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                id_compra_entrada INT UNSIGNED NOT NULL,
                id_produto INT UNSIGNED NOT NULL,
                descricao_snapshot VARCHAR(255) NOT NULL,
                quantidade_total DECIMAL(12,3) NOT NULL DEFAULT 0,
                quantidade_loja DECIMAL(12,3) NOT NULL DEFAULT 0,
                quantidade_estoque_auxiliar DECIMAL(12,3) NOT NULL DEFAULT 0,
                custo_unitario DECIMAL(12,4) NOT NULL DEFAULT 0,
                subtotal_item DECIMAL(12,2) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_compra_item_compra (id_compra_entrada),
                INDEX idx_compra_item_produto (id_produto),
                CONSTRAINT fk_compra_item_compra
                    FOREIGN KEY (id_compra_entrada) REFERENCES compra_entradas(id_compra_entrada)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS compra_parcelas (
                id_compra_parcela INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                id_compra_entrada INT UNSIGNED NOT NULL,
                numero_parcela INT UNSIGNED NOT NULL,
                vencimento DATE NOT NULL,
                valor_parcela DECIMAL(12,2) NOT NULL DEFAULT 0,
                tipo_pagamento_previsto VARCHAR(80) NOT NULL DEFAULT 'Boleto',
                qtd_parcelas INT UNSIGNED NOT NULL DEFAULT 1,
                total_parcelas DECIMAL(12,2) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_compra_parcela_compra (id_compra_entrada),
                INDEX idx_compra_parcela_vencimento (vencimento),
                CONSTRAINT fk_compra_parcela_compra
                    FOREIGN KEY (id_compra_entrada) REFERENCES compra_entradas(id_compra_entrada)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

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
    }
}
