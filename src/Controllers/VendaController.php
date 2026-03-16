<?php

namespace App\Controllers;

use App\Repositories\ClienteRepository;
use App\Repositories\ProdutoRepository;
use App\Repositories\VendaRepository;
use App\Services\VendaService;
use PDO;

class VendaController
{
    private $service;

    public function __construct(PDO $pdo)
    {
        $this->service = new VendaService(
            new VendaRepository($pdo),
            new ClienteRepository($pdo),
            new ProdutoRepository($pdo)
        );
    }

    public function formData()
    {
        return $this->service->buildFormData();
    }

    public function saveFromPayload(array $payload)
    {
        return $this->service->createFromPayload($payload);
    }
}
