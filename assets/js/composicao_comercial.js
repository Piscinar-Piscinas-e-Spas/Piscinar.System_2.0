(function (global) {
    'use strict';

    function valorNum(v) {
        if (typeof v === 'number') return Number.isFinite(v) ? v : 0;
        const texto = String(v ?? '').trim();
        const normalizado = texto.includes(',')
            ? texto.replace(/\./g, '').replace(',', '.')
            : texto;
        const n = parseFloat(normalizado);
        return Number.isFinite(n) ? n : 0;
    }

    function moeda(valor) {
        return Number(valor || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    function criarComposicaoComercial(config) {
        const state = {
            itens_produto: [],
            itens_microservico: [],
            parcelas: [],
            totais: {
                subtotal_produtos: 0,
                subtotal_microservicos: 0,
                desconto_total: 0,
                frete_itens: 0,
                frete_total: 0,
                total_geral: 0
            },
            flags: {
                descontoPercentControlando: false,
                descontoTotalEditando: false,
                freteTotalEditando: false
            }
        };

        const estrategias = {
            produto(item) {
                const quantidade = Math.max(1, parseInt(item.quantidade, 10) || 1);
                const valorUnitario = Math.max(0, valorNum(item.valorUnitario));
                const subtotal = quantidade * valorUnitario;
                const desconto = Math.max(0, Math.min(valorNum(item.desconto), subtotal));
                const frete = Math.max(0, valorNum(item.freteItem));
                const totalComDesconto = Math.max(0, subtotal - desconto);
                const unitComDesconto = quantidade > 0 ? totalComDesconto / quantidade : 0;
                const total = totalComDesconto + frete;
                return {
                    ...item,
                    tipo: 'produto',
                    quantidade,
                    valorUnitario,
                    desconto,
                    freteItem: frete,
                    subtotal,
                    totalComDesconto,
                    unitComDesconto,
                    total
                };
            },
            microservico(item) {
                const quantidade = Math.max(1, parseInt(item.quantidade, 10) || 1);
                const valorUnitario = Math.max(0, valorNum(item.valorUnitario));
                const subtotal = quantidade * valorUnitario;
                const desconto = Math.max(0, Math.min(valorNum(item.desconto), subtotal));
                const totalComDesconto = Math.max(0, subtotal - desconto);
                const unitComDesconto = quantidade > 0 ? totalComDesconto / quantidade : 0;
                return {
                    ...item,
                    tipo: 'microservico',
                    quantidade,
                    valorUnitario,
                    desconto,
                    freteItem: 0,
                    subtotal,
                    totalComDesconto,
                    unitComDesconto,
                    total: totalComDesconto
                };
            }
        };

        const dom = {
            itensBody: document.querySelector(config.seletores.itensBody),
            parcelasBody: document.querySelector(config.seletores.parcelasBody),
            freteManualCheck: document.querySelector(config.seletores.freteManualCheck),
            freteTotalInput: document.querySelector(config.seletores.freteTotalInput),
            descontoTotalInput: document.querySelector(config.seletores.descontoTotalInput),
            descontoPercentInput: document.querySelector(config.seletores.descontoPercentInput),
            condicaoPagamento: document.querySelector(config.seletores.condicaoPagamento),
            qtdParcelas: document.querySelector(config.seletores.qtdParcelas),
            subtotalProdutos: document.querySelector(config.seletores.subtotalProdutos),
            totalDescontos: document.querySelector(config.seletores.totalDescontos),
            totalFrete: document.querySelector(config.seletores.totalFrete),
            totalGeral: document.querySelector(config.seletores.totalGeral)
        };

        const hoje = config.hoje;
        const tiposPagamento = Array.isArray(config.tiposPagamento) ? config.tiposPagamento : ['PIX'];

        function obterItens(tipo) {
            return tipo === 'microservico' ? state.itens_microservico : state.itens_produto;
        }

        function normalizarItem(item, tipo) {
            const estrategia = estrategias[tipo] || estrategias.produto;
            return estrategia(item);
        }

        function obterResumo() {
            const produtos = state.itens_produto.map((item) => normalizarItem(item, 'produto'));
            const microservicos = state.itens_microservico.map((item) => normalizarItem(item, 'microservico'));

            const subtotalProdutos = produtos.reduce((acc, item) => acc + item.subtotal, 0);
            const subtotalMicro = microservicos.reduce((acc, item) => acc + item.subtotal, 0);
            const descontoProdutos = produtos.reduce((acc, item) => acc + item.desconto, 0);
            const descontoMicro = microservicos.reduce((acc, item) => acc + item.desconto, 0);
            const freteItens = produtos.reduce((acc, item) => acc + item.freteItem, 0);

            const freteManual = dom.freteManualCheck.checked;
            const freteTotal = Math.max(0, freteManual ? valorNum(dom.freteTotalInput.value) : freteItens);

            const subtotalTotal = subtotalProdutos + subtotalMicro;
            const descontoTotal = descontoProdutos + descontoMicro;
            const totalGeral = Math.max(0, subtotalTotal - descontoTotal + freteTotal);

            state.totais = {
                subtotal_produtos: subtotalProdutos,
                subtotal_microservicos: subtotalMicro,
                desconto_total: descontoTotal,
                frete_itens: freteItens,
                frete_total: freteTotal,
                total_geral: totalGeral
            };

            return state.totais;
        }

        function totalVenda() {
            return obterResumo().total_geral;
        }

        function renderItens() {
            if (!dom.itensBody) return;
            dom.itensBody.innerHTML = '';

            state.itens_produto = state.itens_produto.map((item) => normalizarItem(item, 'produto'));

            state.itens_produto.forEach((item, index) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${index + 1}</td>
                    <td>${item.nome}</td>
                    <td><input type="number" min="1" step="1" class="form-control form-control-sm item-qtd" data-tipo="produto" data-index="${index}" value="${item.quantidade}"></td>
                    <td><input type="text" inputmode="decimal" class="form-control form-control-sm item-unit" data-tipo="produto" data-index="${index}" value="${item.valorUnitario.toFixed(2).replace('.', ',')}"></td>
                    <td>${moeda(item.subtotal)}</td>
                    <td><input type="text" inputmode="decimal" class="form-control form-control-sm item-desc" data-tipo="produto" data-index="${index}" value="${item.desconto.toFixed(2).replace('.', ',')}"></td>
                    <td>${moeda(item.unitComDesconto)}</td>
                    <td>${moeda(item.totalComDesconto)}</td>
                    <td><input type="text" inputmode="decimal" class="form-control form-control-sm item-frete" data-tipo="produto" data-index="${index}" value="${item.freteItem.toFixed(2).replace('.', ',')}"></td>
                    <td>${moeda(item.total)}</td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger item-remove" data-tipo="produto" data-index="${index}"><i class="fas fa-trash"></i></button></td>
                `;
                dom.itensBody.appendChild(tr);
            });

            atualizarResumo();
        }

        function criarParcela(vencimento = hoje, valor = 0, tipoPagamento = 'PIX', manual = false) {
            return { vencimento, valor, tipoPagamento, manual };
        }

        function renderParcelas() {
            if (!dom.parcelasBody) return;
            dom.parcelasBody.innerHTML = '';
            const condicao = dom.condicaoPagamento.value;
            const qtdTotal = state.parcelas.length;

            state.parcelas.forEach((parcela, index) => {
                const tr = document.createElement('tr');
                tr.dataset.index = index;
                const tipoOptions = tiposPagamento
                    .map(tipo => `<option value="${tipo}" ${parcela.tipoPagamento === tipo ? 'selected' : ''}>${tipo}</option>`)
                    .join('');

                tr.innerHTML = `
                    <td>${index + 1}</td>
                    <td><input type="date" class="form-control form-control-sm parcela-venc" data-index="${index}" value="${parcela.vencimento}" ${condicao === 'vista' ? 'readonly' : ''}></td>
                    <td><input type="text" inputmode="decimal" class="form-control form-control-sm parcela-valor" data-index="${index}" value="${valorNum(parcela.valor).toFixed(2).replace('.', ',')}"></td>
                    <td><select class="form-select form-select-sm parcela-tipo" data-index="${index}">${tipoOptions}</select></td>
                    <td>${index + 1}</td>
                    <td>${qtdTotal}</td>
                `;

                dom.parcelasBody.appendChild(tr);
            });

            if (dom.qtdParcelas) {
                dom.qtdParcelas.value = qtdTotal;
            }
        }

        function montarParcelas(qtd) {
            const quantidade = Math.max(1, qtd);
            state.parcelas = Array.from({ length: quantidade }, (_, i) => {
                const d = new Date(hoje + 'T12:00:00');
                d.setMonth(d.getMonth() + i);
                const dataISO = d.toISOString().slice(0, 10);
                return criarParcela(dataISO, 0, 'PIX', false);
            });
            recalcularParcelas();
        }

        function recalcularParcelas() {
            if (!state.parcelas.length) montarParcelas(1);

            const total = totalVenda();
            const manuais = state.parcelas.filter(p => p.manual);
            const somaManuais = manuais.reduce((acc, p) => acc + valorNum(p.valor), 0);
            const editaveis = state.parcelas.filter(p => !p.manual);
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

        function ratearDescontoProdutos(totalDesconto) {
            const itens = state.itens_produto;
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

        function atualizarResumo() {
            const totais = obterResumo();

            if (!dom.freteManualCheck.checked && !state.flags.freteTotalEditando) {
                dom.freteTotalInput.value = totais.frete_itens.toFixed(2).replace('.', ',');
            }

            dom.subtotalProdutos.textContent = moeda(totais.subtotal_produtos);
            dom.totalDescontos.textContent = moeda(totais.desconto_total);
            dom.totalFrete.textContent = moeda(totais.frete_total);
            dom.totalGeral.textContent = moeda(totais.total_geral);

            if (!state.flags.descontoTotalEditando) {
                dom.descontoTotalInput.value = totais.desconto_total.toFixed(2).replace('.', ',');
            }

            if (!state.flags.descontoPercentControlando) {
                const perc = totais.subtotal_produtos > 0 ? (totais.desconto_total / totais.subtotal_produtos) * 100 : 0;
                dom.descontoPercentInput.value = perc.toFixed(2).replace('.', ',');
            }

            recalcularParcelas();
            if (typeof config.onChange === 'function') {
                config.onChange(getState());
            }
        }

        function atualizarItemPorCampo(target) {
            const tipo = target.dataset.tipo || 'produto';
            const idx = Number(target.dataset.index);
            const itens = obterItens(tipo);
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

        function getState() {
            return {
                itens_produto: state.itens_produto.map((item) => ({ ...item })),
                itens_microservico: state.itens_microservico.map((item) => ({ ...item })),
                parcelas: state.parcelas.map((item) => ({ ...item })),
                totais: { ...state.totais }
            };
        }

        function bindEvents() {
            if (dom.itensBody) {
                dom.itensBody.addEventListener('input', (event) => {
                    if (!atualizarItemPorCampo(event.target)) return;
                    atualizarResumo();
                });

                dom.itensBody.addEventListener('change', (event) => {
                    if (!atualizarItemPorCampo(event.target)) return;
                    renderItens();
                });

                dom.itensBody.addEventListener('click', (event) => {
                    const btn = event.target.closest('.item-remove');
                    if (!btn) return;
                    const tipo = btn.dataset.tipo || 'produto';
                    const idx = Number(btn.dataset.index);
                    const itens = obterItens(tipo);
                    itens.splice(idx, 1);
                    renderItens();
                });
            }

            dom.freteManualCheck.addEventListener('change', atualizarResumo);

            dom.freteTotalInput.addEventListener('focus', () => { state.flags.freteTotalEditando = true; });
            dom.freteTotalInput.addEventListener('blur', () => { state.flags.freteTotalEditando = false; atualizarResumo(); });
            dom.freteTotalInput.addEventListener('input', atualizarResumo);

            dom.descontoTotalInput.addEventListener('focus', () => { state.flags.descontoTotalEditando = true; });
            dom.descontoTotalInput.addEventListener('blur', () => { state.flags.descontoTotalEditando = false; atualizarResumo(); });
            dom.descontoTotalInput.addEventListener('input', (event) => {
                const valor = Math.max(0, valorNum(event.target.value));
                if (!state.itens_produto.length) return;
                ratearDescontoProdutos(valor);
            });

            dom.descontoPercentInput.addEventListener('input', (event) => {
                state.flags.descontoPercentControlando = true;
                const perc = Math.max(0, Math.min(100, valorNum(event.target.value)));
                const subtotal = state.itens_produto.reduce((acc, item) => acc + (item.quantidade * item.valorUnitario), 0);
                const descontoTotal = subtotal * (perc / 100);
                if (state.itens_produto.length) {
                    ratearDescontoProdutos(descontoTotal);
                }
                state.flags.descontoPercentControlando = false;
            });

            dom.condicaoPagamento.addEventListener('change', (event) => {
                const isVista = event.target.value === 'vista';
                if (isVista) {
                    dom.qtdParcelas.value = 1;
                    dom.qtdParcelas.setAttribute('readonly', 'readonly');
                    state.parcelas = [criarParcela(hoje, totalVenda(), 'PIX', false)];
                    renderParcelas();
                    return;
                }

                dom.qtdParcelas.removeAttribute('readonly');
                montarParcelas(Math.max(1, parseInt(dom.qtdParcelas.value, 10) || 1));
            });

            dom.qtdParcelas.addEventListener('input', (event) => {
                if (dom.condicaoPagamento.value === 'vista') return;
                montarParcelas(Math.max(1, Math.min(24, parseInt(event.target.value, 10) || 1)));
            });

            dom.parcelasBody.addEventListener('input', (event) => {
                const idx = Number(event.target.dataset.index);
                if (!Number.isInteger(idx) || !state.parcelas[idx]) return;
                if (event.target.classList.contains('parcela-venc')) state.parcelas[idx].vencimento = event.target.value || hoje;
                if (event.target.classList.contains('parcela-valor')) {
                    state.parcelas[idx].valor = Math.max(0, valorNum(event.target.value));
                    state.parcelas[idx].manual = true;
                }
                if (event.target.classList.contains('parcela-tipo')) state.parcelas[idx].tipoPagamento = event.target.value;
            });

            dom.parcelasBody.addEventListener('change', (event) => {
                const idx = Number(event.target.dataset.index);
                if (!Number.isInteger(idx) || !state.parcelas[idx]) return;

                if (event.target.classList.contains('parcela-venc')) {
                    state.parcelas[idx].vencimento = event.target.value || hoje;
                    return;
                }

                if (event.target.classList.contains('parcela-valor')) {
                    state.parcelas[idx].valor = Math.max(0, valorNum(event.target.value));
                    state.parcelas[idx].manual = true;
                    recalcularParcelas();
                    return;
                }

                if (event.target.classList.contains('parcela-tipo')) {
                    state.parcelas[idx].tipoPagamento = event.target.value;
                    const tipoNormalizado = (event.target.value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
                    if (tipoNormalizado.includes('credito') || tipoNormalizado.includes('cheque')) {
                        for (let i = idx + 1; i < state.parcelas.length; i += 1) {
                            state.parcelas[i].tipoPagamento = event.target.value;
                        }
                        renderParcelas();
                    }
                }
            });

            dom.parcelasBody.addEventListener('contextmenu', (event) => {
                if (dom.condicaoPagamento.value !== 'parcelado') return;
                event.preventDefault();
                const row = event.target.closest('tr');
                if (row && state.parcelas.length > 1) {
                    state.parcelas.splice(Number(row.dataset.index), 1);
                    recalcularParcelas();
                    return;
                }
                state.parcelas.push(criarParcela(hoje, 0, 'PIX', false));
                recalcularParcelas();
            });
        }

        function addItem(tipo, item) {
            const itens = obterItens(tipo);
            itens.push(normalizarItem(item, tipo));
            renderItens();
        }

        function setItens(itensProduto, itensMicroservico) {
            state.itens_produto = Array.isArray(itensProduto)
                ? itensProduto.map((item) => normalizarItem(item, 'produto'))
                : [];
            state.itens_microservico = Array.isArray(itensMicroservico)
                ? itensMicroservico.map((item) => normalizarItem(item, 'microservico'))
                : [];

            if (dom.itensBody) {
                renderItens();
                return;
            }

            atualizarResumo();
        }

        function setParcelas(parcelas) {
            state.parcelas = Array.isArray(parcelas) ? parcelas.map((p) => ({ ...p })) : [];
            recalcularParcelas();
        }

        function zerarDescontosProdutos() {
            state.itens_produto.forEach((item) => { item.desconto = 0; });
            dom.descontoPercentInput.value = '0,00';
            renderItens();
        }

        function reset() {
            state.itens_produto = [];
            state.itens_microservico = [];
            state.parcelas = [criarParcela(hoje, 0, 'PIX', false)];
            state.flags.descontoPercentControlando = false;
            state.flags.descontoTotalEditando = false;
            state.flags.freteTotalEditando = false;

            dom.freteManualCheck.checked = false;
            dom.freteTotalInput.value = '0,00';
            dom.descontoTotalInput.value = '0,00';
            dom.descontoPercentInput.value = '0,00';
            dom.condicaoPagamento.value = 'vista';
            dom.qtdParcelas.value = '1';
            dom.qtdParcelas.setAttribute('readonly', 'readonly');

            renderItens();
            renderParcelas();
        }

        bindEvents();
        montarParcelas(1);
        dom.condicaoPagamento.dispatchEvent(new Event('change'));
        renderItens();

        return {
            addItem,
            setItens,
            getState,
            getResumo: obterResumo,
            totalVenda,
            setParcelas,
            zerarDescontosProdutos,
            reset,
            valorNum,
            moeda
        };
    }

    global.ComposicaoComercial = {
        init: criarComposicaoComercial,
        valorNum,
        moeda
    };
})(window);
