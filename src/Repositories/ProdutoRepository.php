<?php

namespace App\Repositories;

use PDO;

class ProdutoRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
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

        $sql = 'SELECT id, nome, preco1, preco2, grupo, subgrupo, marca, qtdLoja, qtdEstoque,
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
        $stmt = $this->pdo->query('SELECT id, nome, preco1 FROM produtos ORDER BY nome LIMIT ' . (int) $limit);
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

        return (int) $this->pdo->lastInsertId();
    }

    public function update($id, array $produto)
    {
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

        return $stmt->execute($produto);
    }

    public function delete($id)
    {
        $stmt = $this->pdo->prepare('DELETE FROM produtos WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
