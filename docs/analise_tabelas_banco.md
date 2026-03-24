# Verificação precisa: salvamento de serviços **hoje**

## Escopo desta verificação
Foco no fluxo **atual** de cadastro/edição de serviços acessado pela tela `servicos/nova.php`, e em artefatos legados/desuso no diretório.

## Fluxo atual de persistência de serviços (em produção no código de hoje)

### 1) Front-end de serviços envia para `servicos/salvar.php`
Na tela `servicos/nova.php`, o botão de salvar monta o payload e chama `fetch('salvar.php', ...)`.

### 2) Endpoint `servicos/salvar.php` grava nas tabelas abaixo
No salvamento/edição de serviço, o endpoint usa:
- `servicos_pedidos` (insert/update do cabeçalho)
- `servicos_itens` (itens de produto e microserviço)
- `servicos_parcelas` (parcelamento)

Além disso, ao editar, ele limpa e regrava itens/parcelas do mesmo serviço nas tabelas acima.

### 3) Leitura/listagem/detalhe também usam o mesmo modelo plural
- `servicos/listar.php` usa `ServicoRepository::listWithCliente()`
- `servicos/detalhes.php` usa `ServicoRepository::findCompleteById()`
- Essas consultas leem `servicos_pedidos`, `servicos_itens`, `servicos_parcelas`.

## Tabelas usadas hoje no módulo de serviços (salvar + ler)
- `servicos_pedidos`
- `servicos_itens`
- `servicos_parcelas`
- `clientes` (join/seleção de cliente no contexto de serviços)
- `produtos` (lista de produtos para compor itens na tela)

## Tabelas de serviços que aparecem no código, mas **não** no fluxo atual de salvar via tela
Existe um modelo alternativo em `src/Services/ServicoService.php` + `src/Repositories/ServicoRepository.php` que grava em:
- `servicos`
- `servico_produtos`
- `servico_microservicos`
- `servico_parcelas`

Pelo código atual das páginas de `servicos/`, esse conjunto **não é o caminho principal de salvamento hoje** (a tela chama `servicos/salvar.php`, que grava no modelo plural).

## Conclusão objetiva para exclusão de tabelas
Se o critério for “o que o sistema usa **hoje** no salvamento de serviços”:
- **Manter (uso atual):** `servicos_pedidos`, `servicos_itens`, `servicos_parcelas`.
- **Candidatas à descontinuação (após validação):** `servicos`, `servico_produtos`, `servico_microservicos`, `servico_parcelas`.

## Arquivos/código com indício de desuso (legado)

### 1) Fluxo alternativo de serviços (Controller/Service)
- Existe `src/Controllers/ServicoController.php` e `src/Services/ServicoService.php`.
- Porém, hoje a tela `servicos/nova.php` salva direto em `servicos/salvar.php`.
- Em busca textual no repositório, não há ponto chamando `new \App\Controllers\ServicoController(...)`.

Indício prático: o módulo de serviços ativo usa endpoint procedural (`servicos/salvar.php`) e não o controller/service de serviços.

### 2) Métodos de gravação singular em `ServicoRepository`
No `ServicoRepository`, métodos `createServico*` gravam em `servicos`/`servico_*`, mas esse caminho depende do `ServicoService` (que não está ligado ao fluxo principal atual de tela).

### 3) Arquivo com provável legado de rota/compatibilidade
- `servicos/exclluir.php` (com typo no nome) só redireciona para `servicos/excluir.php`.
- Não há referência ativa para `exclluir.php` nas páginas do sistema.

### 4) Pasta `descontinuar/`
- Conjunto (`display.php`, `list.php`, `save.php`, `cadbarras.js`) opera com `codes.txt` local.
- Não há chamadas/links do fluxo principal para essa pasta.
- A documentação arquitetural já aponta `descontinuar/` como recurso auxiliar não integrado ao fluxo principal.


## Status da pasta `src/` (resposta objetiva)
A pasta `src/` **não está inteira em desuso**.

### Partes ativas hoje
- `src/bootstrap.php`: carregado por `includes/db.php` em praticamente todo fluxo autenticado.
- Controllers ativos: `ClienteController`, `ProdutoController`, `VendaController`, `AuditoriaLogController`.
- Repositories/Services ativos: cliente, produto e venda (usados pelas páginas de `clientes/`, `produtos/`, `vendas/`).
- Views utilitárias ativas: `ApiResponse` e `AlertRenderer`.
- `ServicoRepository` também está ativo para leitura/listagem/detalhe de serviços.

### Parte com forte indício de desuso dentro de `src/`
- `src/Controllers/ServicoController.php`: sem referência direta nas páginas/endpoints atuais.
- `src/Services/ServicoService.php`: acoplado ao `ServicoController` e ao modelo singular (`servicos` + `servico_*`), não ligado ao `servicos/salvar.php` usado pela UI.
- No `src/Repositories/ServicoRepository.php`, os métodos de criação singular (`createServico*`) aparentam ser legado do fluxo acima; já os métodos de leitura em `servicos_pedidos/servicos_itens/servicos_parcelas` seguem ativos.

## Checklist antes de excluir tabelas/arquivos legados
1. Backup completo do banco e dos arquivos.
2. Confirmar em produção se existe algum endpoint externo consumindo `ServicoController/ServicoService`.
3. Conferir dados históricos nas tabelas candidatas e migrar se necessário.
4. Só então remover tabelas/arquivos legados.
