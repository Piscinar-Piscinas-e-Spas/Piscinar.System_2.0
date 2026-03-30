<footer class="site-footer mt-5">
  <div class="container py-2 py-sm-4 py-md-5">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="footer-brand mb-3">
          <img src="<?= htmlspecialchars(app_url('assets/img/Branco-sem-fundo.png'), ENT_QUOTES, 'UTF-8') ?>"
            alt="Piscinar" class="footer-logo me-2">
          <span class="fw-semibold">Piscinar System 2.0</span>
        </div>
        <p class="mb-0 text-white">Sistema de Gestao para Loja de Piscinas com foco em vendas, produtos e
          clientes.</p>
      </div>
      <div class="col-lg-5">
        <h6 class="footer-title">Mapa do sistema</h6>
        <div class="footer-map-columns">
          <ul id="focais" class="footer-map list-unstyled mb-0">
            <li><a href="<?= htmlspecialchars(app_url('index.php'), ENT_QUOTES, 'UTF-8') ?>">Inicio</a></li>
            <li><a href="<?= htmlspecialchars(app_url('produtos/listar.php'), ENT_QUOTES, 'UTF-8') ?>">Produtos</a></li>
            <li><a href="<?= htmlspecialchars(app_url('clientes/listar.php'), ENT_QUOTES, 'UTF-8') ?>">Clientes</a></li>
            <li><a href="<?= htmlspecialchars(app_url('fornecedores/listar.php'), ENT_QUOTES, 'UTF-8') ?>">Fornecedor</a></li>
            <li><a href="<?= htmlspecialchars(app_url('vendas/nova.php'), ENT_QUOTES, 'UTF-8') ?>">Vendas</a></li>
            <li><a href="<?= htmlspecialchars(app_url('compras/listar.php'), ENT_QUOTES, 'UTF-8') ?>">Compras</a></li>
            <li><a href="<?= htmlspecialchars(app_url('servicos/nova.php'), ENT_QUOTES, 'UTF-8') ?>">Servico</a></li>
          </ul>

          <div class="footer-map-divider" aria-hidden="true"></div>

          <ul id="administrativas" class="footer-map list-unstyled mb-0">
            <li><a href="<?= htmlspecialchars(app_url('usuarios/cadastrar.php'), ENT_QUOTES, 'UTF-8') ?>">Cadastro de usuarios</a></li>
            <li><a href="<?= htmlspecialchars(app_url('logs/listar.php'), ENT_QUOTES, 'UTF-8') ?>">Logs</a></li>
            <li><a href="<?= htmlspecialchars(app_url('vendas/listar.php'), ENT_QUOTES, 'UTF-8') ?>">Dashboard de vendas</a></li>
            <li><a href="<?= htmlspecialchars(app_url('servicos/listar.php'), ENT_QUOTES, 'UTF-8') ?>">Dashboard de Servicos</a></li>
            <li><a href="<?= htmlspecialchars(app_url('logistica/inventario.php'), ENT_QUOTES, 'UTF-8') ?>">Gestao de Estoque</a></li>
            <li><a href="<?= htmlspecialchars(app_url('compras/entrada.php'), ENT_QUOTES, 'UTF-8') ?>">Entrada de Mercadoria</a></li>
            <li><a href="<?= htmlspecialchars(app_url('logistica/transferencia.php'), ENT_QUOTES, 'UTF-8') ?>">Transferencias Entre Estoques</a></li>
          </ul>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <h6 class="footer-title">Informacoes de versao</h6>
        <div class="version-badge"><span id="resultado">Carregando...</span></div>

        <p class="small mb-0 text-white">Versao calculada automaticamente com base no total de commits do
          repositorio.<small id="mensagem"></small></p>
      </div>
    </div>
  </div>
</footer>

<!-- Bootstrap JS e dependencias -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>

    async function getCommitCount(owner, repo) {
      const url = `https://api.github.com/repos/${owner}/${repo}/commits?per_page=1`;

      const response = await fetch(url, {
        headers: {
          "Accept": "application/vnd.github+json",
          "X-GitHub-Api-Version": "2022-11-28"
        }
      });

      if (!response.ok) {
        throw new Error(`Erro ${response.status}: nao foi possivel consultar o repositorio.`);
      }

      const linkHeader = response.headers.get("Link");

      if (linkHeader) {
        const match = linkHeader.match(/[?&]page=(\d+)>;\s*rel="last"/);
        if (match) {
          return parseInt(match[1], 10);
        }
      }

      const commits = await response.json();
      return Array.isArray(commits) ? commits.length : 0;
    }

    async function carregarTotalCommits() {
      const resultado = document.getElementById("resultado");
      const mensagem = document.getElementById("mensagem");

      try {
        const count = await getCommitCount("Piscinar-Piscinas-e-Spas", "Piscinar.System_2.0");
        const versao = "2.0" + count.toString().padStart(5, "0");
        resultado.textContent = versao;
        mensagem.textContent = "Consulta realizada com sucesso.";
        mensagem.classList.add("text-info");
      } catch (error) {
        console.error(error);
        resultado.textContent = "Erro ao carregar";
        mensagem.textContent = error.message;
        mensagem.classList.add("text-warnig");
      }
    }

    carregarTotalCommits();

</script>

<?php if (function_exists('is_authenticated') && is_authenticated()): ?>
  <script>
    (function () {
      var timeoutSeconds = <?= (int) (defined('SESSION_TIMEOUT_SECONDS') ? SESSION_TIMEOUT_SECONDS : 12600); ?>;
      var warningSeconds = <?= (int) (defined('SESSION_EXPIRY_WARNING_SECONDS') ? SESSION_EXPIRY_WARNING_SECONDS : 60); ?>;
      var lastActivityAt = <?= (int) ($_SESSION['last_activity_at'] ?? time()); ?>;

      if (timeoutSeconds <= 0 || warningSeconds <= 0 || warningSeconds >= timeoutSeconds) {
        return;
      }

      var warned = false;
      var warningMoment = (lastActivityAt + timeoutSeconds - warningSeconds) * 1000;

      var updateActivity = function () {
        lastActivityAt = Math.floor(Date.now() / 1000);
        warningMoment = (lastActivityAt + timeoutSeconds - warningSeconds) * 1000;
        warned = false;
      };

      ['click', 'keydown', 'mousemove', 'scroll', 'touchstart'].forEach(function (evt) {
        window.addEventListener(evt, updateActivity, { passive: true });
      });

      window.setInterval(function () {
        var now = Date.now();
        if (!warned && now >= warningMoment && now < (lastActivityAt + timeoutSeconds) * 1000) {
          warned = true;
          if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
            var toastEl = document.getElementById('sessionExpiryWarningToast');
            if (toastEl) {
              var toast = bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 10000, animation: true });
              toast.show();
              return;
            }
          }

          alert('Sua sessao sera encerrada em aproximadamente 1 minuto por inatividade.');
        }
      }, 15000);
    })();

  </script>

  <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1080;">
    <div id="sessionExpiryWarningToast" class="toast align-items-center text-bg-secondary border-0" role="alert"
      aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">Sua sessao sera encerrada em aproximadamente 1 minuto por inatividade.</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
          aria-label="Fechar"></button>
      </div>
    </div>
  </div>
<?php endif; ?>
</div>
</body>

</html>
