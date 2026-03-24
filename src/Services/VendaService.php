<?php

namespace App\Services;

use App\Repositories\ClienteRepository;
use App\Repositories\ProdutoRepository;
use App\Repositories\VendaRepository;
use PDO;
use Throwable;

class VendaService
{
    private $vendaRepository;
    private $clienteRepository;
    private $produtoRepository;
    private $pdo;

    public function __construct(
        PDO $pdo,
        VendaRepository $vendaRepository,
        ClienteRepository $clienteRepository,
        ProdutoRepository $produtoRepository
    ) {
        $this->pdo = $pdo;
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
        $vendaId = (int) ($dados['id_venda'] ?? 0);
        $clienteId = (int) ($dados['cliente_id'] ?? 0);
        $dataVenda = trim((string) ($dados['data_venda'] ?? date('Y-m-d')));
        $condicaoPagamento = trim((string) ($dados['condicao_pagamento'] ?? 'vista'));
        $itens = is_array($dados['itens'] ?? null) ? $dados['itens'] : [];
        $parcelas = is_array($dados['parcelas'] ?? null) ? $dados['parcelas'] : [];
        $clienteResolucao = trim((string) ($dados['cliente_resolucao'] ?? 'manter'));
        $clientePayload = is_array($dados['cliente'] ?? null) ? $dados['cliente'] : null;
        $validarConsistenciaCliente = (bool) ($dados['validar_cliente_consistencia'] ?? false);

        if ($clienteId <= 0) {
            return $this->error(422, 'Cliente inválido para a venda.');
        }

        if (!in_array($condicaoPagamento, ['vista', 'parcelado'], true)) {
            return $this->error(422, 'Condição de pagamento inválida.');
        }

        if (!$this->isValidDate($dataVenda)) {
            return $this->error(422, 'Data da venda invÃ¡lida.');
        }

        if (!$itens) {
            return $this->error(422, 'A venda deve ter pelo menos um item.');
        }

        if (!$parcelas) {
            return $this->error(422, 'A venda deve ter pelo menos uma parcela.');
        }

        $itensNormalizados = [];
        $subtotalCalculado = 0.0;
        $descontoCalculado = 0.0;
        $freteItensCalculado = 0.0;

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
            $freteItensCalculado += $itemNormalizado['frete_valor'];

            unset($itemNormalizado['subtotal_item']);
            $itensNormalizados[] = $itemNormalizado;
        }

        $freteInformado = round($this->toDecimal($dados['frete_total'] ?? 0), 2);
        if ($freteInformado < 0) {
            return $this->error(422, 'Frete total invalido.');
        }

        $totalCalculado = round($subtotalCalculado - $descontoCalculado + $freteInformado, 2);

        if (
            !$this->almostEquals($subtotalCalculado, $this->toDecimal($dados['subtotal'] ?? 0)) ||
            !$this->almostEquals($descontoCalculado, $this->toDecimal($dados['desconto_total'] ?? 0)) ||
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
                            'frete_itens' => round($freteItensCalculado, 2),
                            'frete_total' => $freteInformado,
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

        $startedTransaction = false;
        try {
            $startedTransaction = !$this->pdo->inTransaction();
            if ($startedTransaction) {
                $this->pdo->beginTransaction();
            }

            $resolucaoCliente = $this->resolverClienteParaVenda(
                $clienteId,
                $clienteResolucao,
                $clientePayload,
                $validarConsistenciaCliente
            );

            if (isset($resolucaoCliente['error'])) {
                if ($startedTransaction && $this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                return $resolucaoCliente['error'];
            }

            $clienteId = $resolucaoCliente['cliente_id'];

            $vendaPersistida = [
                'cliente_id' => $clienteId,
                'data_venda' => $dataVenda,
                'subtotal' => round($subtotalCalculado, 2),
                'desconto_total' => round($descontoCalculado, 2),
                'frete_total' => $freteInformado,
                'total_geral' => $totalCalculado,
                'condicao_pagamento' => $condicaoPagamento,
            ];

            if ($vendaId > 0) {
                $vendaId = $this->vendaRepository->update($vendaId, $vendaPersistida, $itensNormalizados, $parcelasNormalizadas);
            } else {
                $vendaId = $this->vendaRepository->create($vendaPersistida, $itensNormalizados, $parcelasNormalizadas);
            }

            if ($startedTransaction && $this->pdo->inTransaction()) {
                $this->pdo->commit();
            }

            return [
                'status_code' => 200,
                'payload' => [
                    'status' => true,
                    'mensagem' => 'Venda salva com sucesso.',
                    'id_venda' => $vendaId,
                    'cliente_id' => $clienteId,
                ],
            ];
        } catch (Throwable $e) {
            if ($startedTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('Erro ao salvar venda: ' . $e->getMessage());
            return $this->error(500, 'Erro ao salvar venda.');
        }
    }

    private function resolverClienteParaVenda($clienteId, $clienteResolucao, ?array $clientePayload, $validarConsistencia)
    {
        if (!in_array($clienteResolucao, ['manter', 'atualizar', 'novo'], true)) {
            return ['error' => $this->error(422, 'Valor inválido para cliente_resolucao.')];
        }

        if ($clienteResolucao !== 'novo' && !$this->clienteRepository->exists($clienteId)) {
            return ['error' => $this->error(422, 'Cliente informado não existe.')];
        }

        if ($clientePayload === null) {
            if ($clienteResolucao !== 'manter') {
                return ['error' => $this->error(422, 'Dados do cliente são obrigatórios para a resolução solicitada.')];
            }

            return ['cliente_id' => $clienteId];
        }

        $clienteNormalizado = $this->normalizarClientePayload($clientePayload);
        if ($clienteNormalizado['nome_cliente'] === '' || $clienteNormalizado['telefone_contato'] === '') {
            return ['error' => $this->error(422, 'Nome e telefone do cliente são obrigatórios.')];
        }

        if ($clienteResolucao === 'novo') {
            $novoClienteId = $this->clienteRepository->create($clienteNormalizado);
            return ['cliente_id' => $novoClienteId];
        }

        $clienteBase = $this->clienteRepository->findById($clienteId);
        if (!$clienteBase) {
            return ['error' => $this->error(422, 'Cliente informado não existe.')];
        }

        $divergencias = $this->compararClienteBaseComPayload($clienteBase, $clienteNormalizado);
        if ($clienteResolucao === 'atualizar') {
            $this->clienteRepository->update($clienteId, $clienteNormalizado);
            return ['cliente_id' => $clienteId];
        }

        if ($validarConsistencia && $divergencias) {
            return ['error' => $this->error(422, 'Dados do cliente divergem do cadastro base. Informe cliente_resolucao=atualizar ou novo.')];
        }

        return ['cliente_id' => $clienteId];
    }

    private function normalizarClientePayload(array $cliente)
    {
        return [
            'nome_cliente' => trim((string) ($cliente['nome'] ?? '')),
            'telefone_contato' => trim((string) ($cliente['telefone'] ?? '')),
            'cpf_cnpj' => $this->nullableDigits($cliente['cpf_cnpj'] ?? null),
            'email_contato' => $this->nullableTrim($cliente['email'] ?? null),
            'endereco' => $this->nullableTrim($cliente['endereco'] ?? null),
        ];
    }

    private function compararClienteBaseComPayload(array $clienteBase, array $clientePayload)
    {
        $baseNormalizado = [
            'nome_cliente' => trim((string) ($clienteBase['nome_cliente'] ?? '')),
            'telefone_contato' => trim((string) ($clienteBase['telefone_contato'] ?? '')),
            'cpf_cnpj' => $this->nullableDigits($clienteBase['cpf_cnpj'] ?? null),
            'email_contato' => $this->nullableTrim($clienteBase['email_contato'] ?? null),
            'endereco' => $this->nullableTrim($clienteBase['endereco'] ?? null),
        ];

        return $baseNormalizado !== $clientePayload;
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

    private function isValidDate($date)
    {
        $data = \DateTime::createFromFormat('Y-m-d', (string) $date);
        return $data && $data->format('Y-m-d') === $date;
    }

    private function nullableDigits($valor)
    {
        $digits = preg_replace('/\D+/', '', (string) $valor);
        return $digits === '' ? null : $digits;
    }

    private function nullableTrim($valor)
    {
        $texto = trim((string) $valor);
        return $texto === '' ? null : $texto;
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
