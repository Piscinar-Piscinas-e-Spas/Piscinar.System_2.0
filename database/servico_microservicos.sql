CREATE TABLE IF NOT EXISTS servico_microservicos (
    id_servico_microservico INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_servico INT UNSIGNED NOT NULL,
    descricao VARCHAR(150) NOT NULL,
    quantidade DECIMAL(10,2) NOT NULL,
    valor_unitario DECIMAL(12,2) NOT NULL,
    desconto_valor DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_item DECIMAL(12,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_servico_microservicos_servico FOREIGN KEY (id_servico) REFERENCES servicos(id_servico)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    INDEX idx_servico_microservicos_servico (id_servico)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
