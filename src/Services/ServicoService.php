<?php

namespace App\Services;

use App\Repositories\ServicoRepository;
use PDO;
use Throwable;

class ServicoService
{
    private $pdo;
    private $servicoRepository;

    public function __construct(PDO $pdo, ServicoRepository $servicoRepository)
    {
        $this->pdo = $pdo;
        $this->servicoRepository = $servicoRepository;
    }

    public function createFromPayload(array $dados): array
    {
        $clienteId = (int) ($dados['cliente_id'] ?? 0);
        $condicaoPagamento = trim((string) ($dados['condicao_pagamento'] ?? 'vista'));
        $itensProduto = is_array($dados['itens_produto'] ?? null) ? $dados['itens_produto'] : [];
        $itensMicroservico = is_array($dados['itens_microservico'] ?? null) ? $dados['itens_microservico'] : [];
        $parcelas = is_array($dados['parcelas'] ?? null) ? $dados['parcelas'] : [];

        if ($clienteId <= 0) {
            return $this->error(422, 'Cliente inválido para o serviço.');
        }

        if (!in_array($condicaoPagamento, ['vista', 'parcelado'], true)) {
            return $this->error(422, 'Condição de pagamento inválida para o serviço.');
        }

        if (!$itensProduto && !$itensMicroservico) {
            return $this->error(422, 'O serviço deve ter pelo menos um item de produto ou microserviço.');
        }

        if (!$parcelas) {
            return $this->error(422, 'O serviço deve ter pelo menos uma parcela.');
        }

        $produtosNormalizados = [];
        $subtotalProdutosCalculado = 0.0;
        foreach ($itensProduto as $idx => $item) {
            $normalizado = $this->normalizeItemProduto($item, $idx);
            if (isset($normalizado['error'])) {
                return $normalizado['error'];
            }

            $subtotalProdutosCalculado += $normalizado['subtotal_item'];
            unset($normalizado['subtotal_item']);
            $produtosNormalizados[] = $normalizado;
        }

        $microservicosNormalizados = [];
        $subtotalMicroservicosCalculado = 0.0;
        foreach ($itensMicroservico as $idx => $item) {
            $normalizado = $this->normalizeItemMicroservico($item, $idx);
            if (isset($normalizado['error'])) {
                return $normalizado['error'];
            }

            $subtotalMicroservicosCalculado += $normalizado['subtotal_item'];
            unset($normalizado['subtotal_item']);
            $microservicosNormalizados[] = $normalizado;
        }

        $descontoTotalCalculado = array_reduce(
            array_merge($produtosNormalizados, $microservicosNormalizados),
            static function (float $acc, array $item): float {
                return $acc + (float) $item['desconto_valor'];
            },
            0.0
        );

        $freteTotalInformado = round($this->toDecimal($dados['frete_total'] ?? 0), 2);
        if ($freteTotalInformado < 0) {
            return $this->error(422, 'Frete total inválido no serviço.');
        }

        $totalCalculado = round(
            $subtotalProdutosCalculado + $subtotalMicroservicosCalculado - $descontoTotalCalculado + $freteTotalInformado,
            2
        );

        if (
            !$this->almostEquals($subtotalProdutosCalculado, $this->toDecimal($dados['subtotal_produtos'] ?? 0)) ||
            !$this->almostEquals($subtotalMicroservicosCalculado, $this->toDecimal($dados['subtotal_microservicos'] ?? 0)) ||
            !$this->almostEquals($descontoTotalCalculado, $this->toDecimal($dados['desconto_total'] ?? 0)) ||
            !$this->almostEquals($totalCalculado, $this->toDecimal($dados['total_geral'] ?? 0))
        ) {
            return [
                'status_code' => 422,
                'payload' => [
                    'status' => false,
                    'mensagem' => 'Totais inconsistentes no payload de serviço.',
                    'totais_calculados' => [
                        'subtotal_produtos' => round($subtotalProdutosCalculado, 2),
                        'subtotal_microservicos' => round($subtotalMicroservicosCalculado, 2),
                        'desconto_total' => round($descontoTotalCalculado, 2),
                        'frete_total' => $freteTotalInformado,
                        'total_geral' => $totalCalculado,
                    ],
                ],
            ];
        }

        $parcelasNormalizadas = [];
        $somaParcelas = 0.0;
        foreach ($parcelas as $idx => $parcela) {
            $normalizada = $this->normalizeParcela($parcela, $idx, count($parcelas), $totalCalculado);
            if (isset($normalizada['error'])) {
                return $normalizada['error'];
            }

            $somaParcelas += $normalizada['valor'];
            $parcelasNormalizadas[] = $normalizada;
        }

        if (!$this->almostEquals(round($somaParcelas, 2), $totalCalculado, 0.05)) {
            return $this->error(422, 'A soma das parcelas difere do total do serviço.');
        }

        $startedTransaction = false;
        try {
            $startedTransaction = !$this->pdo->inTransaction();
            if ($startedTransaction) {
                $this->pdo->beginTransaction();
            }

            $servico = [
                'cliente_id' => $clienteId,
                'condicao_pagamento' => $condicaoPagamento,
                'subtotal_produtos' => round($subtotalProdutosCalculado, 2),
                'subtotal_microservicos' => round($subtotalMicroservicosCalculado, 2),
                'desconto_total' => round($descontoTotalCalculado, 2),
                'frete_total' => $freteTotalInformado,
                'total_geral' => $totalCalculado,
            ];

            $servicoId = $this->servicoRepository->createServico($servico);

            foreach ($produtosNormalizados as $item) {
                $this->servicoRepository->createServicoProduto($servicoId, $item);
            }

            foreach ($microservicosNormalizados as $item) {
                $this->servicoRepository->createServicoMicroservico($servicoId, $item);
            }

            foreach ($parcelasNormalizadas as $parcela) {
                $this->servicoRepository->createServicoParcela($servicoId, $parcela);
            }

            $this->servicoRepository->logCreate(
                $servicoId,
                $servico,
                $produtosNormalizados,
                $microservicosNormalizados,
                $parcelasNormalizadas
            );

            if ($startedTransaction && $this->pdo->inTransaction()) {
                $this->pdo->commit();
            }

            return [
                'status_code' => 200,
                'payload' => [
                    'status' => true,
                    'mensagem' => 'Serviço salvo com sucesso.',
                    'id_servico' => $servicoId,
                    'cliente_id' => $clienteId,
                ],
            ];
        } catch (Throwable $e) {
            if ($startedTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log('Erro ao salvar serviço: ' . $e->getMessage());
            return $this->error(500, 'Erro ao salvar serviço.');
        }
    }

    private function normalizeItemProduto(array $item, int $idx): array
    {
        $produtoId = (int) ($item['produto_id'] ?? 0);
        if ($produtoId <= 0) {
            return ['error' => $this->error(422, 'Produto inválido no item #' . ($idx + 1) . ' do serviço.')];
        }

        $base = $this->normalizeItemComercial($item, $idx, 'produto');
        if (isset($base['error'])) {
            return $base;
        }

        $base['produto_id'] = $produtoId;
        return $base;
    }

    private function normalizeItemMicroservico(array $item, int $idx): array
    {
        return $this->normalizeItemComercial($item, $idx, 'microserviço');
    }

    private function normalizeItemComercial(array $item, int $idx, string $tipo): array
    {
        $descricao = trim((string) ($item['descricao'] ?? ''));
        $quantidade = $this->toDecimal($item['quantidade'] ?? 0);
        $valorUnitario = $this->toDecimal($item['valor_unitario'] ?? 0);
        $descontoValor = round($this->toDecimal($item['desconto_valor'] ?? 0), 2);
        $freteValor = round($this->toDecimal($item['frete_valor'] ?? 0), 2);
        $totalItem = round($this->toDecimal($item['total_item'] ?? 0), 2);

        if ($descricao === '') {
            return ['error' => $this->error(422, 'Descrição obrigatória para o item #' . ($idx + 1) . ' (' . $tipo . ').')];
        }

        if ($quantidade <= 0 || $valorUnitario < 0 || $descontoValor < 0 || $freteValor < 0) {
            return ['error' => $this->error(422, 'Valores inválidos no item #' . ($idx + 1) . ' (' . $tipo . ').')];
        }

        $subtotalItem = round($quantidade * $valorUnitario, 2);
        $totalCalculado = round($subtotalItem - $descontoValor + $freteValor, 2);

        if (!$this->almostEquals($totalCalculado, $totalItem)) {
            return ['error' => $this->error(422, 'Total inconsistente no item #' . ($idx + 1) . ' (' . $tipo . ').')];
        }

        return [
            'descricao' => $descricao,
            'quantidade' => $quantidade,
            'valor_unitario' => round($valorUnitario, 2),
            'desconto_valor' => $descontoValor,
            'frete_valor' => $freteValor,
            'total_item' => $totalItem,
            'subtotal_item' => $subtotalItem,
        ];
    }

    private function normalizeParcela(array $parcela, int $idx, int $totalParcelas, float $totalGeral): array
    {
        $numeroParcela = (int) ($parcela['numero_parcela'] ?? ($idx + 1));
        $vencimento = trim((string) ($parcela['vencimento'] ?? ''));
        $valor = round($this->toDecimal($parcela['valor'] ?? $parcela['valor_parcela'] ?? 0), 2);
        $tipoPagamento = trim((string) ($parcela['tipo_pagamento'] ?? $parcela['tipo'] ?? 'PIX'));
        $qtdParcelas = (int) ($parcela['qtd_parcelas'] ?? $totalParcelas);
        $totalParcelasInformado = round($this->toDecimal($parcela['total_parcelas'] ?? $totalGeral), 2);

        if ($numeroParcela <= 0 || $valor <= 0 || $vencimento === '') {
            return ['error' => $this->error(422, 'Parcela inválida na posição #' . ($idx + 1) . '.')];
        }

        if (!$this->isValidDate($vencimento)) {
            return ['error' => $this->error(422, 'Data de vencimento inválida na parcela #' . ($idx + 1) . '.')];
        }

        return [
            'numero_parcela' => $numeroParcela,
            'vencimento' => $vencimento,
            'valor' => $valor,
            'tipo_pagamento' => $tipoPagamento === '' ? 'PIX' : $tipoPagamento,
            'qtd_parcelas' => $qtdParcelas > 0 ? $qtdParcelas : $totalParcelas,
            'total_parcelas' => $totalParcelasInformado,
        ];
    }

    private function isValidDate(string $date): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $date);
        return $dt instanceof \DateTime && $dt->format('Y-m-d') === $date;
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

    private function almostEquals(float $a, float $b, float $epsilon = 0.01): bool
    {
        return abs($a - $b) <= $epsilon;
    }

    private function error(int $statusCode, string $message): array
    {
        return [
            'status_code' => $statusCode,
            'payload' => [
                'status' => false,
                'mensagem' => $message,
            ],
        ];
    }
}
