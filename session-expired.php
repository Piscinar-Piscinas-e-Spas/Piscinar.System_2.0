<?php
require_once __DIR__ . '/includes/auth.php';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light" data-theme-preference="auto" data-app-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    <title>Sessão encerrada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="<?= htmlspecialchars(app_url('assets/js/theme_preference.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <style>
      :root {
        --session-expired-bg:
          radial-gradient(circle at top, rgba(13, 110, 253, 0.12), transparent 34%),
          linear-gradient(180deg, #f5f8ff 0%, #eef3fb 100%);
        --session-expired-overlay: rgba(0, 0, 0, 0.35);
      }

      body.session-expired-page {
        min-height: 100vh;
        background: var(--session-expired-bg);
      }

      .session-expired-backdrop {
        display: block;
        background: var(--session-expired-overlay);
      }

      [data-bs-theme="dark"] {
        --session-expired-bg:
          radial-gradient(circle at top, rgba(13, 110, 253, 0.18), transparent 36%),
          linear-gradient(180deg, #07111f 0%, #0b1628 100%);
        --session-expired-overlay: rgba(0, 0, 0, 0.58);
      }

      [data-app-theme="wellbeing"] {
        --session-expired-bg:
          radial-gradient(circle at top, rgba(117, 142, 92, 0.14), transparent 34%),
          linear-gradient(180deg, #f7f1e8 0%, #eee3d4 100%);
      }

      [data-app-theme="sunwash"] {
        --session-expired-bg:
          radial-gradient(circle at top, rgba(195, 130, 120, 0.14), transparent 34%),
          linear-gradient(180deg, #fff5ef 0%, #f2e6df 100%);
      }

      [data-app-theme="walnut"] {
        --session-expired-bg:
          radial-gradient(circle at top, rgba(143, 95, 61, 0.14), transparent 34%),
          linear-gradient(180deg, #f7ecdf 0%, #ead8c6 100%);
      }

      [data-app-theme="neo-neon"] {
        --session-expired-bg:
          radial-gradient(circle at top, rgba(103, 244, 255, 0.14), transparent 34%),
          linear-gradient(180deg, #06080c 0%, #0b1118 100%);
      }

      [data-app-theme="thermal"] {
        --session-expired-bg:
          radial-gradient(circle at top, rgba(124, 247, 255, 0.14), transparent 34%),
          linear-gradient(180deg, #140621 0%, #260d3d 100%);
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
