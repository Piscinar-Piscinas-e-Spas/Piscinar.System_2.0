<?php

namespace App\Controllers;

use App\Repositories\AuditoriaLogRepository;
use PDO;

class AuditoriaLogController
{
    private $repository;

    public function __construct(PDO $pdo)
    {
        $this->repository = new AuditoriaLogRepository($pdo);
    }

    public function list(array $query)
    {
        return [
            'logs' => $this->repository->search($query),
            'entidades' => $this->repository->listDistinctEntities(),
        ];
    }
}
