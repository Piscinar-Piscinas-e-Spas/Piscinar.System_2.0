<?php

declare(strict_types=1);

function servicos_ensure_schema(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS servicos_pedidos (
            id_servico INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            cliente_id INT UNSIGNED NULL,
            data_servico DATE NOT NULL,
            condicao_pagamento VARCHAR(20) NOT NULL DEFAULT 'vista',
            subtotal_produtos DECIMAL(12,2) NOT NULL DEFAULT 0,
            subtotal_microservicos DECIMAL(12,2) NOT NULL DEFAULT 0,
            desconto_total DECIMAL(12,2) NOT NULL DEFAULT 0,
            frete_total DECIMAL(12,2) NOT NULL DEFAULT 0,
            total_geral DECIMAL(12,2) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_servico_data (data_servico),
            INDEX idx_servico_cliente (cliente_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS servicos_itens (
            id_item INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            servico_id INT UNSIGNED NOT NULL,
            tipo_item ENUM('produto', 'microservico') NOT NULL,
            produto_id INT UNSIGNED NULL,
            descricao VARCHAR(255) NOT NULL,
            quantidade DECIMAL(10,2) NOT NULL DEFAULT 1,
            valor_unitario DECIMAL(12,2) NOT NULL DEFAULT 0,
            desconto_valor DECIMAL(12,2) NOT NULL DEFAULT 0,
            frete_valor DECIMAL(12,2) NOT NULL DEFAULT 0,
            total_item DECIMAL(12,2) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_servicos_itens_pedido FOREIGN KEY (servico_id) REFERENCES servicos_pedidos(id_servico) ON DELETE CASCADE,
            INDEX idx_servicos_itens_tipo (tipo_item)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS servicos_parcelas (
            id_parcela INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            servico_id INT UNSIGNED NOT NULL,
            numero_parcela INT UNSIGNED NOT NULL,
            vencimento DATE NOT NULL,
            tipo_pagamento VARCHAR(80) NOT NULL DEFAULT 'PIX',
            valor_parcela DECIMAL(12,2) NOT NULL DEFAULT 0,
            qtd_parcelas INT UNSIGNED NOT NULL DEFAULT 1,
            total_parcelas DECIMAL(12,2) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_servicos_parcelas_pedido FOREIGN KEY (servico_id) REFERENCES servicos_pedidos(id_servico) ON DELETE CASCADE,
            INDEX idx_servicos_parcelas_venc (vencimento)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

function servicos_obter_clientes(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT id_cliente, nome_cliente, telefone_contato, cpf_cnpj, email_contato, endereco FROM clientes ORDER BY nome_cliente");
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

function servicos_cliente_obrigatorio(): bool
{
    $valor = getenv('SERVICOS_CLIENTE_OBRIGATORIO');
    if ($valor === false || trim((string) $valor) === '') {
        return false;
    }

    return (bool) filter_var($valor, FILTER_VALIDATE_BOOLEAN);
}
