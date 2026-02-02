<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Piscinas</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            padding-top: 20px;
            background-color: #f8f9fa;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-weight: bold;
            color: #0d6efd !important;
        }

        #reader { width: 100%; max-width: 420px; margin: 18px auto; }
        .row { display: flex; gap: 10px; justify-content: center; margin: 10px 0 6px; flex-wrap: wrap; }
        .hint { font-size: 14px; opacity: 0.8; }
        #last { font-size: 16px; margin: 10px 0; text-align: center; }
        #last strong { font-family: monospace; }
        
        .buttonBar { padding: 10px 12px; border: 0; border-radius: 10px; cursor: pointer; }
        .buttonBar.secondary { background:#eee; }
        .buttonBar.primary { background:#111; color:#fff; }
    
        ul.ulBar { list-style: none; padding: 0; margin: 12px 0; max-width: 520px; margin-left: auto; margin-right: auto; }
        ul.ulBar, li {
        display: grid;
        grid-template-columns: 42px 1fr auto;
        gap: 10px;
        align-items: center;
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 12px;
        margin-bottom: 10px;
        background: #fff;
        }
        .code { font-family: monospace; font-size: 15px; word-break: break-all; }
        .tag { font-size: 12px; opacity: 0.75; }
        .icon { font-size: 20px; text-align: center; width: 42px; }
        .ok { color: #1a9c2c; } /* verde */
        .plusBtn {
        padding: 8px 10px;
        border-radius: 10px;
        background: #f5f5f5;
        font-weight: bold;
        min-width: 44px;
        }
        .plusBtn.saved { background: #e8ffe8; cursor: default; }



    </style>
    <script src="https://unpkg.com/html5-qrcode/html5-qrcode.min.js" type="text/javascript"></script>
</head>
<body>
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">
                    <i class="fas fa-swimming-pool me-2"></i>Piscinar System 2.0
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="../index.php"><i class="fas fa-home"></i> Início</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="listar.php"><i class="fas fa-box-open"></i> Produtos</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>