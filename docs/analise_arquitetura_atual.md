# Análise Arquitetural Atual — Piscinar.System_2.0

## Estrutura de Pastas

- `includes/`: infraestrutura compartilhada (conexão com banco e layout base).
- `produtos/`: CRUD de produtos, filtros e endpoint auxiliar de subgrupos.
- `clientes/`: CRUD de clientes com validações de entrada.
- `vendas/`: tela de composição de venda/orçamento com lógica rica no frontend.
- `servicos/`: módulo reservado, atualmente sem implementação.
- `descontinuar/`: utilitários de leitura/escrita de códigos em arquivo texto.
- `assets/css` e `assets/js`: estilo e scripts de interação da UI.
- `database/`: scripts SQL de criação/alteração de estruturas.
- raiz (`index.php`, `config.php`, `README.md`): dashboard, configuração global e documentação.

## Tecnologias Identificadas

- **Backend**: PHP procedural por página (sem framework).
- **Banco**: MySQL com acesso via PDO.
- **Frontend**: HTML server-rendered + Bootstrap 5 + JavaScript vanilla.
- **Bibliotecas de UI**: Font Awesome.
- **Integração assíncrona**: `fetch()` para endpoints PHP específicos.

## Conteúdo dos Arquivos Principais

- `config.php`: define `BASE_URL` por variável de ambiente e helper `app_url()`.
- `includes/db.php`: inicializa conexão PDO com MySQL.
- `includes/header.php`: navbar, estilos/scripts globais e marcação de menu ativo por rota.
- `index.php`: dashboard com indicadores de estoque baseados em agregações SQL na tabela `produtos`.
- `produtos/*.php`: operações de listar/cadastrar/editar/excluir com validações.
- `clientes/*.php`: operações de listar/cadastrar/editar/excluir com validações de negócio.
- `vendas/nova.php`: montagem de pedido/orçamento no cliente (itens, desconto, frete, parcelas).

## Padrão Arquitetural

Atualmente o sistema segue um padrão **monolítico procedural por módulo/página**, com características de:

1. **Page Controller**: cada arquivo `.php` recebe requisição, trata dados, executa SQL e renderiza HTML.
2. **Server-Side Rendering**: a view principal é montada no backend.
3. **Lógica mista por arquivo**: acesso a dados + regra de negócio + apresentação no mesmo ponto.
4. **Reuso por includes**: elementos comuns centralizados em `includes/`.

## Fluxo de Dados (Pedido de Venda: frontend → banco)

### Situação atual observada

1. **Carga inicial**: `vendas/nova.php` consulta clientes e produtos no banco para popular campos da tela.
2. **Interação da venda**: toda regra de itens, frete, descontos e parcelamento ocorre no JavaScript da própria página.
3. **Persistência**: **não há endpoint de gravação final da venda no banco** no estado atual.
4. **Conclusão**: o fluxo está funcional para simulação/montagem comercial, mas incompleto para fechamento transacional de pedido.

## Componentes-Chave

- **`includes/`**: padroniza infraestrutura e layout global.
- **`produtos/`**: sustenta catálogo e parte de estoque.
- **`clientes/`**: mantém cadastro de clientes e dados de contato.
- **`vendas/`**: concentra a lógica de negociação/composição da venda.
- **`database/`**: registra evolução de schema por scripts SQL incrementais.
- **`descontinuar/`**: recursos auxiliares não integrados ao fluxo principal.

## Veredito: está tudo ok?

### O que está ok

- Estrutura por domínio (`produtos`, `clientes`, `vendas`) está clara e compreensível.
- Uso de prepared statements em várias operações críticas de CRUD.
- Navegação e links adaptáveis entre ambientes via `BASE_URL` + `app_url()`.
- Dashboard inicial e módulos de produto/cliente com boa cobertura de operações básicas.

### Pontos de atenção (não bloqueantes, mas importantes)

1. **Vendas sem persistência**: principal lacuna funcional para um sistema de administração de vendas.
2. **Credenciais de banco fixas no código**: ideal mover para variáveis de ambiente (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).
3. **Sem camada de serviço/repositório**: manutenção tende a ficar mais custosa com o crescimento do sistema.
4. **Operações destrutivas via GET** (`excluir.php`): recomendável migrar para POST + CSRF.
5. **Módulo `servicos/` vazio**: pode confundir roadmap se não houver indicação explícita de backlog.

## Sugestões rápidas de melhoria

1. Criar `vendas/salvar.php` com transação PDO e tabelas `vendas`, `venda_itens`, `venda_parcelas`.
2. Externalizar configuração de banco para ambiente e fortalecer DSN com `charset=utf8mb4`.
3. Introduzir separação gradual de camadas (controller/service/repository) sem refatoração disruptiva.
4. Adotar token CSRF e troca de exclusões para POST.
5. Registrar ADR curto (Architecture Decision Record) para orientar evolução técnica futura.
