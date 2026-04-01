(function (global) {
  'use strict';

  var storageKey = 'piscinar.theme.preference';
  var defaultPreference = 'auto';
  var docEl = global.document ? global.document.documentElement : null;
  var mediaQuery = typeof global.matchMedia === 'function'
    ? global.matchMedia('(prefers-color-scheme: dark)')
    : null;

  function normalizePreference(value) {
    if (value === 'light' || value === 'dark' || value === 'auto') {
      return value;
    }

    return defaultPreference;
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

  function resolveTheme(preference) {
    var normalized = normalizePreference(preference);
    if (normalized === 'auto') {
      return mediaQuery && mediaQuery.matches ? 'dark' : 'light';
    }

    return normalized;
  }

  function dispatchThemeChange(detail) {
    if (!docEl || typeof global.dispatchEvent !== 'function' || typeof global.CustomEvent !== 'function') {
      return;
    }

    global.dispatchEvent(new global.CustomEvent('app-theme-change', { detail: detail }));
  }

  function applyTheme(preference) {
    var normalized = normalizePreference(typeof preference === 'string' ? preference : readStoredPreference());
    var resolvedTheme = resolveTheme(normalized);

    if (!docEl) {
      return resolvedTheme;
    }

    docEl.setAttribute('data-theme-preference', normalized);
    docEl.setAttribute('data-bs-theme', resolvedTheme);
    docEl.style.colorScheme = resolvedTheme;

    dispatchThemeChange({
      preference: normalized,
      theme: resolvedTheme
    });

    return resolvedTheme;
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
    return resolveTheme(getPreference());
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
    resolveTheme: resolveTheme,
    applyTheme: applyTheme,
    setPreference: setPreference
  };

  applyTheme();
})(window);
