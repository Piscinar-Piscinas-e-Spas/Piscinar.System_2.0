document.addEventListener('DOMContentLoaded', function () {
  var produtos = Array.isArray(window.LOGISTICA_PRODUTOS) ? window.LOGISTICA_PRODUTOS : [];
  var sugestoes = Array.isArray(window.LOGISTICA_SUGESTOES) ? window.LOGISTICA_SUGESTOES : [];
  var form = document.getElementById('transferenciaForm');
  var origemSelect = document.getElementById('origemSelect');
  var destinoSelect = document.getElementById('destinoSelect');
  var itemsContainer = document.getElementById('transferItems');
  var btnAdicionarItem = document.getElementById('btnAdicionarItem');
  var btnSugerirReposicao = document.getElementById('btnSugerirReposicao');
  var alertBox = document.getElementById('transferAlert');
  var nextIndex = 0;

  var produtosMap = new Map(produtos.map(function (produto) {
    return [String(produto.id), produto];
  }));

  function nomeLocal(coluna) {
    return coluna === 'qtdEstoque' ? 'Estoque Auxiliar' : 'Loja';
  }

  function saldoDisponivel(produto) {
    if (!produto) {
      return 0;
    }

    return Number(produto[origemSelect.value] || 0);
  }

  function mostrarAlerta(texto) {
    alertBox.textContent = texto;
    alertBox.classList.remove('d-none');
  }

  function limparAlerta() {
    alertBox.textContent = '';
    alertBox.classList.add('d-none');
  }

  function garantirLocaisDiferentes(origemAlterada) {
    if (origemSelect.value !== destinoSelect.value) {
      return;
    }

    if (origemAlterada) {
      destinoSelect.value = origemSelect.value === 'qtdEstoque' ? 'qtdLoja' : 'qtdEstoque';
    } else {
      origemSelect.value = destinoSelect.value === 'qtdEstoque' ? 'qtdLoja' : 'qtdEstoque';
    }
  }

  function atualizarSaldoRow(row) {
    var select = row.querySelector('.transfer-product-select');
    var saldoEl = row.querySelector('.transfer-saldo');
    var inputQtd = row.querySelector('.transfer-qty-input');
    var produto = produtosMap.get(String(select.value));
    var saldo = saldoDisponivel(produto);

    saldoEl.textContent = 'Saldo disponivel em ' + nomeLocal(origemSelect.value) + ': ' + saldo;
    inputQtd.max = String(saldo);

    if (Number(inputQtd.value || 0) > saldo) {
      inputQtd.classList.add('is-invalid');
      mostrarAlerta('Saldo Insuficiente no ' + nomeLocal(origemSelect.value) + '.');
    } else {
      inputQtd.classList.remove('is-invalid');
    }
  }

  function atualizarTodosOsSaldos() {
    limparAlerta();
    itemsContainer.querySelectorAll('.transfer-item-row').forEach(function (row) {
      atualizarSaldoRow(row);
    });
  }

  function opcoesProdutos() {
    function escapeHtml(value) {
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    return produtos.map(function (produto) {
      var grupo = produto.grupo ? ' [' + escapeHtml(produto.grupo) + ']' : '';
      return '<option value="' + escapeHtml(produto.id) + '">' + escapeHtml(produto.nome) + grupo + '</option>';
    }).join('');
  }

  function inicializarSelect2(element) {
    if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.select2) {
      return;
    }

    window.jQuery(element).select2({
      width: '100%',
      placeholder: 'Buscar produto...',
      allowClear: true
    });

    window.jQuery(element).on('change', function () {
      atualizarSaldoRow(element.closest('.transfer-item-row'));
    });
  }

  function criarLinha(item) {
    var index = nextIndex++;
    var row = document.createElement('div');
    row.className = 'transfer-item-row border rounded-3 p-3';
    row.innerHTML = [
      '<div class="row g-3 align-items-end">',
      '  <div class="col-lg-6">',
      '    <label class="form-label">Produto</label>',
      '    <select class="form-select transfer-product-select" name="itens[' + index + '][produto_id]" required>',
      '      <option value=""></option>',
             opcoesProdutos(),
      '    </select>',
      '  </div>',
      '  <div class="col-lg-3">',
      '    <label class="form-label">Quantidade</label>',
      '    <input type="number" min="1" step="1" class="form-control transfer-qty-input" name="itens[' + index + '][quantidade]" required>',
      '    <div class="invalid-feedback">Saldo indisponivel para este local.</div>',
      '  </div>',
      '  <div class="col-lg-2">',
      '    <div class="transfer-saldo small text-muted mb-2">Selecione um produto</div>',
      '  </div>',
      '  <div class="col-lg-1 d-grid">',
      '    <button type="button" class="btn btn-outline-danger transfer-remove-btn" aria-label="Remover item">',
      '      <i class="bi bi-trash"></i>',
      '    </button>',
      '  </div>',
      '</div>'
    ].join('');

    itemsContainer.appendChild(row);

    var select = row.querySelector('.transfer-product-select');
    var qtyInput = row.querySelector('.transfer-qty-input');
    var removeBtn = row.querySelector('.transfer-remove-btn');

    inicializarSelect2(select);

    select.addEventListener('change', function () {
      limparAlerta();
      atualizarSaldoRow(row);
    });

    qtyInput.addEventListener('input', function () {
      var produto = produtosMap.get(String(select.value));
      var saldo = saldoDisponivel(produto);
      var quantidade = Number(qtyInput.value || 0);

      if (quantidade > saldo) {
        qtyInput.classList.add('is-invalid');
        mostrarAlerta('Saldo Insuficiente no ' + nomeLocal(origemSelect.value) + '.');
      } else {
        qtyInput.classList.remove('is-invalid');
        if (!itemsContainer.querySelector('.transfer-qty-input.is-invalid')) {
          limparAlerta();
        }
      }
    });

    removeBtn.addEventListener('click', function () {
      if (typeof window.jQuery !== 'undefined' && window.jQuery.fn.select2) {
        window.jQuery(select).select2('destroy');
      }

      row.remove();
      if (!itemsContainer.querySelector('.transfer-qty-input.is-invalid')) {
        limparAlerta();
      }
    });

    if (item) {
      select.value = String(item.id);
      qtyInput.value = String(item.quantidade_sugerida || item.quantidade || 1);
      if (typeof window.jQuery !== 'undefined' && window.jQuery.fn.select2) {
        window.jQuery(select).val(String(item.id)).trigger('change');
      }
      atualizarSaldoRow(row);
    } else {
      atualizarSaldoRow(row);
    }
  }

  btnAdicionarItem.addEventListener('click', function () {
    criarLinha();
  });

  btnSugerirReposicao.addEventListener('click', function () {
    origemSelect.value = 'qtdEstoque';
    destinoSelect.value = 'qtdLoja';
    itemsContainer.innerHTML = '';
    nextIndex = 0;
    limparAlerta();

    if (!sugestoes.length) {
      mostrarAlerta('Nao existem produtos com necessidade de reposicao da loja neste momento.');
      return;
    }

    sugestoes.forEach(function (item) {
      criarLinha(item);
    });
  });

  origemSelect.addEventListener('change', function () {
    garantirLocaisDiferentes(true);
    atualizarTodosOsSaldos();
  });

  destinoSelect.addEventListener('change', function () {
    garantirLocaisDiferentes(false);
    atualizarTodosOsSaldos();
  });

  form.addEventListener('submit', function (event) {
    var linhas = itemsContainer.querySelectorAll('.transfer-item-row');
    var possuiErro = false;

    limparAlerta();
    garantirLocaisDiferentes(true);

    if (!linhas.length) {
      event.preventDefault();
      mostrarAlerta('Adicione pelo menos um item para transferir.');
      return;
    }

    linhas.forEach(function (row) {
      var select = row.querySelector('.transfer-product-select');
      var qtyInput = row.querySelector('.transfer-qty-input');
      var produto = produtosMap.get(String(select.value));
      var saldo = saldoDisponivel(produto);
      var quantidade = Number(qtyInput.value || 0);

      if (!select.value || quantidade <= 0 || quantidade > saldo) {
        qtyInput.classList.add('is-invalid');
        possuiErro = true;
      }
    });

    if (possuiErro) {
      event.preventDefault();
      mostrarAlerta('Saldo Insuficiente no ' + nomeLocal(origemSelect.value) + '.');
    }
  });

  criarLinha();
});
