<?php

namespace App\Repositories;

use PDO;

class ClienteRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function search($term = '', $limit = 100)
    {
        $sql = 'SELECT id_cliente, nome_cliente, telefone_contato, cpf_cnpj, endereco, email_contato
                FROM clientes';
        $params = [];

        if ($term !== '') {
            $sql .= ' WHERE nome_cliente LIKE :termo
                      OR telefone_contato LIKE :termo
                      OR cpf_cnpj LIKE :termo
                      OR email_contato LIKE :termo';
            $params[':termo'] = '%' . $term . '%';
        }

        $sql .= ' ORDER BY nome_cliente ASC LIMIT ' . (int) $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listForSales($limit = 300)
    {
        $stmt = $this->pdo->query('SELECT id_cliente, nome_cliente, telefone_contato, cpf_cnpj, endereco, email_contato FROM clientes ORDER BY nome_cliente LIMIT ' . (int) $limit);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM clientes WHERE id_cliente = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        return $cliente ?: null;
    }

    public function exists($id)
    {
        $stmt = $this->pdo->prepare('SELECT id_cliente FROM clientes WHERE id_cliente = :id LIMIT 1');
        $stmt->execute([':id' => $id]);

        return (bool) $stmt->fetchColumn();
    }

    public function create(array $cliente)
    {
        $stmt = $this->pdo->prepare('INSERT INTO clientes (
                nome_cliente,
                telefone_contato,
                cpf_cnpj,
                endereco,
                email_contato,
                created_at,
                updated_at
            ) VALUES (
                :nome_cliente,
                :telefone_contato,
                :cpf_cnpj,
                :endereco,
                :email_contato,
                NOW(),
                NOW()
            )');

        $stmt->execute($cliente);

        return (int) $this->pdo->lastInsertId();
    }

    public function update($id, array $cliente)
    {
        $cliente['id_cliente'] = $id;

        $stmt = $this->pdo->prepare('UPDATE clientes SET
                nome_cliente = :nome_cliente,
                telefone_contato = :telefone_contato,
                cpf_cnpj = :cpf_cnpj,
                endereco = :endereco,
                email_contato = :email_contato,
                updated_at = NOW()
            WHERE id_cliente = :id_cliente');

        return $stmt->execute($cliente);
    }

    public function delete($id)
    {
        $stmt = $this->pdo->prepare('DELETE FROM clientes WHERE id_cliente = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
