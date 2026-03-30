document.addEventListener('DOMContentLoaded', function () {
  var linhas = document.querySelectorAll('.inventory-table tbody tr');

  linhas.forEach(function (linha) {
    var input = linha.querySelector('.inventory-physical-input');
    var diffEl = linha.querySelector('.inventory-diff');

    if (!input || !diffEl) {
      return;
    }

    var recalcular = function () {
      var sistema = Number(input.dataset.system || 0);
      var fisico = Number(input.value || 0);
      var diferenca = fisico - sistema;

      diffEl.textContent = diferenca > 0 ? '+' + diferenca : String(diferenca);
      diffEl.classList.remove('text-danger', 'text-primary', 'text-muted');

      if (diferenca < 0) {
        diffEl.classList.add('text-danger');
      } else if (diferenca > 0) {
        diffEl.classList.add('text-primary');
      } else {
        diffEl.classList.add('text-muted');
      }
    };

    input.addEventListener('input', recalcular);
    recalcular();
  });
});
