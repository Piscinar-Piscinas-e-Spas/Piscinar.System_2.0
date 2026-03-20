<?php
    $currentRoute = parse_url($_SERVER['REQUEST_URI'] ?? ($_SERVER['PHP_SELF'] ?? ''), PHP_URL_PATH) ?? '';
    $currentRoute = rtrim($currentRoute, '/');
    $currentRoute = $currentRoute === '' ? '/' : $currentRoute;

    require_once dirname(__DIR__) . '/config.php';

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
    $styleActive = strpos($currentRoute, '/assets/css/') !== false ? ' active' : '';
?>

<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Piscinas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" class="<?php echo $styleActive; ?>" href="<?php echo app_url('assets/css/styles.css'); ?>">
    <link rel="icon" type="image/svg+xml" href="<?php echo app_url('assets/img/favicon.svg'); ?>">
    <link rel="apple-touch-icon" href="<?php echo app_url('assets/img/piscinar-logo.svg'); ?>">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</head>
<body>
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
                    <ul class="navbar-nav w-75 justify-content-evenly">
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
                    </ul>

                    <ul class="navbar-nav ms-auto">
                        <?php if (function_exists('is_authenticated') && is_authenticated()): ?>
                        <li class="nav-item">
                            <span class="nav-link text-muted">
                                <i class="fas fa-user-circle"></i>
                                <?= htmlspecialchars((string) ((auth_user()['nome'] ?? auth_user()['usuario'] ?? 'Usuario')), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="<?= htmlspecialchars(app_url('logout.php'), ENT_QUOTES, 'UTF-8') ?>">
                                <i class="fas fa-sign-out-alt"></i> Sair
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
