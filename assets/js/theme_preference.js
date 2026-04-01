(function (global) {
  'use strict';

  var storageKey = 'piscinar.theme.preference';
  var defaultPreference = 'auto';
  var docEl = global.document ? global.document.documentElement : null;
  var mediaQuery = typeof global.matchMedia === 'function'
    ? global.matchMedia('(prefers-color-scheme: dark)')
    : null;
  var themeRegistry = {
    auto: {
      label: 'Automatico',
      bootstrapTheme: null
    },
    light: {
      label: 'Claro',
      bootstrapTheme: 'light'
    },
    dark: {
      label: 'Escuro',
      bootstrapTheme: 'dark'
    },
    wellbeing: {
      label: 'Bem-estar Digital',
      bootstrapTheme: 'light'
    },
    'neo-neon': {
      label: 'Neo-Neon / Cyber-Synth',
      bootstrapTheme: 'dark'
    },
    sunwash: {
      label: 'Sunwash',
      bootstrapTheme: 'light'
    },
    thermal: {
      label: 'Termico / Iridescente',
      bootstrapTheme: 'dark'
    },
    walnut: {
      label: 'Walnut Retro',
      bootstrapTheme: 'light'
    }
  };

  function normalizePreference(value) {
    return Object.prototype.hasOwnProperty.call(themeRegistry, value)
      ? value
      : defaultPreference;
  }

  function getThemeMeta(themeName) {
    var normalized = normalizePreference(themeName);
    return themeRegistry[normalized] || themeRegistry[defaultPreference];
  }

  function readStoredPreference() {
    try {
      return normalizePreference(global.localStorage ? global.localStorage.getItem(storageKey) : null);
    } catch (error) {
      return defaultPreference;
    }
  }

  function persistPreference(value) {
    try {
      if (global.localStorage) {
        global.localStorage.setItem(storageKey, normalizePreference(value));
      }
    } catch (error) {
      // Ignore localStorage failures and keep the active theme in memory.
    }
  }

  function resolveAppTheme(preference) {
    var normalized = normalizePreference(preference);
    if (normalized === 'auto') {
      return mediaQuery && mediaQuery.matches ? 'dark' : 'light';
    }

    return normalized;
  }

  function resolveBootstrapTheme(preference) {
    var resolvedAppTheme = resolveAppTheme(preference);
    return getThemeMeta(resolvedAppTheme).bootstrapTheme || 'light';
  }

  function getThemeLabel(themeName) {
    return getThemeMeta(themeName).label;
  }

  function dispatchThemeChange(detail) {
    if (!docEl || typeof global.dispatchEvent !== 'function' || typeof global.CustomEvent !== 'function') {
      return;
    }

    global.dispatchEvent(new global.CustomEvent('app-theme-change', { detail: detail }));
  }

  function applyTheme(preference) {
    var normalized = normalizePreference(typeof preference === 'string' ? preference : readStoredPreference());
    var resolvedAppTheme = resolveAppTheme(normalized);
    var resolvedBootstrapTheme = resolveBootstrapTheme(normalized);

    if (!docEl) {
      return {
        preference: normalized,
        appTheme: resolvedAppTheme,
        bootstrapTheme: resolvedBootstrapTheme
      };
    }

    docEl.setAttribute('data-theme-preference', normalized);
    docEl.setAttribute('data-app-theme', resolvedAppTheme);
    docEl.setAttribute('data-bs-theme', resolvedBootstrapTheme);
    docEl.style.colorScheme = resolvedBootstrapTheme;

    dispatchThemeChange({
      preference: normalized,
      appTheme: resolvedAppTheme,
      bootstrapTheme: resolvedBootstrapTheme,
      label: getThemeLabel(resolvedAppTheme)
    });

    return {
      preference: normalized,
      appTheme: resolvedAppTheme,
      bootstrapTheme: resolvedBootstrapTheme
    };
  }

  function setPreference(preference) {
    var normalized = normalizePreference(preference);
    persistPreference(normalized);
    applyTheme(normalized);
    return normalized;
  }

  function getPreference() {
    return readStoredPreference();
  }

  function getResolvedTheme() {
    return resolveBootstrapTheme(getPreference());
  }

  function getResolvedAppTheme() {
    return resolveAppTheme(getPreference());
  }

  function listThemes() {
    return Object.keys(themeRegistry).map(function (themeKey) {
      return {
        key: themeKey,
        label: getThemeMeta(themeKey).label,
        bootstrapTheme: getThemeMeta(themeKey).bootstrapTheme
      };
    });
  }

  function handleSystemThemeChange() {
    if (getPreference() === 'auto') {
      applyTheme('auto');
    }
  }

  if (mediaQuery) {
    if (typeof mediaQuery.addEventListener === 'function') {
      mediaQuery.addEventListener('change', handleSystemThemeChange);
    } else if (typeof mediaQuery.addListener === 'function') {
      mediaQuery.addListener(handleSystemThemeChange);
    }
  }

  if (typeof global.addEventListener === 'function') {
    global.addEventListener('storage', function (event) {
      if (event.key === storageKey) {
        applyTheme();
      }
    });
  }

  global.AppThemePreference = {
    storageKey: storageKey,
    getPreference: getPreference,
    getResolvedTheme: getResolvedTheme,
    getResolvedAppTheme: getResolvedAppTheme,
    getThemeLabel: getThemeLabel,
    getThemeMeta: getThemeMeta,
    listThemes: listThemes,
    resolveTheme: resolveBootstrapTheme,
    resolveAppTheme: resolveAppTheme,
    applyTheme: applyTheme,
    setPreference: setPreference
  };

  applyTheme();
})(window);
