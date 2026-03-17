(function () {
  var errorMap = {
    usuario_invalido: 'Não encontramos esse usuário. Verifique o login e tente novamente.',
    senha_invalida: 'A senha informada está incorreta. Revise os caracteres e tente de novo.',
    usuario_inativo: 'Seu acesso está inativo. Entre em contato com o administrador.',
    campos_obrigatorios: 'Preencha usuário e senha para continuar.'
  };

  function onReady(callback) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback);
      return;
    }
    callback();
  }

  onReady(function () {
    var form = document.getElementById('loginForm');
    if (!form) {
      return;
    }

    var feedback = document.getElementById('loginFeedback');
    var submitButton = document.getElementById('submitLogin');
    var spinner = document.getElementById('submitSpinner');
    var submitLabel = document.getElementById('submitLabel');
    var passwordInput = document.getElementById('senha');
    var togglePassword = document.getElementById('toggleSenha');

    function setFeedback(type, message) {
      if (!feedback) {
        return;
      }

      feedback.classList.remove('alert-danger', 'alert-success', 'is-visible');
      feedback.hidden = false;
      feedback.textContent = message;
      feedback.classList.add(type === 'success' ? 'alert-success' : 'alert-danger', 'is-visible');
    }

    function setLoading(isLoading) {
      if (!submitButton || !spinner || !submitLabel) {
        return;
      }

      submitButton.disabled = isLoading;
      spinner.classList.toggle('d-none', !isLoading);
      submitLabel.textContent = isLoading ? 'Entrando...' : 'Entrar';
    }

    if (togglePassword && passwordInput) {
      togglePassword.addEventListener('click', function () {
        var showPassword = passwordInput.type === 'password';
        passwordInput.type = showPassword ? 'text' : 'password';
        togglePassword.setAttribute('aria-pressed', showPassword ? 'true' : 'false');
        togglePassword.setAttribute('aria-label', showPassword ? 'Ocultar senha' : 'Mostrar senha');
        togglePassword.innerHTML = showPassword
          ? '<i class="fa-solid fa-eye-slash" aria-hidden="true"></i>'
          : '<i class="fa-solid fa-eye" aria-hidden="true"></i>';
      });
    }

    if (typeof window.fetch !== 'function' || typeof window.FormData !== 'function') {
      return;
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      setLoading(true);

      var formData = new FormData(form);

      fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
        .then(function (response) {
          return response
            .json()
            .catch(function () {
              return {
                status: false,
                mensagem: 'Não foi possível processar a resposta do servidor.'
              };
            })
            .then(function (payload) {
              return {
                ok: response.ok,
                payload: payload
              };
            });
        })
        .then(function (result) {
          if (result.ok && result.payload.status) {
            setFeedback('success', result.payload.mensagem || 'Login realizado com sucesso.');

            var redirect = result.payload.redirect || formData.get('next') || '/';
            window.setTimeout(function () {
              window.location.href = redirect;
            }, 700);
            return;
          }

          var code = result.payload.error_code || '';
          var friendlyMessage = errorMap[code] || result.payload.mensagem || 'Não foi possível entrar. Tente novamente.';
          setFeedback('error', friendlyMessage);
          setLoading(false);
        })
        .catch(function () {
          setLoading(false);
          form.submit();
        });
    });
  });
})();
