<?php

namespace App\Services;

use App\Repositories\ClienteRepository;
use App\Repositories\ProdutoRepository;
use App\Repositories\VendaRepository;
use Throwable;

class VendaService
{
    private $vendaRepository;
    private $clienteRepository;
    private $produtoRepository;

    public function __construct(
        VendaRepository $vendaRepository,
        ClienteRepository $clienteRepository,
        ProdutoRepository $produtoRepository
    ) {
        $this->vendaRepository = $vendaRepository;
        $this->clienteRepository = $clienteRepository;
        $this->produtoRepository = $produtoRepository;
    }

    public function buildFormData()
    {
        return [
            'clientes' => $this->clienteRepository->listForSales(),
            'produtos' => $this->produtoRepository->listForSales(),
        ];
    }

    public function createFromPayload(array $dados)
    {
        $clienteId = (int) ($dados['cliente_id'] ?? 0);
        $condicaoPagamento = trim((string) ($dados['condicao_pagamento'] ?? 'vista'));
        $itens = is_array($dados['itens'] ?? null) ? $dados['itens'] : [];
        $parcelas = is_array($dados['parcelas'] ?? null) ? $dados['parcelas'] : [];

        if ($clienteId <= 0) {
            return $this->error(422, 'Cliente inválido para a venda.');
        }

        if (!in_array($condicaoPagamento, ['vista', 'parcelado'], true)) {
            return $this->error(422, 'Condição de pagamento inválida.');
        }

        if (!$itens) {
            return $this->error(422, 'A venda deve ter pelo menos um item.');
        }

        if (!$parcelas) {
            return $this->error(422, 'A venda deve ter pelo menos uma parcela.');
        }

        if (!$this->clienteRepository->exists($clienteId)) {
            return $this->error(422, 'Cliente informado não existe.');
        }

        $itensNormalizados = [];
        $subtotalCalculado = 0.0;
        $descontoCalculado = 0.0;
        $freteCalculado = 0.0;

        foreach ($itens as $idx => $item) {
            $itemNormalizado = $this->normalizeItem($item, $idx);
            if (isset($itemNormalizado['error'])) {
                return $itemNormalizado['error'];
            }

            if (!$this->produtoRepository->exists($itemNormalizado['produto_id'])) {
                return $this->error(422, 'Produto inválido na venda: ' . $itemNormalizado['produto_id']);
            }

            $subtotalCalculado += $itemNormalizado['subtotal_item'];
            $descontoCalculado += $itemNormalizado['desconto_valor'];
            $freteCalculado += $itemNormalizado['frete_valor'];

            unset($itemNormalizado['subtotal_item']);
            $itensNormalizados[] = $itemNormalizado;
        }

        $totalCalculado = round($subtotalCalculado - $descontoCalculado + $freteCalculado, 2);

        if (
            !$this->almostEquals($subtotalCalculado, $this->toDecimal($dados['subtotal'] ?? 0)) ||
            !$this->almostEquals($descontoCalculado, $this->toDecimal($dados['desconto_total'] ?? 0)) ||
            !$this->almostEquals($freteCalculado, $this->toDecimal($dados['frete_total'] ?? 0)) ||
            !$this->almostEquals($totalCalculado, $this->toDecimal($dados['total_geral'] ?? 0))
        ) {
            return [
                'status_code' => 422,
                'payload' => [
                    'status' => false,
                    'mensagem' => 'Totais inconsistentes no payload.',
                    'totais_calculados' => [
                        'subtotal' => round($subtotalCalculado, 2),
                        'desconto_total' => round($descontoCalculado, 2),
                        'frete_total' => round($freteCalculado, 2),
                        'total_geral' => round($totalCalculado, 2),
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
            return $this->error(422, 'A soma das parcelas difere do total da venda.');
        }

        try {
            $vendaId = $this->vendaRepository->create([
                'cliente_id' => $clienteId,
                'subtotal' => round($subtotalCalculado, 2),
                'desconto_total' => round($descontoCalculado, 2),
                'frete_total' => round($freteCalculado, 2),
                'total_geral' => $totalCalculado,
                'condicao_pagamento' => $condicaoPagamento,
            ], $itensNormalizados, $parcelasNormalizadas);

            return [
                'status_code' => 200,
                'payload' => [
                    'status' => true,
                    'mensagem' => 'Venda salva com sucesso.',
                    'id_venda' => $vendaId,
                ],
            ];
        } catch (Throwable $e) {
            error_log('Erro ao salvar venda: ' . $e->getMessage());
            return $this->error(500, 'Erro ao salvar venda.');
        }
    }

    private function normalizeItem(array $item, $idx)
    {
        $produtoId = (int) ($item['produto_id'] ?? 0);
        $quantidade = $this->toDecimal($item['quantidade'] ?? 0);
        $valorUnitario = $this->toDecimal($item['valor_unitario'] ?? 0);
        $descontoItem = $this->toDecimal($item['desconto_valor'] ?? 0);
        $freteItem = $this->toDecimal($item['frete_valor'] ?? 0);

        if ($produtoId <= 0 || $quantidade <= 0 || $valorUnitario < 0 || $descontoItem < 0 || $freteItem < 0) {
            return ['error' => $this->error(422, 'Item inválido na posição ' . ($idx + 1) . '.')];
        }

        $subtotalItem = round($quantidade * $valorUnitario, 2);
        if ($descontoItem > $subtotalItem) {
            return ['error' => $this->error(422, 'Desconto maior que subtotal no item ' . ($idx + 1) . '.')];
        }

        return [
            'produto_id' => $produtoId,
            'quantidade' => $quantidade,
            'valor_unitario' => $valorUnitario,
            'desconto_valor' => $descontoItem,
            'frete_valor' => $freteItem,
            'total_item' => round($subtotalItem - $descontoItem + $freteItem, 2),
            'subtotal_item' => $subtotalItem,
        ];
    }

    private function normalizeParcela(array $parcela, $idx, $totalParcelas, $totalVenda)
    {
        $numeroParcela = (int) ($parcela['numero_parcela'] ?? ($idx + 1));
        $vencimento = trim((string) ($parcela['vencimento'] ?? ''));
        $valorParcela = $this->toDecimal($parcela['valor'] ?? 0);
        $tipoPagamento = trim((string) ($parcela['tipo_pagamento'] ?? 'PIX'));
        $qtdParcelas = max(1, (int) ($parcela['qtd_parcelas'] ?? $totalParcelas));
        $totalParcelasInformado = $this->toDecimal($parcela['total_parcelas'] ?? $totalVenda);

        if ($numeroParcela <= 0 || $valorParcela < 0 || $tipoPagamento === '' || $vencimento === '') {
            return ['error' => $this->error(422, 'Parcela inválida na posição ' . ($idx + 1) . '.')];
        }

        $data = \DateTime::createFromFormat('Y-m-d', $vencimento);
        if (!$data || $data->format('Y-m-d') !== $vencimento) {
            return ['error' => $this->error(422, 'Data de vencimento inválida na parcela ' . ($idx + 1) . '.')];
        }

        return [
            'numero_parcela' => $numeroParcela,
            'vencimento' => $vencimento,
            'valor' => $valorParcela,
            'tipo_pagamento' => $tipoPagamento,
            'qtd_parcelas' => $qtdParcelas,
            'total_parcelas' => $totalParcelasInformado,
        ];
    }

    private function toDecimal($valor)
    {
        if (is_float($valor) || is_int($valor)) {
            return (float) $valor;
        }

        $texto = trim((string) $valor);
        if ($texto === '') {
            return 0.0;
        }

        if (strpos($texto, ',') !== false) {
            $texto = str_replace('.', '', $texto);
            $texto = str_replace(',', '.', $texto);
        }

        return is_numeric($texto) ? (float) $texto : 0.0;
    }

    private function almostEquals($a, $b, $tolerance = 0.02)
    {
        return abs($a - $b) <= $tolerance;
    }

    private function error($statusCode, $message)
    {
        return [
            'status_code' => (int) $statusCode,
            'payload' => [
                'status' => false,
                'mensagem' => $message,
            ],
        ];
    }
}
