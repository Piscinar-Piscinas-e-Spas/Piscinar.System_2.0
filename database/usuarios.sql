CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(100) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    nome_exibicao VARCHAR(150) NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO usuarios (usuario, senha_hash, nome_exibicao, ativo)
VALUES (
    'admin',
    '$2y$12$abV70wmgC4trM1SR3yEFvuu7i2Qsfej0OsbyFvCHkPSJerihXKz.i',
    'Administrador',
    1
)
ON DUPLICATE KEY UPDATE
    senha_hash = VALUES(senha_hash),
    nome_exibicao = VALUES(nome_exibicao),
    ativo = VALUES(ativo),
    updated_at = CURRENT_TIMESTAMP;
