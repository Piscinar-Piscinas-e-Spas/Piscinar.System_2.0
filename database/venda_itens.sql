CREATE TABLE IF NOT EXISTS venda_itens (
    id_venda_item INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_venda INT UNSIGNED NOT NULL,
    id_produto INT NOT NULL,
    quantidade DECIMAL(10,2) NOT NULL,
    valor_unitario DECIMAL(12,2) NOT NULL,
    desconto_valor DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    frete_valor DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_item DECIMAL(12,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_venda_itens_venda FOREIGN KEY (id_venda) REFERENCES vendas(id_venda)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_venda_itens_produto FOREIGN KEY (id_produto) REFERENCES produtos(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT chk_venda_itens_valores CHECK (
        quantidade > 0 AND valor_unitario >= 0 AND desconto_valor >= 0 AND frete_valor >= 0 AND total_item >= 0
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
