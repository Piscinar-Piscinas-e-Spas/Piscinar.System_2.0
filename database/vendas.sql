CREATE TABLE IF NOT EXISTS vendas (
    id_venda INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT UNSIGNED NOT NULL,
    data_venda DATE NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    desconto_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    frete_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_geral DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    condicao_pagamento ENUM('vista', 'parcelado') NOT NULL DEFAULT 'vista',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_vendas_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT chk_vendas_valores_nao_negativos CHECK (
        subtotal >= 0 AND desconto_total >= 0 AND frete_total >= 0 AND total_geral >= 0
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
