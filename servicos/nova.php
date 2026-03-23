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
                        <div class="row g-2 mb-2">
                            <div class="col-md-6"><label class="form-label">Frete total</label><input type="text" id="freteTotalInput" class="form-control" value="0,00"></div>
                            <div class="col-md-6"><label class="form-label">Desconto extra (R$)</label><input type="text" id="descontoExtraInput" class="form-control" value="0,00"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-4"><span>Subtotal produtos:</span><strong id="subtotalProdutos">R$ 0,00</strong></div>
                        <div class="d-flex justify-content-between"><span>Subtotal micro-serviços:</span><strong id="subtotalMicro">R$ 0,00</strong></div>
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
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="parcelasTable"><thead class="table-secondary"><tr><th>#</th><th>Vencimento</th><th>Valor</th><th>Tipo</th></tr></thead><tbody></tbody></table>
                        </div>
                        <div class="d-grid mt-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRecalcularParcelas">
                                <i class="fas fa-calculator me-1"></i>Recalcular parcelas
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div id="servicoFeedback" class="alert d-none" role="alert"></div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary" id="btnSalvarServico"><i class="fas fa-save me-1"></i>Salvar serviço</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const hojeSP = '<?= $hojeSaoPaulo ?>';
const csrfToken = '<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>';
const clientesData = <?= json_encode($clientes, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const clienteObrigatorio = <?= $clienteObrigatorio ? 'true' : 'false' ?>;

const state = { produtos: [], microservicos: [], parcelas: [{ vencimento: hojeSP, valor: 0, tipo: 'PIX', manual: false }] };
let ultimoTotalParcelado = null;

const moeda = (v) => Number(v || 0).toLocaleString('pt-BR', { style:'currency', currency:'BRL' });
const valorNum = (v) => {
  const t = String(v ?? '').trim();
  const n = parseFloat(t.includes(',') ? t.replace(/\./g, '').replace(',', '.') : t);
  return Number.isFinite(n) ? n : 0;
};

let clienteSelecionadoId = null;
const clientesSugestoes = document.getElementById('clientesSugestoes');
const btnSalvarCliente = document.getElementById('btnSalvarCliente');

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

  renderResumoEParcelas();
}

function resumo() {
  const subtotalProdutos = state.produtos.reduce((a,b) => a + b.subtotal, 0);
  const subtotalMicro = state.microservicos.reduce((a,b) => a + b.subtotal, 0);
  const descontosItens = state.produtos.reduce((a,b) => a + Number(b.desconto_valor || 0), 0) + state.microservicos.reduce((a,b) => a + Number(b.desconto_valor || 0), 0);
  const freteItens = state.produtos.reduce((a,b) => a + Number(b.frete_valor || 0), 0);
  const descontoExtra = Math.max(0, valorNum(document.getElementById('descontoExtraInput').value));
  const freteManual = Math.max(0, valorNum(document.getElementById('freteTotalInput').value));
  const frete_total = freteItens + freteManual;
  const descontoTotal = descontosItens + descontoExtra;
  const total = Math.max(0, subtotalProdutos + subtotalMicro - descontoTotal + frete_total);
  return { subtotalProdutos, subtotalMicro, descontoTotal, frete_total, total };
}

function renderParcelas() {
  const body = document.querySelector('#parcelasTable tbody');
  body.innerHTML = '';
  state.parcelas.forEach((p, idx) => {
    body.insertAdjacentHTML('beforeend', `<tr>
      <td>${idx+1}</td>
      <td><input type="date" class="form-control form-control-sm" data-parcela="vencimento" data-idx="${idx}" value="${p.vencimento}"></td>
      <td><input class="form-control form-control-sm" data-parcela="valor" data-idx="${idx}" value="${Number(p.valor).toFixed(2).replace('.', ',')}"></td>
      <td><input class="form-control form-control-sm" data-parcela="tipo" data-idx="${idx}" value="${p.tipo}"></td>
    </tr>`);
  });
}

function obterCondicaoPagamento() {
  return document.getElementById('condicaoPagamento').value === 'parcelado' ? 'parcelado' : 'vista';
}

function distribuirParcelas() {
  const total = resumo().total;
  const qtd = Math.max(1, parseInt(document.getElementById('qtdParcelas').value, 10) || 1);
  const parcelaBase = state.parcelas[0] || { vencimento: hojeSP, tipo: 'PIX' };
  const baseVencimento = parcelaBase.vencimento || hojeSP;
  const baseTipo = parcelaBase.tipo || 'PIX';
  const baseDate = new Date(`${baseVencimento}T12:00:00`);
  const dataInicial = Number.isNaN(baseDate.getTime()) ? new Date(`${hojeSP}T12:00:00`) : baseDate;

  state.parcelas = Array.from({ length: qtd }, (_, i) => {
    const atual = state.parcelas[i] || {};
    const d = new Date(dataInicial);
    d.setMonth(d.getMonth() + i);
    return {
      vencimento: atual.vencimento || d.toISOString().slice(0,10),
      valor: Number(atual.valor || 0),
      tipo: atual.tipo || baseTipo,
      manual: Boolean(atual.manual)
    };
  });

  const manuais = state.parcelas.filter((p) => p.manual);
  const editaveis = state.parcelas.filter((p) => !p.manual);
  const somaManuais = manuais.reduce((acc, p) => acc + Number(p.valor || 0), 0);
  const restante = Math.max(0, Number((total - somaManuais).toFixed(2)));
  const valorPadrao = editaveis.length ? restante / editaveis.length : 0;

  editaveis.forEach((p, i) => {
    if (i === editaveis.length - 1) {
      const somaPrev = editaveis.slice(0, -1).reduce((acc, cur) => acc + Number(cur.valor || 0), 0);
      p.valor = Number((restante - somaPrev).toFixed(2));
      return;
    }
    p.valor = Number(valorPadrao.toFixed(2));
  });

  ultimoTotalParcelado = Number(total.toFixed(2));
  renderParcelas();
}

function forcarParcelaVista() {
  const total = resumo().total;
  const atual = state.parcelas[0] || {};
  state.parcelas = [{
    vencimento: atual.vencimento || hojeSP,
    valor: Number(total.toFixed(2)),
    tipo: atual.tipo || 'PIX',
    manual: false
  }];
  ultimoTotalParcelado = Number(total.toFixed(2));
  renderParcelas();
}

function aplicarCondicaoPagamento({ redistribuir = true } = {}) {
  const condicao = obterCondicaoPagamento();
  const qtdInput = document.getElementById('qtdParcelas');

  if (condicao === 'vista') {
    qtdInput.value = '1';
    qtdInput.disabled = true;
    forcarParcelaVista();
    return;
  }

  qtdInput.disabled = false;
  qtdInput.value = String(Math.max(1, parseInt(qtdInput.value, 10) || state.parcelas.length || 1));
  if (redistribuir) distribuirParcelas();
  else renderParcelas();
}

function sincronizarValoresParcelasComTotal() {
  const totalAtual = Number(resumo().total.toFixed(2));
  const totalAnterior = Number.isFinite(ultimoTotalParcelado) ? ultimoTotalParcelado : null;
  const diferenca = totalAnterior === null ? totalAtual : Number((totalAtual - totalAnterior).toFixed(2));

  if (obterCondicaoPagamento() === 'vista') {
    forcarParcelaVista();
    return;
  }

  if (!state.parcelas.length) return;

  if (Math.abs(diferenca) < 0.01) {
    return;
  }

  const indicesNaoManuais = state.parcelas
    .map((p, idx) => ({ p, idx }))
    .filter(({ p }) => !p.manual)
    .map(({ idx }) => idx);

  if (!indicesNaoManuais.length) {
    ultimoTotalParcelado = totalAtual;
    return;
  }

  const idxAjuste = indicesNaoManuais[indicesNaoManuais.length - 1];
  state.parcelas[idxAjuste].valor = Number((Number(state.parcelas[idxAjuste].valor || 0) + diferenca).toFixed(2));
  if (state.parcelas[idxAjuste].valor < 0) state.parcelas[idxAjuste].valor = 0;

  ultimoTotalParcelado = totalAtual;
  renderParcelas();
}

function renderResumoEParcelas() {
  const r = resumo();
  document.getElementById('subtotalProdutos').textContent = moeda(r.subtotalProdutos);
  document.getElementById('subtotalMicro').textContent = moeda(r.subtotalMicro);
  document.getElementById('totalDescontos').textContent = moeda(r.descontoTotal);
  document.getElementById('totalFrete').textContent = moeda(r.frete_total);
  document.getElementById('totalGeralServico').textContent = moeda(r.total);
  sincronizarValoresParcelasComTotal();
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

  document.getElementById('freteTotalInput').value = '0,00';
  document.getElementById('descontoExtraInput').value = '0,00';

  document.getElementById('condicaoPagamento').value = 'vista';
  document.getElementById('qtdParcelas').value = '1';

  state.produtos = [];
  state.microservicos = [];
  state.parcelas = [{ vencimento: hojeSP, valor: 0, tipo: 'PIX', manual: false }];
  ultimoTotalParcelado = null;

  renderClientesSugestao('');
  renderTabelas();
  aplicarCondicaoPagamento({ redistribuir: false });
  limparFeedback();
  document.getElementById('clienteNome').focus();
}

document.getElementById('clienteNome').addEventListener('input', (e) => renderClientesSugestao(e.target.value));
document.getElementById('clienteNome').addEventListener('change', (e) => preencherCliente(e.target.value));
btnSalvarCliente.addEventListener('click', salvarClienteRapido);

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

['freteTotalInput','descontoExtraInput'].forEach((id) => {
  document.getElementById(id).addEventListener('input', renderResumoEParcelas);
  document.getElementById(id).addEventListener('change', renderResumoEParcelas);
});

document.getElementById('qtdParcelas').addEventListener('input', () => {
  if (obterCondicaoPagamento() !== 'parcelado') return;
  distribuirParcelas();
});
document.getElementById('qtdParcelas').addEventListener('change', () => {
  if (obterCondicaoPagamento() !== 'parcelado') return;
  distribuirParcelas();
});

document.getElementById('condicaoPagamento').addEventListener('change', () => {
  aplicarCondicaoPagamento({ redistribuir: true });
});

document.getElementById('btnRecalcularParcelas').addEventListener('click', () => {
  if (obterCondicaoPagamento() === 'vista') {
    forcarParcelaVista();
    return;
  }
  distribuirParcelas();
});

document.querySelector('#parcelasTable tbody').addEventListener('input', (e) => {
  const idx = Number(e.target.dataset.idx); if (!state.parcelas[idx]) return;
  if (e.target.dataset.parcela === 'vencimento') state.parcelas[idx].vencimento = e.target.value || hojeSP;
  if (e.target.dataset.parcela === 'valor') {
    state.parcelas[idx].valor = valorNum(e.target.value);
    state.parcelas[idx].manual = true;
  }
  if (e.target.dataset.parcela === 'tipo') state.parcelas[idx].tipo = e.target.value || 'PIX';
});

document.getElementById('formServico').addEventListener('submit', async (e) => {
  e.preventDefault();
  if (!state.produtos.length && !state.microservicos.length) return feedback('warning', 'Adicione ao menos um item de produto ou micro-serviço.');
  if (clienteObrigatorio && !clienteSelecionadoId) {
    return feedback('warning', 'Selecione um cliente existente ou use o botão "Salvar cliente rápido" antes de salvar o serviço.');
  }
  const r = resumo();
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
    subtotal_produtos: Number(r.subtotalProdutos.toFixed(2)),
    subtotal_microservicos: Number(r.subtotalMicro.toFixed(2)),
    desconto_total: Number(r.descontoTotal.toFixed(2)),
    frete_total: Number(r.frete_total.toFixed(2)),
    total_geral: Number(r.total.toFixed(2)),
    itens_produto: state.produtos,
    itens_microservico: state.microservicos,
    parcelas: state.parcelas
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
aplicarCondicaoPagamento({ redistribuir: false });
</script>

<?php include '../includes/footer.php'; ?>
