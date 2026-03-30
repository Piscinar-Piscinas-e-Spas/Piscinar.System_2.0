<?php

namespace App\Services;

use App\Repositories\CompraEntradaRepository;
use App\Repositories\FornecedorRepository;
use App\Repositories\ProdutoRepository;
use Throwable;

class CompraEntradaService
{
    private $repository;
    private $fornecedorRepository;
    private $produtoRepository;

    public function __construct(
        \PDO $pdo,
        CompraEntradaRepository $repository,
        FornecedorRepository $fornecedorRepository,
        ProdutoRepository $produtoRepository
    ) {
        $this->repository = $repository;
        $this->fornecedorRepository = $fornecedorRepository;
        $this->produtoRepository = $produtoRepository;
    }

    public function buildFormData(): array
    {
        return [
            'fornecedores' => $this->fornecedorRepository->listForPurchase(),
            'produtos' => $this->produtoRepository->search([], 600),
        ];
    }

    public function createFromPayload(array $dados): array
    {
        $fornecedorId = (int) ($dados['fornecedor_id'] ?? 0);
        $numeroNota = trim((string) ($dados['numero_nota'] ?? ''));
        $dataEmissao = trim((string) ($dados['data_emissao'] ?? date('Y-m-d')));
        $dataEntrada = trim((string) ($dados['data_entrada'] ?? date('Y-m-d')));
        $condicaoPagamento = trim((string) ($dados['condicao_pagamento'] ?? 'vista'));
        $observacoes = trim((string) ($dados['observacoes'] ?? ''));
        $valorFrete = round($this->toDecimal($dados['valor_frete'] ?? 0), 2);
        $valorDesconto = round($this->toDecimal($dados['valor_desconto'] ?? 0), 2);
        $valorOutrasDespesas = round($this->toDecimal($dados['valor_outras_despesas'] ?? 0), 2);
        $totalNotaInformado = round($this->toDecimal($dados['total_nota'] ?? 0), 2);
        $itens = is_array($dados['itens'] ?? null) ? $dados['itens'] : [];
        $parcelas = is_array($dados['parcelas'] ?? null) ? $dados['parcelas'] : [];

        if ($fornecedorId <= 0 || !$this->fornecedorRepository->exists($fornecedorId)) {
            return $this->error(422, 'Fornecedor invalido para a entrada.');
        }

        if ($numeroNota === '') {
            return $this->error(422, 'Informe o numero da nota.');
        }

        if (!$this->isValidDate($dataEmissao) || !$this->isValidDate($dataEntrada)) {
            return $this->error(422, 'As datas da nota sao invalidas.');
        }

        if (!in_array($condicaoPagamento, ['vista', 'parcelado'], true)) {
            return $this->error(422, 'Condicao de pagamento invalida.');
        }

        if ($itens === []) {
            return $this->error(422, 'Adicione pelo menos um item na nota.');
        }

        if ($parcelas === []) {
            return $this->error(422, 'Informe pelo menos uma parcela para a nota.');
        }

        if ($condicaoPagamento === 'vista' && count($parcelas) !== 1) {
            return $this->error(422, 'Compras a vista devem ter exatamente uma parcela.');
        }

        if ($valorFrete < 0 || $valorDesconto < 0 || $valorOutrasDespesas < 0) {
            return $this->error(422, 'Frete, desconto e outras despesas nao podem ser negativos.');
        }

        $itensNormalizados = [];
        $subtotalItens = 0.0;

        foreach ($itens as $idx => $item) {
            $itemNormalizado = $this->normalizeItem($item, $idx);
            if (isset($itemNormalizado['error'])) {
                return $itemNormalizado['error'];
            }

            if (!$this->produtoRepository->exists($itemNormalizado['id_produto'])) {
                return $this->error(422, 'Produto invalido em um dos itens da nota.');
            }

            $subtotalItens += $itemNormalizado['subtotal_item'];
            $itensNormalizados[] = $itemNormalizado;
        }

        $subtotalItens = round($subtotalItens, 2);
        $totalCalculado = round($subtotalItens + $valorFrete + $valorOutrasDespesas - $valorDesconto, 2);

        if (!$this->almostEquals($totalCalculado, $totalNotaInformado, 0.05)) {
            return [
                'status_code' => 422,
                'payload' => [
                    'status' => false,
                    'mensagem' => 'O total da nota nao confere com os itens e encargos informados.',
                    'totais_calculados' => [
                        'subtotal_itens' => $subtotalItens,
                        'valor_frete' => $valorFrete,
                        'valor_desconto' => $valorDesconto,
                        'valor_outras_despesas' => $valorOutrasDespesas,
                        'total_nota' => $totalCalculado,
                    ],
                ],
            ];
        }

        $parcelasNormalizadas = [];
        $somaParcelas = 0.0;

        foreach ($parcelas as $idx => $parcela) {
            $parcelaNormalizada = $this->normalizeParcela($parcela, $idx, count($parcelas), $totalCalculado);
            if (isset($parcelaNormalizada['error'])) {
                return $parcelaNormalizada['error'];
            }

            $somaParcelas += $parcelaNormalizada['valor'];
            $parcelasNormalizadas[] = $parcelaNormalizada;
        }

        if (!$this->almostEquals(round($somaParcelas, 2), $totalCalculado, 0.05)) {
            return $this->error(422, 'A soma das parcelas difere do total da nota.');
        }

        $cabecalho = [
            'id_fornecedor' => $fornecedorId,
            'numero_nota' => $numeroNota,
            'data_emissao' => $dataEmissao,
            'data_entrada' => $dataEntrada,
            'condicao_pagamento' => $condicaoPagamento,
            'subtotal_itens' => $subtotalItens,
            'valor_frete' => $valorFrete,
            'valor_desconto' => $valorDesconto,
            'valor_outras_despesas' => $valorOutrasDespesas,
            'total_nota' => $totalCalculado,
            'observacoes' => $observacoes === '' ? null : $observacoes,
            'id_usuario_lancamento' => (int) (auth_user_id() ?? 0) ?: null,
            'status' => 'confirmada',
        ];

        try {
            $compraId = $this->repository->create(
                $cabecalho,
                $itensNormalizados,
                $parcelasNormalizadas,
                (int) (auth_user_id() ?? 0)
            );

            return [
                'status_code' => 201,
                'payload' => [
                    'status' => true,
                    'mensagem' => 'Entrada de mercadoria salva com sucesso.',
                    'id_compra_entrada' => $compraId,
                ],
            ];
        } catch (Throwable $e) {
            error_log('Erro ao salvar compra/entrada: ' . $e->getMessage());
            return $this->error(500, 'Erro ao salvar a entrada de mercadoria.');
        }
    }

    private function normalizeItem(array $item, int $idx): array
    {
        $produtoId = (int) ($item['id_produto'] ?? 0);
        $descricao = trim((string) ($item['descricao'] ?? ''));
        $quantidadeTotal = round($this->toDecimal($item['quantidade_total'] ?? 0), 3);
        $quantidadeLoja = round($this->toDecimal($item['quantidade_loja'] ?? 0), 3);
        $quantidadeEstoqueAuxiliar = round($this->toDecimal($item['quantidade_estoque_auxiliar'] ?? 0), 3);
        $custoUnitario = round($this->toDecimal($item['custo_unitario'] ?? 0), 4);
        $subtotalItemInformado = round($this->toDecimal($item['subtotal_item'] ?? 0), 2);

        if ($produtoId <= 0) {
            return ['error' => $this->error(422, 'Produto invalido no item ' . ($idx + 1) . '.')];
        }

        if ($quantidadeTotal <= 0) {
            return ['error' => $this->error(422, 'Quantidade invalida no item ' . ($idx + 1) . '.')];
        }

        if ($quantidadeLoja < 0 || $quantidadeEstoqueAuxiliar < 0) {
            return ['error' => $this->error(422, 'Distribuicao invalida no item ' . ($idx + 1) . '.')];
        }

        if (!$this->almostEquals($quantidadeLoja + $quantidadeEstoqueAuxiliar, $quantidadeTotal, 0.001)) {
            return ['error' => $this->error(422, 'A soma Loja + Estoque Auxiliar deve bater com a quantidade total do item ' . ($idx + 1) . '.')];
        }

        if ($custoUnitario <= 0) {
            return ['error' => $this->error(422, 'Custo unitario invalido no item ' . ($idx + 1) . '.')];
        }

        $subtotalCalculado = round($quantidadeTotal * $custoUnitario, 2);
        if (!$this->almostEquals($subtotalCalculado, $subtotalItemInformado, 0.05)) {
            return ['error' => $this->error(422, 'Subtotal inconsistente no item ' . ($idx + 1) . '.')];
        }

        return [
            'id_produto' => $produtoId,
            'descricao_snapshot' => $descricao !== '' ? $descricao : 'Produto #' . $produtoId,
            'quantidade_total' => $quantidadeTotal,
            'quantidade_loja' => $quantidadeLoja,
            'quantidade_estoque_auxiliar' => $quantidadeEstoqueAuxiliar,
            'custo_unitario' => $custoUnitario,
            'subtotal_item' => $subtotalCalculado,
        ];
    }

    private function normalizeParcela(array $parcela, int $idx, int $qtdParcelas, float $totalNota): array
    {
        $numeroParcela = (int) ($parcela['numero_parcela'] ?? ($idx + 1));
        $vencimento = trim((string) ($parcela['vencimento'] ?? ''));
        $valor = round($this->toDecimal($parcela['valor'] ?? 0), 2);
        $tipoPagamento = trim((string) ($parcela['tipo_pagamento'] ?? 'Boleto'));

        if ($numeroParcela <= 0) {
            return ['error' => $this->error(422, 'Numero de parcela invalido.')];
        }

        if (!$this->isValidDate($vencimento)) {
            return ['error' => $this->error(422, 'Vencimento invalido na parcela ' . $numeroParcela . '.')];
        }

        if ($valor <= 0) {
            return ['error' => $this->error(422, 'Valor invalido na parcela ' . $numeroParcela . '.')];
        }

        if ($tipoPagamento === '') {
            $tipoPagamento = 'Boleto';
        }

        return [
            'numero_parcela' => $numeroParcela,
            'vencimento' => $vencimento,
            'valor' => $valor,
            'tipo_pagamento' => $tipoPagamento,
            'qtd_parcelas' => max($qtdParcelas, 1),
            'total_parcelas' => $totalNota,
        ];
    }

    private function error(int $statusCode, string $mensagem): array
    {
        return [
            'status_code' => $statusCode,
            'payload' => [
                'status' => false,
                'mensagem' => $mensagem,
            ],
        ];
    }

    private function toDecimal($value): float
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

    private function isValidDate(string $value): bool
    {
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value;
    }

    private function almostEquals(float $left, float $right, float $tolerance = 0.01): bool
    {
        return abs($left - $right) <= $tolerance;
    }
}
