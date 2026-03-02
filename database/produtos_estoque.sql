ALTER TABLE produtos
    ADD COLUMN controle_estoque TINYINT(1) NOT NULL DEFAULT 0 AFTER qtdEstoque,
    ADD COLUMN estoque_minimo INT NULL AFTER controle_estoque,
    ADD COLUMN ponto_compra INT NULL AFTER estoque_minimo;
