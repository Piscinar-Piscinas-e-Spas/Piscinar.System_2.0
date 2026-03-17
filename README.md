# Piscinar.System_2.0

## Configuração de ambiente (`BASE_URL`)


## Sessão: cookie de autenticação e lifetime

A aplicação define o cookie de sessão com `lifetime = 0` por padrão. Isso significa que o cookie é de sessão e expira ao fechar o navegador.

> Observação: alguns navegadores podem restaurar abas/sessões após reinício, o que pode manter sessão ativa mesmo com `lifetime 0`, dependendo da configuração do cliente.

Também são aplicados os atributos de segurança do cookie de sessão:

- `HttpOnly: true`
- `Secure: true` quando a requisição estiver em HTTPS
- `SameSite: Lax`

### Override opcional para modo muito longo

Se for necessário um comportamento excepcionalmente longo, use a variável de ambiente `SESSION_COOKIE_LIFETIME_OVERRIDE` (em segundos).

- Padrão seguro: `0`
- Override opcional: valor inteiro positivo (ex.: `2592000` para ~30 dias)

Exemplo:

```bash
SESSION_COOKIE_LIFETIME_OVERRIDE=2592000 php -S localhost:8000
```


O sistema lê `BASE_URL` da variável de ambiente em `config.php`.

- Se `BASE_URL` estiver definido, esse valor é usado como base dos links.
- Se `BASE_URL` não estiver definido, o sistema detecta automaticamente a base da URL atual (raiz ou subpasta).

> Dica: para cenários em que o mesmo código precisa funcionar em host raiz **e** host com subpasta (ex.: DNS interno + localhost), prefira deixar `BASE_URL` **não definido** para usar autodetecção por requisição.

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

## Cadastro de clientes

Foi adicionada estrutura de CRUD para clientes em:

- `clientes/cadastrar.php`
- `clientes/listar.php`
- `clientes/editar.php`
- `clientes/excluir.php`

### Regras de obrigatoriedade

Para o fluxo atual do negócio, apenas estes campos são obrigatórios:

- `nome_cliente`
- `telefone_contato`

Campos opcionais:

- `cpf_cnpj`
- `email_contato`
- `endereco`

### Script SQL da tabela

Use o arquivo `database/clientes.sql` para criar a tabela inicial.

### Padrão de nomenclatura recomendado (evitar confusão futura)

- **Tabela no plural, em minúsculo:** `clientes`
- **PK explícita por entidade:** `id_cliente`
- **Campos descritivos e consistentes:**
  - `nome_cliente`
  - `telefone_contato`
  - `cpf_cnpj`
  - `endereco`
  - `email_contato`
- **Campos técnicos padrão:** `created_at` e `updated_at`

Esse padrão facilita consultas, manutenção e leitura do banco ao longo do crescimento do sistema.

## Produtos: controle de estoque, estoque mínimo e ponto de compra

Para habilitar os novos campos no CRUD de produtos, execute o script:

- `database/produtos_estoque.sql`

Ele adiciona na tabela `produtos`:

- `controle_estoque` (obrigatório, padrão `0`)
- `estoque_minimo` (obrigatório apenas quando `controle_estoque = 1`, validado no formulário)
- `ponto_compra` (opcional)

## ID com auto incremento e exibição com 6 dígitos (MySQL/phpMyAdmin)

No MySQL, o recomendado é manter `id_cliente` como **INT AUTO_INCREMENT** (valor numérico real) e apenas formatar a exibição com zeros à esquerda.

### 1) Garantir coluna auto incremento

No phpMyAdmin (aba **Estrutura**):
- Tipo: `INT UNSIGNED`
- Índice: `PRIMARY`
- A_I (Auto Increment): marcado

SQL equivalente:

```sql
ALTER TABLE clientes
MODIFY id_cliente INT UNSIGNED NOT NULL AUTO_INCREMENT;
```

### 2) Exibir sempre com 6 dígitos

Use `LPAD` em consultas ou `str_pad` no PHP.

Exemplo SQL:

```sql
SELECT LPAD(id_cliente, 6, '0') AS codigo_cliente, nome_cliente
FROM clientes;
```

No sistema, a listagem já exibe o ID nesse formato (`000001`, `000245`, etc.), sem perder a integridade do auto incremento.

## Autenticação por sessão (`REQUIRE_AUTH`)

A proteção adicionada no projeto é **por sessão PHP**, não por token externo automático.

### Quando o login é exigido

- `REQUIRE_AUTH=true`: páginas protegidas exigem usuário na sessão.
- `REQUIRE_AUTH` ausente/`false`: sistema não bloqueia acesso por login.

### O login é no próprio projeto ou em outra instância?

Pode ser dos dois jeitos:

1. **No próprio projeto**: sua tela de login deve gravar os dados na `$_SESSION`.
2. **Em outra instância**: funciona se ela compartilhar a mesma sessão PHP (mesmo domínio/cookie/sessão) e preencher as chaves esperadas.

### Chaves de sessão aceitas

Por padrão, o middleware reconhece:

- ID do usuário: `auth_user_id`, `usuario_id`, `user_id`
- Objeto de usuário: `auth_user`, `usuario`, `user`

Se sua outra instância usa nomes diferentes, configure:

- `AUTH_SESSION_ID_KEYS` (lista separada por vírgula)
- `AUTH_SESSION_USER_KEYS` (lista separada por vírgula)

Exemplo:

```bash
REQUIRE_AUTH=true
AUTH_SESSION_ID_KEYS=cliente_id,usuario_logado_id
AUTH_SESSION_USER_KEYS=cliente_auth,usuario_logado
```

## Login por sessão (usuário e senha)

### Arquivos adicionados

- `login.php`: autenticação com `password_verify`, sessão e resposta JSON para UX dinâmica.
- `logout.php`: encerra sessão e redireciona para login.
- `session-expired.php`: modal visual de sessão expirada com redirecionamento automático.
- `database/usuarios.sql`: criação da tabela `usuarios` + usuário inicial.

### Requisitos de ambiente

- Para obrigar login em todos os módulos protegidos:

```bash
REQUIRE_AUTH=true
```

### Comportamento de sessão

- Cookie de sessão com `lifetime=0` (expira ao fechar navegador).
- Timeout de inatividade no servidor: **3h30** (12600 segundos).
- Ao expirar, o usuário é redirecionado para uma tela com modal informando o fim da sessão.
