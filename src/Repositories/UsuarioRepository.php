<?php

namespace App\Repositories;

use PDO;

class UsuarioRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listActiveForSellerAssignment(): array
    {
        $stmt = $this->pdo->query(
            'SELECT
                id_usuario,
                usuario,
                COALESCE(NULLIF(TRIM(nome_exibicao), ""), usuario) AS nome_exibicao
            FROM usuarios
            WHERE ativo = 1
            ORDER BY nome_exibicao ASC, usuario ASC'
        );

        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function findActiveById(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                id_usuario,
                usuario,
                COALESCE(NULLIF(TRIM(nome_exibicao), ""), usuario) AS nome_exibicao
            FROM usuarios
            WHERE id_usuario = :id_usuario
              AND ativo = 1
            LIMIT 1'
        );
        $stmt->execute([':id_usuario' => $userId]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        return $usuario ?: null;
    }
}
