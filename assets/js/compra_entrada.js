(function () {
    const bootstrapData = window.compraEntradaBootstrap || {};
    const fornecedores = Array.isArray(bootstrapData.fornecedores) ? bootstrapData.fornecedores.slice() : [];
    const produtos = Array.isArray(bootstrapData.produtos) ? bootstrapData.produtos.slice() : [];
    const endpoints = bootstrapData.endpoints || {};
    const hoje = bootstrapData.hoje || new Date().toISOString().slice(0, 10);
    const csrfToken = bootstrapData.csrfToken || '';

    const form = document.getElementById('formCompraEntrada');
    if (!form) {
        return;
    }

    const feedback = document.getElementById('compraEntradaFeedback');
    const fornecedorNomeFantasia = document.getElementById('fornecedorNomeFantasia');
    const fornecedorRazaoSocial = document.getElementById('fornecedorRazaoSocial');
    const fornecedorDocumento = document.getElementById('fornecedorDocumento');
    const fornecedorTelefone = document.getElementById('fornecedorTelefone');
    const fornecedorEmail = document.getElementById('fornecedorEmail');
    const numeroNota = document.getElementById('numeroNota');
    const dataEmissao = document.getElementById('dataEmissao');
    const dataEntrada = document.getElementById('dataEntrada');
    const produtoBusca = document.getElementById('produtoBusca');
    const itemQuantidadeTotal = document.getElementById('itemQuantidadeTotal');
    const itemQuantidadeLoja = document.getElementById('itemQuantidadeLoja');
    const itemQuantidadeEstoque = document.getElementById('itemQuantidadeEstoque');
    const itemCustoUnitario = document.getElementById('itemCustoUnitario');
    const valorFrete = document.getElementById('valorFrete');
    const valorDesconto = document.getElementById('valorDesconto');
    const valorOutrasDespesas = document.getElementById('valorOutrasDespesas');
    const observacoesNota = document.getElementById('observacoesNota');
    const subtotalItensLabel = document.getElementById('subtotalItensLabel');
    const totalNotaLabel = document.getElementById('totalNotaLabel');
    const condicaoPagamento = document.getElementById('condicaoPagamento');
    const qtdParcelas = document.getElementById('qtdParcelas');
    const itensTableBody = document.querySelector('#itensNotaTable tbody');
    const parcelasTableBody = document.querySelector('#parcelasTable tbody');
    const btnAdicionarItem = document.getElementById('btnAdicionarItem');
    const btnGerarParcelas = document.getElementById('btnGerarParcelas');
    const btnSalvarFornecedor = document.getElementById('btnSalvarFornecedor');
    const btnSalvarProduto = document.getElementById('btnSalvarProduto');
    const btnSalvarCompra = document.getElementById('btnSalvarCompra');
    const btnLimparCompra = document.getElementById('btnLimparCompra');
    const fornecedoresDatalist = document.getElementById('fornecedoresSugestoes');
    const produtosDatalist = document.getElementById('produtosSugestoes');

    const state = {
        fornecedorId: 0,
        itens: [],
        parcelas: []
    };

    function normalizeText(value) {
        return String(value || '').trim().toLowerCase();
    }

    function parseDecimal(value) {
        const text = String(value == null ? '' : value).trim();
        if (!text) {
            return 0;
        }

        if (text.includes(',')) {
            const normalized = text.replace(/\./g, '').replace(',', '.');
            return Number(normalized) || 0;
        }

        return Number(text) || 0;
    }

    function formatMoney(value) {
        return Number(value || 0).toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
    }

    function formatDecimalInput(value, decimals = 2) {
        return Number(value || 0).toLocaleString('pt-BR', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    function getFirstWord(value) {
        return String(value || '').trim().split(/\s+/).filter(Boolean)[0] || '';
    }

    function showFeedback(type, message, voiceCode) {
        feedback.className = 'alert alert-' + type;
        feedback.textContent = message;
        feedback.classList.remove('d-none');
        if (voiceCode && window.AppSpeechFeedback) {
            window.AppSpeechFeedback.speakFeedback({
                screen: 'purchase',
                type,
                text: message,
                code: voiceCode
            });
        }
    }

    function hideFeedback() {
        feedback.classList.add('d-none');
        feedback.textContent = '';
    }

    function findFornecedorByFantasyName(name) {
        const normalized = normalizeText(name);
        return fornecedores.find((item) => normalizeText(item.nome_fantasia || item.nome_fornecedor) === normalized) || null;
    }

    function findProdutoByName(name) {
        const normalized = normalizeText(name);
        return produtos.find((item) => normalizeText(item.nome) === normalized) || null;
    }

    function refreshFornecedorDatalist() {
        if (!fornecedoresDatalist) {
            return;
        }

        fornecedoresDatalist.innerHTML = fornecedores
            .map((item) => `<option value="${escapeAttribute(item.nome_fantasia || item.nome_fornecedor)}"></option>`)
            .join('');
    }

    function refreshProdutoDatalist() {
        if (!produtosDatalist) {
            return;
        }

        produtosDatalist.innerHTML = produtos
            .map((item) => `<option value="${escapeAttribute(item.nome)}"></option>`)
            .join('');
    }

    function getTotais() {
        const subtotalItens = state.itens.reduce((total, item) => total + item.subtotal_item, 0);
        const totalNota = subtotalItens + parseDecimal(valorFrete.value) + parseDecimal(valorOutrasDespesas.value) - parseDecimal(valorDesconto.value);

        return {
            subtotalItens: Number(subtotalItens.toFixed(2)),
            totalNota: Number(totalNota.toFixed(2))
        };
    }

    function updateSummary() {
        const totais = getTotais();
        subtotalItensLabel.textContent = formatMoney(totais.subtotalItens);
        totalNotaLabel.textContent = formatMoney(totais.totalNota);
    }

    function renderItens() {
        itensTableBody.innerHTML = '';

        if (!state.itens.length) {
            itensTableBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Nenhum item adicionado.</td></tr>';
            updateSummary();
            return;
        }

        state.itens.forEach((item, index) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${index + 1}</td>
                <td>${escapeHtml(item.descricao)}</td>
                <td>${formatDecimalInput(item.quantidade_total, 3)}</td>
                <td>${formatDecimalInput(item.quantidade_loja, 3)}</td>
                <td>${formatDecimalInput(item.quantidade_estoque_auxiliar, 3)}</td>
                <td>${formatMoney(item.custo_unitario)}</td>
                <td>${formatMoney(item.subtotal_item)}</td>
                <td><button type="button" class="btn btn-sm btn-outline-danger" data-item-index="${index}">Remover</button></td>
            `;
            itensTableBody.appendChild(row);
        });

        updateSummary();
    }

    function renderParcelas() {
        parcelasTableBody.innerHTML = '';

        if (!state.parcelas.length) {
            parcelasTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Nenhuma parcela gerada.</td></tr>';
            return;
        }

        state.parcelas.forEach((parcela, index) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${index + 1}</td>
                <td><input type="date" class="form-control form-control-sm" data-parcela-field="vencimento" data-parcela-index="${index}" value="${escapeAttribute(parcela.vencimento)}"></td>
                <td><input type="text" class="form-control form-control-sm" data-parcela-field="valor" data-parcela-index="${index}" value="${escapeAttribute(formatDecimalInput(parcela.valor, 2))}"></td>
                <td>
                    <select class="form-select form-select-sm" data-parcela-field="tipo_pagamento" data-parcela-index="${index}">
                        ${['Boleto', 'PIX', 'Transferencia', 'Dinheiro', 'Cartao'].map((tipo) => `<option value="${tipo}" ${parcela.tipo_pagamento === tipo ? 'selected' : ''}>${tipo}</option>`).join('')}
                    </select>
                </td>
                <td><button type="button" class="btn btn-sm btn-outline-danger" data-remove-parcela="${index}">Remover</button></td>
            `;
            parcelasTableBody.appendChild(row);
        });
    }

    function addItem() {
        hideFeedback();
        const produto = findProdutoByName(produtoBusca.value);
        const quantidadeTotal = parseDecimal(itemQuantidadeTotal.value);
        const quantidadeLoja = parseDecimal(itemQuantidadeLoja.value);
        const quantidadeEstoque = parseDecimal(itemQuantidadeEstoque.value);
        const custoUnitario = parseDecimal(itemCustoUnitario.value);

        if (!produto) {
            showFeedback('warning', 'Selecione um produto existente ou cadastre um novo produto.');
            return;
        }

        if (quantidadeTotal <= 0 || custoUnitario <= 0) {
            showFeedback('warning', 'Quantidade total e custo unitario devem ser maiores que zero.');
            return;
        }

        if (Math.abs((quantidadeLoja + quantidadeEstoque) - quantidadeTotal) > 0.001) {
            showFeedback('warning', 'A soma entre loja e estoque auxiliar deve ser igual a quantidade total.');
            return;
        }

        state.itens.push({
            id_produto: Number(produto.id),
            descricao: produto.nome,
            quantidade_total: quantidadeTotal,
            quantidade_loja: quantidadeLoja,
            quantidade_estoque_auxiliar: quantidadeEstoque,
            custo_unitario: Number(custoUnitario.toFixed(4)),
            subtotal_item: Number((quantidadeTotal * custoUnitario).toFixed(2))
        });

        produtoBusca.value = '';
        itemQuantidadeTotal.value = '1';
        itemQuantidadeLoja.value = '0';
        itemQuantidadeEstoque.value = '1';
        itemCustoUnitario.value = '0,00';
        renderItens();
    }

    function generateParcelas() {
        hideFeedback();
        const totalNota = getTotais().totalNota;
        const quantidade = Math.max(1, Number(qtdParcelas.value || 1));

        if (totalNota <= 0) {
            showFeedback('warning', 'Adicione itens antes de gerar as parcelas.');
            return;
        }

        const parcelas = [];
        let acumulado = 0;

        for (let i = 0; i < quantidade; i += 1) {
            let valor = Number((totalNota / quantidade).toFixed(2));
            if (i === quantidade - 1) {
                valor = Number((totalNota - acumulado).toFixed(2));
            }
            acumulado += valor;

            const vencimento = addDays(hoje, i * 30);
            parcelas.push({
                numero_parcela: i + 1,
                vencimento,
                valor,
                tipo_pagamento: condicaoPagamento.value === 'vista' ? 'PIX' : 'Boleto'
            });
        }

        state.parcelas = parcelas;
        renderParcelas();
    }

    function addDays(dateString, days) {
        const base = new Date(dateString + 'T12:00:00');
        base.setDate(base.getDate() + days);
        return base.toISOString().slice(0, 10);
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeAttribute(value) {
        return escapeHtml(value);
    }

    function syncFornecedor() {
        const fornecedor = findFornecedorByFantasyName(fornecedorNomeFantasia.value);
        if (!fornecedor) {
            state.fornecedorId = 0;
            return;
        }

        state.fornecedorId = Number(fornecedor.id_fornecedor);
        fornecedorRazaoSocial.value = fornecedor.razao_social || fornecedor.nome_fornecedor || '';
        fornecedorDocumento.value = fornecedor.documento || '';
        fornecedorTelefone.value = fornecedor.telefone || '';
        fornecedorEmail.value = fornecedor.email || '';
    }

    async function saveFornecedorRapido() {
        hideFeedback();
        const payload = {
            csrf_token: csrfToken,
            razao_social: fornecedorRazaoSocial.value,
            nome_fantasia: fornecedorNomeFantasia.value,
            documento: fornecedorDocumento.value,
            telefone: fornecedorTelefone.value,
            email: fornecedorEmail.value
        };

        const response = await fetch(endpoints.salvarFornecedor, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();
        if (!response.ok || !data.status) {
            throw new Error(data.mensagem || 'Falha ao salvar fornecedor.');
        }

        fornecedores.push(data.fornecedor);
        state.fornecedorId = Number(data.id_fornecedor);
        fornecedorRazaoSocial.value = data.fornecedor.razao_social || '';
        fornecedorNomeFantasia.value = data.fornecedor.nome_fantasia || '';
        refreshFornecedorDatalist();
        showFeedback('success', data.mensagem || 'Fornecedor salvo com sucesso.');
        if (window.AppSpeechFeedback) {
            window.AppSpeechFeedback.speakText('Fornecedor Salvo.', {
                screen: 'purchase',
                type: 'success'
            });
        }
    }

    async function saveProdutoRapido() {
        hideFeedback();
        const nome = produtoBusca.value.trim();
        if (!nome) {
            showFeedback('warning', 'Digite o nome do produto antes de cadastrar.', 'purchase.product_name_missing');
            return;
        }

        const custo = window.prompt('Informe o custo inicial deste produto:', '0,00');
        if (custo === null) {
            return;
        }

        const preco1 = window.prompt('Informe o preco inicial de venda (opcional):', custo);
        if (preco1 === null) {
            return;
        }

        const payload = {
            csrf_token: csrfToken,
            nome,
            custo,
            preco1
        };

        const response = await fetch(endpoints.salvarProduto, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();
        if (!response.ok || !data.status) {
            throw new Error(data.mensagem || 'Falha ao salvar produto.');
        }

        produtos.push(data.produto);
        refreshProdutoDatalist();
        produtoBusca.value = data.produto.nome;
        itemCustoUnitario.value = formatDecimalInput(data.produto.custo, 2);
        showFeedback('success', data.mensagem || 'Produto salvo com sucesso.');
        const firstProductName = getFirstWord(data.produto?.nome);
        if (firstProductName && window.AppSpeechFeedback) {
            window.AppSpeechFeedback.speakText(`Produto ${firstProductName} Salvo.`, {
                screen: 'purchase',
                type: 'success'
            });
        }
    }

    function validateBeforeSubmit() {
        syncFornecedor();

        if (!state.fornecedorId) {
            showFeedback('warning', 'Selecione ou cadastre um fornecedor antes de salvar.', 'purchase.supplier_required');
            return false;
        }

        if (!numeroNota.value.trim()) {
            showFeedback('warning', 'Informe o numero da nota.', 'purchase.invoice_number_required');
            return false;
        }

        if (!state.itens.length) {
            showFeedback('warning', 'Adicione ao menos um item na nota.', 'purchase.items_required');
            return false;
        }

        if (!state.parcelas.length) {
            showFeedback('warning', 'Gere ou informe as parcelas antes de salvar.', 'purchase.installments_required');
            return false;
        }

        const totais = getTotais();
        const somaParcelas = state.parcelas.reduce((total, parcela) => total + Number(parcela.valor || 0), 0);
        if (Math.abs(somaParcelas - totais.totalNota) > 0.05) {
            showFeedback('warning', 'A soma das parcelas precisa ser igual ao total da nota.', 'purchase.installments_mismatch');
            return false;
        }

        return true;
    }

    async function submitForm(event) {
        event.preventDefault();
        hideFeedback();

        if (!validateBeforeSubmit()) {
            return;
        }

        const totais = getTotais();
        btnSalvarCompra.disabled = true;
        btnSalvarCompra.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';

        try {
            const payload = {
                csrf_token: csrfToken,
                fornecedor_id: state.fornecedorId,
                numero_nota: numeroNota.value.trim(),
                data_emissao: dataEmissao.value,
                data_entrada: dataEntrada.value,
                condicao_pagamento: condicaoPagamento.value,
                valor_frete: parseDecimal(valorFrete.value),
                valor_desconto: parseDecimal(valorDesconto.value),
                valor_outras_despesas: parseDecimal(valorOutrasDespesas.value),
                total_nota: totais.totalNota,
                observacoes: observacoesNota.value.trim(),
                itens: state.itens.map((item) => ({
                    id_produto: item.id_produto,
                    descricao: item.descricao,
                    quantidade_total: item.quantidade_total,
                    quantidade_loja: item.quantidade_loja,
                    quantidade_estoque_auxiliar: item.quantidade_estoque_auxiliar,
                    custo_unitario: item.custo_unitario,
                    subtotal_item: item.subtotal_item
                })),
                parcelas: state.parcelas.map((parcela, index) => ({
                    numero_parcela: index + 1,
                    vencimento: parcela.vencimento,
                    valor: Number(parcela.valor),
                    tipo_pagamento: parcela.tipo_pagamento
                }))
            };

            const response = await fetch(endpoints.salvarEntrada, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();
            if (!response.ok || !data.status) {
                throw new Error(data.mensagem || 'Falha ao salvar a entrada.');
            }

            showFeedback('success', data.mensagem || 'Entrada salva com sucesso.');
            if (window.AppSpeechFeedback) {
                window.AppSpeechFeedback.speakText('Compra Salva.', {
                    screen: 'purchase',
                    type: 'success'
                });
            }
            resetForm();
        } catch (error) {
            showFeedback('danger', error.message || 'Erro ao salvar a entrada.', 'purchase.save_error');
        } finally {
            btnSalvarCompra.disabled = false;
            btnSalvarCompra.textContent = 'Salvar entrada';
        }
    }

    function resetForm() {
        form.reset();
        state.fornecedorId = 0;
        state.itens = [];
        state.parcelas = [];
        dataEmissao.value = hoje;
        dataEntrada.value = hoje;
        itemQuantidadeTotal.value = '1';
        itemQuantidadeLoja.value = '0';
        itemQuantidadeEstoque.value = '1';
        itemCustoUnitario.value = '0,00';
        valorFrete.value = '0,00';
        valorDesconto.value = '0,00';
        valorOutrasDespesas.value = '0,00';
        qtdParcelas.value = '1';
        condicaoPagamento.value = 'vista';
        renderItens();
        renderParcelas();
    }

    fornecedorNomeFantasia.addEventListener('change', syncFornecedor);
    fornecedorNomeFantasia.addEventListener('blur', syncFornecedor);

    produtoBusca.addEventListener('change', () => {
        const produto = findProdutoByName(produtoBusca.value);
        if (produto && Number(produto.custo || 0) > 0) {
            itemCustoUnitario.value = formatDecimalInput(produto.custo, 2);
        }
    });

    [valorFrete, valorDesconto, valorOutrasDespesas].forEach((field) => {
        field.addEventListener('input', updateSummary);
    });

    condicaoPagamento.addEventListener('change', () => {
        if (condicaoPagamento.value === 'vista') {
            qtdParcelas.value = '1';
        }
    });

    itensTableBody.addEventListener('click', (event) => {
        const button = event.target.closest('[data-item-index]');
        if (!button) {
            return;
        }
        const index = Number(button.getAttribute('data-item-index'));
        state.itens.splice(index, 1);
        renderItens();
    });

    parcelasTableBody.addEventListener('input', (event) => {
        const field = event.target.getAttribute('data-parcela-field');
        const index = Number(event.target.getAttribute('data-parcela-index'));
        if (!field || Number.isNaN(index) || !state.parcelas[index]) {
            return;
        }

        if (field === 'valor') {
            state.parcelas[index].valor = parseDecimal(event.target.value);
            return;
        }

        state.parcelas[index][field] = event.target.value;
    });

    parcelasTableBody.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-parcela]');
        if (!button) {
            return;
        }

        const index = Number(button.getAttribute('data-remove-parcela'));
        state.parcelas.splice(index, 1);
        state.parcelas = state.parcelas.map((parcela, parcelaIndex) => ({
            ...parcela,
            numero_parcela: parcelaIndex + 1
        }));
        renderParcelas();
    });

    btnAdicionarItem.addEventListener('click', addItem);
    btnGerarParcelas.addEventListener('click', generateParcelas);
    btnSalvarFornecedor.addEventListener('click', () => {
        saveFornecedorRapido().catch((error) => showFeedback('danger', error.message || 'Erro ao salvar fornecedor.'));
    });
    btnSalvarProduto.addEventListener('click', () => {
        saveProdutoRapido().catch((error) => showFeedback('danger', error.message || 'Erro ao salvar produto.'));
    });
    btnLimparCompra.addEventListener('click', () => {
        hideFeedback();
        resetForm();
    });
    form.addEventListener('submit', submitForm);

    renderItens();
    renderParcelas();
    updateSummary();
    refreshFornecedorDatalist();
    refreshProdutoDatalist();
})();
