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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Anton&family=Fraunces:opsz,wght@9..144,600;9..144,700&family=JetBrains+Mono:wght@400;500;600;700&family=Lora:wght@500;600;700&family=Nunito:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&family=Space+Grotesk:wght@400;500;700&family=Syne:wght@700;800&display=swap">
    <script src="<?= htmlspecialchars(app_url('assets/js/theme_preference.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <style>
      :root {
        --session-expired-font-body: "Segoe UI", "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Helvetica Neue", Arial, sans-serif;
        --session-expired-font-heading: "Segoe UI", "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Helvetica Neue", Arial, sans-serif;
        --session-expired-bg:
          radial-gradient(circle at top, rgba(13, 110, 253, 0.12), transparent 34%),
          linear-gradient(180deg, #f5f8ff 0%, #eef3fb 100%);
        --session-expired-overlay: rgba(0, 0, 0, 0.35);
        --session-expired-modal-bg: rgba(255, 255, 255, 0.96);
        --session-expired-modal-border: rgba(13, 110, 253, 0.12);
        --session-expired-modal-shadow: 0 20px 48px rgba(18, 37, 68, 0.18);
        --session-expired-modal-radius: 22px;
        --session-expired-modal-filter: none;
        --session-expired-heading-color: #1f2f4b;
        --session-expired-text-color: #53627b;
        --session-expired-header-bg: linear-gradient(135deg, rgba(13, 110, 253, 0.08), rgba(18, 151, 244, 0.08));
        --session-expired-btn-bg: linear-gradient(135deg, #2e7df6, #1f68d8);
        --session-expired-btn-hover-bg: linear-gradient(135deg, #276fdf, #1a5fc8);
        --session-expired-btn-border: #2e7df6;
        --session-expired-btn-color: #ffffff;
        --session-expired-btn-hover-color: #ffffff;
        --session-expired-btn-shadow: none;
        --session-expired-btn-hover-shadow: none;
        --session-expired-title-shadow: none;
      }

      body.session-expired-page {
        min-height: 100vh;
        background: var(--session-expired-bg);
        font-family: var(--session-expired-font-body);
      }

      .session-expired-backdrop {
        display: block;
        background: var(--session-expired-overlay);
      }

      .session-expired-backdrop .modal-content {
        background: var(--session-expired-modal-bg);
        border-color: var(--session-expired-modal-border);
        box-shadow: var(--session-expired-modal-shadow);
        border-radius: var(--session-expired-modal-radius);
        color: var(--session-expired-text-color);
        backdrop-filter: var(--session-expired-modal-filter);
        -webkit-backdrop-filter: var(--session-expired-modal-filter);
      }

      .session-expired-backdrop .modal-header {
        background: var(--session-expired-header-bg);
        border-bottom-color: var(--session-expired-modal-border);
        border-top-left-radius: var(--session-expired-modal-radius);
        border-top-right-radius: var(--session-expired-modal-radius);
      }

      .session-expired-backdrop .modal-title {
        color: var(--session-expired-heading-color);
        font-family: var(--session-expired-font-heading);
        text-shadow: var(--session-expired-title-shadow);
      }

      .session-expired-backdrop .modal-body {
        color: var(--session-expired-text-color);
      }

      .session-expired-backdrop .btn-primary {
        background: var(--session-expired-btn-bg);
        border-color: var(--session-expired-btn-border);
        color: var(--session-expired-btn-color);
        box-shadow: var(--session-expired-btn-shadow);
      }

      .session-expired-backdrop .btn-primary:hover,
      .session-expired-backdrop .btn-primary:focus {
        background: var(--session-expired-btn-hover-bg);
        border-color: var(--session-expired-btn-border);
        color: var(--session-expired-btn-hover-color);
        box-shadow: var(--session-expired-btn-hover-shadow);
      }

      [data-bs-theme="dark"] {
        --session-expired-bg:
          radial-gradient(circle at top, rgba(13, 110, 253, 0.18), transparent 36%),
          linear-gradient(180deg, #07111f 0%, #0b1628 100%);
        --session-expired-overlay: rgba(0, 0, 0, 0.58);
        --session-expired-modal-bg: rgba(11, 22, 39, 0.96);
        --session-expired-modal-border: rgba(137, 168, 216, 0.18);
        --session-expired-modal-shadow: 0 22px 48px rgba(0, 0, 0, 0.42);
        --session-expired-heading-color: #eef4ff;
        --session-expired-text-color: #a6b9d7;
        --session-expired-header-bg: linear-gradient(135deg, rgba(84, 166, 255, 0.08), rgba(18, 151, 244, 0.08));
      }

      [data-app-theme="wellbeing"] {
        --session-expired-font-body: "Nunito", "Inter", "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, "Helvetica Neue", Arial, sans-serif;
        --session-expired-font-heading: "Lora", Georgia, "Times New Roman", serif;
        --session-expired-bg:
          radial-gradient(circle at top, oklch(61.80% 0.032 145.18 / 0.12), transparent 34%),
          linear-gradient(180deg, oklch(97.93% 0.003 84.56) 0%, oklch(95.80% 0.008 82.60) 100%);
        --session-expired-overlay: oklch(29% 0 90 / 0.24);
        --session-expired-modal-bg: oklch(98.80% 0.004 84.56 / 0.98);
        --session-expired-modal-border: oklch(89.60% 0.010 80.00);
        --session-expired-modal-shadow: 0 8px 24px oklch(29% 0 90 / 0.12);
        --session-expired-modal-radius: 12px;
        --session-expired-heading-color: oklch(34.85% 0 0);
        --session-expired-text-color: oklch(48.80% 0.006 80.00);
        --session-expired-header-bg: linear-gradient(135deg, oklch(96.70% 0.007 82.60), oklch(91.00% 0.017 76.10));
        --session-expired-btn-bg: linear-gradient(135deg, oklch(61.80% 0.032 145.18), oklch(57.50% 0.034 145.18));
        --session-expired-btn-hover-bg: linear-gradient(135deg, oklch(57.50% 0.034 145.18), oklch(53.80% 0.032 145.18));
        --session-expired-btn-border: oklch(61.80% 0.032 145.18);
        --session-expired-btn-color: oklch(98.80% 0.003 84.56);
      }

      [data-app-theme="sunwash"] {
        --session-expired-font-body: "Space Grotesk", "Inter", "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, "Helvetica Neue", Arial, sans-serif;
        --session-expired-font-heading: "Fraunces", "Lora", Georgia, "Times New Roman", serif;
        --session-expired-bg:
          radial-gradient(circle at 18% 22%, oklch(63.52% 0.029 114.16 / 0.10) 0 1px, transparent 1.5px),
          radial-gradient(circle at 68% 36%, oklch(76.20% 0.015 180.57 / 0.12) 0 1.2px, transparent 1.8px),
          radial-gradient(circle at 42% 78%, oklch(70.22% 0.101 39.64 / 0.10) 0 1px, transparent 1.6px),
          linear-gradient(180deg, oklch(97.26% 0.009 67.73) 0%, oklch(95.20% 0.015 58.00) 100%);
        --session-expired-modal-bg: oklch(98.50% 0.008 67.73 / 0.96);
        --session-expired-modal-border: oklch(84.20% 0.020 56.00);
        --session-expired-modal-shadow: 4px 4px 0 oklch(63.52% 0.029 114.16 / 0.56);
        --session-expired-modal-radius: 14px 10px 12px 8px;
        --session-expired-heading-color: oklch(43.55% 0.016 23.47);
        --session-expired-text-color: oklch(55.20% 0.013 32.00);
        --session-expired-header-bg: linear-gradient(135deg, oklch(95.00% 0.015 180.57), oklch(92.80% 0.028 48.00));
        --session-expired-btn-bg: linear-gradient(135deg, oklch(70.22% 0.101 39.64), oklch(76.20% 0.015 180.57));
        --session-expired-btn-hover-bg: linear-gradient(135deg, oklch(66.80% 0.090 39.64), oklch(72.50% 0.018 180.57));
        --session-expired-btn-border: oklch(63.52% 0.029 114.16);
        --session-expired-btn-color: oklch(98.00% 0.010 67.73);
        --session-expired-btn-hover-color: oklch(98.00% 0.010 67.73);
        --session-expired-btn-shadow: 4px 4px 0 oklch(63.52% 0.029 114.16);
        --session-expired-btn-hover-shadow: 2px 2px 0 oklch(63.52% 0.029 114.16 / 0.82);
      }

      [data-app-theme="walnut"] {
        --session-expired-font-body: "Space Grotesk", "Nunito", "Inter", "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, "Helvetica Neue", Arial, sans-serif;
        --session-expired-font-heading: "Playfair Display", "Fraunces", "Lora", Georgia, "Times New Roman", serif;
        --session-expired-bg:
          radial-gradient(circle at 14% 18%, oklch(65.32% 0.120 57.87 / 0.16) 0, transparent 24%),
          radial-gradient(circle at 82% 14%, oklch(35.13% 0.033 151.19 / 0.10) 0, transparent 28%),
          linear-gradient(180deg, oklch(94.80% 0.018 70.00) 0%, oklch(91.14% 0.027 69.28) 100%);
        --session-expired-overlay: oklch(20.00% 0.028 60.00 / 0.24);
        --session-expired-modal-bg: oklch(96.40% 0.012 72.00 / 0.96);
        --session-expired-modal-border: oklch(81.20% 0.030 64.00);
        --session-expired-modal-shadow: 0 20px 42px oklch(20.00% 0.028 60.00 / 0.14);
        --session-expired-modal-radius: 18px;
        --session-expired-heading-color: oklch(29.54% 0.039 60.56);
        --session-expired-text-color: oklch(45.80% 0.020 60.80);
        --session-expired-header-bg: linear-gradient(135deg, oklch(96.20% 0.012 72.00), oklch(93.40% 0.020 69.00));
        --session-expired-btn-bg: oklch(65.32% 0.120 57.87);
        --session-expired-btn-hover-bg: oklch(29.54% 0.039 60.56);
        --session-expired-btn-border: oklch(49.50% 0.074 55.00);
        --session-expired-btn-color: oklch(96.20% 0.012 75.00);
        --session-expired-btn-hover-color: oklch(96.20% 0.012 75.00);
        --session-expired-btn-shadow: 0 12px 24px oklch(20.00% 0.028 60.00 / 0.14);
        --session-expired-btn-hover-shadow: 0 16px 30px oklch(20.00% 0.028 60.00 / 0.18);
      }

      [data-app-theme="neo-neon"] {
        --session-expired-font-body: "Space Grotesk", "Inter", "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, "Helvetica Neue", Arial, sans-serif;
        --session-expired-font-heading: "JetBrains Mono", "Fira Code", "Courier New", monospace;
        --session-expired-bg:
          radial-gradient(circle at top, oklch(90.80% 0.128 188.42 / 0.14), transparent 34%),
          linear-gradient(180deg, oklch(15.51% 0.009 274.28) 0%, oklch(18.20% 0.010 271.00) 100%);
        --session-expired-overlay: oklch(6% 0.004 270 / 0.56);
        --session-expired-modal-bg: oklch(17.80% 0.010 270.00 / 0.96);
        --session-expired-modal-border: oklch(90.80% 0.128 188.42 / 0.18);
        --session-expired-modal-shadow: 0 0 0 1px oklch(87.18% 0.255 147.64 / 0.10), 0 22px 48px oklch(6% 0.004 270 / 0.64);
        --session-expired-modal-radius: 4px;
        --session-expired-heading-color: oklch(90.80% 0.128 188.42);
        --session-expired-text-color: oklch(82.61% 0.002 247.84);
        --session-expired-header-bg: linear-gradient(135deg, oklch(18.60% 0.012 270.00), oklch(21.20% 0.018 258.00));
        --session-expired-btn-bg: transparent;
        --session-expired-btn-hover-bg: oklch(90.80% 0.128 188.42);
        --session-expired-btn-border: oklch(87.18% 0.255 147.64 / 0.48);
        --session-expired-btn-color: oklch(90.80% 0.128 188.42);
        --session-expired-btn-hover-color: oklch(15.51% 0.009 274.28);
        --session-expired-btn-shadow: inset 0 0 0 1px oklch(87.18% 0.255 147.64 / 0.12), 0 0 12px oklch(87.18% 0.255 147.64 / 0.10);
        --session-expired-btn-hover-shadow: 0 0 14px oklch(87.18% 0.255 147.64 / 0.22), 0 0 28px oklch(70.17% 0.322 328.36 / 0.18);
        --session-expired-title-shadow: 0 0 10px oklch(87.18% 0.255 147.64 / 0.72), 0 0 20px oklch(87.18% 0.255 147.64 / 0.48);
      }

      [data-app-theme="thermal"] {
        --session-expired-font-body: "Syne", "Space Grotesk", "Inter", "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, "Helvetica Neue", Arial, sans-serif;
        --session-expired-font-heading: "Anton", "Syne", "Impact", "Arial Black", sans-serif;
        --session-expired-bg:
          radial-gradient(circle at 18% 22%, oklch(68.60% 0.210 41.14 / 0.42) 0, transparent 24%),
          radial-gradient(circle at 82% 16%, oklch(58.04% 0.232 8.66 / 0.46) 0, transparent 30%),
          linear-gradient(140deg, oklch(14.74% 0.077 303.51) 0%, oklch(20.60% 0.112 318.00) 46%, oklch(28.40% 0.166 18.00) 100%);
        --session-expired-overlay: oklch(4% 0.010 300 / 0.36);
        --session-expired-modal-bg: oklch(100% 0 180 / 0.10);
        --session-expired-modal-border: oklch(100% 0 0 / 0.16);
        --session-expired-modal-shadow: 0 18px 36px oklch(4% 0.010 300 / 0.28);
        --session-expired-modal-filter: blur(8px);
        --session-expired-heading-color: oklch(100% 0 0);
        --session-expired-text-color: oklch(96% 0.010 0 / 0.82);
        --session-expired-header-bg: linear-gradient(135deg, oklch(58.04% 0.232 8.66 / 0.30), oklch(68.60% 0.210 41.14 / 0.32));
        --session-expired-btn-bg: linear-gradient(135deg, oklch(68.60% 0.210 41.14 / 0.84), oklch(58.04% 0.232 8.66 / 0.86));
        --session-expired-btn-hover-bg: linear-gradient(135deg, oklch(70.20% 0.214 41.14 / 0.90), oklch(60.20% 0.236 8.66 / 0.92));
        --session-expired-btn-border: oklch(100% 0 0 / 0.18);
        --session-expired-btn-color: oklch(100% 0 0);
        --session-expired-btn-hover-color: oklch(100% 0 0);
        --session-expired-btn-shadow: 0 12px 26px oklch(58.04% 0.232 8.66 / 0.14);
        --session-expired-btn-hover-shadow: 0 16px 30px oklch(58.04% 0.232 8.66 / 0.16);
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
