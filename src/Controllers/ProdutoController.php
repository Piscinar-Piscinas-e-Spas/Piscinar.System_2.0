<?php

namespace App\Controllers;

use App\Repositories\ProdutoRepository;
use App\Services\ProdutoService;
use App\Views\AlertRenderer;
use PDO;

class ProdutoController
{
    private $repository;
    private $service;

    public function __construct(PDO $pdo)
    {
        $this->repository = new ProdutoRepository($pdo);
        $this->service = new ProdutoService($this->repository);
    }

    public function list(array $query)
    {
        $statusMap = [
            'editado' => ['class' => 'success', 'texto' => 'Produto atualizado com sucesso.'],
            'excluido' => ['class' => 'success', 'texto' => 'Produto excluído com sucesso.'],
            'erro_id' => ['class' => 'warning', 'texto' => 'ID inválido informado.'],
            'nao_encontrado' => ['class' => 'warning', 'texto' => 'Produto não encontrado.'],
            'erro_exclusao' => ['class' => 'danger', 'texto' => 'Erro ao excluir o produto.'],
        ];

        return [
            'produtos' => $this->repository->search($query),
            'grupos' => $this->repository->listDistinctGroups(),
            'alert' => AlertRenderer::fromStatus((string) ($query['status'] ?? ''), $statusMap),
        ];
    }

    public function create(array $post, $method)
    {
        if ($method === 'POST' && isset($post['nome'])) {
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
        $produto = $this->repository->findById($id);

        if (!$produto) {
            return ['redirect' => 'nao_encontrado'];
        }

        if ($method !== 'POST') {
            return ['ok' => false, 'alert' => null, 'data' => $produto];
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

    public function subgroups($group)
    {
        if ($group === '') {
            return [];
        }

        return $this->repository->listDistinctSubgroupsByGroup($group);
    }
}
