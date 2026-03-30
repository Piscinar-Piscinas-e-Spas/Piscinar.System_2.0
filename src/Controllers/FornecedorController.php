<?php

namespace App\Controllers;

use App\Repositories\FornecedorRepository;
use App\Services\FornecedorService;
use App\Views\AlertRenderer;
use PDO;

class FornecedorController
{
    private $repository;
    private $service;

    public function __construct(PDO $pdo)
    {
        $this->repository = new FornecedorRepository($pdo);
        $this->service = new FornecedorService($this->repository);
    }

    public function list(array $query): array
    {
        $statusMap = [
            'editado' => ['class' => 'success', 'texto' => 'Fornecedor atualizado com sucesso.'],
            'excluido' => ['class' => 'success', 'texto' => 'Fornecedor excluido com sucesso.'],
            'erro_id' => ['class' => 'warning', 'texto' => 'ID do fornecedor invalido.'],
            'nao_encontrado' => ['class' => 'warning', 'texto' => 'Fornecedor nao encontrado.'],
            'erro_exclusao' => ['class' => 'danger', 'texto' => 'Erro ao excluir fornecedor.'],
        ];

        return [
            'fornecedores' => $this->repository->search(trim((string) ($query['termo'] ?? ''))),
            'alert' => AlertRenderer::fromStatus((string) ($query['status'] ?? ''), $statusMap),
        ];
    }

    public function create(array $post, $method): array
    {
        if ($method === 'POST') {
            return $this->service->create($post);
        }

        return [
            'ok' => false,
            'alert' => null,
            'data' => $this->service->defaultData(),
        ];
    }

    public function edit(int $fornecedorId, array $post, $method): array
    {
        $fornecedor = $this->repository->findById($fornecedorId);

        if (!$fornecedor) {
            return ['redirect' => 'nao_encontrado'];
        }

        if ($method !== 'POST') {
            return ['ok' => false, 'alert' => null, 'data' => $fornecedor];
        }

        $result = $this->service->update($fornecedorId, $post);
        if ($result['ok']) {
            return ['redirect' => 'editado'];
        }

        return $result;
    }

    public function delete($fornecedorId): string
    {
        if (!$fornecedorId) {
            return 'erro_id';
        }

        return $this->repository->delete((int) $fornecedorId) ? 'excluido' : 'nao_encontrado';
    }
}
