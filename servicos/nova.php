<?php
include '../includes/db.php';
require_login();
require_once __DIR__ . '/_infra.php';

servicos_ensure_schema($pdo);
$clientes = servicos_obter_clientes($pdo);
$clienteObrigatorio = servicos_cliente_obrigatorio();

$produtosStmt = $pdo->query("SELECT id, nome, preco1 FROM produtos ORDER BY nome");
$produtos = $produtosStmt ? $produtosStmt->fetchAll(PDO::FETCH_ASSOC) : [];

$hojeSaoPaulo = (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="fas fa-tools me-2"></i>Tela de Serviços</h4>
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
                                <label class="form-label">Nome cliente</label>
                                <input type="text" class="form-control" id="clienteNome" list="clientesSugestoes" placeholder="Digite para buscar ou preencher manualmente...">
                                <datalist id="clientesSugestoes">
                                    <?php foreach ($clientes as $cliente): ?>
                                        <option value="<?= htmlspecialchars($cliente['nome_cliente']) ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Telefone</label>
                                <input type="text" class="form-control" id="clienteTelefone">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">CPF/CNPJ</label>
                                <input type="text" class="form-control" id="clienteCpfCnpj">
                            </div>
                        </div>
                        <div class="row g-3 align-items-end mt-1">
                            <div class="col-md-4">
                                <label class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="clienteEmail">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Endereço</label>
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
                                <label class="form-label">Produto</label>
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
                                <label class="form-label">Qtd.</label>
                                <input type="number" min="1" class="form-control" id="produtoQtd" value="1">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Vlr. unitário</label>
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
                                    <label class="form-label">Micro-serviço</label>
                                    <input type="text" id="microDescricao" class="form-control" placeholder="Ex.: Instalação de bomba">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Qtd.</label>
                                    <input type="number" id="microQtd" min="1" class="form-control" value="1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Vlr. unitário</label>
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
                                    <label class="form-label">Frete total</label>
                                    <input type="text" inputmode="decimal" class="form-control" id="freteTotalInput" value="0,00">
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
                                    <label class="form-label">Desconto total (R$)</label>
                                    <input type="text" inputmode="decimal" class="form-control" id="descontoTotalInput" value="0,00">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">ou Desconto (%)</label>
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

                        <div class="d-flex justify-content-between mt-4"><span>Subtotal produtos:</span><strong id="subtotalProdutos">R$ 0,00</strong></div>
                        <div class="d-flex justify-content-between"><span>Total descontos:</span><strong id="totalDescontos">R$ 0,00</strong></div>
                        <div class="d-flex justify-content-between"><span>Total frete:</span><strong id="totalFrete">R$ 0,00</strong></div>
                        <hr>
                        <div class="d-flex justify-content-between"><span>Total geral do serviço:</span><span class="total-pill" id="totalGeralServico">R$ 0,00</span></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-primary-subtle h-100">
                    <div class="card-header bg-light sales-block-title">Forma e condição de pagamento</div>
                    <div class="card-body">
                        <div class="row g-2 mb-2">
                            <div class="col-md-6"><label class="form-label">Condição</label><select id="condicaoPagamento" class="form-select"><option value="vista">À vista</option><option value="parcelado">Parcelado</option></select></div>
                            <div class="col-md-6"><label class="form-label">Qtd. parcelas</label><input type="number" id="qtdParcelas" class="form-control" min="1" max="24" value="1"></div>
                        </div>
                        <p class="context-tip mb-2">No modo parcelado, clique com o botão direito na tabela para adicionar parcela; botão direito em uma linha remove.</p>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="parcelasTable">
                                <thead class="table-secondary">
                                    <tr>
                                        <th>#</th>
                                        <th>Vencimento</th>
                                        <th>Valor</th>
                                        <th>Tipo Pagamento</th>
                                        <th>Qtd. Parcelas</th>
                                        <th>Total Parcelas</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div id="servicoFeedback" class="alert d-none" role="alert"></div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="button" class="btn btn-outline-secondary" id="btnLimparServico"><i class="fas fa-broom me-1"></i>Limpar campos</button>
                    <button type="submit" class="btn btn-primary" id="btnSalvarServico"><i class="fas fa-save me-1"></i>Salvar serviço</button>
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
const tiposPagamento = [
  'PIX', 'Dinheiro', 'Boleto', 'Cheque', 'Pix Pague Seguro',
  'Débito PagSeguro', 'Crédito PagSeguro', 'Débito Stone',
  'Crédito Stone', 'Débito Infinite', 'Crédito Infinite'
];

const state = { produtos: [], microservicos: [] };

const moeda = (v) => Number(v || 0).toLocaleString('pt-BR', { style:'currency', currency:'BRL' });
const valorNum = window.ComposicaoComercial.valorNum;

let clienteSelecionadoId = null;
const clientesSugestoes = document.getElementById('clientesSugestoes');
const btnSalvarCliente = document.getElementById('btnSalvarCliente');
const btnLimparServico = document.getElementById('btnLimparServico');

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

  composicao.setItens(produtos, microservicos);
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

function renderTabelas() {
  const b1 = document.querySelector('#itensProdutoTable tbody');
  const b2 = document.querySelector('#itensMicroTable tbody');
  b1.innerHTML = '';
  b2.innerHTML = '';

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
    b2.insertAdjacentHTML('beforeend', `<tr>
      <td>${idx+1}</td><td>${i.descricao}</td>
      <td><input class="form-control form-control-sm" data-tipo="micro" data-campo="quantidade" data-idx="${idx}" value="${i.quantidade}"></td>
      <td><input class="form-control form-control-sm" data-tipo="micro" data-campo="valor_unitario" data-idx="${idx}" value="${Number(i.valor_unitario).toFixed(2).replace('.', ',')}"></td>
      <td><input class="form-control form-control-sm" data-tipo="micro" data-campo="desconto_valor" data-idx="${idx}" value="${Number(i.desconto_valor).toFixed(2).replace('.', ',')}"></td>
      <td>${moeda(i.total)}</td>
      <td><button type="button" class="btn btn-sm btn-outline-danger" data-remover="micro" data-idx="${idx}"><i class="fas fa-trash"></i></button></td>
    </tr>`);
  });

  syncComposicaoFromState();
}

function feedback(tipo, msg) {
  const box = document.getElementById('servicoFeedback');
  box.className = `alert alert-${tipo}`;
  box.textContent = msg;
  box.classList.remove('d-none');
}

function limparFeedback() {
  const box = document.getElementById('servicoFeedback');
  box.className = 'alert d-none';
  box.textContent = '';
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
  document.getElementById('freteTotalInput').value = '0,00';
  document.getElementById('descontoTotalInput').value = '0,00';
  document.getElementById('descontoPercentInput').value = '0,00';
  document.getElementById('condicaoPagamento').value = 'vista';
  document.getElementById('qtdParcelas').value = '1';

  state.produtos = [];
  state.microservicos = [];

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
document.getElementById('btnZerarDescontos').addEventListener('click', () => composicao.zerarDescontosProdutos());

document.getElementById('produtoSelect').addEventListener('change', (e) => {
  const opt = e.target.selectedOptions[0];
  document.getElementById('produtoValorUnitario').value = valorNum(opt?.dataset?.preco).toFixed(2).replace('.', ',');
});

document.getElementById('btnAdicionarProduto').addEventListener('click', () => {
  const opt = document.getElementById('produtoSelect').selectedOptions[0];
  if (!opt || !opt.value) return alert('Selecione um produto.');
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
  if (!descricao) return alert('Informe a descrição do micro-serviço.');
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
    const campo = el.dataset.campo;
    state[tipo][idx][campo] = campo === 'quantidade' ? Math.max(1, parseInt(el.value, 10) || 1) : valorNum(el.value);
    renderTabelas();
  });
  document.querySelector(sel).addEventListener('click', (e) => {
    const btn = e.target.closest('[data-remover]');
    if (!btn) return;
    const idx = Number(btn.dataset.idx);
    if (btn.dataset.remover === 'produto') state.produtos.splice(idx, 1);
    else state.microservicos.splice(idx, 1);
    renderTabelas();
  });
});

document.getElementById('formServico').addEventListener('submit', async (e) => {
  e.preventDefault();
  if (!state.produtos.length && !state.microservicos.length) return feedback('warning', 'Adicione ao menos um item de produto ou micro-serviço.');
  if (clienteObrigatorio && !clienteSelecionadoId) {
    return feedback('warning', 'Selecione um cliente existente ou use o botão "Salvar cliente rápido" antes de salvar o serviço.');
  }

  const resumo = composicao.getResumo();
  const composicaoState = composicao.getState();

  const payload = {
    csrf_token: csrfToken,
    cliente_id: clienteSelecionadoId,
    cliente: {
      nome: document.getElementById('clienteNome').value.trim(),
      telefone: document.getElementById('clienteTelefone').value.trim(),
      cpf_cnpj: document.getElementById('clienteCpfCnpj').value.trim(),
      email: document.getElementById('clienteEmail').value.trim(),
      endereco: document.getElementById('clienteEndereco').value.trim()
    },
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
    window.alert(`Serviço #${dados.id_servico} salvo com sucesso.`);
    limparFormularioServicoPosSucesso();
  } catch (err) {
    feedback('danger', err.message || 'Erro ao salvar serviço.');
  } finally { btn.disabled = false; }
});

renderClientesSugestao('');
renderTabelas();
document.getElementById('condicaoPagamento').dispatchEvent(new Event('change'));
</script>

<?php include '../includes/footer.php'; ?>
