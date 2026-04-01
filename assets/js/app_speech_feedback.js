(function (global) {
  'use strict';

  var storageKey = 'piscinar.voiceFeedback.enabled';
  var voiceStorageKey = 'piscinar.voiceFeedback.voiceURI';
  var dedupeWindowMs = 1200;
  var defaultLang = 'pt-BR';
  var voiceWarmupDelays = [0, 250, 1000, 2500];
  var lastSpoken = {
    text: '',
    at: 0
  };

  var catalog = {
    'sales.customer_required': 'Selecione um cliente cadastrado ou salve rapidamente.',
    'sales.product_required': 'Selecione um produto antes de adicionar.',
    'sales.vendor_required': 'Selecione um vendedor para continuar.',
    'sales.items_required': 'Adicione pelo menos um item para concluir a venda.',
    'sales.save_error': 'Nao foi possivel salvar a venda agora. Confira os dados e tente novamente.',
    'services.customer_required': 'Selecione um cliente cadastrado ou salve rapidamente.',
    'services.product_required': 'Selecione um produto antes de adicionar.',
    'services.micro_description_required': 'Faltou a descricao do micro-servico.',
    'services.vendor_required': 'Selecione um vendedor para continuar.',
    'services.items_required': 'Adicione pelo menos um item para salvar o servico.',
    'services.save_error': 'Nao foi possivel salvar o servico agora. Confira os dados e tente novamente.',
    'purchase.supplier_required': 'Selecione ou cadastre um fornecedor para continuar.',
    'purchase.product_name_missing': 'Faltou o nome do produto.',
    'purchase.invoice_number_required': 'Informe o numero da nota para continuar.',
    'purchase.items_required': 'Adicione pelo menos um item na nota.',
    'purchase.installments_required': 'Informe as parcelas antes de salvar a entrada.',
    'purchase.installments_mismatch': 'Confira as parcelas. O total precisa fechar com a nota.',
    'purchase.save_error': 'Nao foi possivel salvar a entrada agora. Confira os dados e tente novamente.'
  };

  function hasSupport() {
    return typeof global !== 'undefined'
      && 'speechSynthesis' in global
      && typeof global.SpeechSynthesisUtterance === 'function';
  }

  function readEnabledSetting() {
    try {
      var stored = global.localStorage ? global.localStorage.getItem(storageKey) : null;
      if (stored === null) {
        return true;
      }

      return stored !== 'false';
    } catch (error) {
      return true;
    }
  }

  var enabled = readEnabledSetting();

  function persistEnabled(value) {
    enabled = value !== false;

    try {
      if (global.localStorage) {
        global.localStorage.setItem(storageKey, String(enabled));
      }
    } catch (error) {
      // Ignore localStorage failures and keep the in-memory flag.
    }

    if (!enabled && hasSupport()) {
      global.speechSynthesis.cancel();
    }

    return enabled;
  }

  function normalizeText(value) {
    return String(value || '').trim();
  }

  function getSavedVoicePreference() {
    try {
      return global.localStorage ? normalizeText(global.localStorage.getItem(voiceStorageKey)) : '';
    } catch (error) {
      return '';
    }
  }

  function setVoicePreference(voiceURI) {
    var normalized = normalizeText(voiceURI);
    try {
      if (global.localStorage) {
        if (normalized === '') {
          global.localStorage.removeItem(voiceStorageKey);
        } else {
          global.localStorage.setItem(voiceStorageKey, normalized);
        }
      }
    } catch (error) {
      // Ignore storage errors and keep runtime behavior.
    }

    return normalized;
  }

  function listVoices() {
    if (!hasSupport()) {
      return [];
    }

    return global.speechSynthesis.getVoices().map(function (voice) {
      return {
        voiceURI: voice.voiceURI,
        name: voice.name,
        lang: voice.lang,
        default: !!voice.default,
        source: detectVoiceSource(voice)
      };
    });
  }

  function detectVoiceSource(voice) {
    var signature = (String(voice.name || '') + ' ' + String(voice.voiceURI || '')).toLowerCase();
    if (signature.indexOf('microsoft') !== -1 || signature.indexOf('windows') !== -1 || signature.indexOf('desktop') !== -1) {
      return 'system';
    }
    if (signature.indexOf('google') !== -1 || signature.indexOf('chrome') !== -1) {
      return 'browser';
    }
    return 'available';
  }

  function chooseVoice() {
    if (!hasSupport()) {
      return null;
    }

    var voices = global.speechSynthesis.getVoices();
    if (!Array.isArray(voices) || voices.length === 0) {
      return null;
    }

    var preferredVoiceURI = getSavedVoicePreference();
    if (preferredVoiceURI !== '') {
      for (var preferredIndex = 0; preferredIndex < voices.length; preferredIndex += 1) {
        if (String(voices[preferredIndex].voiceURI || '') === preferredVoiceURI) {
          return voices[preferredIndex];
        }
      }
    }

    for (var i = 0; i < voices.length; i += 1) {
      if (String(voices[i].lang || '').toLowerCase().indexOf('pt-br') === 0) {
        return voices[i];
      }
    }

    for (var j = 0; j < voices.length; j += 1) {
      if (String(voices[j].lang || '').toLowerCase().indexOf('pt') === 0) {
        return voices[j];
      }
    }

    return voices[0] || null;
  }

  function shouldSkip(text) {
    var now = Date.now();
    if (text === lastSpoken.text && now - lastSpoken.at < dedupeWindowMs) {
      return true;
    }

    lastSpoken.text = text;
    lastSpoken.at = now;
    return false;
  }

  function speakText(text, options) {
    var finalText = normalizeText(text);
    if (!enabled || !hasSupport() || finalText === '') {
      return false;
    }

    if (shouldSkip(finalText)) {
      return false;
    }

    var utterance = new global.SpeechSynthesisUtterance(finalText);
    var voice = chooseVoice();
    if (voice) {
      utterance.voice = voice;
      utterance.lang = voice.lang || defaultLang;
    } else {
      utterance.lang = defaultLang;
    }

    utterance.rate = options && typeof options.rate === 'number' ? options.rate : 1;
    utterance.pitch = options && typeof options.pitch === 'number' ? options.pitch : 1;
    utterance.volume = options && typeof options.volume === 'number' ? options.volume : 1;

    global.speechSynthesis.cancel();
    global.speechSynthesis.speak(utterance);
    return true;
  }

  function speakByCode(code, options) {
    var script = catalog[code];
    if (!script) {
      return false;
    }

    return speakText(script, options);
  }

  function speakFeedback(payload) {
    var data = payload || {};
    var type = normalizeText(data.type).toLowerCase();
    if (type !== 'warning' && type !== 'danger') {
      return false;
    }

    if (data.code) {
      return speakByCode(data.code, data.options);
    }

    return speakText(data.text, data.options);
  }

  if (hasSupport()) {
    voiceWarmupDelays.forEach(function (delay) {
      global.setTimeout(function () {
        global.speechSynthesis.getVoices();
      }, delay);
    });
    if (typeof global.speechSynthesis.addEventListener === 'function') {
      global.speechSynthesis.addEventListener('voiceschanged', function () {
        global.speechSynthesis.getVoices();
      });
    }
  }

  global.AppSpeechFeedback = {
    isSupported: hasSupport,
    isEnabled: function () {
      return enabled;
    },
    setEnabled: persistEnabled,
    getVoicePreference: getSavedVoicePreference,
    setVoicePreference: setVoicePreference,
    listVoices: listVoices,
    speakByCode: speakByCode,
    speakText: speakText,
    speakFeedback: speakFeedback,
    catalog: catalog
  };
})(window);
