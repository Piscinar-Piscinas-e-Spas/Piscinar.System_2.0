<?php
include '../includes/db.php';
require_login();

$status = (string) ($_GET['status'] ?? '');
$toastMap = [
    'transferencia_ok' => ['title' => 'Transferencia concluida', 'body' => 'Os saldos foram movimentados com sucesso.', 'class' => 'text-bg-primary'],
    'transferencia_erro' => ['title' => 'Transferencia nao realizada', 'body' => 'Revise os dados e tente novamente.', 'class' => 'text-bg-danger'],
    'saldo_insuficiente' => ['title' => 'Saldo insuficiente', 'body' => 'Um ou mais itens nao possuem saldo suficiente na origem.', 'class' => 'text-bg-warning'],
];
$toast = $toastMap[$status] ?? null;

$produtos = $pdo->query('SELECT id, nome, grupo, qtdLoja, qtdEstoque, estoque_minimo FROM produtos ORDER BY nome ASC')->fetchAll();
$sugestoes = $pdo->query('
    SELECT id, nome, grupo, qtdLoja, qtdEstoque, estoque_minimo,
           GREATEST(LEAST(COALESCE(estoque_minimo, 0) - COALESCE(qtdLoja, 0), COALESCE(qtdEstoque, 0)), 0) AS quantidade_sugerida
    FROM produtos
    WHERE COALESCE(estoque_minimo, 0) > COALESCE(qtdLoja, 0)
      AND COALESCE(qtdEstoque, 0) > 0
    ORDER BY nome ASC
')->fetchAll();

$produtosJson = json_encode($produtos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$sugestoesJson = json_encode(array_values(array_filter($sugestoes, static function (array $item): bool {
    return (int) ($item['quantidade_sugerida'] ?? 0) > 0;
})), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$extraHeadContent = '
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="' . htmlspecialchars(app_url('assets/css/logistica.css'), ENT_QUOTES, 'UTF-8') . '">
';

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h3 class="mb-1"><i class="bi bi-arrow-left-right me-2 text-primary"></i>Transferencia Inteligente</h3>
            <p class="text-muted mb-0">Movimente mercadorias com validacao de saldo e sugestoes automaticas para abastecer a loja.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <a href="<?= htmlspecialchars(app_url('logistica/inventario.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-clipboard-data me-1"></i>Ir para Inventario
            </a>
            <button type="button" class="btn btn-outline-primary" id="btnSugerirReposicao">
                <i class="bi bi-stars me-1"></i>Sugerir Reposicao de Loja
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm logistics-card">
        <div class="card-body">
            <form method="post" action="<?= htmlspecialchars(app_url('logistica/processar_transferencia.php'), ENT_QUOTES, 'UTF-8') ?>" id="transferenciaForm">
                <?= csrf_input() ?>
                <div class="transfer-layout mb-4">
                    <div class="transfer-side">
                        <div class="text-uppercase small text-muted mb-2">Lado A</div>
                        <label class="form-label fw-semibold">Origem</label>
                        <select name="origem" id="origemSelect" class="form-select">
                            <option value="qtdEstoque" selected>Estoque Auxiliar</option>
                            <option value="qtdLoja">Loja</option>
                        </select>
                        <p class="text-muted small mt-3 mb-0">O saldo disponivel sera lido deste local.</p>
                    </div>

                    <div class="transfer-arrow text-primary">
                        <i class="bi bi-arrow-left-right"></i>
                    </div>

                    <div class="transfer-side">
                        <div class="text-uppercase small text-muted mb-2">Lado B</div>
                        <label class="form-label fw-semibold">Destino</label>
                        <select name="destino" id="destinoSelect" class="form-select">
                            <option value="qtdLoja" selected>Loja</option>
                            <option value="qtdEstoque">Estoque Auxiliar</option>
                        </select>
                        <p class="text-muted small mt-3 mb-0">A quantidade confirmada sera somada aqui.</p>
                    </div>
                </div>

                <div class="alert alert-warning d-none" id="transferAlert" role="alert"></div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <h5 class="mb-1">Itens da transferencia</h5>
                        <p class="text-muted mb-0">Use Select2 para localizar rapidamente entre os produtos cadastrados.</p>
                    </div>
                    <button type="button" class="btn btn-outline-secondary" id="btnAdicionarItem">
                        <i class="bi bi-plus-circle me-1"></i>Adicionar Item
                    </button>
                </div>

                <div id="transferItems" class="d-grid gap-3"></div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4">
                    <div class="text-muted small">
                        A transferencia confirma a saida da origem e a entrada no destino em uma unica transacao.
                    </div>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check2-circle me-1"></i>Confirmar Transferencia
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($toast): ?>
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
        <div id="transferToast" class="toast <?= htmlspecialchars($toast['class'], ENT_QUOTES, 'UTF-8') ?>" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="bi bi-truck me-2"></i>
                <strong class="me-auto"><?= htmlspecialchars($toast['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Fechar"></button>
            </div>
            <div class="toast-body"><?= htmlspecialchars($toast['body'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>
<?php endif; ?>

<script>
    window.LOGISTICA_PRODUTOS = <?= $produtosJson ?: '[]' ?>;
    window.LOGISTICA_SUGESTOES = <?= $sugestoesJson ?: '[]' ?>;
</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="<?= htmlspecialchars(app_url('assets/js/logistica_transferencia.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<?php if ($toast): ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var toastEl = document.getElementById('transferToast');
        if (toastEl && typeof bootstrap !== 'undefined' && bootstrap.Toast) {
            bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 4500, animation: true }).show();
        }
    });
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
