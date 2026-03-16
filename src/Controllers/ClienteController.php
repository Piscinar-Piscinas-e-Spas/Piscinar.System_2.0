<?php

namespace App\Controllers;

use App\Repositories\ClienteRepository;
use App\Services\ClienteService;
use App\Views\AlertRenderer;
use PDO;

class ClienteController
{
    private $repository;
    private $service;

    public function __construct(PDO $pdo)
    {
        $this->repository = new ClienteRepository($pdo);
        $this->service = new ClienteService($this->repository);
    }

    public function list(array $query)
    {
        $statusMap = [
            'editado' => ['class' => 'success', 'texto' => 'Cliente atualizado com sucesso.'],
            'excluido' => ['class' => 'success', 'texto' => 'Cliente excluído com sucesso.'],
            'erro_id' => ['class' => 'warning', 'texto' => 'ID do cliente inválido.'],
            'nao_encontrado' => ['class' => 'warning', 'texto' => 'Cliente não encontrado.'],
            'erro_exclusao' => ['class' => 'danger', 'texto' => 'Erro ao excluir cliente.'],
        ];

        return [
            'clientes' => $this->repository->search(trim((string) ($query['termo'] ?? ''))),
            'alert' => AlertRenderer::fromStatus((string) ($query['status'] ?? ''), $statusMap),
        ];
    }

    public function create(array $post, $method)
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

    public function edit($id, array $post, $method)
    {
        $cliente = $this->repository->findById($id);

        if (!$cliente) {
            return ['redirect' => 'nao_encontrado'];
        }

        if ($method !== 'POST') {
            return ['ok' => false, 'alert' => null, 'data' => $cliente];
        }

        $result = $this->service->update($id, $post);
        if ($result['ok']) {
            return ['redirect' => 'editado'];
        }

        return $result;
    }

    public function delete($id)
    {
        if (!$id) {
            return 'erro_id';
        }

        return $this->repository->delete($id) ? 'excluido' : 'nao_encontrado';
    }
}
