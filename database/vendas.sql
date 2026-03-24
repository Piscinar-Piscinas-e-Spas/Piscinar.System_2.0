CREATE TABLE IF NOT EXISTS vendas (
    id_venda INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT UNSIGNED NOT NULL,
    vendedor_id INT UNSIGNED NULL,
    vendedor_nome VARCHAR(120) DEFAULT NULL,
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
    KEY idx_vendas_vendedor_id (vendedor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
