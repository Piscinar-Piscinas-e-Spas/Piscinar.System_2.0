<?php
include '../includes/db.php';
require_login();
include '../includes/header.php';

$controller = new \App\Controllers\VendaController($pdo);
$formData = $controller->formData();
$clientes = $formData['clientes'];
$produtos = $formData['produtos'];

$hojeSaoPaulo = (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
?>

<div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>Tela de Vendas</h4>
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
                                <label class="form-label">Nome cliente</label>
                                <input type="text" class="form-control" id="clienteNome" list="clientesSugestoes" placeholder="Digite para buscar ou preencher manualmente...">
                                <datalist id="clientesSugestoes">
                                    <?php foreach ($clientes as $cliente): ?>
                                        <option value="<?= htmlspecialchars($cliente['nome_cliente']) ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
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
                        <div class="row g-3 align-items-end" style="margin-top: 2px;">
                            <div class="col-md-4">
                                <label class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="clienteEmail" placeholder="cliente@email.com">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Endereço</label>
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
                                <input type="text" inputmode="decimal" class="form-control" id="produtoValorUnitario" value="0,00">
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

            <div class="col-12">
                <div id="vendaFeedback" class="alert d-none" role="alert"></div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary" id="btnSalvarVenda">
                        <i class="fas fa-save me-1"></i>Salvar venda
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    const hojeSP = '<?= $hojeSaoPaulo ?>';
    const csrfToken = '<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>';
    const tiposPagamento = [
        'PIX', 'Dinheiro', 'Boleto', 'Cheque', 'Pix Pague Seguro',
        'Débito PagSeguro', 'Crédito PagSeguro', 'Débito Stone',
        'Crédito Stone', 'Débito Infinite', 'Crédito Infinite'
    ];
    const clientesData = <?= json_encode($clientes, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    const itens = [];
    let parcelas = [];
    let descontoPercentControlando = false;
    let descontoTotalEditando = false;
    let freteTotalEditando = false;

    const itensBody = document.querySelector('#itensTable tbody');
    const parcelasBody = document.querySelector('#parcelasTable tbody');
    const formVenda = document.getElementById('formVenda');
    const feedbackBox = document.getElementById('vendaFeedback');
    const btnSalvarVenda = document.getElementById('btnSalvarVenda');
    const btnSalvarCliente = document.getElementById('btnSalvarCliente');
    let clienteSelecionadoId = null;

    function moeda(valor) {
        return Number(valor || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    function valorNum(v) {
        if (typeof v === 'number') return Number.isFinite(v) ? v : 0;
        const texto = String(v ?? '').trim();
        const normalizado = texto.includes(',')
            ? texto.replace(/\./g, '').replace(',', '.')
            : texto;
        const n = parseFloat(normalizado);
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
                <td><input type="text" inputmode="decimal" class="form-control form-control-sm item-unit" data-index="${index}" value="${item.valorUnitario.toFixed(2).replace('.', ",")}"></td>
                <td>${moeda(total)}</td>
                <td><input type="text" inputmode="decimal" class="form-control form-control-sm item-desc" data-index="${index}" value="${item.desconto.toFixed(2).replace('.', ",")}"></td>
                <td>${moeda(unitComDesconto)}</td>
                <td>${moeda(totalComDesconto)}</td>
                <td><input type="text" inputmode="decimal" class="form-control form-control-sm item-frete" data-index="${index}" value="${item.freteItem.toFixed(2).replace('.', ",")}"></td>
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
        return obterResumo().total_geral;
    }

    function atualizarResumo() {
        const subtotal = itens.reduce((acc, item) => acc + (item.quantidade * item.valorUnitario), 0);
        const desconto = itens.reduce((acc, item) => acc + item.desconto, 0);
        const freteItens = itens.reduce((acc, item) => acc + item.freteItem, 0);

        const freteManual = document.getElementById('freteManualCheck').checked;
        const freteInput = document.getElementById('freteTotalInput');
        const freteFinal = Math.max(0, freteManual ? valorNum(freteInput.value) : freteItens);

        if (!freteManual && !freteTotalEditando) {
            freteInput.value = freteItens.toFixed(2).replace('.', ',');
        }

        const total = Math.max(0, subtotal - desconto + freteFinal);
        const descontoPercent = subtotal > 0 ? (desconto / subtotal) * 100 : 0;

        document.getElementById('subtotalProdutos').textContent = moeda(subtotal);
        document.getElementById('totalDescontos').textContent = moeda(desconto);
        document.getElementById('totalFrete').textContent = moeda(freteFinal);
        document.getElementById('totalGeralVenda').textContent = moeda(total);
        if (!descontoTotalEditando) {
            document.getElementById('descontoTotalInput').value = desconto.toFixed(2).replace('.', ',');
        }

        if (!descontoPercentControlando) {
            document.getElementById('descontoPercentInput').value = descontoPercent.toFixed(2).replace('.', ',');
        }

        recalcularParcelas();
    }

    function criarParcela(vencimento = hojeSP, valor = 0, tipoPagamento = 'PIX', manual = false) {
        return { vencimento, valor, tipoPagamento, manual };
    }

    function mostrarFeedback(tipo, mensagem) {
        feedbackBox.className = `alert alert-${tipo}`;
        feedbackBox.textContent = mensagem;
        feedbackBox.classList.remove('d-none');
    }

    function limparFeedback() {
        feedbackBox.className = 'alert d-none';
        feedbackBox.textContent = '';
    }

    function obterResumo() {
        const subtotal = itens.reduce((acc, item) => acc + (item.quantidade * item.valorUnitario), 0);
        const descontoTotal = itens.reduce((acc, item) => acc + item.desconto, 0);
        const freteItens = itens.reduce((acc, item) => acc + item.freteItem, 0);
        const freteManual = document.getElementById('freteManualCheck').checked;
        const freteTotal = Math.max(0, freteManual ? valorNum(document.getElementById('freteTotalInput').value) : freteItens);
        const totalGeral = Math.max(0, subtotal - descontoTotal + freteTotal);

        return {
            subtotal,
            desconto_total: descontoTotal,
            frete_total: freteTotal,
            total_geral: totalGeral
        };
    }

    function montarPayloadVenda() {
        const clienteId = Number(clienteSelecionadoId);
        if (!Number.isInteger(clienteId) || clienteId <= 0) {
            throw new Error('Selecione ou salve um cliente antes de salvar a venda.');
        }

        if (!itens.length) {
            throw new Error('Adicione ao menos um item na venda.');
        }

        const resumo = obterResumo();
        const payloadParcelas = parcelas.map((parcela, index) => ({
            numero_parcela: index + 1,
            vencimento: parcela.vencimento || hojeSP,
            valor: Number(valorNum(parcela.valor).toFixed(2)),
            tipo_pagamento: parcela.tipoPagamento || 'PIX',
            qtd_parcelas: parcelas.length,
            total_parcelas: Number(resumo.total_geral.toFixed(2))
        }));

        if (!payloadParcelas.length) {
            throw new Error('Informe ao menos uma parcela para a venda.');
        }

        return {
            csrf_token: csrfToken,
            cliente_id: clienteId,
            cliente: {
                nome: document.getElementById('clienteNome').value.trim(),
                telefone: document.getElementById('clienteTelefone').value.trim(),
                cpf_cnpj: document.getElementById('clienteCpfCnpj').value.trim(),
                email: document.getElementById('clienteEmail').value.trim(),
                endereco: document.getElementById('clienteEndereco').value.trim()
            },
            condicao_pagamento: document.getElementById('condicaoPagamento').value,
            subtotal: Number(resumo.subtotal.toFixed(2)),
            desconto_total: Number(resumo.desconto_total.toFixed(2)),
            frete_total: Number(resumo.frete_total.toFixed(2)),
            total_geral: Number(resumo.total_geral.toFixed(2)),
            itens: itens.map((item) => ({
                produto_id: Number(item.produtoId),
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
        try {
            payload = montarPayloadVenda();
        } catch (error) {
            mostrarFeedback('warning', error.message || 'Verifique os dados da venda antes de salvar.');
            return;
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

            mostrarFeedback('success', `Venda #${dados.id_venda} salva com sucesso.`);
        } catch (error) {
            mostrarFeedback('danger', error.message || 'Erro inesperado ao salvar venda.');
        } finally {
            btnSalvarVenda.disabled = false;
            btnSalvarVenda.innerHTML = '<i class="fas fa-save me-1"></i>Salvar venda';
        }
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
                <td><input type="text" inputmode="decimal" class="form-control form-control-sm parcela-valor" data-index="${index}" value="${valorNum(parcela.valor).toFixed(2).replace('.', ",")}"></td>
                <td><select class="form-select form-select-sm parcela-tipo" data-index="${index}">${tipoOptions}</select></td>
                <td>${index + 1}</td>
                <td>${qtdTotal}</td>
            `;
            parcelasBody.appendChild(tr);
        });

        document.getElementById('qtdParcelas').value = qtdTotal;
    }

    const clienteNome = document.getElementById('clienteNome');
    const clienteTelefone = document.getElementById('clienteTelefone');
    const clienteCpfCnpj = document.getElementById('clienteCpfCnpj');
    const clienteEmail = document.getElementById('clienteEmail');
    const clienteEndereco = document.getElementById('clienteEndereco');
    const clientesSugestoes = document.getElementById('clientesSugestoes');

    function preencherDadosCliente(cliente) {
        if (!cliente) return;
        clienteSelecionadoId = Number(cliente.id_cliente) || null;
        clienteNome.value = cliente.nome_cliente || '';
        clienteTelefone.value = cliente.telefone_contato || '';
        clienteCpfCnpj.value = cliente.cpf_cnpj || '';
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

    function filtrarSugestoesClientes(valorDigitado) {
        const termo = (valorDigitado || '').trim().toLowerCase();
        const filtrados = clientesData.filter((cliente) => {
            if (!termo) return true;
            return (cliente.nome_cliente || '').toLowerCase().includes(termo);
        });

        clientesSugestoes.innerHTML = filtrados
            .map((cliente) => `<option value="${escapeHtmlAttr(cliente.nome_cliente)}" data-id="${Number(cliente.id_cliente) || ''}"></option>`)
            .join('');
    }

    function obterClienteSelecionadoPorInput(nomeDigitado) {
        const nomeNormalizado = (nomeDigitado || '').trim().toLowerCase();
        if (!nomeNormalizado) return null;

        const opcoes = Array.from(clientesSugestoes.options);
        const opcao = opcoes.find((item) => (item.value || '').trim().toLowerCase() === nomeNormalizado);
        if (opcao && opcao.dataset.id) {
            const id = Number(opcao.dataset.id);
            if (Number.isInteger(id) && id > 0) {
                return clientesData.find((cliente) => Number(cliente.id_cliente) === id) || null;
            }
        }

        return buscarClientePorNome(nomeDigitado);
    }

    function buscarClientePorNome(nome) {
        const nomeNormalizado = (nome || '').trim().toLowerCase();
        if (!nomeNormalizado) return null;

        return clientesData.find(
            (cliente) => (cliente.nome_cliente || '').trim().toLowerCase() === nomeNormalizado
        ) || null;
    }

    filtrarSugestoesClientes('');

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

    function montarPayloadClienteRapido() {
        return {
            csrf_token: csrfToken,
            nome_cliente: clienteNome.value.trim(),
            telefone_contato: clienteTelefone.value.trim(),
            cpf_cnpj: clienteCpfCnpj.value.trim(),
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
        } catch (error) {
            mostrarFeedback('danger', error.message || 'Erro ao salvar cliente.');
        } finally {
            btnSalvarCliente.disabled = false;
            btnSalvarCliente.innerHTML = '<i class="fas fa-user-plus me-1"></i>Salvar cliente rápido';
        }
    }

    btnSalvarCliente.addEventListener('click', salvarClienteRapido);

    document.getElementById('produtoSelect').addEventListener('change', (event) => {
        const opt = event.target.selectedOptions[0];
        document.getElementById('produtoValorUnitario').value = (valorNum(opt?.dataset?.preco)).toFixed(2).replace('.', ',');
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

    function atualizarItemPorCampo(target) {
        const idx = Number(target.dataset.index);
        if (!Number.isInteger(idx) || !itens[idx]) return false;

        if (target.classList.contains('item-qtd')) {
            itens[idx].quantidade = Math.max(1, parseInt(target.value, 10) || 1);
            return true;
        }

        if (target.classList.contains('item-unit')) {
            itens[idx].valorUnitario = Math.max(0, valorNum(target.value));
            return true;
        }

        if (target.classList.contains('item-desc')) {
            itens[idx].desconto = Math.max(0, valorNum(target.value));
            return true;
        }

        if (target.classList.contains('item-frete')) {
            itens[idx].freteItem = Math.max(0, valorNum(target.value));
            return true;
        }

        return false;
    }

    itensBody.addEventListener('input', (event) => {
        const idx = Number(event.target.dataset.index);
        if (!Number.isInteger(idx) || !itens[idx]) return;

        if (!atualizarItemPorCampo(event.target)) return;
        atualizarResumo();
    });

    itensBody.addEventListener('change', (event) => {
        if (!atualizarItemPorCampo(event.target)) return;
        renderItens();
    });

    itensBody.addEventListener('click', (event) => {
        const btn = event.target.closest('.item-remove');
        if (!btn) return;

        const idx = Number(btn.dataset.index);
        itens.splice(idx, 1);
        renderItens();
    });

    const freteTotalInput = document.getElementById('freteTotalInput');
    const descontoTotalInput = document.getElementById('descontoTotalInput');

    document.getElementById('freteManualCheck').addEventListener('change', atualizarResumo);

    freteTotalInput.addEventListener('focus', () => {
        freteTotalEditando = true;
    });

    freteTotalInput.addEventListener('blur', () => {
        freteTotalEditando = false;
        atualizarResumo();
    });

    freteTotalInput.addEventListener('input', atualizarResumo);

    descontoTotalInput.addEventListener('focus', () => {
        descontoTotalEditando = true;
    });

    descontoTotalInput.addEventListener('blur', () => {
        descontoTotalEditando = false;
        atualizarResumo();
    });

    descontoTotalInput.addEventListener('input', (event) => {
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
        document.getElementById('descontoPercentInput').value = '0,00';
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
        }

        if (event.target.classList.contains('parcela-tipo')) {
            parcelas[idx].tipoPagamento = event.target.value;
        }
    });

    parcelasBody.addEventListener('change', (event) => {
        const idx = Number(event.target.dataset.index);
        if (!Number.isInteger(idx) || !parcelas[idx]) return;

        if (event.target.classList.contains('parcela-venc')) {
            parcelas[idx].vencimento = event.target.value || hojeSP;
            return;
        }

        if (event.target.classList.contains('parcela-valor')) {
            parcelas[idx].valor = Math.max(0, valorNum(event.target.value));
            parcelas[idx].manual = true;
            recalcularParcelas();
            return;
        }

        if (event.target.classList.contains('parcela-tipo')) {
            parcelas[idx].tipoPagamento = event.target.value;

            const tipoNormalizado = (event.target.value || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase();

            if (tipoNormalizado.includes('credito') || tipoNormalizado.includes('cheque')) {
                for (let i = idx + 1; i < parcelas.length; i += 1) {
                    parcelas[i].tipoPagamento = event.target.value;
                }

                renderParcelas();
            }
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

    formVenda.addEventListener('submit', (event) => {
        event.preventDefault();
        enviarVenda();
    });

    montarParcelas(1);
    document.getElementById('condicaoPagamento').dispatchEvent(new Event('change'));
    renderItens();
</script>

<?php include '../includes/footer.php'; ?>
