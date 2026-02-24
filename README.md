# Piscinar.System_2.0

## Configuração de ambiente (`BASE_URL`)

O sistema lê `BASE_URL` da variável de ambiente em `config.php`.

- Fallback padrão: `''` (raiz do domínio).
- Com fallback vazio, URLs ficam como `/index.php`, `/produtos/listar.php`, etc.

### Desenvolvimento local

Subpasta:

```bash
BASE_URL=/piscinar.system_2.0 php -S localhost:8000
```

Raiz do domínio:

```bash
BASE_URL='' php -S localhost:8000
```

### Produção

Defina `BASE_URL` no ambiente do servidor web/PHP-FPM (ou no processo do PHP):

- Subpasta: `BASE_URL=/piscinar.system_2.0`
- Raiz do domínio: `BASE_URL=''`

## Helper global para links

O `config.php` expõe a função `app_url($path)` para montar URLs absolutas da aplicação sem barras duplicadas.

Exemplos:

- `app_url('index.php')` → `/index.php` (raiz) ou `/piscinar.system_2.0/index.php` (subpasta)
- `app_url('produtos/listar.php')`
- `app_url('assets/js/filtrar_produtos.js')`

Use `app_url(...)` em páginas dentro da raiz, pastas e subpastas para manter a navegação consistente em qualquer ambiente.
