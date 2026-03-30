<?php
include '../includes/db.php';
require_login();

$controller = new \App\Controllers\CompraEntradaController($pdo);
$formData = $controller->formData();
$fornecedores = is_array($formData['fornecedores'] ?? null) ? $formData['fornecedores'] : [];
$produtos = is_array($formData['produtos'] ?? null) ? $formData['produtos'] : [];
$hoje = (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');

include '../includes/header.php';
?>

<div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="fas fa-truck-loading me-2"></i>Entrada de Mercadoria</h4>
        <span class="badge bg-light text-dark">Compra por nota</span>
    </div>
    <div class="card-body">
        <form id="formCompraEntrada" class="row g-3" autocomplete="off">
            <?= csrf_input() ?>
            <div class="col-12">
                <div class="card border-primary-subtle">
                    <div class="card-header bg-light">1) Fornecedor e dados da nota</div>
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-5">
                                <label class="form-label" for="fornecedorNome">Fornecedor</label>
                                <input type="text" class="form-control" id="fornecedorNome" list="fornecedoresSugestoes" placeholder="Digite para buscar fornecedor">
                                <datalist id="fornecedoresSugestoes">
                                    <?php foreach ($fornecedores as $fornecedor): ?>
                                        <option value="<?= htmlspecialchars((string) $fornecedor['nome_fornecedor'], ENT_QUOTES, 'UTF-8') ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            <div class="col-lg-2">
                                <label class="form-label" for="fornecedorDocumento">Documento</label>
                                <input type="text" class="form-control" id="fornecedorDocumento" placeholder="CPF/CNPJ">
                            </div>
                            <div class="col-lg-2">
                                <label class="form-label" for="fornecedorTelefone">Telefone</label>
                                <input type="text" class="form-control" id="fornecedorTelefone" placeholder="Contato">
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label" for="fornecedorEmail">E-mail</label>
                                <input type="email" class="form-control" id="fornecedorEmail" placeholder="financeiro@fornecedor.com">
                            </div>
                        </div>
                        <div class="row g-3 align-items-end mt-1">
                            <div class="col-md-3">
                                <label class="form-label" for="numeroNota">Numero da nota</label>
                                <input type="text" class="form-control" id="numeroNota" placeholder="Ex.: 12345">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="dataEmissao">Data de emissao</label>
                                <input type="date" class="form-control" id="dataEmissao" value="<?= htmlspecialchars($hoje, ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="dataEntrada">Data de entrada</label>
                                <input type="date" class="form-control" id="dataEntrada" value="<?= htmlspecialchars($hoje, ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-3 d-grid">
                                <button type="button" class="btn btn-outline-primary" id="btnSalvarFornecedor">
                                    <i class="fas fa-building me-1"></i>Salvar fornecedor rapido
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-primary-subtle">
                    <div class="card-header bg-light">2) Itens da nota</div>
                    <div class="card-body">
                        <div class="row g-2 align-items-end mb-3">
                            <div class="col-lg-4">
                                <label class="form-label" for="produtoBusca">Produto</label>
                                <input type="text" class="form-control" id="produtoBusca" list="produtosSugestoes" placeholder="Digite para buscar produto">
                                <datalist id="produtosSugestoes">
                                    <?php foreach ($produtos as $produto): ?>
                                        <option value="<?= htmlspecialchars((string) $produto['nome'], ENT_QUOTES, 'UTF-8') ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            <div class="col-lg-1">
                                <label class="form-label" for="itemQuantidadeTotal">Qtd total</label>
                                <input type="number" class="form-control" id="itemQuantidadeTotal" min="0.001" step="0.001" value="1">
                            </div>
                            <div class="col-lg-1">
                                <label class="form-label" for="itemQuantidadeLoja">Qtd loja</label>
                                <input type="number" class="form-control" id="itemQuantidadeLoja" min="0" step="0.001" value="0">
                            </div>
                            <div class="col-lg-2">
                                <label class="form-label" for="itemQuantidadeEstoque">Qtd estoque auxiliar</label>
                                <input type="number" class="form-control" id="itemQuantidadeEstoque" min="0" step="0.001" value="1">
                            </div>
                            <div class="col-lg-2">
                                <label class="form-label" for="itemCustoUnitario">Custo unitario</label>
                                <input type="text" class="form-control" id="itemCustoUnitario" inputmode="decimal" value="0,00">
                            </div>
                            <div class="col-lg-2 d-grid gap-2">
                                <button type="button" class="btn btn-success" id="btnAdicionarItem">
                                    <i class="fas fa-plus me-1"></i>Adicionar item
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="btnSalvarProduto">
                                    <i class="fas fa-box-open me-1"></i>Cadastrar produto
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle" id="itensNotaTable">
                                <thead class="table-primary">
                                    <tr>
                                        <th>#</th>
                                        <th>Produto</th>
                                        <th>Qtd total</th>
                                        <th>Qtd loja</th>
                                        <th>Qtd estoque auxiliar</th>
                                        <th>Custo unitario</th>
                                        <th>Subtotal</th>
                                        <th>Acao</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="small text-muted mt-2">Cada item precisa distribuir 100% da quantidade entre Loja e Estoque Auxiliar.</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-primary-subtle h-100">
                    <div class="card-header bg-light">3) Totais da compra</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="valorFrete">Frete</label>
                                <input type="text" class="form-control" id="valorFrete" inputmode="decimal" value="0,00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="valorDesconto">Desconto</label>
                                <input type="text" class="form-control" id="valorDesconto" inputmode="decimal" value="0,00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="valorOutrasDespesas">Outras despesas</label>
                                <input type="text" class="form-control" id="valorOutrasDespesas" inputmode="decimal" value="0,00">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="observacoesNota">Observacoes</label>
                                <textarea class="form-control" id="observacoesNota" rows="3" placeholder="Informacoes adicionais da nota ou da entrega"></textarea>
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between"><span>Subtotal dos itens</span><strong id="subtotalItensLabel">R$ 0,00</strong></div>
                        <div class="d-flex justify-content-between"><span>Total da nota</span><strong id="totalNotaLabel">R$ 0,00</strong></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-primary-subtle h-100">
                    <div class="card-header bg-light">4) Parcelas a pagar</div>
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label" for="condicaoPagamento">Condicao</label>
                                <select class="form-select" id="condicaoPagamento">
                                    <option value="vista">A vista</option>
                                    <option value="parcelado">Parcelado</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="qtdParcelas">Qtd parcelas</label>
                                <input type="number" class="form-control" id="qtdParcelas" min="1" max="24" value="1">
                            </div>
                            <div class="col-md-4 d-grid">
                                <button type="button" class="btn btn-outline-primary" id="btnGerarParcelas">
                                    <i class="fas fa-calendar-alt me-1"></i>Gerar parcelas
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive mt-3">
                            <table class="table table-sm table-bordered align-middle" id="parcelasTable">
                                <thead class="table-secondary">
                                    <tr>
                                        <th>#</th>
                                        <th>Vencimento</th>
                                        <th>Valor</th>
                                        <th>Tipo pagamento</th>
                                        <th>Acao</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="small text-muted">A soma das parcelas precisa fechar exatamente com o total da nota.</div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div id="compraEntradaFeedback" class="alert d-none" role="alert"></div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" id="btnLimparCompra">Limpar</button>
                    <button type="submit" class="btn btn-primary" id="btnSalvarCompra">Salvar entrada</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
window.compraEntradaBootstrap = {
    csrfToken: <?= json_encode(csrf_token(), JSON_UNESCAPED_UNICODE) ?>,
    hoje: <?= json_encode($hoje, JSON_UNESCAPED_UNICODE) ?>,
    fornecedores: <?= json_encode($fornecedores, JSON_UNESCAPED_UNICODE) ?>,
    produtos: <?= json_encode($produtos, JSON_UNESCAPED_UNICODE) ?>,
    endpoints: {
        salvarEntrada: <?= json_encode(app_url('compras/salvar_entrada.php'), JSON_UNESCAPED_UNICODE) ?>,
        salvarFornecedor: <?= json_encode(app_url('fornecedores/salvar_rapido.php'), JSON_UNESCAPED_UNICODE) ?>,
        salvarProduto: <?= json_encode(app_url('produtos/salvar_rapido_compra.php'), JSON_UNESCAPED_UNICODE) ?>
    }
};
</script>
<script src="<?= htmlspecialchars(app_url('assets/js/compra_entrada.js'), ENT_QUOTES, 'UTF-8') ?>"></script>

<?php include '../includes/footer.php'; ?>
