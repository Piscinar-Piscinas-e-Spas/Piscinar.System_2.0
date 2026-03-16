
function appUrl(path = '') {
    const base = (window.APP_BASE_URL || '').replace(/\/$/, '');
    const cleanPath = String(path).replace(/^\//, '');

    if (!cleanPath) {
        return base ? `${base}/` : '/';
    }

    return `${base}/${cleanPath}`;
}


document.getElementById('grupoInput').addEventListener('input', function() {
    const grupo = this.value;
    const subgrupoInput = document.getElementById('subgrupoInput');

    if(grupo) {
        fetch(`${appUrl('produtos/ajax_subgrupos.php')}?grupo=${encodeURIComponent(grupo)}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('subgruposList').innerHTML = data;
                subgrupoInput.disabled = false;
            });
    } else {
        subgrupoInput.disabled = true;
        subgrupoInput.value = '';
        document.getElementById('subgruposList').innerHTML = '';
    }
});


function atualizarSubgrupos() {
    const grupo = document.getElementById('grupo').value;
    const subgrupoSelect = document.getElementById('subgrupo');

    if(grupo) {
        fetch(`${appUrl('produtos/ajax_subgrupos.php')}?grupo=${encodeURIComponent(grupo)}`)
            .then(response => response.text())
            .then(data => {
                subgrupoSelect.innerHTML = data;
                subgrupoSelect.disabled = false;
                document.querySelector('[name="nome"]').disabled = true;
            });
    } else {
        subgrupoSelect.innerHTML = '<option value="">Todos os Subgrupos</option>';
        subgrupoSelect.disabled = true;
        document.querySelector('[name="nome"]').disabled = true;
    }
}

function atualizarNomes() {
    const subgrupo = document.getElementById('subgrupo').value;
    document.querySelector('[name="nome"]').disabled = !subgrupo;
}

