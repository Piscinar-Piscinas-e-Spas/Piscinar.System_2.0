CREATE TABLE IF NOT EXISTS auditoria_logs (
    id_log BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    acao ENUM('create', 'update', 'delete') NOT NULL,
    entidade VARCHAR(80) NOT NULL,
    tabela_referencia VARCHAR(80) NOT NULL,
    id_registro VARCHAR(80) NOT NULL,
    usuario_id VARCHAR(80) DEFAULT NULL,
    usuario_nome VARCHAR(150) DEFAULT NULL,
    campos_alterados LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_log),
    KEY idx_auditoria_entidade_registro (entidade, id_registro),
    KEY idx_auditoria_usuario (usuario_id),
    KEY idx_auditoria_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
