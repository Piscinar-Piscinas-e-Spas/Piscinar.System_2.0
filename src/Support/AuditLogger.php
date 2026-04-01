<?php

namespace App\Support;

use PDO;

class AuditLogger
{
    // Logger unico para rastrear create, update e delete em modulos diferentes.
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function logCreate($entity, $table, $recordId, array $data)
    {
        // Em create o snapshot completo costuma ser mais util do que um diff.
        $this->insertSnapshotLog('create', $entity, $table, $recordId, $data);
    }

    public function logUpdate($entity, $table, $recordId, array $before, array $after)
    {
        // Update gera diff textual para facilitar leitura do historico.
        $lines = [];

        foreach ($after as $field => $newValue) {
            $oldValue = array_key_exists($field, $before) ? $before[$field] : null;

            if ($this->valuesAreEqual($oldValue, $newValue)) {
                continue;
            }

            $lines[] = sprintf(
                '%s: %s -> %s',
                (string) $field,
                $this->stringifyValue($oldValue),
                $this->stringifyValue($newValue)
            );
        }

        if ($lines === []) {
            $lines[] = 'Nenhuma alteracao de campos detectada.';
        }

        $this->insertLog('update', $entity, $table, $recordId, $lines);
    }

    public function logDelete($entity, $table, $recordId, array $before)
    {
        $this->insertSnapshotLog('delete', $entity, $table, $recordId, $before);
    }

    public function logSnapshot($action, $entity, $table, $recordId, array $data)
    {
        $allowedActions = ['create', 'update', 'delete'];
        $action = in_array($action, $allowedActions, true) ? $action : 'create';

        $this->insertSnapshotLog($action, $entity, $table, $recordId, $data);
    }

    private function insertLog($action, $entity, $table, $recordId, array $lines)
    {
        // Toda auditoria cai na mesma tabela, independente do modulo.
        $actor = $this->resolveActor();

        $stmt = $this->pdo->prepare('INSERT INTO auditoria_logs (
                acao,
                entidade,
                tabela_referencia,
                id_registro,
                usuario_id,
                usuario_nome,
                campos_alterados,
                created_at
            ) VALUES (
                :acao,
                :entidade,
                :tabela_referencia,
                :id_registro,
                :usuario_id,
                :usuario_nome,
                :campos_alterados,
                NOW()
            )');

        $stmt->execute([
            ':acao' => $action,
            ':entidade' => $entity,
            ':tabela_referencia' => $table,
            ':id_registro' => (string) $recordId,
            ':usuario_id' => $actor['id'],
            ':usuario_nome' => $actor['name'],
            ':campos_alterados' => implode(PHP_EOL, $lines),
        ]);
    }

    private function resolveActor()
    {
        // O usuario vem da sessao quando existir, mas o log continua funcionando sem ele.
        $userId = null;
        $userName = null;

        if (\function_exists('auth_user_id')) {
            $userId = auth_user_id();
        }

        if (\function_exists('auth_user')) {
            $user = auth_user();

            if (\is_array($user)) {
                $userName = trim((string) ($user['nome'] ?? $user['usuario'] ?? ''));
            }
        }

        return [
            'id' => $userId !== null && $userId !== '' ? (string) $userId : null,
            'name' => $userName !== '' ? $userName : null,
        ];
    }

    private function formatFieldLine($field, $value)
    {
        return sprintf('%s: %s', $field, $this->stringifyValue($value));
    }

    private function insertSnapshotLog($action, $entity, $table, $recordId, array $data)
    {
        // Snapshot vira lista campo: valor para ser legivel direto no banco.
        $lines = [];

        foreach ($data as $field => $value) {
            $lines[] = $this->formatFieldLine((string) $field, $value);
        }

        $this->insertLog($action, $entity, $table, $recordId, $lines);
    }

    private function stringifyValue($value)
    {
        // Converte varios tipos para um formato estavel usado em log e comparacao.
        if ($value === null) {
            return 'NULL';
        }

        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (\is_array($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $encoded !== false ? $encoded : '[array]';
        }

        if (\is_object($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $encoded !== false ? $encoded : '[object]';
        }

        $text = trim((string) $value);

        if ($text === '') {
            return '""';
        }

        return preg_replace('/\s+/', ' ', $text) ?: '""';
    }

    private function valuesAreEqual($left, $right)
    {
        return $this->stringifyValue($left) === $this->stringifyValue($right);
    }
}
