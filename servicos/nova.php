<?php
include '../includes/db.php';
require_login();
require_once __DIR__ . '/_infra.php';

$hojeSaoPaulo = (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
servicos_ensure_schema($pdo);
$clientes = servicos_obter_clientes($pdo);
$vendedores = servicos_obter_vendedores($pdo);
$clienteObrigatorio = servicos_cliente_obrigatorio();
$vendedorLogadoId = (int) (auth_user_id() ?? 0);
$vendedorLogadoNome = auth_user_display_name();
$servicoIdEdicao = (int) ($_GET['id'] ?? $_GET['id_servico'] ?? $_GET['servico_id'] ?? 0);
$servicoEdicaoPayload = null;

if ($servicoIdEdicao > 0) {
    action_firewall_require_grant('servico', 'edit', $servicoIdEdicao, app_url('servicos/listar.php?status=firewall'));

    $repository = new \App\Repositories\ServicoRepository($pdo);
    $detalhes = $repository->findCompleteById($servicoIdEdicao);
    if (!$detalhes) {
        header('Location: ' . app_url('servicos/listar.php'));
        exit;
    }

    $servico = $detalhes['servico'] ?? [];
    $itens = is_array($detalhes['itens'] ?? null) ? $detalhes['itens'] : [];
    $parcelas = is_array($detalhes['parcelas'] ?? null) ? $detalhes['parcelas'] : [];

    $produtosEdicao = [];
    $microservicosEdicao = [];
    foreach ($itens as $item) {
        if (!is_array($item)) {
            continue;
        }

        $normalizado = [
            'produto_id' => (int) ($item['produto_id'] ?? 0),
            'descricao' => (string) ($item['descricao'] ?? ''),
            'quantidade' => (float) ($item['quantidade'] ?? 1),
            'valor_unitario' => (float) ($item['valor_unitario'] ?? 0),
            'desconto_valor' => (float) ($item['desconto_valor'] ?? 0),
            'frete_valor' => (float) ($item['frete_valor'] ?? 0),
            'is_frete_embutido' => false,
        ];

        if (($item['tipo_item'] ?? '') === 'produto') {
            $produtosEdicao[] = $normalizado;
            continue;
        }

        $normalizado['frete_valor'] = 0;
        $normalizado['is_frete_embutido'] = stripos((string) ($item['descricao'] ?? ''), 'frete') !== false;
        $microservicosEdicao[] = $normalizado;
    }

    $parcelasEdicao = [];
    foreach ($parcelas as $parcela) {
        if (!is_array($parcela)) {
            continue;
        }

        $parcelasEdicao[] = [
            'vencimento' => (string) ($parcela['vencimento'] ?? date('Y-m-d')),
            'valor' => (float) ($parcela['valor_parcela'] ?? 0),
            'tipoPagamento' => (string) ($parcela['tipo_pagamento'] ?? 'PIX'),
            'manual' => true,
        ];
    }

    $servicoEdicaoPayload = [
        'id_servico' => (int) ($servico['id_servico'] ?? $servicoIdEdicao),
        'cliente_id' => (int) ($servico['cliente_id'] ?? 0),
        'vendedor_id' => (int) ($servico['vendedor_id'] ?? $vendedorLogadoId),
        'vendedor_nome' => (string) ($servico['vendedor_nome'] ?? $vendedorLogadoNome),
        'data_servico' => (string) ($servico['data_servico'] ?? $hojeSaoPaulo),
        'cliente' => [
            'nome_cliente' => (string) ($servico['nome_cliente'] ?? ''),
            'telefone_contato' => (string) ($servico['telefone_contato'] ?? ''),
            'cpf_cnpj' => (string) ($servico['cpf_cnpj'] ?? ''),
            'email_contato' => (string) ($servico['email_contato'] ?? ''),
            'endereco' => (string) ($servico['endereco'] ?? ''),
        ],
        'condicao_pagamento' => (string) ($servico['condicao_pagamento'] ?? 'vista'),
        'produtos' => $produtosEdicao,
        'microservicos' => $microservicosEdicao,
        'parcelas' => $parcelasEdicao,
    ];
}

$produtosStmt = $pdo->query("SELECT id, nome, preco1 FROM produtos WHERE COALESCE(preco1, 0) > 0 ORDER BY nome");
$produtos = $produtosStmt ? $produtosStmt->fetchAll(PDO::FETCH_ASSOC) : [];

include '../includes/header.php';
?>

<style>
    .servico-desconto-card .form-label {
        font-size: 0.92rem;
        margin-bottom: 0.35rem;
    }

    .servico-desconto-card .form-control {
        height: 38px;
        font-size: 0.95rem;
        padding-top: 0.35rem;
        padding-bottom: 0.35rem;
    }

    .servico-desconto-card .btn {
        height: 38px;
        font-size: 0.95rem;
        padding-top: 0.35rem;
        padding-bottom: 0.35rem;
    }

    .servico-resumo-linhas > div {
        margin-bottom: 0.2rem;
    }
</style>

<div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="fas fa-tools me-2"></i><?= $servicoEdicaoPayload ? 'Editar Serviço' : 'Tela de Serviços' ?></h4>
        <span class="badge bg-light text-dark">Ordem de Serviço</span>
    </div>

    <div class="card-body">
        <form id="formServico" class="row g-3" autocomplete="off">
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
                                        <option value="<?= htmlspecialchars($cliente['nome_cliente']) ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="clienteTelefone">Telefone</label>
                                <input type="text" class="form-control" id="clienteTelefone">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="clienteCpfCnpj">CPF/CNPJ</label>
                                <input type="text" class="form-control" id="clienteCpfCnpj">
                            </div>
                        </div>
                        <div class="row g-3 align-items-end mt-1">
                            <div class="col-md-4">
                                <label class="form-label" for="clienteEmail">E-mail</label>
                                <input type="email" class="form-control" id="clienteEmail">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label" for="clienteEndereco">Endereço</label>
                                <input type="text" class="form-control" id="clienteEndereco">
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
                    <div class="card-header bg-light sales-block-title">2) Produtos e micro-serviços</div>
                    <div class="card-body">
                        <div class="row g-2 align-items-end mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="produtoSelect">Produto</label>
                                <select class="form-select" id="produtoSelect">
                                    <option value="">Selecione um produto...</option>
                                    <?php foreach ($produtos as $produto): ?>
                                        <option value="<?= (int) $produto['id'] ?>" data-nome="<?= htmlspecialchars($produto['nome']) ?>" data-preco="<?= number_format((float) ($produto['preco1'] ?? 0), 2, '.', '') ?>">
                                            <?= htmlspecialchars($produto['nome']) ?> (R$ <?= number_format((float) ($produto['preco1'] ?? 0), 2, ',', '.') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="produtoQtd">Qtd.</label>
                                <input type="number" min="1" class="form-control" id="produtoQtd" value="1">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="produtoValorUnitario">Vlr. unitário</label>
                                <input type="text" class="form-control" id="produtoValorUnitario" value="0,00">
                            </div>
                            <div class="col-md-2 d-grid">
                                <button type="button" class="btn btn-success" id="btnAdicionarProduto"><i class="fas fa-plus me-1"></i>Produto</button>
                            </div>
                        </div>

                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-bordered align-middle" id="itensProdutoTable">
                                <thead class="table-primary">
                                    <tr><th>Item</th><th>Produto</th><th>Qtd.</th><th>Vlr. unitário</th><th>Desconto</th><th>Frete</th><th>Total</th><th>Ação</th></tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <div class="border rounded p-3" style="background:#fff7e6;border-color:#ffb84d !important;">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label" for="microDescricao">Micro-serviço</label>
                                    <input type="text" id="microDescricao" class="form-control" placeholder="Ex.: Instalação de bomba">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="microQtd">Qtd.</label>
                                    <input type="number" id="microQtd" min="1" class="form-control" value="1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="microValor">Vlr. unitário</label>
                                    <input type="text" id="microValor" class="form-control" value="0,00">
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button type="button" class="btn btn-warning" id="btnAdicionarMicro"><i class="fas fa-plus me-1"></i>Micro</button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive mt-3">
                            <table class="table table-sm table-bordered align-middle" id="itensMicroTable">
                                <thead style="background:#ffd79a;">
                                    <tr><th>Item</th><th>Descrição</th><th>Qtd.</th><th>Vlr. unitário</th><th>Desconto</th><th>Total</th><th>Ação</th></tr>
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
                                    <input type="text" inputmode="decimal" class="form-control" id="freteTotalInput" value="0,00">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="freteComoMicroservicoCheck">
                                        <label class="form-check-label" for="freteComoMicroservicoCheck">
                                            Lançar frete como micro-serviço de deslocamento
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 form-check mt-4 ps-5">
                                    <input class="form-check-input" type="checkbox" id="freteManualCheck">
                                    <label class="form-check-label" for="freteManualCheck">
                                        Informar frete manual (sobrescrever soma dos itens)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="resumo-card servico-desconto-card">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label" for="descontoTotalInput">Desconto total (R$)</label>
                                    <input type="text" inputmode="decimal" class="form-control form-control-sm" id="descontoTotalInput" value="0,00">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="descontoPercentInput">ou Desconto (%)</label>
                                    <input type="text" inputmode="decimal" class="form-control form-control-sm" id="descontoPercentInput" value="0,00">
                                </div>
                                <div class="col-md-4 d-grid">
                                    <button type="button" id="btnZerarDescontos" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-eraser me-1"></i>Zerar descontos
                                    </button>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="form-label d-block mb-2">Aplicar desconto com rateio em</span>
                                <div class="d-flex flex-wrap gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="descontoRateioModo" id="descontoRateioProdutos" value="produtos" checked>
                                        <label class="form-check-label" for="descontoRateioProdutos">Produtos</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="descontoRateioModo" id="descontoRateioMicro" value="microservicos">
                                        <label class="form-check-label" for="descontoRateioMicro">Micro-serviços</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="descontoRateioModo" id="descontoRateioGeral" value="geral">
                                        <label class="form-check-label" for="descontoRateioGeral">Rateio geral</label>
                                    </div>
                                </div>
                            </div>
                            <div class="context-tip mt-2">
                                Se houver itens com desconto preenchido, o rateio respeita esses itens. Caso contrário, distribui proporcionalmente pelo valor dos itens.
                            </div>
                        </div>

                        <div class="servico-resumo-linhas mt-4">
                            <div class="d-flex justify-content-between"><span>Subtotal produtos:</span><strong id="subtotalProdutos">R$ 0,00</strong></div>
                            <div class="d-flex justify-content-between"><span>Subtotal micro-serviços:</span><strong id="subtotalMicro">R$ 0,00</strong></div>
                            <div class="d-flex justify-content-between"><span>Total descontos:</span><strong id="totalDescontos">R$ 0,00</strong></div>
                            <div class="d-flex justify-content-between"><span>Total frete:</span><strong id="totalFrete">R$ 0,00</strong></div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between"><span>Total geral do serviço:</span><span class="total-pill" id="totalGeralServico">R$ 0,00</span></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-primary-subtle h-100">
                    <div class="card-header bg-light sales-block-title">4) Forma e condição de pagamento</div>
                    <div class="card-body">
                        <div class="row g-2 mb-3 align-items-end">
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="usarDataRetroativaServico">
                                    <label class="form-check-label" for="usarDataRetroativaServico">Ativar data retroativa</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="dataRetroativaServico">Data do serviÃ§o</label>
                                <input type="date" id="dataRetroativaServico" class="form-control" value="<?= htmlspecialchars($hojeSaoPaulo, ENT_QUOTES, 'UTF-8') ?>" max="<?= htmlspecialchars($hojeSaoPaulo, ENT_QUOTES, 'UTF-8') ?>" disabled>
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6"><label class="form-label" for="condicaoPagamento">Condição</label><select id="condicaoPagamento" class="form-select"><option value="vista">À vista</option><option value="parcelado">Parcelado</option></select></div>
                            <div class="col-md-6"><label class="form-label" for="qtdParcelas">Qtd. parcelas</label><input type="number" id="qtdParcelas" class="form-control" min="1" max="24" value="1"></div>
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
                <div id="servicoFeedback" class="alert d-none" role="alert"></div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="button" class="btn btn-outline-secondary" id="btnLimparServico"><i class="fas fa-broom me-1"></i>Limpar campos</button>
                    <button type="button" class="btn btn-primary" id="btnSalvarServico"><i class="fas fa-save me-1"></i><?= $servicoEdicaoPayload ? 'Atualizar serviço' : 'Salvar serviço' ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/composicao_comercial.js"></script>
<script>
const hojeSP = '<?= $hojeSaoPaulo ?>';
const csrfToken = '<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>';
const clientesData = <?= json_encode($clientes, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const clienteObrigatorio = <?= $clienteObrigatorio ? 'true' : 'false' ?>;
const servicoEdicaoData = <?= json_encode($servicoEdicaoPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
const vendedorLogadoPadrao = {
  id: <?= json_encode($vendedorLogadoId, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
  nome: <?= json_encode($vendedorLogadoNome, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
};
const tiposPagamento = [
  'PIX', 'Dinheiro', 'Boleto', 'Cheque', 'Pix Pague Seguro',
  'Débito PagSeguro', 'Crédito PagSeguro', 'Débito Stone',
  'Crédito Stone', 'Débito Infinite', 'Crédito Infinite'
];

const state = { produtos: [], microservicos: [] };
const DESCRICAO_MICROSERVICO_FRETE = 'Deslocamento e Frete de equipe, material ou equipamento';
let sincronizandoComposicao = false;
let ultimaOrigemDesconto = 'valor';

const moeda = (v) => Number(v || 0).toLocaleString('pt-BR', { style:'currency', currency:'BRL' });
const valorNum = window.ComposicaoComercial.valorNum;

let clienteSelecionadoId = null;
const clientesSugestoes = document.getElementById('clientesSugestoes');
const vendedorSelect = document.getElementById('vendedorSelect');
const btnSalvarCliente = document.getElementById('btnSalvarCliente');
const btnLimparServico = document.getElementById('btnLimparServico');
const subtotalMicroEl = document.getElementById('subtotalMicro');
const descontoTotalInput = document.getElementById('descontoTotalInput');
const descontoPercentInput = document.getElementById('descontoPercentInput');
const usarDataRetroativaServico = document.getElementById('usarDataRetroativaServico');
const dataRetroativaServico = document.getElementById('dataRetroativaServico');

const composicao = window.ComposicaoComercial.init({
  hoje: hojeSP,
  tiposPagamento,
  seletores: {
    itensBody: '#itensComposicaoDummy tbody',
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
    totalGeral: '#totalGeralServico'
  },
  onChange: (snapshot) => {
    if (subtotalMicroEl) {
      subtotalMicroEl.textContent = moeda(snapshot?.totais?.subtotal_microservicos || 0);
    }
    atualizarPercentualDescontoServico(snapshot);
    if (sincronizandoComposicao) return;
    syncStateFromComposicao(snapshot);
    renderTabelas({ skipComposicaoSync: true });
  }
});

function syncComposicaoFromState() {
  const produtos = state.produtos.map((item) => ({
    nome: item.descricao,
    quantidade: item.quantidade,
    valorUnitario: item.valor_unitario,
    desconto: item.desconto_valor,
    freteItem: item.frete_valor
  }));

  const microservicos = state.microservicos.map((item) => ({
    nome: item.descricao,
    quantidade: item.quantidade,
    valorUnitario: item.valor_unitario,
    desconto: item.desconto_valor,
    freteItem: 0
  }));

  sincronizandoComposicao = true;
  try {
    composicao.setItens(produtos, microservicos);
  } finally {
    sincronizandoComposicao = false;
  }
}

function syncStateFromComposicao(snapshot) {
  const itensProduto = Array.isArray(snapshot?.itens_produto) ? snapshot.itens_produto : [];
  const itensMicroservico = Array.isArray(snapshot?.itens_microservico) ? snapshot.itens_microservico : [];

  state.produtos = state.produtos.map((item, idx) => {
    const composicaoItem = itensProduto[idx];
    if (!composicaoItem) return item;
    return {
      ...item,
      quantidade: Number(composicaoItem.quantidade || item.quantidade || 0),
      valor_unitario: Number(composicaoItem.valorUnitario || 0),
      desconto_valor: Number(composicaoItem.desconto || 0),
      frete_valor: Number(composicaoItem.freteItem || 0)
    };
  });

  state.microservicos = state.microservicos.map((item, idx) => {
    const composicaoItem = itensMicroservico[idx];
    if (!composicaoItem) return item;
    return {
      ...item,
      quantidade: Number(composicaoItem.quantidade || item.quantidade || 0),
      valor_unitario: Number(composicaoItem.valorUnitario || 0),
      desconto_valor: Number(composicaoItem.desconto || 0)
    };
  });
}

function obterModoRateioDesconto() {
  return document.querySelector('input[name="descontoRateioModo"]:checked')?.value || 'produtos';
}

function obterEntradasRateioDesconto() {
  const modo = obterModoRateioDesconto();
  const entradas = [];

  if (modo === 'produtos' || modo === 'geral') {
    state.produtos.forEach((item, idx) => {
      entradas.push({
        grupo: 'produtos',
        idx,
        subtotal: Math.max(0, Number(item.quantidade || 0) * Number(item.valor_unitario || 0)),
        desconto: Math.max(0, Number(item.desconto_valor || 0))
      });
    });
  }

  if (modo === 'microservicos' || modo === 'geral') {
    state.microservicos.forEach((item, idx) => {
      if (item.is_frete_embutido === true) return;
      entradas.push({
        grupo: 'microservicos',
        idx,
        subtotal: Math.max(0, Number(item.quantidade || 0) * Number(item.valor_unitario || 0)),
        desconto: Math.max(0, Number(item.desconto_valor || 0))
      });
    });
  }

  return entradas.filter((item) => item.subtotal > 0);
}

function definirDescontoItem(grupo, idx, valor) {
  if (!state[grupo]?.[idx]) return;
  state[grupo][idx].desconto_valor = Math.max(0, Number(valor || 0));
}

function zerarDescontosServico() {
  state.produtos = state.produtos.map((item) => ({ ...item, desconto_valor: 0 }));
  state.microservicos = state.microservicos.map((item) => ({ ...item, desconto_valor: 0 }));
}

function aplicarDescontoRateadoServico(totalDesconto) {
  const descontoNormalizado = Math.max(0, Number(totalDesconto || 0));
  const entradas = obterEntradasRateioDesconto();

  zerarDescontosServico();

  if (!entradas.length || descontoNormalizado <= 0) {
    renderTabelas();
    return;
  }

  const candidatosComDesconto = entradas.filter((item) => item.desconto > 0);
  const base = candidatosComDesconto.length ? candidatosComDesconto : entradas;
  const somaBase = base.reduce((acc, item) => acc + (candidatosComDesconto.length ? item.desconto : item.subtotal), 0) || 1;
  const limiteTotal = entradas.reduce((acc, item) => acc + item.subtotal, 0);
  const descontoAplicado = Math.min(descontoNormalizado, limiteTotal);

  let distribuido = 0;
  base.forEach((item, pos) => {
    if (pos === base.length - 1) {
      definirDescontoItem(item.grupo, item.idx, Math.max(0, descontoAplicado - distribuido));
      return;
    }

    const valorBase = candidatosComDesconto.length ? item.desconto : item.subtotal;
    const parcial = (descontoAplicado * valorBase) / somaBase;
    const arredondado = Number(parcial.toFixed(2));
    distribuido += arredondado;
    definirDescontoItem(item.grupo, item.idx, arredondado);
  });

  entradas.forEach((item) => {
    const atual = Math.max(0, Number(state[item.grupo]?.[item.idx]?.desconto_valor || 0));
    definirDescontoItem(item.grupo, item.idx, Math.min(atual, item.subtotal));
  });

  renderTabelas();
}

function reaplicarDescontoAtualServico() {
  if (ultimaOrigemDesconto === 'percentual') {
    const entradas = obterEntradasRateioDesconto();
    const subtotalBase = entradas.reduce((acc, item) => acc + item.subtotal, 0);
    const percentual = Math.max(0, Math.min(100, valorNum(descontoPercentInput.value)));
    aplicarDescontoRateadoServico(subtotalBase * (percentual / 100));
    return;
  }

  aplicarDescontoRateadoServico(valorNum(descontoTotalInput.value));
}

function atualizarPercentualDescontoServico(snapshot) {
  const entradas = obterEntradasRateioDesconto();
  const subtotalBase = entradas.reduce((acc, item) => acc + item.subtotal, 0);
  const descontoTotal = Number(snapshot?.totais?.desconto_total || 0);
  const percentual = subtotalBase > 0 ? (descontoTotal / subtotalBase) * 100 : 0;
  descontoPercentInput.value = percentual.toFixed(2).replace('.', ',');
}

function prepararCampoDescontoParaDigitacao(input) {
  input.addEventListener('focus', () => {
    if (input.value === '0,00') {
      input.value = '';
      return;
    }

    input.select();
  });

  input.addEventListener('blur', () => {
    if (String(input.value || '').trim() !== '') return;
    input.value = '0,00';
  });
}

function obterIndexMicroservicoFrete() {
  return state.microservicos.findIndex((item) => item.is_frete_embutido === true);
}

function limparFreteProdutosServico() {
  state.produtos = state.produtos.map((item) => ({ ...item, frete_valor: 0 }));
}

function ratearFreteProdutosServico(valorFreteTotal) {
  if (!state.produtos.length) return;

  const totalFrete = Math.max(0, valorFreteTotal);
  const subtotais = state.produtos.map((item) => Math.max(0, Number(item.quantidade || 0) * Number(item.valor_unitario || 0)));
  const somaSubtotais = subtotais.reduce((acc, valor) => acc + valor, 0);
  const divisorFallback = state.produtos.length || 1;

  let distribuido = 0;
  state.produtos = state.produtos.map((item, idx) => {
    if (idx === state.produtos.length - 1) {
      return { ...item, frete_valor: Number((totalFrete - distribuido).toFixed(2)) };
    }

    const base = somaSubtotais > 0 ? subtotais[idx] : 1;
    const divisor = somaSubtotais > 0 ? somaSubtotais : divisorFallback;
    const parcial = (totalFrete * base) / divisor;
    const arredondado = Number(parcial.toFixed(2));
    distribuido += arredondado;
    return { ...item, frete_valor: arredondado };
  });
}

function sincronizarMicroservicoFrete() {
  const checkFreteComoMicro = document.getElementById('freteComoMicroservicoCheck');
  const checkFreteManual = document.getElementById('freteManualCheck');
  const freteTotalInput = document.getElementById('freteTotalInput');
  const valorFreteTotal = Math.max(0, valorNum(freteTotalInput.value));
  const idxFrete = obterIndexMicroservicoFrete();

  if (!checkFreteComoMicro.checked) {
    if (idxFrete >= 0) {
      state.microservicos.splice(idxFrete, 1);
    }
    checkFreteManual.disabled = false;
    return;
  }

  checkFreteManual.checked = true;
  checkFreteManual.disabled = true;
  limparFreteProdutosServico();

  const itemFrete = {
    descricao: DESCRICAO_MICROSERVICO_FRETE,
    quantidade: 1,
    valor_unitario: valorFreteTotal,
    desconto_valor: 0,
    frete_valor: 0,
    is_frete_embutido: true
  };

  if (idxFrete >= 0) {
    state.microservicos[idxFrete] = { ...state.microservicos[idxFrete], ...itemFrete };
    return;
  }

  state.microservicos.push(itemFrete);
}

function renderClientesSugestao(filtro='') {
  const termo = filtro.trim().toLowerCase();
  clientesSugestoes.innerHTML = clientesData.filter(c => !termo || (c.nome_cliente || '').toLowerCase().includes(termo))
    .map(c => `<option value="${String(c.nome_cliente || '').replace(/"/g, '&quot;')}"></option>`).join('');
}

function preencherCliente(nome) {
  const c = clientesData.find(x => (x.nome_cliente || '').trim().toLowerCase() === (nome || '').trim().toLowerCase());
  if (!c) { clienteSelecionadoId = null; return; }
  clienteSelecionadoId = Number(c.id_cliente) || null;
  document.getElementById('clienteTelefone').value = c.telefone_contato || '';
  document.getElementById('clienteCpfCnpj').value = c.cpf_cnpj || '';
  document.getElementById('clienteEmail').value = c.email_contato || '';
  document.getElementById('clienteEndereco').value = c.endereco || '';
}

function montarPayloadClienteRapido() {
  return {
    csrf_token: csrfToken,
    nome_cliente: document.getElementById('clienteNome').value.trim(),
    telefone_contato: document.getElementById('clienteTelefone').value.trim(),
    cpf_cnpj: document.getElementById('clienteCpfCnpj').value.trim(),
    email_contato: document.getElementById('clienteEmail').value.trim(),
    endereco: document.getElementById('clienteEndereco').value.trim()
  };
}

function atualizarClienteNaLista(cliente) {
  if (!cliente || !cliente.id_cliente) return;
  const clienteId = Number(cliente.id_cliente);
  const idx = clientesData.findIndex((item) => Number(item.id_cliente) === clienteId);
  if (idx >= 0) clientesData[idx] = cliente;
  else clientesData.push(cliente);
  clientesData.sort((a, b) => (a.nome_cliente || '').localeCompare((b.nome_cliente || ''), 'pt-BR'));
  renderClientesSugestao(cliente.nome_cliente || '');
}

async function salvarClienteRapido() {
  const textoPadraoBotao = '<i class="fas fa-user-plus me-1"></i>Salvar cliente rápido';
  btnSalvarCliente.disabled = true;
  btnSalvarCliente.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';

  try {
    const resp = await fetch('salvar_cliente.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(montarPayloadClienteRapido())
    });

    const dados = await resp.json();
    if (!resp.ok || !dados.status || !dados.cliente) {
      throw new Error(dados.mensagem || 'Não foi possível salvar o cliente.');
    }

    atualizarClienteNaLista(dados.cliente);
    document.getElementById('clienteNome').value = dados.cliente.nome_cliente || '';
    document.getElementById('clienteTelefone').value = dados.cliente.telefone_contato || '';
    document.getElementById('clienteCpfCnpj').value = dados.cliente.cpf_cnpj || '';
    document.getElementById('clienteEmail').value = dados.cliente.email_contato || '';
    document.getElementById('clienteEndereco').value = dados.cliente.endereco || '';
    clienteSelecionadoId = Number(dados.id_cliente);
    feedback('success', `Cliente #${dados.id_cliente} salvo com sucesso.`);
    const primeiroNomeCliente = obterPrimeiraPalavra(dados.cliente?.nome_cliente);
    if (primeiroNomeCliente && window.AppSpeechFeedback) {
      window.AppSpeechFeedback.speakText(`Cliente ${primeiroNomeCliente} Salvo.`, {
        screen: 'services',
        type: 'success'
      });
    }
  } catch (err) {
    feedback('danger', err.message || 'Erro ao salvar cliente.');
  } finally {
    btnSalvarCliente.disabled = false;
    btnSalvarCliente.innerHTML = textoPadraoBotao;
  }
}

function calcItem(item, isProduto) {
  const subtotal = Math.max(1, Number(item.quantidade || 1)) * Math.max(0, Number(item.valor_unitario || 0));
  const desconto = Math.max(0, Math.min(Number(item.desconto_valor || 0), subtotal));
  const frete = isProduto ? Math.max(0, Number(item.frete_valor || 0)) : 0;
  return { ...item, subtotal, total: Math.max(0, subtotal - desconto + frete) };
}

function renderTabelas({ skipComposicaoSync = false } = {}) {
  const b1 = document.querySelector('#itensProdutoTable tbody');
  const b2 = document.querySelector('#itensMicroTable tbody');
  b1.innerHTML = '';
  b2.innerHTML = '';

  sincronizarMicroservicoFrete();
  state.produtos = state.produtos.map(i => calcItem(i, true));
  state.microservicos = state.microservicos.map(i => calcItem(i, false));

  state.produtos.forEach((i, idx) => {
    b1.insertAdjacentHTML('beforeend', `<tr>
      <td>${idx+1}</td><td>${i.descricao}</td>
      <td><input class="form-control form-control-sm" data-tipo="produto" data-campo="quantidade" data-idx="${idx}" value="${i.quantidade}"></td>
      <td><input class="form-control form-control-sm" data-tipo="produto" data-campo="valor_unitario" data-idx="${idx}" value="${Number(i.valor_unitario).toFixed(2).replace('.', ',')}"></td>
      <td><input class="form-control form-control-sm" data-tipo="produto" data-campo="desconto_valor" data-idx="${idx}" value="${Number(i.desconto_valor).toFixed(2).replace('.', ',')}"></td>
      <td><input class="form-control form-control-sm" data-tipo="produto" data-campo="frete_valor" data-idx="${idx}" value="${Number(i.frete_valor).toFixed(2).replace('.', ',')}"></td>
      <td>${moeda(i.total)}</td>
      <td><button type="button" class="btn btn-sm btn-outline-danger" data-remover="produto" data-idx="${idx}"><i class="fas fa-trash"></i></button></td>
    </tr>`);
  });

  state.microservicos.forEach((i, idx) => {
    const linhaFrete = i.is_frete_embutido === true;
    b2.insertAdjacentHTML('beforeend', `<tr>
      <td>${idx+1}</td><td>${i.descricao}</td>
      <td><input class="form-control form-control-sm" data-tipo="micro" data-campo="quantidade" data-idx="${idx}" value="${i.quantidade}" ${linhaFrete ? 'readonly' : ''}></td>
      <td><input class="form-control form-control-sm" data-tipo="micro" data-campo="valor_unitario" data-idx="${idx}" value="${Number(i.valor_unitario).toFixed(2).replace('.', ',')}" ${linhaFrete ? 'readonly' : ''}></td>
      <td><input class="form-control form-control-sm" data-tipo="micro" data-campo="desconto_valor" data-idx="${idx}" value="${Number(i.desconto_valor).toFixed(2).replace('.', ',')}" ${linhaFrete ? 'readonly' : ''}></td>
      <td>${moeda(i.total)}</td>
      <td>${linhaFrete ? '<span class="badge bg-secondary">Automático</span>' : `<button type="button" class="btn btn-sm btn-outline-danger" data-remover="micro" data-idx="${idx}"><i class="fas fa-trash"></i></button>`}</td>
    </tr>`);
  });

  if (!skipComposicaoSync) {
    syncComposicaoFromState();
  }
}

function feedback(tipo, msg, voiceCode) {
  const box = document.getElementById('servicoFeedback');
  box.className = `alert alert-${tipo}`;
  box.textContent = msg;
  box.classList.remove('d-none');
  if (voiceCode && window.AppSpeechFeedback) {
    window.AppSpeechFeedback.speakFeedback({
      screen: 'services',
      type: tipo,
      text: msg,
      code: voiceCode
    });
  }
}

function limparFeedback() {
  const box = document.getElementById('servicoFeedback');
  box.className = 'alert d-none';
  box.textContent = '';
}

function showBlockingAlert(message, voiceCode) {
  window.alert(message);
  if (window.AppSpeechFeedback) {
    window.AppSpeechFeedback.speakFeedback({
      screen: 'services',
      type: 'warning',
      text: message,
      code: voiceCode
    });
  }
}

function obterPrimeiraPalavra(value) {
  return String(value || '').trim().split(/\s+/).filter(Boolean)[0] || '';
}

function obterDataServicoSelecionada() {
  if (usarDataRetroativaServico.checked && dataRetroativaServico.value) {
    return dataRetroativaServico.value;
  }

  return hojeSP;
}

function sincronizarDataRetroativaServico() {
  dataRetroativaServico.disabled = !usarDataRetroativaServico.checked;
  if (!usarDataRetroativaServico.checked) {
    dataRetroativaServico.value = hojeSP;
  }
  composicao.setBaseDate(obterDataServicoSelecionada());
}

function limparFormularioServicoPosSucesso() {
  clienteSelecionadoId = null;
  document.getElementById('clienteNome').value = '';
  document.getElementById('clienteTelefone').value = '';
  document.getElementById('clienteCpfCnpj').value = '';
  document.getElementById('clienteEmail').value = '';
  document.getElementById('clienteEndereco').value = '';

  document.getElementById('produtoSelect').value = '';
  document.getElementById('produtoQtd').value = '1';
  document.getElementById('produtoValorUnitario').value = '0,00';

  document.getElementById('microDescricao').value = '';
  document.getElementById('microQtd').value = '1';
  document.getElementById('microValor').value = '0,00';

  document.getElementById('freteManualCheck').checked = false;
  document.getElementById('freteManualCheck').disabled = false;
  document.getElementById('freteComoMicroservicoCheck').checked = false;
  document.getElementById('freteTotalInput').value = '0,00';
  descontoTotalInput.value = '0,00';
  descontoPercentInput.value = '0,00';
  document.getElementById('descontoRateioProdutos').checked = true;
  document.getElementById('condicaoPagamento').value = 'vista';
  document.getElementById('qtdParcelas').value = '1';
  vendedorSelect.value = vendedorLogadoPadrao.id ? String(vendedorLogadoPadrao.id) : '';
  usarDataRetroativaServico.checked = false;
  dataRetroativaServico.value = hojeSP;
  dataRetroativaServico.disabled = true;

  state.produtos = [];
  state.microservicos = [];
  ultimaOrigemDesconto = 'valor';

  renderClientesSugestao('');
  renderTabelas();
  document.getElementById('condicaoPagamento').dispatchEvent(new Event('change'));
  limparFeedback();
  document.getElementById('clienteNome').focus();
}

document.getElementById('clienteNome').addEventListener('input', (e) => renderClientesSugestao(e.target.value));
document.getElementById('clienteNome').addEventListener('change', (e) => preencherCliente(e.target.value));
btnSalvarCliente.addEventListener('click', salvarClienteRapido);
btnLimparServico.addEventListener('click', limparFormularioServicoPosSucesso);
usarDataRetroativaServico.addEventListener('change', sincronizarDataRetroativaServico);
dataRetroativaServico.addEventListener('change', () => {
  if (usarDataRetroativaServico.checked) {
    composicao.setBaseDate(obterDataServicoSelecionada());
  }
});
prepararCampoDescontoParaDigitacao(descontoTotalInput);
prepararCampoDescontoParaDigitacao(descontoPercentInput);
document.getElementById('btnZerarDescontos').addEventListener('click', () => {
  ultimaOrigemDesconto = 'valor';
  descontoTotalInput.value = '0,00';
  descontoPercentInput.value = '0,00';
  zerarDescontosServico();
  renderTabelas();
});

descontoTotalInput.addEventListener('input', (event) => {
  event.stopImmediatePropagation();
  ultimaOrigemDesconto = 'valor';
  aplicarDescontoRateadoServico(valorNum(event.target.value));
}, true);

descontoPercentInput.addEventListener('input', (event) => {
  event.stopImmediatePropagation();
  ultimaOrigemDesconto = 'percentual';
  const percentual = Math.max(0, Math.min(100, valorNum(event.target.value)));
  const entradas = obterEntradasRateioDesconto();
  const subtotalBase = entradas.reduce((acc, item) => acc + item.subtotal, 0);
  aplicarDescontoRateadoServico(subtotalBase * (percentual / 100));
}, true);

document.querySelectorAll('input[name="descontoRateioModo"]').forEach((input) => {
  input.addEventListener('change', () => {
    reaplicarDescontoAtualServico();
  });
});

document.getElementById('freteManualCheck').addEventListener('change', () => {
  if (document.getElementById('freteComoMicroservicoCheck').checked) return;
  if (!document.getElementById('freteManualCheck').checked) return;
  ratearFreteProdutosServico(valorNum(document.getElementById('freteTotalInput').value));
  renderTabelas();
});

document.getElementById('freteTotalInput').addEventListener('input', () => {
  if (document.getElementById('freteComoMicroservicoCheck').checked) {
    renderTabelas();
    return;
  }

  if (!document.getElementById('freteManualCheck').checked) return;
  ratearFreteProdutosServico(valorNum(document.getElementById('freteTotalInput').value));
  renderTabelas();
});

document.getElementById('freteComoMicroservicoCheck').addEventListener('change', () => {
  renderTabelas();
});

document.getElementById('produtoSelect').addEventListener('change', (e) => {
  const opt = e.target.selectedOptions[0];
  document.getElementById('produtoValorUnitario').value = valorNum(opt?.dataset?.preco).toFixed(2).replace('.', ',');
});

document.getElementById('btnAdicionarProduto').addEventListener('click', () => {
  const opt = document.getElementById('produtoSelect').selectedOptions[0];
  if (!opt || !opt.value) return showBlockingAlert('Selecione um produto.', 'services.product_required');
  state.produtos.push({
    produto_id: Number(opt.value),
    descricao: opt.dataset.nome || opt.textContent,
    quantidade: Math.max(1, parseInt(document.getElementById('produtoQtd').value, 10) || 1),
    valor_unitario: valorNum(document.getElementById('produtoValorUnitario').value),
    desconto_valor: 0,
    frete_valor: 0
  });
  renderTabelas();
});

document.getElementById('btnAdicionarMicro').addEventListener('click', () => {
  const descricao = document.getElementById('microDescricao').value.trim();
  if (!descricao) return showBlockingAlert('Informe a descrição do micro-serviço.', 'services.micro_description_required');
  state.microservicos.push({
    descricao,
    quantidade: Math.max(1, parseInt(document.getElementById('microQtd').value, 10) || 1),
    valor_unitario: valorNum(document.getElementById('microValor').value),
    desconto_valor: 0,
    frete_valor: 0
  });
  document.getElementById('microDescricao').value = '';
  renderTabelas();
});

['#itensProdutoTable tbody', '#itensMicroTable tbody'].forEach((sel) => {
  document.querySelector(sel).addEventListener('input', (e) => {
    const el = e.target;
    const idx = Number(el.dataset.idx); if (!Number.isInteger(idx)) return;
    const tipo = el.dataset.tipo === 'micro' ? 'microservicos' : 'produtos';
    if (!state[tipo][idx]) return;
    if (tipo === 'microservicos' && state[tipo][idx].is_frete_embutido === true) return;
    const campo = el.dataset.campo;
    state[tipo][idx][campo] = campo === 'quantidade' ? Math.max(1, parseInt(el.value, 10) || 1) : valorNum(el.value);
    renderTabelas();
  });
  document.querySelector(sel).addEventListener('click', (e) => {
    const btn = e.target.closest('[data-remover]');
    if (!btn) return;
    const idx = Number(btn.dataset.idx);
    if (btn.dataset.remover === 'produto') state.produtos.splice(idx, 1);
    else {
      if (state.microservicos[idx]?.is_frete_embutido === true) return;
      state.microservicos.splice(idx, 1);
    }
    renderTabelas();
  });
});

async function salvarServico() {
  if (!state.produtos.length && !state.microservicos.length) return feedback('warning', 'Adicione ao menos um item de produto ou micro-serviço.', 'services.items_required');
  if (clienteObrigatorio && !clienteSelecionadoId) {
    return feedback('warning', 'Selecione um cliente existente ou use o botão "Salvar cliente rápido" antes de salvar o serviço.', 'services.customer_required');
  }

  const vendedorSelecionado = {
    id: Number(vendedorSelect.value || vendedorLogadoPadrao.id || 0),
    nome: vendedorSelect.selectedOptions[0]?.dataset?.nome || vendedorLogadoPadrao.nome || ''
  };
  if (!vendedorSelecionado.id || !vendedorSelecionado.nome) {
    return feedback('warning', 'Selecione um vendedor antes de salvar o serviço.', 'services.vendor_required');
  }

  const resumo = composicao.getResumo();
  const composicaoState = composicao.getState();

  const payload = {
    csrf_token: csrfToken,
    id_servico: servicoEdicaoData && servicoEdicaoData.id_servico ? Number(servicoEdicaoData.id_servico) : null,
    cliente_id: clienteSelecionadoId,
    vendedor_id: vendedorSelecionado.id,
    vendedor_nome: vendedorSelecionado.nome,
    cliente: {
      nome: document.getElementById('clienteNome').value.trim(),
      telefone: document.getElementById('clienteTelefone').value.trim(),
      cpf_cnpj: document.getElementById('clienteCpfCnpj').value.trim(),
      email: document.getElementById('clienteEmail').value.trim(),
      endereco: document.getElementById('clienteEndereco').value.trim()
    },
    data_servico: obterDataServicoSelecionada(),
    condicao_pagamento: document.getElementById('condicaoPagamento').value,
    subtotal_produtos: Number(resumo.subtotal_produtos.toFixed(2)),
    subtotal_microservicos: Number(resumo.subtotal_microservicos.toFixed(2)),
    desconto_total: Number(resumo.desconto_total.toFixed(2)),
    frete_total: Number(resumo.frete_total.toFixed(2)),
    total_geral: Number(resumo.total_geral.toFixed(2)),
    itens_produto: state.produtos,
    itens_microservico: state.microservicos,
    parcelas: composicaoState.parcelas.map((p) => ({
      vencimento: p.vencimento,
      valor: Number(Number(p.valor || 0).toFixed(2)),
      tipo: p.tipoPagamento || 'PIX'
    }))
  };

  const btn = document.getElementById('btnSalvarServico');
  btn.disabled = true;
  try {
    const resp = await fetch('salvar.php', { method:'POST', headers:{ 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
    const dados = await resp.json();
    if (!resp.ok || !dados.status) throw new Error(dados.mensagem || 'Erro ao salvar serviço.');
    window.alert(servicoEdicaoData && servicoEdicaoData.id_servico
      ? `Serviço #${dados.id_servico} atualizado com sucesso.`
      : `Serviço #${dados.id_servico} salvo com sucesso.`);
    if (window.AppSpeechFeedback) {
      window.AppSpeechFeedback.speakText('Serviço Salvo.', {
        screen: 'services',
        type: 'success'
      });
    }
    if (servicoEdicaoData && servicoEdicaoData.id_servico) {
      window.location.href = `<?= app_url('servicos/detalhes.php?id=') ?>${dados.id_servico}`;
      return;
    }
    limparFormularioServicoPosSucesso();
  } catch (err) {
    feedback('danger', err.message || 'Erro ao salvar serviço.', 'services.save_error');
  } finally { btn.disabled = false; }
}

const formServico = document.getElementById('formServico');
const btnSalvarServico = document.getElementById('btnSalvarServico');
let liberarSalvarServicoPorMouse = false;

formServico.addEventListener('submit', (event) => {
  event.preventDefault();
});

btnSalvarServico.addEventListener('pointerdown', () => {
  liberarSalvarServicoPorMouse = true;
});

btnSalvarServico.addEventListener('click', (event) => {
  if (!liberarSalvarServicoPorMouse) {
    event.preventDefault();
    return;
  }

  liberarSalvarServicoPorMouse = false;
  salvarServico();
});

btnSalvarServico.addEventListener('blur', () => {
  liberarSalvarServicoPorMouse = false;
});

function aplicarDadosEdicaoServico() {
  if (!servicoEdicaoData || !servicoEdicaoData.id_servico) {
    return;
  }

  clienteSelecionadoId = Number(servicoEdicaoData.cliente_id || 0) || null;
  document.getElementById('clienteNome').value = servicoEdicaoData.cliente?.nome_cliente || '';
  document.getElementById('clienteTelefone').value = servicoEdicaoData.cliente?.telefone_contato || '';
  document.getElementById('clienteCpfCnpj').value = servicoEdicaoData.cliente?.cpf_cnpj || '';
  document.getElementById('clienteEmail').value = servicoEdicaoData.cliente?.email_contato || '';
  document.getElementById('clienteEndereco').value = servicoEdicaoData.cliente?.endereco || '';
  vendedorSelect.value = String(servicoEdicaoData.vendedor_id || vendedorLogadoPadrao.id || '');
  const dataServicoEdicao = servicoEdicaoData.data_servico || hojeSP;
  const usarRetroativo = dataServicoEdicao < hojeSP;
  usarDataRetroativaServico.checked = usarRetroativo;
  dataRetroativaServico.value = usarRetroativo ? dataServicoEdicao : hojeSP;
  sincronizarDataRetroativaServico();

  state.produtos = Array.isArray(servicoEdicaoData.produtos) ? servicoEdicaoData.produtos : [];
  state.microservicos = Array.isArray(servicoEdicaoData.microservicos) ? servicoEdicaoData.microservicos : [];

  document.getElementById('condicaoPagamento').value = servicoEdicaoData.condicao_pagamento === 'parcelado' ? 'parcelado' : 'vista';

  renderClientesSugestao(servicoEdicaoData.cliente?.nome_cliente || '');
  renderTabelas();

  const parcelas = Array.isArray(servicoEdicaoData.parcelas) ? servicoEdicaoData.parcelas : [];
  if (parcelas.length) {
    composicao.setParcelas(parcelas);
  }
}

renderClientesSugestao('');
renderTabelas();
document.getElementById('condicaoPagamento').dispatchEvent(new Event('change'));
aplicarDadosEdicaoServico();
</script>

<?php include '../includes/footer.php'; ?>
