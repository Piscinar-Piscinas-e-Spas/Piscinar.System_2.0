<?php

namespace App\Repositories;

use App\Support\AuditLogger;
use PDO;
use Throwable;

class FornecedorRepository
{
    private $pdo;
    private $auditLogger;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->ensureSchema();
        $this->auditLogger = new AuditLogger($pdo);
    }

    public function listForPurchase(int $limit = 300): array
    {
        $stmt = $this->pdo->query(
            'SELECT
                id_fornecedor,
                razao_social,
                nome_fantasia,
                nome_fornecedor,
                documento,
                telefone,
                email
            FROM fornecedores
            WHERE ativo = 1
            ORDER BY nome_fantasia, razao_social
            LIMIT ' . (int) $limit
        );

        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function search(string $term = '', int $limit = 100): array
    {
        $sql = 'SELECT
                id_fornecedor,
                razao_social,
                nome_fantasia,
                nome_fornecedor,
                documento,
                telefone,
                email,
                ativo
            FROM fornecedores';
        $params = [];

        if ($term !== '') {
            $sql .= ' WHERE razao_social LIKE :termo
                      OR nome_fantasia LIKE :termo
                      OR nome_fornecedor LIKE :termo
                      OR documento LIKE :termo
                      OR telefone LIKE :termo
                      OR email LIKE :termo';
            $params[':termo'] = '%' . $term . '%';
        }

        $sql .= ' ORDER BY nome_fantasia ASC, razao_social ASC LIMIT ' . (int) $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $fornecedorId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM fornecedores WHERE id_fornecedor = :id LIMIT 1');
        $stmt->execute([':id' => $fornecedorId]);
        $fornecedor = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fornecedor ?: null;
    }

    public function exists(int $fornecedorId): bool
    {
        $stmt = $this->pdo->prepare('SELECT id_fornecedor FROM fornecedores WHERE id_fornecedor = :id LIMIT 1');
        $stmt->execute([':id' => $fornecedorId]);

        return (bool) $stmt->fetchColumn();
    }

    public function create(array $fornecedor): int
    {
        $startedTransaction = !$this->pdo->inTransaction();

        try {
            if ($startedTransaction) {
                $this->pdo->beginTransaction();
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO fornecedores (
                    razao_social,
                    nome_fantasia,
                    nome_fornecedor,
                    documento,
                    telefone,
                    email,
                    ativo,
                    created_at,
                    updated_at
                ) VALUES (
                    :razao_social,
                    :nome_fantasia,
                    :nome_fornecedor,
                    :documento,
                    :telefone,
                    :email,
                    :ativo,
                    NOW(),
                    NOW()
                )'
            );

            $stmt->execute($fornecedor);
            $fornecedorId = (int) $this->pdo->lastInsertId();

            $this->auditLogger->logCreate('fornecedor', 'fornecedores', $fornecedorId, $fornecedor);

            if ($startedTransaction) {
                $this->pdo->commit();
            }

            return $fornecedorId;
        } catch (Throwable $e) {
            if ($startedTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function update(int $fornecedorId, array $fornecedor): bool
    {
        $before = $this->findById($fornecedorId);
        if ($before === null) {
            return false;
        }

        $startedTransaction = !$this->pdo->inTransaction();

        try {
            if ($startedTransaction) {
                $this->pdo->beginTransaction();
            }

            $fornecedor['id_fornecedor'] = $fornecedorId;

            $stmt = $this->pdo->prepare(
                'UPDATE fornecedores SET
                    razao_social = :razao_social,
                    nome_fantasia = :nome_fantasia,
                    nome_fornecedor = :nome_fornecedor,
                    documento = :documento,
                    telefone = :telefone,
                    email = :email,
                    ativo = :ativo,
                    updated_at = NOW()
                WHERE id_fornecedor = :id_fornecedor'
            );

            $updated = $stmt->execute($fornecedor);

            if ($updated) {
                $this->auditLogger->logUpdate('fornecedor', 'fornecedores', $fornecedorId, $before, $fornecedor);
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

    public function delete(int $fornecedorId): bool
    {
        $before = $this->findById($fornecedorId);
        if ($before === null) {
            return false;
        }

        $startedTransaction = !$this->pdo->inTransaction();

        try {
            if ($startedTransaction) {
                $this->pdo->beginTransaction();
            }

            $stmt = $this->pdo->prepare('DELETE FROM fornecedores WHERE id_fornecedor = :id');
            $stmt->execute([':id' => $fornecedorId]);
            $deleted = $stmt->rowCount() > 0;

            if ($deleted) {
                $this->auditLogger->logDelete('fornecedor', 'fornecedores', $fornecedorId, $before);
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

    private function ensureSchema(): void
    {
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS fornecedores (
                id_fornecedor INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                razao_social VARCHAR(160) NULL,
                nome_fantasia VARCHAR(160) NULL,
                nome_fornecedor VARCHAR(160) NOT NULL,
                documento VARCHAR(20) NULL,
                telefone VARCHAR(30) NULL,
                email VARCHAR(160) NULL,
                ativo TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_fornecedores_fantasia (nome_fantasia),
                INDEX idx_fornecedores_razao (razao_social),
                INDEX idx_fornecedores_nome (nome_fornecedor),
                INDEX idx_fornecedores_documento (documento)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $hasRazaoSocial = $this->pdo->query("SHOW COLUMNS FROM fornecedores LIKE 'razao_social'")->fetch(PDO::FETCH_ASSOC);
        if (!$hasRazaoSocial) {
            $this->pdo->exec("ALTER TABLE fornecedores ADD COLUMN razao_social VARCHAR(160) NULL AFTER id_fornecedor");
        }

        $hasNomeFantasia = $this->pdo->query("SHOW COLUMNS FROM fornecedores LIKE 'nome_fantasia'")->fetch(PDO::FETCH_ASSOC);
        if (!$hasNomeFantasia) {
            $this->pdo->exec("ALTER TABLE fornecedores ADD COLUMN nome_fantasia VARCHAR(160) NULL AFTER razao_social");
        }

        $this->pdo->exec("
            UPDATE fornecedores
            SET
                razao_social = COALESCE(NULLIF(TRIM(razao_social), ''), nome_fornecedor),
                nome_fantasia = COALESCE(NULLIF(TRIM(nome_fantasia), ''), COALESCE(NULLIF(TRIM(razao_social), ''), nome_fornecedor))
            WHERE razao_social IS NULL
               OR TRIM(razao_social) = ''
               OR nome_fantasia IS NULL
               OR TRIM(nome_fantasia) = ''
        ");
    }
}
