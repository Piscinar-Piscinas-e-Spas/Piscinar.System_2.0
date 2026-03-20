CREATE TABLE IF NOT EXISTS servicos (
    id_servico INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT UNSIGNED NULL,
    data_servico DATE NOT NULL,
    subtotal_produtos DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    subtotal_microservicos DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    desconto_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    frete_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_geral DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    condicao_pagamento ENUM('vista', 'parcelado') NOT NULL DEFAULT 'vista',
    observacao VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_servicos_data (data_servico),
    INDEX idx_servicos_cliente (id_cliente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
