<?php

if (!function_exists('action_firewall_storage_key')) {
    // Esse firewall adiciona uma confirmacao extra para acoes sensiveis.
    // Ele funciona como uma "liberacao temporaria" depois que a senha e validada.
    function action_firewall_storage_key(): string
    {
        return '__action_firewall_grants';
    }

    function action_firewall_cleanup_grants(): void
    {
        // Limpa grants vencidos, malformados ou amarrados a outro usuario.
        $storageKey = action_firewall_storage_key();
        $currentUserId = (string) (auth_user_id() ?? '');
        $now = time();

        if (!isset($_SESSION[$storageKey]) || !is_array($_SESSION[$storageKey])) {
            $_SESSION[$storageKey] = [];
            return;
        }

        foreach ($_SESSION[$storageKey] as $token => $grant) {
            if (!is_array($grant)) {
                unset($_SESSION[$storageKey][$token]);
                continue;
            }

            $expired = (int) ($grant['expires_at'] ?? 0) < $now;
            $wrongUser = (string) ($grant['user_id'] ?? '') !== $currentUserId;

            if ($expired || $wrongUser) {
                unset($_SESSION[$storageKey][$token]);
            }
        }
    }

    function action_firewall_normalize_entity($entity): string
    {
        $normalized = strtolower(trim((string) $entity));
        return preg_match('/^[a-z0-9_]+$/', $normalized) ? $normalized : '';
    }

    function action_firewall_normalize_intent($intent): string
    {
        $normalized = strtolower(trim((string) $intent));
        return in_array($normalized, ['edit', 'delete'], true) ? $normalized : '';
    }

    function action_firewall_issue_grant(string $entity, string $intent, int $recordId, int $ttlSeconds = 300): string
    {
        // O token temporario evita pedir senha de novo no passo final da mesma acao.
        action_firewall_cleanup_grants();

        $token = bin2hex(random_bytes(24));
        $_SESSION[action_firewall_storage_key()][$token] = [
            'entity' => action_firewall_normalize_entity($entity),
            'intent' => action_firewall_normalize_intent($intent),
            'record_id' => $recordId,
            'user_id' => (string) (auth_user_id() ?? ''),
            'expires_at' => time() + max(60, $ttlSeconds),
        ];

        return $token;
    }

    function action_firewall_request_token(): ?string
    {
        // O grant pode chegar por GET, POST ou JSON dependendo do fluxo.
        $postToken = $_POST['fw_token'] ?? null;
        if (is_string($postToken) && $postToken !== '') {
            return $postToken;
        }

        $getToken = $_GET['fw_token'] ?? null;
        if (is_string($getToken) && $getToken !== '') {
            return $getToken;
        }

        $json = read_json_input();
        $jsonToken = $json['fw_token'] ?? null;

        return is_string($jsonToken) && $jsonToken !== '' ? $jsonToken : null;
    }

    function action_firewall_consume_grant(string $entity, string $intent, int $recordId, ?string $token = null): bool
    {
        // O grant e de uso unico. Se contexto nao bater, ele e descartado.
        action_firewall_cleanup_grants();

        $candidateToken = is_string($token) && $token !== '' ? $token : action_firewall_request_token();
        if (!is_string($candidateToken) || $candidateToken === '') {
            return false;
        }

        $grant = $_SESSION[action_firewall_storage_key()][$candidateToken] ?? null;
        if (!is_array($grant)) {
            return false;
        }

        $entityMatches = (string) ($grant['entity'] ?? '') === action_firewall_normalize_entity($entity);
        $intentMatches = (string) ($grant['intent'] ?? '') === action_firewall_normalize_intent($intent);
        $recordMatches = (int) ($grant['record_id'] ?? 0) === $recordId;
        $userMatches = (string) ($grant['user_id'] ?? '') === (string) (auth_user_id() ?? '');
        $notExpired = (int) ($grant['expires_at'] ?? 0) >= time();

        if (!$entityMatches || !$intentMatches || !$recordMatches || !$userMatches || !$notExpired) {
            unset($_SESSION[action_firewall_storage_key()][$candidateToken]);
            return false;
        }

        unset($_SESSION[action_firewall_storage_key()][$candidateToken]);
        return true;
    }

    function action_firewall_require_grant(string $entity, string $intent, int $recordId, string $redirectUrl): void
    {
        // Esse ponto protege o endpoint final da acao sensivel.
        if (action_firewall_consume_grant($entity, $intent, $recordId)) {
            return;
        }

        if (request_expects_json()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => false,
                'error_code' => 'firewall_required',
                'mensagem' => 'Confirmacao de senha obrigatoria para esta operacao.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        header('Location: ' . $redirectUrl);
        exit;
    }

    function action_firewall_password_is_valid(PDO $pdo, string $senha): bool
    {
        // A senha e validada direto no banco para evitar confiar em dado stale de sessao.
        $userId = (int) (auth_user_id() ?? 0);
        if ($userId <= 0 || $senha === '') {
            return false;
        }

        $stmt = $pdo->prepare('SELECT senha_hash, ativo FROM usuarios WHERE id_usuario = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($user)
            && (int) ($user['ativo'] ?? 0) === 1
            && password_verify($senha, (string) ($user['senha_hash'] ?? ''));
    }

    function render_action_firewall_modal(): void
    {
        // O modal so e renderizado uma vez por pagina, mesmo se varios botoes usarem o fluxo.
        static $rendered = false;
        if ($rendered) {
            return;
        }

        $rendered = true;
        ?>
        <div class="modal fade" id="actionFirewallModal" tabindex="-1" aria-labelledby="actionFirewallModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="actionFirewallModalLabel">Confirmar senha</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2" id="actionFirewallDescription">Digite sua senha para continuar.</p>
                        <div id="actionFirewallFeedback" class="alert alert-danger d-none py-2" role="alert"></div>
                        <label for="actionFirewallPassword" class="form-label">Senha do usuario</label>
                        <input type="password" id="actionFirewallPassword" class="form-control" autocomplete="current-password" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="actionFirewallConfirmButton">
                            <span class="js-btn-label">Confirmar</span>
                            <span class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
