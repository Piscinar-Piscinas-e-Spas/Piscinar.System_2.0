<?php

namespace App\Services;

use App\Repositories\ProdutoRepository;
use App\Views\AlertRenderer;
use Throwable;

class ProdutoService
{
    private $repository;

    public function __construct(ProdutoRepository $repository)
    {
        $this->repository = $repository;
    }

    public function defaultData()
    {
        return [
            'nome' => '',
            'custo' => '',
            'preco1' => '',
            'preco2' => '',
            'qtdLoja' => '',
            'qtdEstoque' => '',
            'controle_estoque' => 1,
            'estoque_minimo' => '',
            'ponto_compra' => '',
            'grupo' => '',
            'subgrupo' => '',
            'marca' => '',
            'observacoes' => '',
        ];
    }

    public function normalize(array $input)
    {
        $data = [
            'nome' => trim((string) ($input['nome'] ?? '')),
            'custo' => $this->toDecimal($input['custo'] ?? 0),
            'preco1' => $this->toDecimal($input['preco1'] ?? 0),
            'preco2' => $this->toDecimal($input['preco2'] ?? 0),
            'qtdLoja' => $this->toInt($input['qtdLoja'] ?? 0),
            'qtdEstoque' => $this->toInt($input['qtdEstoque'] ?? 0),
            'controle_estoque' => isset($input['controle_estoque']) ? 1 : 0,
            'estoque_minimo' => $this->nullableInt($input['estoque_minimo'] ?? ''),
            'ponto_compra' => $this->nullableInt($input['ponto_compra'] ?? ''),
            'grupo' => $this->requiredTrim($input['grupo'] ?? ''),
            'subgrupo' => $this->requiredTrim($input['subgrupo'] ?? ''),
            'marca' => $this->requiredTrim($input['marca'] ?? ''),
            'observacoes' => $this->requiredTrim($input['observacoes'] ?? ''),
        ];

        if ($data['controle_estoque'] === 0) {
            $data['estoque_minimo'] = null;
            $data['ponto_compra'] = null;
        }

        return $data;
    }

    public function validate(array $produto)
    {
        if ($produto['nome'] === '') {
            return AlertRenderer::make('danger', 'O nome do produto e obrigatorio.');
        }

        if ($produto['preco1'] <= 0) {
            return AlertRenderer::make('danger', 'O preco 1 deve ser maior que zero.');
        }

        if ($produto['controle_estoque'] === 1 && ($produto['estoque_minimo'] === null || $produto['estoque_minimo'] < 0)) {
            return AlertRenderer::make('danger', 'Com controle de estoque ativo, o estoque minimo e obrigatorio e deve ser maior ou igual a zero.');
        }

        return null;
    }

    public function create(array $input)
    {
        $produto = $this->normalize($input);
        $alert = $this->validate($produto);

        if ($alert) {
            return ['ok' => false, 'alert' => $alert, 'data' => $produto];
        }

        try {
            $this->repository->create($produto);
            return [
                'ok' => true,
                'alert' => AlertRenderer::make('success', 'Produto cadastrado com sucesso!'),
                'data' => $this->defaultData(),
            ];
        } catch (Throwable $e) {
            error_log('Erro tecnico ao cadastrar produto: ' . $e->getMessage());

            return [
                'ok' => false,
                'alert' => AlertRenderer::make('danger', 'Erro ao cadastrar produto. Tente novamente.'),
                'data' => $produto,
            ];
        }
    }

    public function update($id, array $input)
    {
        $produto = $this->normalize($input);
        $alert = $this->validate($produto);

        if ($alert) {
            return ['ok' => false, 'alert' => $alert, 'data' => $produto];
        }

        try {
            $this->repository->update($id, $produto);
            return ['ok' => true, 'alert' => null, 'data' => $produto];
        } catch (Throwable $e) {
            error_log('Erro ao atualizar produto: ' . $e->getMessage());

            return [
                'ok' => false,
                'alert' => AlertRenderer::make('danger', 'Erro ao atualizar produto. Tente novamente.'),
                'data' => $produto,
            ];
        }
    }

    private function toDecimal($value)
    {
        $text = trim((string) $value);
        if ($text === '') {
            return 0.0;
        }

        if (strpos($text, ',') !== false) {
            $text = str_replace('.', '', $text);
            $text = str_replace(',', '.', $text);
        }

        return is_numeric($text) ? (float) $text : 0.0;
    }

    private function toInt($value)
    {
        return (int) $value;
    }

    private function nullableInt($value)
    {
        $text = trim((string) $value);
        return $text === '' ? null : (int) $text;
    }

    private function requiredTrim($value)
    {
        return trim((string) $value);
    }
}
