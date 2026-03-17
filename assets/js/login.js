(function(){
  const form = document.getElementById('loginForm');
  const feedback = document.getElementById('loginFeedback');
  const btn = document.getElementById('btnEntrar');
  const spinner = btn.querySelector('.spinner-border');
  const senha = document.getElementById('senha');
  const toggle = document.getElementById('toggleSenha');

  function setFeedback(type, msg){
    feedback.className = 'alert alert-' + type;
    feedback.textContent = msg;
    feedback.classList.remove('d-none');
  }

  function setLoading(on){
    btn.disabled = on;
    btn.classList.toggle('loading', on);
    spinner.classList.toggle('d-none', !on);
  }

  toggle.addEventListener('click', function(){
    const show = senha.type === 'password';
    senha.type = show ? 'text' : 'password';
    toggle.innerHTML = show ? '<i class="fa-regular fa-eye-slash"></i>' : '<i class="fa-regular fa-eye"></i>';
  });

  form.addEventListener('submit', async function(e){
    e.preventDefault();
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
        setLoading(false);
        return;
      }

      setFeedback('success', 'Login realizado com sucesso. Redirecionando...');
      window.location.href = json.redirect || '/index.php';
    } catch (err) {
      setFeedback('danger', 'Erro de comunicação. Tente novamente.');
      setLoading(false);
    }
  });
})();
