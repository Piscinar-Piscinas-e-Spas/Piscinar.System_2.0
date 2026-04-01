(function () {
  'use strict';

  var panel = document.getElementById('userSettingsPanel');
  if (!panel) {
    return;
  }

  var feedback = document.getElementById('userSettingsFeedback');
  var form = document.getElementById('userSettingsForm');
  var saveButton = document.getElementById('userSettingsSaveButton');
  var saveSpinner = saveButton ? saveButton.querySelector('.spinner-border') : null;
  var saveLabel = saveButton ? saveButton.querySelector('.js-btn-label') : null;
  var usernameInput = document.getElementById('userSettingsUsuario');
  var displayNameInput = document.getElementById('userSettingsNomeExibicao');
  var voiceEnabledInput = document.getElementById('voiceFeedbackEnabled');
  var voiceSelect = document.getElementById('voiceFeedbackSelect');
  var voiceTestButton = document.getElementById('voiceFeedbackTestButton');
  var userShow = document.getElementById('userShow');
  var endpoint = panel.getAttribute('data-profile-endpoint') || '';

  function setFeedback(type, message) {
    feedback.className = 'alert alert-' + type;
    feedback.textContent = message;
    feedback.classList.remove('d-none');
  }

  function clearFeedback() {
    feedback.className = 'alert d-none';
    feedback.textContent = '';
  }

  function setSaving(loading) {
    if (!saveButton) {
      return;
    }

    saveButton.disabled = loading;
    if (saveSpinner) {
      saveSpinner.classList.toggle('d-none', !loading);
    }
    if (saveLabel) {
      saveLabel.innerHTML = loading
        ? 'Salvando...'
        : '<i class="fas fa-save me-1"></i>Salvar alteracoes';
    }
  }

  function updateHeaderName(displayName) {
    if (!userShow) {
      return;
    }

    var label = userShow.querySelector('.nav-link');
    if (!label) {
      return;
    }

    label.innerHTML = '<i class="fas fa-user-circle"></i> ' + displayName;
  }

  function refreshVoiceOptions() {
    if (!voiceSelect || !window.AppSpeechFeedback) {
      return;
    }

    var voices = window.AppSpeechFeedback.listVoices();
    var preferredVoice = window.AppSpeechFeedback.getVoicePreference();
    var isEnabled = window.AppSpeechFeedback.isEnabled();

    voiceEnabledInput.checked = isEnabled;
    voiceSelect.disabled = !isEnabled;
    voiceTestButton.disabled = !isEnabled;

    voiceSelect.innerHTML = '';
    var automaticOption = document.createElement('option');
    automaticOption.value = '';
    automaticOption.textContent = 'Automatica (preferir portugues)';
    voiceSelect.appendChild(automaticOption);

    voices.forEach(function (voice) {
      var option = document.createElement('option');
      option.value = voice.voiceURI || '';
      var sourceLabel = '';
      if (voice.source === 'system') {
        sourceLabel = ' - narracao do sistema';
      } else if (voice.source === 'browser') {
        sourceLabel = ' - navegador';
      }
      option.textContent = voice.name + ' (' + voice.lang + ')' + (voice.default ? ' - padrao' : '') + sourceLabel;
      voiceSelect.appendChild(option);
    });

    voiceSelect.value = preferredVoice;
  }

  voiceEnabledInput.addEventListener('change', function () {
    if (!window.AppSpeechFeedback) {
      return;
    }

    window.AppSpeechFeedback.setEnabled(voiceEnabledInput.checked);
    refreshVoiceOptions();
    clearFeedback();
  });

  voiceSelect.addEventListener('change', function () {
    if (!window.AppSpeechFeedback) {
      return;
    }

    window.AppSpeechFeedback.setVoicePreference(voiceSelect.value);
    clearFeedback();
  });

  voiceTestButton.addEventListener('click', function () {
    if (!window.AppSpeechFeedback) {
      return;
    }

    window.AppSpeechFeedback.speakText('Esta e a voz configurada para o sistema.');
  });

  if (window.speechSynthesis && typeof window.speechSynthesis.addEventListener === 'function') {
    window.speechSynthesis.addEventListener('voiceschanged', refreshVoiceOptions);
  }

  panel.addEventListener('show.bs.offcanvas', function () {
    refreshVoiceOptions();
    clearFeedback();
  });

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    clearFeedback();

    if (!usernameInput.value.trim()) {
      setFeedback('warning', 'Informe o usuario para salvar as alteracoes.');
      usernameInput.focus();
      return;
    }

    setSaving(true);
    try {
      var response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          csrf_token: form.elements.csrf_token.value,
          usuario: usernameInput.value.trim(),
          nome_exibicao: displayNameInput.value.trim()
        })
      });

      var payload = await response.json().catch(function () { return {}; });
      if (!response.ok || !payload.status) {
        throw new Error(payload && payload.mensagem ? payload.mensagem : 'Nao foi possivel atualizar o cadastro.');
      }

      var displayName = payload.usuario && payload.usuario.nome_exibicao
        ? payload.usuario.nome_exibicao
        : usernameInput.value.trim();
      usernameInput.value = payload.usuario && payload.usuario.usuario ? payload.usuario.usuario : usernameInput.value.trim();
      displayNameInput.value = payload.usuario && payload.usuario.nome_exibicao_editavel
        ? payload.usuario.nome_exibicao_editavel
        : '';
      updateHeaderName(displayName);
      setFeedback('success', payload.mensagem || 'Cadastro atualizado com sucesso.');
    } catch (error) {
      setFeedback('danger', error.message || 'Erro ao atualizar cadastro.');
    } finally {
      setSaving(false);
    }
  });

  refreshVoiceOptions();
})();
