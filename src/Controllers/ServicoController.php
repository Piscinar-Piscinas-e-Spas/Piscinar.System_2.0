<?php

namespace App\Controllers;

use App\Repositories\ServicoRepository;
use App\Services\ServicoService;
use PDO;

class ServicoController
{
    private $service;

    public function __construct(PDO $pdo)
    {
        $this->service = new ServicoService($pdo, new ServicoRepository($pdo));
    }

    public function saveFromPayload(array $payload)
    {
        return $this->service->createFromPayload($payload);
    }
}
