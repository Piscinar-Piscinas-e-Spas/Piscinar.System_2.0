<?php

namespace App\Repositories;

use App\Support\AuditLogger;
use PDO;
use Throwable;

class ProdutoRepository
{
    private $pdo;
    private $auditLogger;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->auditLogger = new AuditLogger($pdo);
    }

    public function search(array $filters, $limit = 50)
    {
        $where = [];
        $params = [];

        if (!empty($filters['grupo'])) {
            $where[] = 'grupo LIKE :grupo';
            $params[':grupo'] = '%' . $filters['grupo'] . '%';
        }

        if (!empty($filters['subgrupo'])) {
            $where[] = 'subgrupo LIKE :subgrupo';
            $params[':subgrupo'] = '%' . $filters['subgrupo'] . '%';
        }

        if (!empty($filters['nome'])) {
            $where[] = 'nome LIKE :nome';
            $params[':nome'] = '%' . $filters['nome'] . '%';
        }

        $sql = 'SELECT id, nome, custo, preco1, preco2, grupo, subgrupo, marca, qtdLoja, qtdEstoque,
                       COALESCE(controle_estoque, 0) AS controle_estoque,
                       estoque_minimo,
                       (COALESCE(qtdLoja, 0) + COALESCE(qtdEstoque, 0)) AS estoque_total
                FROM produtos';

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY
                  CASE WHEN grupo IS NOT NULL THEN 0 ELSE 1 END,
                  CASE WHEN subgrupo IS NOT NULL THEN 0 ELSE 1 END,
                  nome
                  LIMIT ' . (int) $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listDistinctGroups()
    {
        $stmt = $this->pdo->query('SELECT DISTINCT grupo FROM produtos WHERE grupo IS NOT NULL ORDER BY grupo');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function listDistinctSubgroupsByGroup($group)
    {
        $stmt = $this->pdo->prepare('SELECT DISTINCT subgrupo FROM produtos WHERE grupo = :grupo AND subgrupo IS NOT NULL ORDER BY subgrupo');
        $stmt->execute([':grupo' => $group]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function listForSales($limit = 500)
    {
        $stmt = $this->pdo->query('SELECT id, nome, preco1 FROM produtos WHERE COALESCE(preco1, 0) > 0 ORDER BY nome LIMIT ' . (int) $limit);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM produtos WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        return $produto ?: null;
    }

    public function exists($id)
    {
        $stmt = $this->pdo->prepare('SELECT id FROM produtos WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);

        return (bool) $stmt->fetchColumn();
    }

    public function create(array $produto)
    {
        $startedTransaction = !$this->pdo->inTransaction();

        try {
            if ($startedTransaction) {
                $this->pdo->beginTransaction();
            }

            $stmt = $this->pdo->prepare('INSERT INTO produtos (
                    nome, custo, preco1, preco2, qtdLoja, qtdEstoque,
                    controle_estoque, estoque_minimo, ponto_compra,
                    grupo, subgrupo, marca, observacoes, created_at
                ) VALUES (
                    :nome, :custo, :preco1, :preco2, :qtdLoja, :qtdEstoque,
                    :controle_estoque, :estoque_minimo, :ponto_compra,
                    :grupo, :subgrupo, :marca, :observacoes, NOW()
                )');

            $stmt->execute($produto);

            $produtoId = (int) $this->pdo->lastInsertId();
            $this->auditLogger->logCreate('produto', 'produtos', $produtoId, $produto);

            if ($startedTransaction) {
                $this->pdo->commit();
            }

            return $produtoId;
        } catch (Throwable $e) {
            if ($startedTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function update($id, array $produto)
    {
        $before = $this->findById($id);
        if ($before === null) {
            return false;
        }

        $startedTransaction = !$this->pdo->inTransaction();

        try {
            if ($startedTransaction) {
                $this->pdo->beginTransaction();
            }

            $produto['id'] = $id;

            $stmt = $this->pdo->prepare('UPDATE produtos SET
                    nome = :nome,
                    custo = :custo,
                    preco1 = :preco1,
                    preco2 = :preco2,
                    qtdLoja = :qtdLoja,
                    qtdEstoque = :qtdEstoque,
                    controle_estoque = :controle_estoque,
                    estoque_minimo = :estoque_minimo,
                    ponto_compra = :ponto_compra,
                    grupo = :grupo,
                    subgrupo = :subgrupo,
                    marca = :marca,
                    observacoes = :observacoes
                WHERE id = :id');

            $updated = $stmt->execute($produto);

            if ($updated) {
                $this->auditLogger->logUpdate('produto', 'produtos', $id, $before, $produto);
            }

            if ($startedTransaction) {
                $this->pdo->commit();
            }

            return $updated;
        } catch (Throwable $e) {
            if ($startedTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function delete($id)
    {
        $before = $this->findById($id);
        if ($before === null) {
            return false;
        }

        $startedTransaction = !$this->pdo->inTransaction();

        try {
            if ($startedTransaction) {
                $this->pdo->beginTransaction();
            }

            $stmt = $this->pdo->prepare('DELETE FROM produtos WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $deleted = $stmt->rowCount() > 0;

            if ($deleted) {
                $this->auditLogger->logDelete('produto', 'produtos', $id, $before);
            }

            if ($startedTransaction) {
                $this->pdo->commit();
            }

            return $deleted;
        } catch (Throwable $e) {
            if ($startedTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }
}
