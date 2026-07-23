<?php
include '../includes/db.php';
require_login();

$hojeSaoPaulo = (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
$controller = new \App\Controllers\VendaController($pdo);
$formData = $controller->formData();
$clientes = $formData['clientes'];
$produtos = $formData['produtos'];
$vendedores = $formData['vendedores'];
$vendedorLogadoId = (int) (auth_user_id() ?? 0);
$vendedorLogadoNome = auth_user_display_name();
$vendaIdEdicao = (int) ($_GET['id'] ?? $_GET['id_venda'] ?? $_GET['venda_id'] ?? 0);
$vendaEdicaoPayload = null;

function vendas_formatar_cliente_sugestao(array $cliente): string
{
    $partes = [
        trim((string) ($cliente['nome_cliente'] ?? '')),
        trim((string) ($cliente['telefone_contato'] ?? '')),
        trim((string) ($cliente['cpf_cnpj'] ?? '')),
    ];

    return implode(' | ', array_filter($partes, static function ($parte) {
        return $parte !== '';
    }));
}

if ($vendaIdEdicao > 0) {
    action_firewall_require_grant('venda', 'edit', $vendaIdEdicao, app_url('vendas/listar.php?status=firewall'));

    $repository = new \App\Repositories\VendaRepository($pdo);
    $detalhes = $repository->findCompleteById($vendaIdEdicao);
    if (!$detalhes) {
        header('Location: ' . app_url('vendas/listar.php'));
        exit;
    }

    $venda = $detalhes['venda'] ?? [];
    $itens = is_array($detalhes['itens'] ?? null) ? $detalhes['itens'] : [];
    $parcelas = is_array($detalhes['parcelas'] ?? null) ? $detalhes['parcelas'] : [];

    $itensEdicao = [];
    foreach ($itens as $item) {
        if (!is_array($item)) {
            continue;
        }

        $itensEdicao[] = [
            'produtoId' => (int) ($item['id_produto'] ?? 0),
            'nome' => (string) ($item['produto_nome'] ?? ''),
            'origemEstoque' => isset($item['origem_estoque']) ? (string) $item['origem_estoque'] : '',
            'quantidade' => (float) ($item['quantidade'] ?? 1),
            'valorUnitario' => (float) ($item['valor_unitario'] ?? 0),
            'desconto' => (float) ($item['desconto_valor'] ?? 0),
            'freteItem' => (float) ($item['frete_valor'] ?? 0),
        ];
    }

    $parcelasEdicao = [];
    foreach ($parcelas as $parcela) {
        if (!is_array($parcela)) {
            continue;
        }

        $parcelasEdicao[] = [
            'vencimento' => (string) ($parcela['vencimento'] ?? $hojeSaoPaulo),
            'valor' => (float) ($parcela['valor_parcela'] ?? 0),
            'tipoPagamento' => (string) ($parcela['tipo_pagamento'] ?? 'PIX'),
            'manual' => true,
        ];
    }

    $vendaEdicaoPayload = [
        'id_venda' => (int) ($venda['id_venda'] ?? $vendaIdEdicao),
        'cliente_id' => (int) ($venda['id_cliente'] ?? 0),
        'vendedor_id' => (int) ($venda['vendedor_id'] ?? $vendedorLogadoId),
        'vendedor_nome' => (string) ($venda['vendedor_nome'] ?? $vendedorLogadoNome),
        'data_venda' => (string) ($venda['data_venda'] ?? $hojeSaoPaulo),
        'cliente' => [
            'nome_cliente' => (string) ($venda['nome_cliente'] ?? ''),
            'telefone_contato' => (string) ($venda['telefone_contato'] ?? ''),
            'cpf_cnpj' => (string) ($venda['cpf_cnpj'] ?? ''),
            'email_contato' => (string) ($venda['email_contato'] ?? ''),
            'endereco' => (string) ($venda['endereco'] ?? ''),
        ],
        'condicao_pagamento' => (string) ($venda['condicao_pagamento'] ?? 'vista'),
        'itens' => $itensEdicao,
        'parcelas' => $parcelasEdicao,
    ];
}

include '../includes/header.php';
?>

<div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i><?= $vendaEdicaoPayload ? 'Editar Venda' : 'Tela de Vendas' ?></h4>
        <span class="badge bg-light text-dark">Orçamento / Pedido</span>
    </div>

    <div class="card-body">
        <form id="formVenda" class="row g-3" autocomplete="off">
            <?= csrf_input() ?>
            <div class="col-12">
                <div class="card border-primary-subtle">
                    <div class="card-header bg-light sales-block-title">1) Cliente</div>
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-6">
                                <label class="form-label" for="clienteNome">Nome cliente</label>
                                <input type="text" class="form-control" id="clienteNome" list="clientesSugestoes" placeholder="Digite para buscar ou preencher manualmente...">
                                <datalist id="clientesSugestoes">
                                    <?php foreach ($clientes as $cliente): ?>
                                        <option value="<?= htmlspecialchars(vendas_formatar_cliente_sugestao($cliente)) ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="clienteTelefone">Telefone</label>
                                <input type="text" class="form-control" id="clienteTelefone" placeholder="(00) 00000-0000">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="clienteCpfCnpj">CPF/CNPJ</label>
                                <input type="text" class="form-control" id="clienteCpfCnpj" inputmode="numeric" maxlength="18" placeholder="000.000.000-00 ou 00.000.000/0000-00">
                            </div>
                        </div>
                        <div class="row g-3 align-items-end" style="margin-top: 2px;">
                            <div class="col-md-4">
                                <label class="form-label" for="clienteEmail">E-mail</label>
                                <input type="email" class="form-control" id="clienteEmail" placeholder="cliente@email.com">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label" for="clienteEndereco">Endereço</label>
                                <input type="text" class="form-control" id="clienteEndereco" placeholder="Rua, número, bairro...">
                            </div>
                        </div>
                        <div class="row g-3 align-items-end mt-1">
                            <div class="col-12 d-flex justify-content-end">
                                <button type="button" class="btn btn-outline-primary" id="btnSalvarCliente">
                                    <i class="fas fa-user-plus me-1"></i>Salvar cliente rápido
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-primary-subtle">
                    <div class="card-header bg-light sales-block-title">2) Produtos da venda</div>
                    <div class="card-body">
                        <div class="row g-2 align-items-end mb-3">
                            <div class="col-md-4">
                                <label class="form-label" for="produtoBusca">Produto</label>
                                <input type="text" class="form-control" id="produtoBusca" list="produtosSugestoes" placeholder="Digite o nome do produto..." autocomplete="off">
                                <datalist id="produtosSugestoes">
                                    <?php foreach ($produtos as $produto): ?>
                                        <option value="<?= htmlspecialchars((string) $produto['nome'] . ' (R$ ' . number_format((float) ($produto['preco1'] ?? 0), 2, ',', '.') . ')', ENT_QUOTES, 'UTF-8') ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
                                
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="produtoQtd">Qtd.</label>
                                <input type="number" min="1" step="1" class="form-control quantidade-adaptativa" id="produtoQtd" value="1">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="produtoValorUnitario">Vlr. unitário</label>
                                <input type="text" inputmode="decimal" class="form-control" id="produtoValorUnitario" value="0,00" data-money-input>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="produtoOrigemEstoque">Origem estoque</label>
                                <select class="form-select" id="produtoOrigemEstoque">
                                    <option value="loja" selected>Loja</option>
                                    <option value="estoque_auxiliar">Estoque Auxiliar</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-grid">
                                <button type="button" class="btn btn-success" id="btnAdicionarProduto">
                                    <i class="fas fa-plus me-1"></i>Adicionar item
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle" id="itensTable">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Item</th>
                                        <th>Produto</th>
                                        <th>Origem estoque</th>
                                        <th>Qtd.</th>
                                        <th>Vlr. unitário</th>
                                        <th>Vlr. total</th>
                                        <th>Desconto</th>
                                        <th>Vlr. unit. c/ desc</th>
                                        <th>Vlr. total c/ desc</th>
                                        <th>Frete item</th>
                                        <th>Total do item</th>
                                        <th>Ação</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card border-primary-subtle h-100">
                    <div class="card-header bg-light sales-block-title">3) Composição (frete e descontos)</div>
                    <div class="card-body">
                        <div class="resumo-card">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-6">
                                    <label class="form-label" for="freteTotalInput">Frete total</label>
                                    <input type="text" inputmode="decimal" class="form-control" id="freteTotalInput" value="0,00" data-money-input>
                                </div>
                                <div class="col-md-6 form-check mt-4 ps-5">
                                    <input class="form-check-input" type="checkbox" id="freteManualCheck">
                                    <label class="form-check-label" for="freteManualCheck">
                                        Informar frete manual (sobrescrever soma dos itens)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="resumo-card">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label" for="descontoTotalInput">Desconto total (R$)</label>
                                    <input type="text" inputmode="decimal" class="form-control" id="descontoTotalInput" value="0,00" data-money-input>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="descontoPercentInput">ou Desconto (%)</label>
                                    <input type="text" inputmode="decimal" class="form-control" id="descontoPercentInput" value="0,00">
                                </div>
                                <div class="col-md-4 d-grid">
                                    <button type="button" id="btnZerarDescontos" class="btn btn-outline-danger">
                                        <i class="fas fa-eraser me-1"></i>Zerar descontos
                                    </button>
                                </div>
                            </div>
                            <div class="context-tip mt-2">
                                Se houver itens com desconto preenchido, o rateio respeita esses itens. Caso contrário, distribui proporcionalmente pelo valor dos itens.
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <span>Subtotal produtos:</span>
                            <strong id="subtotalProdutos">R$ 0,00</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Total descontos:</span>
                            <strong id="totalDescontos">R$ 0,00</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Total frete:</span>
                            <strong id="totalFrete">R$ 0,00</strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Total geral da venda:</span>
                            <span class="total-pill" id="totalGeralVenda">R$ 0,00</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-primary-subtle h-100">
                    <div class="card-header bg-light sales-block-title">Forma e condição de pagamento</div>
                    <div class="card-body">
                        <div class="row g-2 mb-3 align-items-end">
                            <div class="col-md-4">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="usarDataRetroativaVenda">
                                    <label class="form-check-label" for="usarDataRetroativaVenda">Ativar data retroativa</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="dataRetroativaVenda">Data da venda</label>
                                <input type="date" id="dataRetroativaVenda" class="form-control" value="<?= htmlspecialchars($hojeSaoPaulo, ENT_QUOTES, 'UTF-8') ?>" max="<?= htmlspecialchars($hojeSaoPaulo, ENT_QUOTES, 'UTF-8') ?>" disabled>
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="form-label" for="condicaoPagamento">Condição</label>
                                <select id="condicaoPagamento" class="form-select">
                                    <option value="vista">À vista</option>
                                    <option value="parcelado">Parcelado</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="qtdParcelas">Qtd. parcelas</label>
                                <input type="number" id="qtdParcelas" class="form-control" min="1" max="24" value="1">
                            </div>
                        </div>

                        <p class="context-tip mb-2">No modo parcelado, clique com o botão direito na tabela para adicionar parcela; clique duplo com o botão esquerdo em uma linha remove.</p>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="parcelasTable">
                                <thead class="table-secondary">
                                    <tr>
                                        <th>#</th>
                                        <th>Vencimento</th>
                                        <th>Valor</th>
                                        <th>%</th>
                                        <th>Tipo Pagamento</th>
                                        <th>Qtd. Parcelas</th>
                                        <th>Total Parcelas</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <label class="form-label" for="vendedorSelect">Vendedor responsavel</label>
                            <select id="vendedorSelect" class="form-select">
                                <option value="">Selecione um vendedor...</option>
                                <?php foreach ($vendedores as $vendedor): ?>
                                    <option
                                        value="<?= (int) $vendedor['id_usuario'] ?>"
                                        data-nome="<?= htmlspecialchars((string) $vendedor['nome_exibicao'], ENT_QUOTES, 'UTF-8') ?>"
                                        <?= (int) $vendedor['id_usuario'] === $vendedorLogadoId ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars((string) $vendedor['nome_exibicao']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div id="vendaFeedback" class="alert d-none" role="alert"></div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="button" class="btn btn-outline-secondary" id="btnLimparVenda">
                        <i class="fas fa-broom me-1"></i>Limpar campos
                    </button>
                    <button type="button" class="btn btn-primary" id="btnSalvarVenda">
                        <i class="fas fa-save me-1"></i>Salvar venda
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="<?= app_url('assets/js/br_input_masks.js'); ?>"></script>
<script src="../assets/js/composicao_comercial.js"></script>
<script>
    const hojeSP = '<?= $hojeSaoPaulo ?>';
    const csrfToken = '<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>';
    const vendaEdicaoData = <?= json_encode($vendaEdicaoPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const vendedorLogadoPadrao = {
        id: <?= json_encode($vendedorLogadoId, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        nome: <?= json_encode($vendedorLogadoNome, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
    };
    const tiposPagamento = [
        'PIX', 'Dinheiro', 'Boleto', 'Cheque', 'Pix Pague Seguro',
        'Débito PagSeguro', 'Crédito PagSeguro', 'Débito Stone',
        'Crédito Stone', 'Débito Infinite', 'Crédito Infinite'
    ];
    const clientesData = <?= json_encode($clientes, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const produtosData = <?= json_encode($produtos, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    const formVenda = document.getElementById('formVenda');
    const feedbackBox = document.getElementById('vendaFeedback');
    const btnSalvarVenda = document.getElementById('btnSalvarVenda');
    const btnLimparVenda = document.getElementById('btnLimparVenda');
    const btnSalvarCliente = document.getElementById('btnSalvarCliente');
    const clienteNome = document.getElementById('clienteNome');
    const clienteTelefone = document.getElementById('clienteTelefone');
    const clienteCpfCnpj = document.getElementById('clienteCpfCnpj');
    const clienteEmail = document.getElementById('clienteEmail');
    const clienteEndereco = document.getElementById('clienteEndereco');
    const clientesSugestoes = document.getElementById('clientesSugestoes');
    const produtoBusca = document.getElementById('produtoBusca');
    const produtosSugestoes = document.getElementById('produtosSugestoes');
    const produtoQtd = document.getElementById('produtoQtd');
    const produtoValorUnitario = document.getElementById('produtoValorUnitario');
    const produtoOrigemEstoque = document.getElementById('produtoOrigemEstoque');
    const btnAdicionarProduto = document.getElementById('btnAdicionarProduto');
    const vendedorSelect = document.getElementById('vendedorSelect');
    const usarDataRetroativaVenda = document.getElementById('usarDataRetroativaVenda');
    const dataRetroativaVenda = document.getElementById('dataRetroativaVenda');
    const maskHelpers = window.PiscinarMasks || {};
    let clienteSelecionadoId = null;
    let produtoSelecionadoId = null;



    function normalizarDigitos(value, maxLength = 14) {
        if (typeof maskHelpers.onlyDigits === 'function') {
            return maskHelpers.onlyDigits(value, maxLength);
        }

        return String(value ?? '').replace(/\D+/g, '').slice(0, maxLength);
    }

    function formatarCpfCnpj(value) {
        if (typeof maskHelpers.formatCpfCnpj === 'function') {
            return maskHelpers.formatCpfCnpj(value);
        }

        const digits = normalizarDigitos(value, 14);
        if (digits.length <= 11) {
            return digits
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        }

        return digits
            .replace(/^(\d{2})(\d)/, '$1.$2')
            .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d)/, '.$1/$2')
            .replace(/(\d{4})(\d)/, '$1-$2');
    }

    function aplicarMascaraCpfCnpj() {
        clienteCpfCnpj.value = formatarCpfCnpj(clienteCpfCnpj.value);
    }

    function formatarDecimalInput(valor) {
        return Number(valor || 0).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    const composicao = window.ComposicaoComercial.init({
        hoje: hojeSP,
        tiposPagamento,
        seletores: {
            itensBody: '#itensTable tbody',
            parcelasBody: '#parcelasTable tbody',
            freteManualCheck: '#freteManualCheck',
            freteTotalInput: '#freteTotalInput',
            descontoTotalInput: '#descontoTotalInput',
            descontoPercentInput: '#descontoPercentInput',
            condicaoPagamento: '#condicaoPagamento',
            qtdParcelas: '#qtdParcelas',
            subtotalProdutos: '#subtotalProdutos',
            totalDescontos: '#totalDescontos',
            totalFrete: '#totalFrete',
            totalGeral: '#totalGeralVenda'
        }
    });

    function mostrarFeedback(tipo, mensagem, voiceCode) {
        feedbackBox.className = `alert alert-${tipo}`;
        feedbackBox.textContent = mensagem;
        feedbackBox.classList.remove('d-none');
        if (voiceCode && window.AppSpeechFeedback) {
            window.AppSpeechFeedback.speakFeedback({
                screen: 'sales',
                type: tipo,
                text: mensagem,
                code: voiceCode
            });
        }
    }

    function limparFeedback() {
        feedbackBox.className = 'alert d-none';
        feedbackBox.textContent = '';
    }

    function criarErroInterface(mensagem, voiceCode) {
        const error = new Error(mensagem);
        error.voiceCode = voiceCode;
        return error;
    }

    function showBlockingAlert(message, voiceCode) {
        window.alert(message);
        if (window.AppSpeechFeedback) {
            window.AppSpeechFeedback.speakFeedback({
                screen: 'sales',
                type: 'warning',
                text: message,
                code: voiceCode
            });
        }
    }

    function obterPrimeiraPalavra(value) {
        return String(value || '').trim().split(/\s+/).filter(Boolean)[0] || '';
    }

    function obterDataVendaSelecionada() {
        if (usarDataRetroativaVenda.checked && dataRetroativaVenda.value) {
            return dataRetroativaVenda.value;
        }

        return hojeSP;
    }

    function sincronizarDataRetroativaVenda() {
        dataRetroativaVenda.disabled = !usarDataRetroativaVenda.checked;
        if (!usarDataRetroativaVenda.checked) {
            dataRetroativaVenda.value = hojeSP;
        }
        composicao.setBaseDate(obterDataVendaSelecionada());
    }

// Ajusta apenas o step, sem mexer no valor
function atualizarStep(input) {
  let valorStr = input.value.trim();
  if (valorStr === '') {
    input.step = '1';
    return;
  }

  valorStr = valorStr.replace(',', '.');
  const numero = parseFloat(valorStr);
  if (isNaN(numero)) {
    input.step = '1';
    return;
  }

  const partes = valorStr.split('.');
  const casasDecimais = partes.length === 2 ? partes[1].length : 0;

  if (casasDecimais >= 2) {
    input.step = '0.01';
  } else if (casasDecimais === 1) {
    input.step = '0.1';
  } else {
    input.step = '1';
  }
}

// Formata o valor conforme o step e preserva a posição do cursor
function formatarComCursor(input) {
  const pos = input.selectionStart;
  const valorAntigo = input.value;

  // Não formata se estiver vazio ou terminando com separador decimal (digitação incompleta)
  if (valorAntigo.trim() === '' || valorAntigo.endsWith(',') || valorAntigo.endsWith('.')) {
    return;
  }

  let valorStr = valorAntigo.replace(',', '.');
  const numero = parseFloat(valorStr);
  if (isNaN(numero)) return;

  // Determina quantas casas decimais exibir com base no step atual
  const stepAtual = parseFloat(input.step);
  let casas = 0;
  if (stepAtual === 0.01) casas = 2;
  else if (stepAtual === 0.1) casas = 1;

  // Formata o número (arredondado para o step correto)
  const fator = Math.pow(10, casas);
  const arredondado = Math.round(numero * fator) / fator;
  const valorFormatado = casas > 0 ? arredondado.toFixed(casas) : Math.round(arredondado).toString();

  // Compara normalizado (ponto e sem zeros desnecessários) para evitar loop
  const normalizadoAntigo = parseFloat(valorAntigo.replace(',', '.')).toString();
  const normalizadoNovo = parseFloat(valorFormatado).toString();
  if (normalizadoNovo === normalizadoAntigo) return;

  // Aplica o novo valor
  input.value = valorFormatado;

  // Reposiciona o cursor (ajusta pela diferença de comprimento)
  const diff = valorFormatado.length - valorAntigo.length;
  const novaPos = pos + diff;
  input.setSelectionRange(novaPos, novaPos);
}

// Listener delegado (input + blur)
document.addEventListener('input', function(e) {
  if (e.target.classList.contains('quantidade-adaptativa')) {
    atualizarStep(e.target);           // mantém step correto
    formatarComCursor(e.target);       // formata sem travar
  }
});

document.addEventListener('blur', function(e) {
  if (e.target.classList.contains('quantidade-adaptativa')) {
    formatarComCursor(e.target);       // garante formatação final
    // (opcional) força mínimo 1 se necessário
    if (e.target.value === '' || isNaN(parseFloat(e.target.value.replace(',', '.')))) {
      e.target.value = '1';
    }
  }
}, true);


    function limparFormularioVendaPosSucesso() {
        clienteSelecionadoId = null;
        clienteNome.value = '';
        clienteTelefone.value = '';
        clienteCpfCnpj.value = '';
        clienteEmail.value = '';
        clienteEndereco.value = '';
        vendedorSelect.value = vendedorLogadoPadrao.id ? String(vendedorLogadoPadrao.id) : '';

        produtoSelecionadoId = null;
        produtoBusca.value = '';
        produtoQtd.value = '1';
        produtoValorUnitario.value = '0,00';
        produtoOrigemEstoque.value = 'loja';

        usarDataRetroativaVenda.checked = false;
        dataRetroativaVenda.value = hojeSP;
        dataRetroativaVenda.disabled = true;
        composicao.reset();
        filtrarSugestoesClientes('');
        limparFeedback();
        clienteNome.focus();
    }

    function preencherDadosCliente(cliente) {
        if (!cliente) return;
        clienteSelecionadoId = Number(cliente.id_cliente) || null;
        clienteNome.value = cliente.nome_cliente || '';
        clienteTelefone.value = cliente.telefone_contato || '';
        clienteCpfCnpj.value = formatarCpfCnpj(cliente.cpf_cnpj || '');
        clienteEmail.value = cliente.email_contato || '';
        clienteEndereco.value = cliente.endereco || '';
    }

    function escapeHtmlAttr(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function normalizarNomeBusca(value) {
        return String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim()
            .toLowerCase();
    }

    function formatarClienteParaSugestao(cliente) {
        return [
            String(cliente?.nome_cliente || '').trim(),
            String(cliente?.telefone_contato || '').trim(),
            formatarCpfCnpj(cliente?.cpf_cnpj || '').trim()
        ].filter(Boolean).join(' | ');
    }

    function normalizarClienteParaBusca(cliente) {
        return [
            cliente?.nome_cliente || '',
            cliente?.telefone_contato || '',
            cliente?.cpf_cnpj || '',
            formatarCpfCnpj(cliente?.cpf_cnpj || ''),
            formatarClienteParaSugestao(cliente)
        ].map(normalizarNomeBusca).join(' ');
    }

    function filtrarSugestoesClientes(valorDigitado) {
        const termo = normalizarNomeBusca(valorDigitado);
        const filtrados = clientesData.filter((cliente) => {
            if (!termo) return true;
            return normalizarClienteParaBusca(cliente).includes(termo);
        });

        clientesSugestoes.innerHTML = filtrados
            .map((cliente) => `<option value="${escapeHtmlAttr(formatarClienteParaSugestao(cliente))}" data-id="${Number(cliente.id_cliente) || ''}"></option>`)
            .join('');
    }

    function obterClienteSelecionadoPorInput(nomeDigitado) {
        const nomeNormalizado = normalizarNomeBusca(nomeDigitado);
        if (!nomeNormalizado) return null;

        const opcoes = Array.from(clientesSugestoes.options);
        const opcao = opcoes.find((item) => normalizarNomeBusca(item.value || '') === nomeNormalizado);
        if (opcao && opcao.dataset.id) {
            const id = Number(opcao.dataset.id);
            if (Number.isInteger(id) && id > 0) {
                return clientesData.find((cliente) => Number(cliente.id_cliente) === id) || null;
            }
        }

        return buscarClientePorNome(nomeDigitado);
    }

    function buscarClientePorNome(nome) {
        const nomeNormalizado = normalizarNomeBusca(nome);
        if (!nomeNormalizado) return null;

        return clientesData.find(
            (cliente) => normalizarNomeBusca(cliente.nome_cliente || '') === nomeNormalizado
        ) || null;
    }

    function obterClienteSelecionadoPorId(idCliente) {
        const id = Number(idCliente);
        if (!Number.isInteger(id) || id <= 0) return null;
        return clientesData.find((cliente) => Number(cliente.id_cliente) === id) || null;
    }

    function filtrarSugestoesProdutos(valorDigitado) {
        const termo = normalizarNomeBusca(valorDigitado);
        const filtrados = produtosData.filter((produto) => {
            if (!termo) return true;
            return normalizarNomeBusca(produto.nome || '').includes(termo);
        });

        produtosSugestoes.innerHTML = filtrados
            .map((produto) => `<option value="${escapeHtmlAttr(formatarProdutoParaSugestao(produto))}" data-id="${Number(produto.id) || ''}"></option>`)
            .join('');
    }

    function formatarProdutoParaSugestao(produto) {
        const nome = String(produto?.nome || '').trim();
        const preco = Number(produto?.preco1 || 0).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        return `${nome} (R$ ${preco})`;
    }

    function extrairNomeProdutoDigitado(valor) {
        return String(valor || '').replace(/\s+\(R\$\s*[\d.,]+\)\s*$/, '').trim();
    }

    function buscarProdutoPorNome(nome) {
        const nomeNormalizado = normalizarNomeBusca(extrairNomeProdutoDigitado(nome));
        if (!nomeNormalizado) return null;

        return produtosData.find(
            (produto) => normalizarNomeBusca(produto.nome || '') === nomeNormalizado
        ) || null;
    }

    function obterProdutoSelecionadoPorInput(nomeDigitado) {
        const nomeNormalizado = normalizarNomeBusca(nomeDigitado);
        if (!nomeNormalizado) return null;

        const opcoes = Array.from(produtosSugestoes.options);
        const opcao = opcoes.find((item) => normalizarNomeBusca(item.value || '') === nomeNormalizado);
        if (opcao && opcao.dataset.id) {
            const id = Number(opcao.dataset.id);
            if (Number.isInteger(id) && id > 0) {
                return produtosData.find((produto) => Number(produto.id) === id) || null;
            }
        }

        return buscarProdutoPorNome(nomeDigitado);
    }

    function preencherProdutoSelecionado(produto) {
        if (!produto) {
            produtoSelecionadoId = null;
            return;
        }

        produtoSelecionadoId = Number(produto.id) || null;
        produtoBusca.value = formatarProdutoParaSugestao(produto);
        produtoValorUnitario.value = formatarDecimalInput(produto.preco1 || 0);
    }

    function normalizarTextoComparacao(valor) {
        return String(valor ?? '').trim();
    }

    function normalizarDigitosComparacao(valor) {
        return String(valor ?? '').replace(/\D+/g, '');
    }

    function compararClienteEditavel(clienteBase, clienteAtual) {
        const comparacoes = [
            ['nome', normalizarTextoComparacao(clienteBase.nome_cliente), normalizarTextoComparacao(clienteAtual.nome)],
            ['telefone', normalizarDigitosComparacao(clienteBase.telefone_contato), normalizarDigitosComparacao(clienteAtual.telefone)],
            ['cpf_cnpj', normalizarDigitosComparacao(clienteBase.cpf_cnpj), normalizarDigitosComparacao(clienteAtual.cpf_cnpj)],
            ['email', normalizarTextoComparacao(clienteBase.email_contato).toLowerCase(), normalizarTextoComparacao(clienteAtual.email).toLowerCase()],
            ['endereco', normalizarTextoComparacao(clienteBase.endereco), normalizarTextoComparacao(clienteAtual.endereco)]
        ];

        return comparacoes
            .filter(([, base, atual]) => base !== atual)
            .map(([campo]) => campo);
    }

    async function solicitarResolucaoCliente(clienteBase, camposDivergentes) {
        const mensagem = [
            `Foram detectadas divergências no cliente #${clienteBase.id_cliente}.`,
            `Campos divergentes: ${camposDivergentes.join(', ')}.`,
            'Digite 1 para ATUALIZAR cliente existente, 2 para CRIAR novo cliente, 3 para CANCELAR.'
        ].join('\n');

        while (true) {
            const escolha = window.prompt(mensagem, '1');
            if (escolha === null) return 'cancelar';

            const escolhaNormalizada = String(escolha).trim();
            if (escolhaNormalizada === '1') return 'atualizar';
            if (escolhaNormalizada === '2') return 'novo';
            if (escolhaNormalizada === '3') return 'cancelar';
        }
    }

    async function resolverClienteAntesDaVenda(payloadVenda, resolucao) {
        payloadVenda.cliente_resolucao = resolucao;

        if (resolucao === 'manter') {
            return { ok: true };
        }

        try {
            const resposta = await fetch('resolver_cliente.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    csrf_token: csrfToken,
                    cliente_id: payloadVenda.cliente_id,
                    cliente_resolucao: resolucao,
                    cliente: payloadVenda.cliente
                })
            });

            const dados = await resposta.json();
            if (!resposta.ok || !dados.status || !dados.cliente) {
                throw new Error(dados.mensagem || 'Falha ao resolver cliente.');
            }

            atualizarClienteNaLista(dados.cliente);
            preencherDadosCliente(dados.cliente);
            payloadVenda.cliente_id = Number(dados.cliente.id_cliente);
            payloadVenda.cliente_resolucao = 'manter';
            return { ok: true };
        } catch (error) {
            return { ok: false, mensagem: error.message || 'Falha ao resolver cliente antes da venda.' };
        }
    }

    function obterVendedorSelecionado() {
        const selectedOption = vendedorSelect.selectedOptions[0] || null;

        return {
            id: Number(vendedorSelect.value || vendedorLogadoPadrao.id || 0),
            nome: selectedOption?.dataset?.nome || vendedorLogadoPadrao.nome || ''
        };
    }

    function montarPayloadVenda() {
        const clienteBase = obterClienteSelecionadoPorId(clienteSelecionadoId);
        if (!clienteBase) {
            throw criarErroInterface('Selecione ou salve um cliente antes de salvar a venda.', 'sales.customer_required');
        }

        const vendedor = obterVendedorSelecionado();
        if (!vendedor.id || !vendedor.nome) {
            throw criarErroInterface('Selecione um vendedor antes de salvar a venda.', 'sales.vendor_required');
        }

        const estado = composicao.getState();
        if (!estado.itens_produto.length) {
            throw criarErroInterface('Adicione ao menos um item na venda.', 'sales.items_required');
        }

        const resumo = composicao.getResumo();
        const itensSemOrigem = estado.itens_produto
            .map((item, index) => ({ item, index }))
            .filter(({ item }) => !['loja', 'estoque_auxiliar'].includes(String(item.origemEstoque || '').trim()));
        if (itensSemOrigem.length) {
            throw criarErroInterface(
                `Selecione a origem de estoque do item ${itensSemOrigem[0].index + 1} antes de salvar a venda.`,
                'sales.save_error'
            );
        }
        const payloadParcelas = estado.parcelas.map((parcela, index) => ({
            numero_parcela: index + 1,
            vencimento: parcela.vencimento || hojeSP,
            valor: Number(composicao.valorNum(parcela.valor).toFixed(2)),
            tipo_pagamento: parcela.tipoPagamento || 'PIX',
            qtd_parcelas: estado.parcelas.length,
            total_parcelas: Number(resumo.total_geral.toFixed(2))
        }));

        if (!payloadParcelas.length) {
            throw new Error('Informe ao menos uma parcela para a venda.');
        }

        return {
            csrf_token: csrfToken,
            id_venda: vendaEdicaoData && vendaEdicaoData.id_venda ? Number(vendaEdicaoData.id_venda) : null,
            cliente_id: Number(clienteBase.id_cliente),
            vendedor_id: vendedor.id,
            vendedor_nome: vendedor.nome,
            cliente: {
                nome: clienteNome.value.trim(),
                telefone: clienteTelefone.value.trim(),
                cpf_cnpj: normalizarDigitos(clienteCpfCnpj.value, 14),
                email: clienteEmail.value.trim(),
                endereco: clienteEndereco.value.trim()
            },
            cliente_resolucao: 'manter',
            validar_cliente_consistencia: true,
            data_venda: obterDataVendaSelecionada(),
            condicao_pagamento: document.getElementById('condicaoPagamento').value,
            subtotal: Number((resumo.subtotal_produtos + resumo.subtotal_microservicos).toFixed(2)),
            desconto_total: Number(resumo.desconto_total.toFixed(2)),
            frete_total: Number(resumo.frete_total.toFixed(2)),
            total_geral: Number(resumo.total_geral.toFixed(2)),
            itens: estado.itens_produto.map((item) => ({
                produto_id: Number(item.produtoId),
                origem_estoque: String(item.origemEstoque || '').trim(),
                quantidade: Number(item.quantidade),
                valor_unitario: Number(item.valorUnitario.toFixed(2)),
                desconto_valor: Number(item.desconto.toFixed(2)),
                frete_valor: Number(item.freteItem.toFixed(2))
            })),
            parcelas: payloadParcelas
        };
    }

    async function enviarVenda() {
        limparFeedback();

        let payload;
        let clienteBase;
        try {
            payload = montarPayloadVenda();
            clienteBase = obterClienteSelecionadoPorId(payload.cliente_id);
        } catch (error) {
            mostrarFeedback('warning', error.message || 'Verifique os dados da venda antes de salvar.', error.voiceCode);
            return;
        }

        if (!clienteBase) {
            mostrarFeedback('warning', 'Cliente selecionado não encontrado no cadastro base.');
            return;
        }

        const divergencias = compararClienteEditavel(clienteBase, payload.cliente);
        if (divergencias.length > 0) {
            const resolucao = await solicitarResolucaoCliente(clienteBase, divergencias);
            if (resolucao === 'cancelar') {
                mostrarFeedback('warning', 'Operação cancelada pelo usuário.');
                return;
            }

            const resolucaoApi = await resolverClienteAntesDaVenda(payload, resolucao);
            if (!resolucaoApi.ok) {
                mostrarFeedback('danger', resolucaoApi.mensagem || 'Não foi possível resolver os dados do cliente.');
                return;
            }
        }

        btnSalvarVenda.disabled = true;
        btnSalvarVenda.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';

        try {
            const resposta = await fetch('salvar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const dados = await resposta.json();

            if (!resposta.ok || !dados.status) {
                throw new Error(dados.mensagem || 'Falha ao salvar venda.');
            }

            window.alert(vendaEdicaoData && vendaEdicaoData.id_venda
                ? `Venda #${dados.id_venda} atualizada com sucesso.`
                : `Venda #${dados.id_venda} salva com sucesso.`);

            if (window.AppSpeechFeedback) {
                window.AppSpeechFeedback.speakText('Venda Salva.', {
                    screen: 'sales',
                    type: 'success'
                });
            }

            if (vendaEdicaoData && vendaEdicaoData.id_venda) {
                window.location.href = `<?= app_url('vendas/detalhes.php?id=') ?>${dados.id_venda}`;
                return;
            }

            limparFormularioVendaPosSucesso();
        } catch (error) {
            mostrarFeedback('danger', error.message || 'Erro inesperado ao salvar venda.', 'sales.save_error');
        } finally {
            btnSalvarVenda.disabled = false;
            btnSalvarVenda.innerHTML = '<i class="fas fa-save me-1"></i>Salvar venda';
        }
    }

    function montarPayloadClienteRapido() {
        return {
            csrf_token: csrfToken,
            nome_cliente: clienteNome.value.trim(),
            telefone_contato: clienteTelefone.value.trim(),
            cpf_cnpj: normalizarDigitos(clienteCpfCnpj.value, 14),
            email_contato: clienteEmail.value.trim(),
            endereco: clienteEndereco.value.trim()
        };
    }

    function atualizarClienteNaLista(cliente) {
        if (!cliente || !cliente.id_cliente) return;

        const clienteId = Number(cliente.id_cliente);
        const idx = clientesData.findIndex((item) => Number(item.id_cliente) === clienteId);
        if (idx >= 0) {
            clientesData[idx] = cliente;
        } else {
            clientesData.push(cliente);
        }

        clientesData.sort((a, b) => (a.nome_cliente || '').localeCompare((b.nome_cliente || ''), 'pt-BR'));
        filtrarSugestoesClientes(cliente.nome_cliente || '');
    }

    async function salvarClienteRapido() {
        limparFeedback();
        btnSalvarCliente.disabled = true;
        btnSalvarCliente.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';

        try {
            const resposta = await fetch('salvar_cliente.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(montarPayloadClienteRapido())
            });

            const dados = await resposta.json();
            if (!resposta.ok || !dados.status || !dados.cliente) {
                throw new Error(dados.mensagem || 'Não foi possível salvar o cliente.');
            }

            atualizarClienteNaLista(dados.cliente);
            preencherDadosCliente(dados.cliente);
            mostrarFeedback('success', `Cliente #${dados.id_cliente} salvo com sucesso.`);
            const primeiroNomeCliente = obterPrimeiraPalavra(dados.cliente?.nome_cliente);
            if (primeiroNomeCliente && window.AppSpeechFeedback) {
                window.AppSpeechFeedback.speakText(`Cliente ${primeiroNomeCliente} Salvo.`, {
                    screen: 'sales',
                    type: 'success'
                });
            }
        } catch (error) {
            mostrarFeedback('danger', error.message || 'Erro ao salvar cliente.');
        } finally {
            btnSalvarCliente.disabled = false;
            btnSalvarCliente.innerHTML = '<i class="fas fa-user-plus me-1"></i>Salvar cliente rápido';
        }
    }

    filtrarSugestoesClientes('');
    filtrarSugestoesProdutos('');

    clienteNome.addEventListener('input', (event) => {
        clienteSelecionadoId = null;
        filtrarSugestoesClientes(event.target.value);
    });

    clienteNome.addEventListener('change', (event) => {
        const cliente = obterClienteSelecionadoPorInput(event.target.value);
        if (cliente) {
            preencherDadosCliente(cliente);
            return;
        }
        clienteSelecionadoId = null;
    });

    clienteNome.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') return;
        const cliente = obterClienteSelecionadoPorInput(clienteNome.value);
        if (!cliente) return;
        event.preventDefault();
        preencherDadosCliente(cliente);
    });

    clienteCpfCnpj.addEventListener('input', aplicarMascaraCpfCnpj);

    btnSalvarCliente.addEventListener('click', salvarClienteRapido);

    // Reaproveita a mesma ideia do campo de cliente:
    // filtramos em tempo real e só confirmamos um produto quando ele
    // bater exatamente com um item existente no catálogo.
    produtoBusca.addEventListener('input', (event) => {
        const produto = obterProdutoSelecionadoPorInput(event.target.value);
        produtoSelecionadoId = produto ? Number(produto.id) || null : null;
        filtrarSugestoesProdutos(event.target.value);
    });

    produtoBusca.addEventListener('change', (event) => {
        const produto = obterProdutoSelecionadoPorInput(event.target.value);
        if (produto) {
            preencherProdutoSelecionado(produto);
            return;
        }

        produtoSelecionadoId = null;
    });

    produtoBusca.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') return;
        const produto = obterProdutoSelecionadoPorInput(produtoBusca.value);
        if (!produto) return;
        event.preventDefault();
        preencherProdutoSelecionado(produto);
    });

    btnAdicionarProduto.addEventListener('click', () => {
        const produto = produtoSelecionadoId
            ? produtosData.find((item) => Number(item.id) === Number(produtoSelecionadoId)) || null
            : obterProdutoSelecionadoPorInput(produtoBusca.value);

        if (!produto) {
            showBlockingAlert('Selecione um produto para adicionar.', 'sales.product_required');
            return;
        }

        composicao.addItem('produto', {
            produtoId: Number(produto.id),
            nome: produto.nome || '',
            origemEstoque: produtoOrigemEstoque.value || 'loja',
            quantidade: Math.max(1, parseFloat(produtoQtd.value) || 1),
            valorUnitario: Math.max(0, composicao.valorNum(produtoValorUnitario.value)),
            desconto: 0,
            freteItem: 0
        });
        produtoSelecionadoId = null;
        produtoBusca.value = '';
        produtoQtd.value = '1';
        produtoValorUnitario.value = '0,00';
        produtoOrigemEstoque.value = 'loja';
        filtrarSugestoesProdutos('');
    });

    document.getElementById('btnZerarDescontos').addEventListener('click', () => {
        composicao.zerarDescontosProdutos();
    });

    btnLimparVenda.addEventListener('click', () => {
        limparFormularioVendaPosSucesso();
    });

    usarDataRetroativaVenda.addEventListener('change', sincronizarDataRetroativaVenda);
    dataRetroativaVenda.addEventListener('change', () => {
        if (usarDataRetroativaVenda.checked) {
            composicao.setBaseDate(obterDataVendaSelecionada());
        }
    });

    function renderClientesSugestao(valorInicial) {
        filtrarSugestoesClientes(valorInicial || '');
    }

    function renderProdutosSugestao(valorInicial) {
        filtrarSugestoesProdutos(valorInicial || '');
    }

    function aplicarDadosEdicaoVenda() {
        if (!vendaEdicaoData || !vendaEdicaoData.id_venda) {
            return;
        }

        clienteSelecionadoId = Number(vendaEdicaoData.cliente_id || 0) || null;
        clienteNome.value = vendaEdicaoData.cliente?.nome_cliente || '';
        clienteTelefone.value = vendaEdicaoData.cliente?.telefone_contato || '';
        clienteCpfCnpj.value = formatarCpfCnpj(vendaEdicaoData.cliente?.cpf_cnpj || '');
        clienteEmail.value = vendaEdicaoData.cliente?.email_contato || '';
        clienteEndereco.value = vendaEdicaoData.cliente?.endereco || '';
        vendedorSelect.value = String(vendaEdicaoData.vendedor_id || vendedorLogadoPadrao.id || '');
        const dataVendaEdicao = vendaEdicaoData.data_venda || hojeSP;
        const usarRetroativo = dataVendaEdicao < hojeSP;
        usarDataRetroativaVenda.checked = usarRetroativo;
        dataRetroativaVenda.value = usarRetroativo ? dataVendaEdicao : hojeSP;
        sincronizarDataRetroativaVenda();

        const condicaoPagamento = document.getElementById('condicaoPagamento');
        condicaoPagamento.value = vendaEdicaoData.condicao_pagamento === 'parcelado' ? 'parcelado' : 'vista';
        condicaoPagamento.dispatchEvent(new Event('change'));
        composicao.setItens(Array.isArray(vendaEdicaoData.itens) ? vendaEdicaoData.itens : [], []);
        renderClientesSugestao(vendaEdicaoData.cliente?.nome_cliente || '');
        renderProdutosSugestao('');

        const parcelas = Array.isArray(vendaEdicaoData.parcelas) ? vendaEdicaoData.parcelas : [];
        if (parcelas.length) {
            composicao.setParcelas(parcelas);
        }
    }

    let liberarSalvarVendaPorMouse = false;

    formVenda.addEventListener('submit', (event) => {
        event.preventDefault();
    });

    btnSalvarVenda.addEventListener('pointerdown', () => {
        liberarSalvarVendaPorMouse = true;
    });

    btnSalvarVenda.addEventListener('click', (event) => {
        if (!liberarSalvarVendaPorMouse) {
            event.preventDefault();
            return;
        }

        liberarSalvarVendaPorMouse = false;
        enviarVenda();
    });

    btnSalvarVenda.addEventListener('blur', () => {
        liberarSalvarVendaPorMouse = false;
    });

    renderClientesSugestao('');
    renderProdutosSugestao('');
    aplicarMascaraCpfCnpj();
    aplicarDadosEdicaoVenda();
</script>

<?php include '../includes/footer.php'; ?>
