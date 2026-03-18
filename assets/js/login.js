(function(){
  const form = document.getElementById('loginForm');
  const feedback = document.getElementById('loginFeedback');
  const btn = document.getElementById('btnEntrar');
  const spinner = btn.querySelector('.spinner-border');
  const btnLabel = btn.querySelector('.btn-label');
  const senha = document.getElementById('senha');
  const toggle = document.getElementById('toggleSenha');
  const defaultBtnLabel = btnLabel ? btnLabel.textContent : '';

  function setFeedback(type, msg){
    feedback.className = 'alert alert-' + type;
    feedback.textContent = msg;
    feedback.classList.remove('d-none');
  }

  function setLoading(on){
    btn.disabled = on;
    btn.classList.toggle('loading', on);
    spinner.classList.toggle('d-none', !on);
    if (btnLabel) {
      btnLabel.textContent = on ? 'Validando...' : defaultBtnLabel;
    }
  }

  async function waitMinimumLoadingTime(loadingStart){
    const elapsed = performance.now() - loadingStart;
    const remaining = Math.max(0, 1500 - elapsed);
    await new Promise(r => setTimeout(r, remaining));
  }

  toggle.addEventListener('click', function(){
    const show = senha.type === 'password';
    senha.type = show ? 'text' : 'password';
    toggle.innerHTML = show ? '<i class="fa-regular fa-eye-slash"></i>' : '<i class="fa-regular fa-eye"></i>';
  });

  form.addEventListener('submit', async function(e){
    e.preventDefault();
    if (btn.disabled) return;

    const loadingStart = performance.now();
    setLoading(true);
    feedback.classList.add('d-none');

    try {
      const data = new FormData(form);
      const res = await fetch(window.LOGIN_ENDPOINT, {
        method: 'POST',
        body: data,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const json = await res.json();

      if (!res.ok || !json.status) {
        setFeedback('danger', json.error || 'Falha ao autenticar usuário.');
        await waitMinimumLoadingTime(loadingStart);
        setLoading(false);
        return;
      }

      setFeedback('success', 'Login realizado com sucesso. Redirecionando...');
      await waitMinimumLoadingTime(loadingStart);
      window.location.href = json.redirect || window.LOGIN_REDIRECT_FALLBACK || '/index.php';
    } catch (err) {
      setFeedback('danger', 'Erro de comunicação. Tente novamente.');
      await waitMinimumLoadingTime(loadingStart);
      setLoading(false);
    }
  });
})();
