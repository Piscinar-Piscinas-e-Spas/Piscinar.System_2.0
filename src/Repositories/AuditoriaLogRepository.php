<?php

namespace App\Repositories;

use PDO;

class AuditoriaLogRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function search(array $filters, $limit = 200)
    {
        $where = [];
        $params = [];

        $entidade = trim((string) ($filters['entidade'] ?? ''));
        if ($entidade !== '') {
            $where[] = 'entidade = :entidade';
            $params[':entidade'] = $entidade;
        }

        $acao = trim((string) ($filters['acao'] ?? ''));
        if ($acao !== '') {
            $where[] = 'acao = :acao';
            $params[':acao'] = $acao;
        }

        $usuario = trim((string) ($filters['usuario'] ?? ''));
        if ($usuario !== '') {
            $where[] = '(usuario_nome LIKE :usuario OR usuario_id LIKE :usuario)';
            $params[':usuario'] = '%' . $usuario . '%';
        }

        $idRegistro = trim((string) ($filters['id_registro'] ?? ''));
        if ($idRegistro !== '') {
            $where[] = 'id_registro = :id_registro';
            $params[':id_registro'] = $idRegistro;
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
                    id_log,
                    acao,
                    entidade,
                    tabela_referencia,
                    id_registro,
                    usuario_id,
                    usuario_nome,
                    campos_alterados,
                    created_at
                FROM auditoria_logs';

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY created_at DESC, id_log DESC LIMIT ' . (int) $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listDistinctEntities()
    {
        $stmt = $this->pdo->query('SELECT DISTINCT entidade FROM auditoria_logs ORDER BY entidade ASC');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function isValidDate($value)
    {
        if ($value === '') {
            return false;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
