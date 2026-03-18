CREATE TABLE IF NOT EXISTS usuarios (
  id_usuario INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario VARCHAR(80) NOT NULL,
  nome_exibicao VARCHAR(120) DEFAULT NULL,
  senha_hash VARCHAR(255) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario),
  UNIQUE KEY uk_usuarios_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS usuario_reset_senha_tokens (
  id_token BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_usuario INT UNSIGNED NOT NULL,
  token_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_token),
  KEY idx_reset_usuario_status (id_usuario, used_at, expires_at),
  CONSTRAINT fk_reset_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Senha padrão do exemplo: admin123
INSERT INTO usuarios (usuario, nome_exibicao, senha_hash, ativo)
VALUES ('admin', 'Administrador', '$2y$10$cxS0zlyFZW.cYaD2uw2/vuAiaTsxW6Bv33ImV2gP8QunJ5vIn9vRS', 1)
ON DUPLICATE KEY UPDATE nome_exibicao = VALUES(nome_exibicao), ativo = VALUES(ativo);
