<?php
    // O header resolve o menu ativo para o layout inteiro,
    // assim as paginas nao precisam duplicar essa rotina.
    $currentRoute = parse_url($_SERVER['REQUEST_URI'] ?? ($_SERVER['PHP_SELF'] ?? ''), PHP_URL_PATH) ?? '';
    $currentRoute = rtrim($currentRoute, '/');
    $currentRoute = $currentRoute === '' ? '/' : $currentRoute;

    require_once dirname(__DIR__) . '/config.php';

    // Tira o BASE_URL da rota atual para a comparacao funcionar igual
    // em raiz de dominio ou subpasta.
    $basePath = parse_url(app_url(), PHP_URL_PATH) ?? '';
    $basePath = rtrim($basePath, '/');

    if ($basePath !== '' && strpos($currentRoute, $basePath) === 0) {
        $currentRoute = substr($currentRoute, strlen($basePath));
        $currentRoute = $currentRoute === '' ? '/' : $currentRoute;
    }

    $inicioActive = ($currentRoute === '/' || $currentRoute === '/index.php') ? ' active' : '';
    $produtosActive = strpos($currentRoute, '/produtos/') !== false ? ' active' : '';
    $clientesActive = strpos($currentRoute, '/clientes/') !== false ? ' active' : '';
    $vendasActive = strpos($currentRoute, '/vendas/') !== false ? ' active' : '';
    $servicosActive = strpos($currentRoute, '/servicos/') !== false ? ' active' : '';
    $comprasActive = strpos($currentRoute, '/compras/') !== false ? ' active' : '';
    $styleActive = strpos($currentRoute, '/assets/css/') !== false ? ' active' : '';
?>

<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light" data-theme-preference="auto" data-app-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Piscinas</title>

    <script>
        (function () {
            var storageKey = 'piscinar.theme.preference';
            var preference = 'auto';
            var bootstrapThemeMap = {
                light: 'light',
                dark: 'dark',
                wellbeing: 'light',
                'neo-neon': 'dark',
                sunwash: 'light',
                thermal: 'dark',
                walnut: 'light'
            };

            try {
                var storedPreference = window.localStorage ? window.localStorage.getItem(storageKey) : null;
                if (storedPreference === 'auto' || bootstrapThemeMap[storedPreference]) {
                    preference = storedPreference;
                }
            } catch (error) {
                preference = 'auto';
            }

            var resolvedAppTheme = preference === 'auto'
                ? ((window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light')
                : preference;
            var resolvedBootstrapTheme = bootstrapThemeMap[resolvedAppTheme] || 'light';

            document.documentElement.setAttribute('data-theme-preference', preference);
            document.documentElement.setAttribute('data-app-theme', resolvedAppTheme);
            document.documentElement.setAttribute('data-bs-theme', resolvedBootstrapTheme);
            document.documentElement.style.colorScheme = resolvedBootstrapTheme;
        })();
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Anton&family=Fraunces:opsz,wght@9..144,600;9..144,700&family=JetBrains+Mono:wght@400;500;600;700&family=Lora:wght@500;600;700&family=Nunito:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&family=Space+Grotesk:wght@400;500;700&family=Syne:wght@700;800&display=swap">
    <link rel="stylesheet" class="<?php echo $styleActive; ?>" href="<?php echo app_url('assets/css/styles.css'); ?>">
    <link rel="icon" type="image/x-icon" href="<?php echo app_url('assets/img/favicon.ico'); ?>">
    <?php if (!empty($extraHeadContent ?? '')): ?>
        <?= $extraHeadContent ?>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="<?= htmlspecialchars(app_url('assets/js/theme_preference.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script src="<?= htmlspecialchars(app_url('assets/js/app_speech_feedback.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</head>
<body>
    <?php
        // Esses dados alimentam o menu de conta e o painel lateral do usuario autenticado.
        $authUser = function_exists('auth_user') ? auth_user() : null;
        $authDisplayName = function_exists('auth_user_display_name') ? auth_user_display_name() : '';
        $authUsername = is_array($authUser) ? (string) ($authUser['usuario'] ?? '') : '';
    ?>
    <div class="container page-shell">
        <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4 mobile-tablet-fixed-nav">
            <div class="container-fluid menu">
                <a class="navbar-brand link-desativado" href="#">
                    <img src="<?php echo app_url('assets/img/android-chrome-512x512.png'); ?>" alt="Piscinar" class="brand-logo me-2">Piscinar System 2.0
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <!-- Menu principal de navegacao entre os modulos -->
                    <ul class="navbar-nav w-75 justify-content-around">
                        <li class="nav-item">
                            <a class="nav-link<?php echo $inicioActive; ?>" href="<?php echo app_url('index.php'); ?>">
                                <i class="fas fa-home"></i> In&iacute;cio
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo $produtosActive; ?>" href="<?php echo app_url('produtos/listar.php'); ?>"><i class="fas fa-box-open"></i> Produtos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo $clientesActive; ?>" href="<?php echo app_url('clientes/listar.php'); ?>"><i class="fas fa-users"></i> Clientes</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo $vendasActive; ?>" href="<?php echo app_url('vendas/nova.php'); ?>"><i class="fas fa-file-invoice-dollar"></i> Vendas</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo $servicosActive; ?>" href="<?php echo app_url('servicos/nova.php'); ?>"><i class="fas fa-tools"></i> Serviços</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo $comprasActive; ?>" href="<?php echo app_url('compras/entrada.php'); ?>"><i class="fas fa-truck-loading"></i> Compra</a>
                        </li>
                    </ul>

                    <ul class="navbar-nav ms-auto">
                        <!-- Acoes de conta so aparecem para sessao autenticada -->
                        <?php if (function_exists('is_authenticated') && is_authenticated()): ?>
                        <li class="nav-item" id="userShow">
                            <button
                                type="button"
                                class="nav-link text-muted btn btn-link user-settings-trigger"
                                data-bs-toggle="offcanvas"
                                data-bs-target="#userSettingsPanel"
                                aria-controls="userSettingsPanel"
                            >
                                <i class="fas fa-user-circle"></i>
                                <?= htmlspecialchars((string) ($authDisplayName !== '' ? $authDisplayName : ($authUsername !== '' ? $authUsername : 'Usuario')), ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="btn-soft-danger" href="<?= htmlspecialchars(app_url('logout.php'), ENT_QUOTES, 'UTF-8') ?>">
                                <i class="fas fa-sign-out-alt"></i> Sair
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
        <?php if (function_exists('is_authenticated') && is_authenticated()): ?>
        <!-- Offcanvas para ajustes rapidos sem obrigar uma pagina separada de perfil -->
        <div
            class="offcanvas offcanvas-end user-settings-offcanvas"
            tabindex="-1"
            id="userSettingsPanel"
            aria-labelledby="userSettingsPanelLabel"
            data-profile-endpoint="<?= htmlspecialchars(app_url('usuarios/atualizar_perfil.php'), ENT_QUOTES, 'UTF-8') ?>"
        >
            <div class="offcanvas-header">
                <div>
                    <div class="user-settings-eyebrow">Configuracoes rapidas</div>
                    <h5 class="offcanvas-title mb-0" id="userSettingsPanelLabel">Minha conta</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
            </div>
            <div class="offcanvas-body">
                <div id="userSettingsFeedback" class="alert d-none" role="alert"></div>

                <section class="settings-panel-card">
                    <div class="settings-panel-heading">
                        <h6><i class="fas fa-id-badge me-2"></i>Perfil</h6>
                        <p>Atualize seus dados de acesso e nome de exibicao.</p>
                    </div>
                    <form id="userSettingsForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <div class="mb-3">
                            <label class="form-label" for="userSettingsUsuario">Usuario</label>
                            <input
                                type="text"
                                class="form-control"
                                id="userSettingsUsuario"
                                name="usuario"
                                maxlength="80"
                                value="<?= htmlspecialchars($authUsername, ENT_QUOTES, 'UTF-8') ?>"
                                required
                            >
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="userSettingsNomeExibicao">Nome de exibicao</label>
                            <input
                                type="text"
                                class="form-control"
                                id="userSettingsNomeExibicao"
                                name="nome_exibicao"
                                maxlength="120"
                                value="<?= htmlspecialchars((string) (($authDisplayName !== $authUsername) ? $authDisplayName : ''), ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary" id="userSettingsSaveButton">
                                <span class="js-btn-label"><i class="fas fa-save me-1"></i>Salvar alteracoes</span>
                                <span class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
                            </button>
                        </div>
                    </form>
                </section>

                <section class="settings-panel-card mt-3">
                    <div class="settings-panel-heading">
                        <h6><i class="fas fa-circle-half-stroke me-2"></i>Aparencia</h6>
                        <p>O tema segue o navegador por padrao, mas voce pode fixar a exibicao deste navegador.</p>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="themePreferenceSelect">Tema do sistema</label>
                        <select class="form-select" id="themePreferenceSelect" aria-describedby="themePreferenceStatus">
                            <option value="auto">Automatico (usar tema do navegador)</option>
                            <option value="light">Claro</option>
                            <option value="dark">Escuro</option>
                            <option value="wellbeing">Bem-estar Digital</option>
                            <option value="neo-neon">Neo-Neon / Cyber-Synth</option>
                            <option value="sunwash">Sunwash</option>
                            <option value="thermal">Termico / Iridescente</option>
                            <option value="walnut">Walnut Retro</option>
                        </select>
                    </div>
                    <p class="theme-preference-status mb-0" id="themePreferenceStatus"></p>
                </section>

                <section class="settings-panel-card mt-3">
                    <div class="settings-panel-heading">
                        <h6><i class="fas fa-volume-up me-2"></i>Voz do sistema</h6>
                        <p>Escolha a voz usada nos avisos falados do sistema.</p>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="voiceFeedbackEnabled">
                        <label class="form-check-label" for="voiceFeedbackEnabled">Ativar avisos falados</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="voiceFeedbackSelect">Voz disponivel</label>
                        <select class="form-select" id="voiceFeedbackSelect"></select>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary" id="voiceFeedbackTestButton">
                            <i class="fas fa-play me-1"></i>Testar voz
                        </button>
                    </div>
                </section>

                <section class="settings-panel-card mt-3">
                    <div class="settings-panel-heading">
                        <h6><i class="fas fa-key me-2"></i>Senha</h6>
                        <p>Use o fluxo seguro de recuperacao para redefinir sua senha.</p>
                    </div>
                    <div class="d-grid">
                        <a
                            href="<?= htmlspecialchars(app_url('forgot-password.php?from=settings'), ENT_QUOTES, 'UTF-8') ?>"
                            class="btn btn-outline-secondary"
                        >
                            <i class="fas fa-unlock-alt me-1"></i>Mudar senha
                        </a>
                    </div>
                </section>
            </div>
        </div>
        <script src="<?= htmlspecialchars(app_url('assets/js/user_settings_panel.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
        <?php endif; ?>
