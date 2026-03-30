<?php

namespace App\Services;

use App\Support\AuditLogger;
use PDO;

class TransferenciaEstoqueAuditService
{
    private $pdo;
    private $auditLogger;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->auditLogger = new AuditLogger($pdo);
        $this->ensureSchema();
    }

    public function saveTransferencia(array $payload): int
    {
        $stmtTransferencia = $this->pdo->prepare(
            'INSERT INTO estoque_transferencias (
                origem_estoque,
                destino_estoque,
                usuario_id,
                usuario_nome,
                total_itens,
                total_quantidade,
                created_at
            ) VALUES (
                :origem_estoque,
                :destino_estoque,
                :usuario_id,
                :usuario_nome,
                :total_itens,
                :total_quantidade,
                NOW()
            )'
        );

        $stmtTransferencia->execute([
            ':origem_estoque' => $payload['origem_estoque'],
            ':destino_estoque' => $payload['destino_estoque'],
            ':usuario_id' => $payload['usuario_id'] ?: null,
            ':usuario_nome' => $payload['usuario_nome'] ?: null,
            ':total_itens' => count($payload['itens']),
            ':total_quantidade' => $payload['total_quantidade'],
        ]);

        $transferenciaId = (int) $this->pdo->lastInsertId();

        $stmtItem = $this->pdo->prepare(
            'INSERT INTO estoque_transferencia_itens (
                id_transferencia,
                id_produto,
                produto_nome,
                quantidade,
                saldo_origem_validado,
                created_at
            ) VALUES (
                :id_transferencia,
                :id_produto,
                :produto_nome,
                :quantidade,
                :saldo_origem_validado,
                NOW()
            )'
        );

        foreach ($payload['itens'] as $item) {
            $stmtItem->execute([
                ':id_transferencia' => $transferenciaId,
                ':id_produto' => $item['id_produto'],
                ':produto_nome' => $item['produto_nome'],
                ':quantidade' => $item['quantidade'],
                ':saldo_origem_validado' => $item['saldo_origem_validado'],
            ]);
        }

        $this->auditLogger->logCreate('transferencia_estoque', 'estoque_transferencias', $transferenciaId, [
            'origem_estoque' => $payload['origem_estoque'],
            'destino_estoque' => $payload['destino_estoque'],
            'usuario_id' => $payload['usuario_id'],
            'usuario_nome' => $payload['usuario_nome'],
            'total_itens' => count($payload['itens']),
            'total_quantidade' => $payload['total_quantidade'],
            'itens' => $payload['itens'],
            'origem_fluxo' => 'logistica/processar_transferencia.php',
        ]);

        return $transferenciaId;
    }

    private function ensureSchema(): void
    {
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS estoque_transferencias (
                id_transferencia INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                origem_estoque VARCHAR(40) NOT NULL,
                destino_estoque VARCHAR(40) NOT NULL,
                usuario_id INT UNSIGNED NULL,
                usuario_nome VARCHAR(160) NULL,
                total_itens INT UNSIGNED NOT NULL DEFAULT 0,
                total_quantidade DECIMAL(12,3) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_transferencia_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS estoque_transferencia_itens (
                id_transferencia_item INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                id_transferencia INT UNSIGNED NOT NULL,
                id_produto INT UNSIGNED NOT NULL,
                produto_nome VARCHAR(255) NOT NULL,
                quantidade DECIMAL(12,3) NOT NULL DEFAULT 0,
                saldo_origem_validado DECIMAL(12,3) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_transferencia_item_transferencia (id_transferencia),
                CONSTRAINT fk_estoque_transferencia_item_transferencia
                    FOREIGN KEY (id_transferencia) REFERENCES estoque_transferencias(id_transferencia)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }
}
