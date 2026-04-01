<?php
require_once __DIR__ . '/includes/auth.php';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light" data-theme-preference="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
    (function () {
        var storageKey = 'piscinar.theme.preference';
        var preference = 'auto';

        try {
            var storedPreference = window.localStorage ? window.localStorage.getItem(storageKey) : null;
            if (storedPreference === 'light' || storedPreference === 'dark' || storedPreference === 'auto') {
                preference = storedPreference;
            }
        } catch (error) {
            preference = 'auto';
        }

        var resolvedTheme = preference === 'auto'
            ? ((window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light')
            : preference;

        document.documentElement.setAttribute('data-theme-preference', preference);
        document.documentElement.setAttribute('data-bs-theme', resolvedTheme);
        document.documentElement.style.colorScheme = resolvedTheme;
    })();
    </script>
    <title>Sessão encerrada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="<?= htmlspecialchars(app_url('assets/js/theme_preference.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <style>
      body.session-expired-page {
        min-height: 100vh;
        background:
          radial-gradient(circle at top, rgba(13, 110, 253, 0.12), transparent 34%),
          linear-gradient(180deg, #f5f8ff 0%, #eef3fb 100%);
      }

      .session-expired-backdrop {
        display: block;
        background: rgba(0, 0, 0, 0.35);
      }

      [data-bs-theme="dark"] body.session-expired-page {
        background:
          radial-gradient(circle at top, rgba(13, 110, 253, 0.18), transparent 36%),
          linear-gradient(180deg, #07111f 0%, #0b1628 100%);
      }

      [data-bs-theme="dark"] .session-expired-backdrop {
        background: rgba(0, 0, 0, 0.58);
      }
    </style>
</head>
<body class="session-expired-page d-flex align-items-center justify-content-center">
<div class="modal fade show session-expired-backdrop" tabindex="-1" role="dialog" aria-modal="true">
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
