<?php

namespace App\Controllers;

use App\Repositories\CompraEntradaRepository;
use App\Repositories\FornecedorRepository;
use App\Repositories\ProdutoRepository;
use App\Services\CompraEntradaService;
use PDO;

class CompraEntradaController
{
    private $service;

    public function __construct(PDO $pdo)
    {
        $this->service = new CompraEntradaService(
            $pdo,
            new CompraEntradaRepository($pdo),
            new FornecedorRepository($pdo),
            new ProdutoRepository($pdo)
        );
    }

    public function formData(): array
    {
        return $this->service->buildFormData();
    }

    public function saveFromPayload(array $payload): array
    {
        return $this->service->createFromPayload($payload);
    }
}
