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
                    <button type="button" class="btn btn-outline-secondary" id="btnLimparVenda">
                        <i class="fas fa-broom me-1"></i>Limpar campos
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSalvarVenda">
                        <i class="fas fa-save me-1"></i>Salvar venda
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/composicao_comercial.js"></script>
<script>
    const hojeSP = '<?= $hojeSaoPaulo ?>';
    const csrfToken = '<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>';
    const tiposPagamento = [
        'PIX', 'Dinheiro', 'Boleto', 'Cheque', 'Pix Pague Seguro',
        'Débito PagSeguro', 'Crédito PagSeguro', 'Débito Stone',
        'Crédito Stone', 'Débito Infinite', 'Crédito Infinite'
    ];
    const clientesData = <?= json_encode($clientes, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

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
    let clienteSelecionadoId = null;

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

    function mostrarFeedback(tipo, mensagem) {
        feedbackBox.className = `alert alert-${tipo}`;
        feedbackBox.textContent = mensagem;
        feedbackBox.classList.remove('d-none');
    }

    function limparFeedback() {
        feedbackBox.className = 'alert d-none';
        feedbackBox.textContent = '';
    }

    function limparFormularioVendaPosSucesso() {
        clienteSelecionadoId = null;
        clienteNome.value = '';
        clienteTelefone.value = '';
        clienteCpfCnpj.value = '';
        clienteEmail.value = '';
        clienteEndereco.value = '';

        document.getElementById('produtoSelect').value = '';
        document.getElementById('produtoQtd').value = '1';
        document.getElementById('produtoValorUnitario').value = '0,00';

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

    function obterClienteSelecionadoPorId(idCliente) {
        const id = Number(idCliente);
        if (!Number.isInteger(id) || id <= 0) return null;
        return clientesData.find((cliente) => Number(cliente.id_cliente) === id) || null;
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

    function montarPayloadVenda() {
        const clienteBase = obterClienteSelecionadoPorId(clienteSelecionadoId);
        if (!clienteBase) {
            throw new Error('Selecione ou salve um cliente antes de salvar a venda.');
        }

        const estado = composicao.getState();
        if (!estado.itens_produto.length) {
            throw new Error('Adicione ao menos um item na venda.');
        }

        const resumo = composicao.getResumo();
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
            cliente_id: Number(clienteBase.id_cliente),
            cliente: {
                nome: clienteNome.value.trim(),
                telefone: clienteTelefone.value.trim(),
                cpf_cnpj: clienteCpfCnpj.value.trim(),
                email: clienteEmail.value.trim(),
                endereco: clienteEndereco.value.trim()
            },
            cliente_resolucao: 'manter',
            validar_cliente_consistencia: true,
            condicao_pagamento: document.getElementById('condicaoPagamento').value,
            subtotal: Number((resumo.subtotal_produtos + resumo.subtotal_microservicos).toFixed(2)),
            desconto_total: Number(resumo.desconto_total.toFixed(2)),
            frete_total: Number(resumo.frete_total.toFixed(2)),
            total_geral: Number(resumo.total_geral.toFixed(2)),
            itens: estado.itens_produto.map((item) => ({
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
        let clienteBase;
        try {
            payload = montarPayloadVenda();
            clienteBase = obterClienteSelecionadoPorId(payload.cliente_id);
        } catch (error) {
            mostrarFeedback('warning', error.message || 'Verifique os dados da venda antes de salvar.');
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

            window.alert(`Venda #${dados.id_venda} salva com sucesso.`);
            limparFormularioVendaPosSucesso();
        } catch (error) {
            mostrarFeedback('danger', error.message || 'Erro inesperado ao salvar venda.');
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

    btnSalvarCliente.addEventListener('click', salvarClienteRapido);

    document.getElementById('produtoSelect').addEventListener('change', (event) => {
        const opt = event.target.selectedOptions[0];
        document.getElementById('produtoValorUnitario').value = (composicao.valorNum(opt?.dataset?.preco)).toFixed(2).replace('.', ',');
    });

    document.getElementById('btnAdicionarProduto').addEventListener('click', () => {
        const select = document.getElementById('produtoSelect');
        const opt = select.selectedOptions[0];

        if (!opt || !opt.value) {
            alert('Selecione um produto para adicionar.');
            return;
        }

        composicao.addItem('produto', {
            produtoId: Number(opt.value),
            nome: opt.dataset.nome || opt.textContent,
            quantidade: Math.max(1, parseInt(document.getElementById('produtoQtd').value, 10) || 1),
            valorUnitario: Math.max(0, composicao.valorNum(document.getElementById('produtoValorUnitario').value)),
            desconto: 0,
            freteItem: 0
        });
    });

    document.getElementById('btnZerarDescontos').addEventListener('click', () => {
        composicao.zerarDescontosProdutos();
    });

    btnLimparVenda.addEventListener('click', () => {
        limparFormularioVendaPosSucesso();
    });

    formVenda.addEventListener('submit', (event) => {
        event.preventDefault();
        enviarVenda();
    });
</script>

<?php include '../includes/footer.php'; ?>
