<?php
require_once __DIR__ . '/includes/auth.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessão encerrada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh;">
<div class="modal fade show" style="display:block; background:rgba(0,0,0,.35);" tabindex="-1" role="dialog" aria-modal="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Sessão encerrada</h5>
      </div>
      <div class="modal-body">
        <p>Sua sessão expirou por inatividade (3h30). Você será redirecionado para o login.</p>
      </div>
      <div class="modal-footer">
        <a class="btn btn-primary" href="<?= htmlspecialchars(app_url('login.php?reason=session_expired'), ENT_QUOTES, 'UTF-8') ?>">Ir para login agora</a>
      </div>
    </div>
  </div>
</div>
<script>
setTimeout(function(){
  window.location.href = <?= json_encode(app_url('login.php?reason=session_expired')) ?>;
}, 2500);
</script>
</body>
</html>
