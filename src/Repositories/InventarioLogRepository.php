<?php

namespace App\Repositories;

use PDO;

class InventarioLogRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->ensureSchema();
    }

    public function create(array $registro): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO inventario_logs (
                local_inventario,
                usuario_id,
                usuario_nome,
                quantidade_itens,
                quantidade_itens_alterados,
                caminho_arquivo,
                nome_arquivo,
                created_at
            ) VALUES (
                :local_inventario,
                :usuario_id,
                :usuario_nome,
                :quantidade_itens,
                :quantidade_itens_alterados,
                :caminho_arquivo,
                :nome_arquivo,
                NOW()
            )'
        );

        $stmt->execute([
            ':local_inventario' => $registro['local_inventario'],
            ':usuario_id' => $registro['usuario_id'] ?: null,
            ':usuario_nome' => $registro['usuario_nome'] ?: null,
            ':quantidade_itens' => $registro['quantidade_itens'],
            ':quantidade_itens_alterados' => $registro['quantidade_itens_alterados'],
            ':caminho_arquivo' => $registro['caminho_arquivo'],
            ':nome_arquivo' => $registro['nome_arquivo'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function search(array $filters, int $limit = 200): array
    {
        $where = [];
        $params = [];

        $local = trim((string) ($filters['local'] ?? ''));
        if ($local !== '') {
            $where[] = 'local_inventario = :local';
            $params[':local'] = $local;
        }

        $usuario = trim((string) ($filters['usuario'] ?? ''));
        if ($usuario !== '') {
            $where[] = '(usuario_nome LIKE :usuario OR CAST(usuario_id AS CHAR) LIKE :usuario)';
            $params[':usuario'] = '%' . $usuario . '%';
        }

        $dataInicial = trim((string) ($filters['data_inicial'] ?? ''));
        if ($this->isValidDate($dataInicial)) {
            $where[] = 'DATE(created_at) >= :data_inicial';
            $params[':data_inicial'] = $dataInicial;
        }

        $dataFinal = trim((string) ($filters['data_final'] ?? ''));
        if ($this->isValidDate($dataFinal)) {
            $where[] = 'DATE(created_at) <= :data_final';
            $params[':data_final'] = $dataFinal;
        }

        $sql = 'SELECT
                id_inventario_log,
                local_inventario,
                usuario_id,
                usuario_nome,
                quantidade_itens,
                quantidade_itens_alterados,
                nome_arquivo,
                created_at
            FROM inventario_logs';

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY created_at DESC, id_inventario_log DESC LIMIT ' . (int) $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $logId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM inventario_logs WHERE id_inventario_log = :id LIMIT 1');
        $stmt->execute([':id' => $logId]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        return $registro ?: null;
    }

    private function ensureSchema(): void
    {
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS inventario_logs (
                id_inventario_log INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                local_inventario VARCHAR(40) NOT NULL,
                usuario_id INT UNSIGNED NULL,
                usuario_nome VARCHAR(160) NULL,
                quantidade_itens INT UNSIGNED NOT NULL DEFAULT 0,
                quantidade_itens_alterados INT UNSIGNED NOT NULL DEFAULT 0,
                caminho_arquivo VARCHAR(500) NOT NULL,
                nome_arquivo VARCHAR(255) NOT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_inventario_log_data (created_at),
                INDEX idx_inventario_log_local (local_inventario)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    private function isValidDate(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value;
    }
}
