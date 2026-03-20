CREATE TABLE IF NOT EXISTS servico_parcelas (
    id_servico_parcela INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_servico INT UNSIGNED NOT NULL,
    numero_parcela INT UNSIGNED NOT NULL,
    vencimento DATE NOT NULL,
    valor_parcela DECIMAL(12,2) NOT NULL,
    tipo_pagamento VARCHAR(60) NOT NULL,
    qtd_parcelas INT UNSIGNED NOT NULL DEFAULT 1,
    total_parcelas DECIMAL(12,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_servico_parcelas_servico FOREIGN KEY (id_servico) REFERENCES servicos(id_servico)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    INDEX idx_servico_parcelas_servico (id_servico),
    INDEX idx_servico_parcelas_vencimento (vencimento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
