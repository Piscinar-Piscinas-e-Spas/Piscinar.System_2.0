<?php
include '../includes/db.php';
include '../includes/header.php';

$clientesStmt = $pdo->query('SELECT id_cliente, nome_cliente, telefone_contato, cpf_cnpj FROM clientes ORDER BY nome_cliente LIMIT 300');
$clientes = $clientesStmt->fetchAll(PDO::FETCH_ASSOC);

$produtosStmt = $pdo->query('SELECT id, nome, preco1 FROM produtos ORDER BY nome LIMIT 500');
$produtos = $produtosStmt->fetchAll(PDO::FETCH_ASSOC);

$hojeSaoPaulo = (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
?>

<style>
    .sales-block-title {
        font-weight: 600;
        color: #0d6efd;
    }

    .resumo-card {
        background: linear-gradient(135deg, #f8fbff 0%, #f2f7ff 100%);
        border: 1px solid #dbe9ff;
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 12px;
    }

    .table th {
        white-space: nowrap;
        font-size: 0.85rem;
    }

    .table td input,
    .table td select {
        min-width: 110px;
    }

    .total-pill {
        font-size: 1rem;
        font-weight: 700;
        color: #0f5132;
        background-color: #d1e7dd;
        border-radius: 8px;
        padding: 8px 12px;
        display: inline-block;
    }

    .context-tip {
        font-size: 0.85rem;
        color: #6c757d;
    }
</style>

<div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>Tela de Vendas</h4>
        <span class="badge bg-light text-dark">Orçamento / Pedido</span>
    </div>

    <div class="card-body">
        <form id="formVenda" class="row g-3" autocomplete="off">
            <div class="col-12">
                <div class="card border-primary-subtle">
                    <div class="card-header bg-light sales-block-title">1) Cliente</div>
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label">Selecionar cliente</label>
                                <select class="form-select" id="clienteSelect">
                                    <option value="">Selecionar / preencher manualmente...</option>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <option
                                            value="<?= (int) $cliente['id_cliente'] ?>"
                                            data-nome="<?= htmlspecialchars($cliente['nome_cliente']) ?>"
                                            data-telefone="<?= htmlspecialchars($cliente['telefone_contato'] ?? '') ?>"
                                            data-cpfcnpj="<?= htmlspecialchars($cliente['cpf_cnpj'] ?? '') ?>">
                                            <?= htmlspecialchars($cliente['nome_cliente']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nome cliente</label>
                                <input type="text" class="form-control" id="clienteNome" placeholder="Nome do cliente">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Telefone</label>
                                <input type="text" class="form-control" id="clienteTelefone" placeholder="(00) 00000-0000">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">CPF/CNPJ</label>
                                <input type="text" class="form-control" id="clienteCpfCnpj" placeholder="Somente números">
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
                            <div class="col-md-6">
                                <label class="form-label">Produto</label>
                                <select class="form-select" id="produtoSelect">
                                    <option value="">Selecione um produto...</option>
                                    <?php foreach ($produtos as $produto): ?>
                                        <option value="<?= (int) $produto['id'] ?>"
                                            data-nome="<?= htmlspecialchars($produto['nome']) ?>"
                                            data-preco="<?= number_format((float) ($produto['preco1'] ?? 0), 2, '.', '') ?>">
                                            <?= htmlspecialchars($produto['nome']) ?> (R$ <?= number_format((float) ($produto['preco1'] ?? 0), 2, ',', '.') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Qtd.</label>
                                <input type="number" min="1" step="1" class="form-control" id="produtoQtd" value="1">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Vlr. unitário</label>
                                <input type="number" min="0" step="0.01" class="form-control" id="produtoValorUnitario" value="0.00">
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
                                    <label class="form-label">Frete total</label>
                                    <input type="number" min="0" step="0.01" class="form-control" id="freteTotalInput" value="0.00">
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
                                    <input type="number" min="0" step="0.01" class="form-control" id="descontoTotalInput" value="0.00">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">ou Desconto (%)</label>
                                    <input type="number" min="0" max="100" step="0.01" class="form-control" id="descontoPercentInput" value="0.00">
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
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="form-label">Condição</label>
                                <select id="condicaoPagamento" class="form-select">
                                    <option value="vista">À vista</option>
                                    <option value="parcelado">Parcelado</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Qtd. parcelas</label>
                                <input type="number" id="qtdParcelas" class="form-control" min="1" max="24" value="1">
                            </div>
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
        </form>
    </div>
</div>

<script>
    const hojeSP = '<?= $hojeSaoPaulo ?>';
    const tiposPagamento = [
        'PIX', 'Dinheiro', 'Boleto', 'Cheque', 'Pix Pague Seguro',
        'Débito PagSeguro', 'Crédito PagSeguro', 'Débito Stone',
        'Crédito Stone', 'Débito Infinite', 'Crédito Infinite'
    ];

    const itens = [];
    let parcelas = [];
    let descontoPercentControlando = false;

    const itensBody = document.querySelector('#itensTable tbody');
    const parcelasBody = document.querySelector('#parcelasTable tbody');

    function moeda(valor) {
        return Number(valor || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    function valorNum(v) {
        const n = parseFloat(v);
        return Number.isFinite(n) ? n : 0;
    }

    function renderItens() {
        itensBody.innerHTML = '';

        itens.forEach((item, index) => {
            const total = item.quantidade * item.valorUnitario;
            item.desconto = Math.max(0, Math.min(item.desconto, total));
            const totalComDesconto = Math.max(0, total - item.desconto);
            const unitComDesconto = item.quantidade > 0 ? totalComDesconto / item.quantidade : 0;
            const totalItem = totalComDesconto + item.freteItem;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${index + 1}</td>
                <td>${item.nome}</td>
                <td><input type="number" min="1" step="1" class="form-control form-control-sm item-qtd" data-index="${index}" value="${item.quantidade}"></td>
                <td><input type="number" min="0" step="0.01" class="form-control form-control-sm item-unit" data-index="${index}" value="${item.valorUnitario.toFixed(2)}"></td>
                <td>${moeda(total)}</td>
                <td><input type="number" min="0" step="0.01" class="form-control form-control-sm item-desc" data-index="${index}" value="${item.desconto.toFixed(2)}"></td>
                <td>${moeda(unitComDesconto)}</td>
                <td>${moeda(totalComDesconto)}</td>
                <td><input type="number" min="0" step="0.01" class="form-control form-control-sm item-frete" data-index="${index}" value="${item.freteItem.toFixed(2)}"></td>
                <td>${moeda(totalItem)}</td>
                <td><button type="button" class="btn btn-sm btn-outline-danger item-remove" data-index="${index}"><i class="fas fa-trash"></i></button></td>
            `;
            itensBody.appendChild(tr);
        });

        atualizarResumo();
    }

    function ratearDesconto(totalDesconto) {
        const subtotais = itens.map(i => i.quantidade * i.valorUnitario);
        const totalBase = subtotais.reduce((a, b) => a + b, 0);
        if (!totalBase || !itens.length) return;

        const itensComDesconto = itens
            .map((item, idx) => ({ idx, desconto: item.desconto }))
            .filter(x => x.desconto > 0);

        let baseIndices;
        let baseValores;

        if (itensComDesconto.length > 0) {
            baseIndices = itensComDesconto.map(x => x.idx);
            baseValores = itensComDesconto.map(x => x.desconto);
        } else {
            baseIndices = itens.map((_, idx) => idx);
            baseValores = subtotais;
        }

        const somaBase = baseValores.reduce((a, b) => a + b, 0) || 1;

        itens.forEach((item, idx) => {
            if (!baseIndices.includes(idx)) {
                item.desconto = 0;
            }
        });

        let distribuido = 0;
        baseIndices.forEach((idx, pos) => {
            if (pos === baseIndices.length - 1) {
                itens[idx].desconto = Math.max(0, totalDesconto - distribuido);
                return;
            }
            const parcial = (totalDesconto * baseValores[pos]) / somaBase;
            const arredondado = Number(parcial.toFixed(2));
            distribuido += arredondado;
            itens[idx].desconto = arredondado;
        });

        itens.forEach((item, idx) => {
            const subtotal = subtotais[idx];
            item.desconto = Math.max(0, Math.min(item.desconto, subtotal));
        });

        renderItens();
    }

    function totalVenda() {
        return itens.reduce((acc, item) => {
            const subtotal = item.quantidade * item.valorUnitario;
            const subtotalDesc = Math.max(0, subtotal - item.desconto);
            return acc + subtotalDesc + item.freteItem;
        }, 0);
    }

    function atualizarResumo() {
        const subtotal = itens.reduce((acc, item) => acc + (item.quantidade * item.valorUnitario), 0);
        const desconto = itens.reduce((acc, item) => acc + item.desconto, 0);
        const freteItens = itens.reduce((acc, item) => acc + item.freteItem, 0);

        const freteManual = document.getElementById('freteManualCheck').checked;
        const freteInput = document.getElementById('freteTotalInput');
        const freteFinal = freteManual ? valorNum(freteInput.value) : freteItens;

        if (!freteManual) {
            freteInput.value = freteItens.toFixed(2);
        }

        const total = Math.max(0, subtotal - desconto + freteFinal);
        const descontoPercent = subtotal > 0 ? (desconto / subtotal) * 100 : 0;

        document.getElementById('subtotalProdutos').textContent = moeda(subtotal);
        document.getElementById('totalDescontos').textContent = moeda(desconto);
        document.getElementById('totalFrete').textContent = moeda(freteFinal);
        document.getElementById('totalGeralVenda').textContent = moeda(total);
        document.getElementById('descontoTotalInput').value = desconto.toFixed(2);

        if (!descontoPercentControlando) {
            document.getElementById('descontoPercentInput').value = descontoPercent.toFixed(2);
        }

        recalcularParcelas();
    }

    function criarParcela(vencimento = hojeSP, valor = 0, tipoPagamento = 'PIX', manual = false) {
        return { vencimento, valor, tipoPagamento, manual };
    }

    function montarParcelas(qtd) {
        const quantidade = Math.max(1, qtd);
        parcelas = Array.from({ length: quantidade }, (_, i) => {
            const d = new Date(hojeSP + 'T12:00:00');
            d.setMonth(d.getMonth() + i);
            const dataISO = d.toISOString().slice(0, 10);
            return criarParcela(dataISO, 0, 'PIX', false);
        });
        recalcularParcelas();
    }

    function recalcularParcelas() {
        if (!parcelas.length) montarParcelas(1);
        const total = totalVenda();
        const manuais = parcelas.filter(p => p.manual);
        const somaManuais = manuais.reduce((acc, p) => acc + valorNum(p.valor), 0);
        const editaveis = parcelas.filter(p => !p.manual);
        const restante = Math.max(0, total - somaManuais);
        const valorPadrao = editaveis.length ? restante / editaveis.length : 0;

        editaveis.forEach((p, i) => {
            if (i === editaveis.length - 1) {
                const somaPrev = editaveis.slice(0, -1).reduce((acc, cur) => acc + valorNum(cur.valor), 0);
                p.valor = Number((restante - somaPrev).toFixed(2));
            } else {
                p.valor = Number(valorPadrao.toFixed(2));
            }
        });

        renderParcelas();
    }

    function renderParcelas() {
        const condicao = document.getElementById('condicaoPagamento').value;
        const qtdTotal = parcelas.length;
        parcelasBody.innerHTML = '';

        parcelas.forEach((parcela, index) => {
            const tr = document.createElement('tr');
            tr.dataset.index = index;

            const tipoOptions = tiposPagamento
                .map(tipo => `<option value="${tipo}" ${parcela.tipoPagamento === tipo ? 'selected' : ''}>${tipo}</option>`)
                .join('');

            tr.innerHTML = `
                <td>${index + 1}</td>
                <td><input type="date" class="form-control form-control-sm parcela-venc" data-index="${index}" value="${parcela.vencimento}" ${condicao === 'vista' ? 'readonly' : ''}></td>
                <td><input type="number" min="0" step="0.01" class="form-control form-control-sm parcela-valor" data-index="${index}" value="${valorNum(parcela.valor).toFixed(2)}"></td>
                <td><select class="form-select form-select-sm parcela-tipo" data-index="${index}">${tipoOptions}</select></td>
                <td>${index + 1}</td>
                <td>${qtdTotal}</td>
            `;
            parcelasBody.appendChild(tr);
        });

        document.getElementById('qtdParcelas').value = qtdTotal;
    }

    document.getElementById('clienteSelect').addEventListener('change', (event) => {
        const selected = event.target.selectedOptions[0];
        document.getElementById('clienteNome').value = selected?.dataset?.nome || '';
        document.getElementById('clienteTelefone').value = selected?.dataset?.telefone || '';
        document.getElementById('clienteCpfCnpj').value = selected?.dataset?.cpfcnpj || '';
    });

    document.getElementById('produtoSelect').addEventListener('change', (event) => {
        const opt = event.target.selectedOptions[0];
        document.getElementById('produtoValorUnitario').value = (valorNum(opt?.dataset?.preco)).toFixed(2);
    });

    document.getElementById('btnAdicionarProduto').addEventListener('click', () => {
        const select = document.getElementById('produtoSelect');
        const opt = select.selectedOptions[0];

        if (!opt || !opt.value) {
            alert('Selecione um produto para adicionar.');
            return;
        }

        itens.push({
            produtoId: Number(opt.value),
            nome: opt.dataset.nome || opt.textContent,
            quantidade: Math.max(1, parseInt(document.getElementById('produtoQtd').value, 10) || 1),
            valorUnitario: Math.max(0, valorNum(document.getElementById('produtoValorUnitario').value)),
            desconto: 0,
            freteItem: 0
        });

        renderItens();
    });

    itensBody.addEventListener('input', (event) => {
        const idx = Number(event.target.dataset.index);
        if (!Number.isInteger(idx) || !itens[idx]) return;

        if (event.target.classList.contains('item-qtd')) {
            itens[idx].quantidade = Math.max(1, parseInt(event.target.value, 10) || 1);
        }

        if (event.target.classList.contains('item-unit')) {
            itens[idx].valorUnitario = Math.max(0, valorNum(event.target.value));
        }

        if (event.target.classList.contains('item-desc')) {
            itens[idx].desconto = Math.max(0, valorNum(event.target.value));
        }

        if (event.target.classList.contains('item-frete')) {
            itens[idx].freteItem = Math.max(0, valorNum(event.target.value));
        }

        renderItens();
    });

    itensBody.addEventListener('click', (event) => {
        const btn = event.target.closest('.item-remove');
        if (!btn) return;

        const idx = Number(btn.dataset.index);
        itens.splice(idx, 1);
        renderItens();
    });

    document.getElementById('freteManualCheck').addEventListener('change', atualizarResumo);
    document.getElementById('freteTotalInput').addEventListener('input', atualizarResumo);

    document.getElementById('descontoTotalInput').addEventListener('input', (event) => {
        const valor = Math.max(0, valorNum(event.target.value));
        if (!itens.length) return;
        ratearDesconto(valor);
    });

    document.getElementById('descontoPercentInput').addEventListener('input', (event) => {
        descontoPercentControlando = true;
        const perc = Math.max(0, Math.min(100, valorNum(event.target.value)));
        const subtotal = itens.reduce((acc, item) => acc + (item.quantidade * item.valorUnitario), 0);
        const descontoTotal = subtotal * (perc / 100);
        if (itens.length) {
            ratearDesconto(descontoTotal);
        }
        descontoPercentControlando = false;
    });

    document.getElementById('btnZerarDescontos').addEventListener('click', () => {
        itens.forEach(item => { item.desconto = 0; });
        document.getElementById('descontoPercentInput').value = '0.00';
        renderItens();
    });

    document.getElementById('condicaoPagamento').addEventListener('change', (event) => {
        const isVista = event.target.value === 'vista';
        const qtd = document.getElementById('qtdParcelas');

        if (isVista) {
            qtd.value = 1;
            qtd.setAttribute('readonly', 'readonly');
            parcelas = [criarParcela(hojeSP, totalVenda(), 'PIX', false)];
            renderParcelas();
            return;
        }

        qtd.removeAttribute('readonly');
        montarParcelas(Math.max(1, parseInt(qtd.value, 10) || 1));
    });

    document.getElementById('qtdParcelas').addEventListener('input', (event) => {
        if (document.getElementById('condicaoPagamento').value === 'vista') return;
        montarParcelas(Math.max(1, Math.min(24, parseInt(event.target.value, 10) || 1)));
    });

    parcelasBody.addEventListener('input', (event) => {
        const idx = Number(event.target.dataset.index);
        if (!Number.isInteger(idx) || !parcelas[idx]) return;

        if (event.target.classList.contains('parcela-venc')) {
            parcelas[idx].vencimento = event.target.value || hojeSP;
        }

        if (event.target.classList.contains('parcela-valor')) {
            parcelas[idx].valor = Math.max(0, valorNum(event.target.value));
            parcelas[idx].manual = true;
            recalcularParcelas();
        }

        if (event.target.classList.contains('parcela-tipo')) {
            parcelas[idx].tipoPagamento = event.target.value;
        }
    });

    parcelasBody.addEventListener('contextmenu', (event) => {
        if (document.getElementById('condicaoPagamento').value !== 'parcelado') return;

        event.preventDefault();
        const row = event.target.closest('tr');
        if (row && parcelas.length > 1) {
            parcelas.splice(Number(row.dataset.index), 1);
            recalcularParcelas();
            return;
        }

        const nova = criarParcela(hojeSP, 0, 'PIX', false);
        parcelas.push(nova);
        recalcularParcelas();
    });

    montarParcelas(1);
    document.getElementById('condicaoPagamento').dispatchEvent(new Event('change'));
    renderItens();
</script>

<?php include '../includes/footer.php'; ?>
