<?php

// Buscar informações do usuário logado, comunicados não lidos e notificações internas
$userInfo = null;
$navbarNotifCount = 0;
$navbarNotifList = [];
$navbarInternalCount = 0;
$navbarInternalList = [];
$mcpChatAvailable = false;
if (!empty($_SESSION['user_id'])) {
    try {
        $userId = (int)$_SESSION['user_id'];
        $userRepo = new \App\adms\Models\Repository\UsersRepository();
        $userInfo = $userRepo->getUser($userId);
        $infoRepo = new \App\adms\Models\Repository\InformativosRepository();
        $policiesRepo = new \App\adms\Models\Repository\PoliciesRepository();
        $notifRepo = new \App\adms\Models\Repository\NotificationsRepository();
        $navbarNotifCountInformativos = $infoRepo->countNaoLidos($userId);
        $navbarNotifListInformativos = $navbarNotifCountInformativos > 0 ? $infoRepo->getListNaoLidos($userId, 10) : [];

        $navbarNotifCountPolicies = $policiesRepo->countNaoLidos($userId);
        $navbarNotifListPolicies = $navbarNotifCountPolicies > 0 ? $policiesRepo->getListNaoLidos($userId, 10) : [];

        $navbarInternalCount = $notifRepo->countUnread($userId);
        $navbarInternalList = $navbarInternalCount > 0 ? $notifRepo->listUnreadForUser($userId, 10) : [];
        $navbarTotalCount = $navbarNotifCountInformativos + $navbarNotifCountPolicies + $navbarInternalCount;

        // Verificar se o chat MCP está habilitado e se o usuário tem permissão
        $mcpConfigRepo = new \App\adms\Models\Repository\AdmsMcpApiConfigRepository();
        $mcpConfig = $mcpConfigRepo->getConfig();
        $mcpEnabled = !empty($mcpConfig) && !empty($mcpConfig['is_active']) && !empty($mcpConfig['base_url']);
        $userCanMcpChat = false;

        // Super Administrador (nível 1) sempre pode usar o chat MCP se a integração estiver ativa
        if (isset($_SESSION['user_access_level_id']) && (int)$_SESSION['user_access_level_id'] === 1) {
            $userCanMcpChat = true;
        } elseif (!empty($this->data['menuPermission'] ?? [])) {
            // Demais níveis dependem da permissão configurada para a página lógica "McpChat"
            $userCanMcpChat = in_array('McpChat', $this->data['menuPermission'], true);
        }
        $mcpChatAvailable = $mcpEnabled && $userCanMcpChat;
    } catch (\Exception $e) {
        $userInfo = null;
        $navbarTotalCount = 0;
        $navbarNotifList = [];
        $navbarInternalList = [];
        $mcpChatAvailable = false;
    }
} else {
    $navbarTotalCount = 0;
}
?>

<nav class="sb-topnav navbar navbar-expand navbar-dark bg-nav">
    <a class="navbar-brand ps-3" href="<?php echo $_ENV['URL_ADM']; ?>dashboard">Tiaraju</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" type="button" aria-label="Alternar menu lateral" title="Alternar menu lateral">
        <i class="fas fa-bars" aria-hidden="true"></i>
    </button>
    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
        
    </form>
    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4 align-items-center">
        <?php if ($mcpChatAvailable): ?>
        <li class="nav-item me-2">
            <button class="btn btn-outline-light btn-sm position-relative" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#mcpChatOffcanvas" aria-controls="mcpChatOffcanvas"
                    title="Assistente MCP" aria-label="Assistente MCP">
                <i class="fas fa-robot"></i>
            </button>
        </li>
        <?php endif; ?>
        <li class="nav-item dropdown">
            <a class="nav-link position-relative" href="#" id="navbarNotifications" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notificações" title="Notificações">
                <i class="fas fa-bell"></i>
                <?php if (!empty($navbarTotalCount) && $navbarTotalCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light" id="navbarNotifBadge" style="font-size: 0.65rem;"><?php echo $navbarTotalCount > 99 ? '99+' : (int)$navbarTotalCount; ?></span>
                <?php else: ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light" id="navbarNotifBadge" style="display: none; font-size: 0.65rem;">0</span>
                <?php endif; ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow navbar-notifications-dropdown" aria-labelledby="navbarNotifications" style="min-width: 300px; max-height: 400px; overflow-y: auto;">
                <li class="dropdown-header d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Notificações</span>
                    <?php if (!empty($navbarTotalCount) && $navbarTotalCount > 0): ?><span class="badge bg-primary"><?php echo (int)$navbarTotalCount; ?> não lido<?php echo $navbarTotalCount !== 1 ? 's' : ''; ?></span><?php endif; ?>
                </li>
                <li><hr class="dropdown-divider my-0"></li>
                <?php if (!empty($navbarNotifListInformativos)): ?>
                    <li class="dropdown-header small text-muted">Comunicados</li>
                    <?php foreach ($navbarNotifListInformativos as $notif): ?>
                    <li>
                        <a class="dropdown-item py-2 d-block" href="<?php echo $_ENV['URL_ADM']; ?>view-informativo/<?php echo (int)$notif['id']; ?>">
                            <span class="d-block fw-semibold small"><?php if (!empty($notif['urgente'])): ?><i class="fas fa-exclamation-circle text-danger me-1"></i><?php endif; ?><?php echo htmlspecialchars($notif['titulo'] ?? ''); ?></span>
                            <span class="d-block text-muted" style="font-size: 0.8rem;"><?php echo date('d/m/Y H:i', strtotime($notif['created_at'] ?? 'now')); ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                    <li><hr class="dropdown-divider my-0"></li>
                <?php endif; ?>
                <?php if (!empty($navbarNotifListPolicies)): ?>
                    <li class="dropdown-header small text-muted">Políticas Internas</li>
                    <?php foreach ($navbarNotifListPolicies as $polNotif): ?>
                    <li>
                        <a class="dropdown-item py-2 d-block" href="<?php echo $_ENV['URL_ADM']; ?>view-policy/<?php echo (int)$polNotif['id']; ?>">
                            <span class="d-block fw-semibold small">
                                <?php if (!empty($polNotif['urgente'])): ?>
                                    <i class="fas fa-exclamation-circle text-danger me-1"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($polNotif['titulo'] ?? ''); ?>
                            </span>
                            <span class="d-block text-muted" style="font-size: 0.8rem;">
                                <?php echo date('d/m/Y H:i', strtotime($polNotif['created_at'] ?? 'now')); ?>
                            </span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                    <li><hr class="dropdown-divider my-0"></li>
                <?php endif; ?>
                <?php if (!empty($navbarInternalList)): ?>
                    <li class="dropdown-header small text-muted">Atribuições e avisos</li>
                    <?php foreach ($navbarInternalList as $intNotif): ?>
                    <li>
                        <?php
                        $intLink = !empty($intNotif['link_url']) ? $intNotif['link_url'] : ($_ENV['URL_ADM'] . 'list-notifications');
                        if (!empty($intNotif['link_url']) && strpos($intNotif['link_url'], 'list-notifications') === false) {
                            $intLink .= (strpos($intLink, '?') !== false ? '&' : '?') . 'mark_notification=' . (int)($intNotif['id'] ?? 0);
                        }
                        ?>
                        <a class="dropdown-item py-2 d-block" href="<?php echo htmlspecialchars($intLink); ?>">
                            <span class="d-block fw-semibold small"><?php if (($intNotif['type'] ?? '') === 'projeto_etapa'): ?><i class="fas fa-tasks text-primary me-1"></i><?php endif; ?><?php echo htmlspecialchars($intNotif['title'] ?? ''); ?></span>
                            <span class="d-block text-muted" style="font-size: 0.8rem;"><?php echo date('d/m/Y H:i', strtotime($intNotif['created_at'] ?? 'now')); ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                    <li><hr class="dropdown-divider my-0"></li>
                <?php endif; ?>
                <?php if (empty($navbarNotifListInformativos) && empty($navbarNotifListPolicies) && empty($navbarInternalList)): ?>
                <li><div class="dropdown-item text-muted small py-3 text-center">Nenhuma notificação nova.</div></li>
                <li><hr class="dropdown-divider my-0"></li>
                <?php endif; ?>
                <li><a class="dropdown-item small text-center" href="<?php echo $_ENV['URL_ADM']; ?>list-informativos"><i class="fas fa-bullhorn me-1"></i>Comunicados</a></li>
                <li><a class="dropdown-item small text-center" href="<?php echo $_ENV['URL_ADM']; ?>list-notifications"><i class="fas fa-bell me-1"></i>Ver todas as notificações</a></li>
            </ul>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu do usuário">
                <?php
                $navbarAvatarPath = null;
                if (!empty($userInfo['image']) && $userInfo['image'] !== 'icon_user.png') {
                    $navbarAvatarPath = 'users/' . $userInfo['id'] . '/' . $userInfo['image'];
                }
                echo \App\adms\Helpers\ImageHelper::displayImage($navbarAvatarPath, [
                    'alt' => 'Foto do usuário',
                    'class' => 'rounded-circle me-2',
                    'style' => 'width: 32px; height: 32px; object-fit: cover;',
                ], 'icon_user.png', 'users');
                ?>
                
                <div class="d-none d-md-block text-start me-2">
                    <div class="text-white fw-bold" style="font-size: 0.9rem; line-height: 1.1;">
                        <?php echo htmlspecialchars($userInfo['name'] ?? 'Usuário'); ?>
                    </div>
                    <div class="text-white-50" style="font-size: 0.75rem; line-height: 1.1;">
                        <?php echo htmlspecialchars($userInfo['pos_name'] ?? 'Cargo'); ?>
                    </div>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                <li class="dropdown-header">
                    <div class="d-flex align-items-center">
                        <?php
                        $navbarAvatarPathLg = null;
                        if (!empty($userInfo['image']) && $userInfo['image'] !== 'icon_user.png') {
                            $navbarAvatarPathLg = 'users/' . $userInfo['id'] . '/' . $userInfo['image'];
                        }
                        echo \App\adms\Helpers\ImageHelper::displayImage($navbarAvatarPathLg, [
                            'alt' => 'Foto do usuário',
                            'class' => 'rounded-circle me-2',
                            'style' => 'width: 40px; height: 40px; object-fit: cover;',
                        ], 'icon_user.png', 'users');
                        ?>
                        
                        <div>
                            <div class="fw-bold"><?php echo htmlspecialchars($userInfo['name'] ?? 'Usuário'); ?></div>
                            <div class="text-muted small"><?php echo htmlspecialchars($userInfo['pos_name'] ?? 'Cargo'); ?></div>
                        </div>
                    </div>
                </li>
                <li><hr class="dropdown-divider" /></li>
                <li>
                    <a class="dropdown-item" href="<?php echo $_ENV['URL_ADM']; ?>profile">
                        <i class="fa-solid fa-user-pen me-2"></i> Meu Perfil
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="<?php echo $_ENV['URL_ADM']; ?>update-password">
                        <i class="fa-solid fa-key me-2"></i> Alterar Senha
                    </a>
                </li>
                <li><hr class="dropdown-divider" /></li>
                <li>
                    <a class="dropdown-item" href="<?php echo $_ENV['URL_ADM']; ?>logout">
                        <i class="fas fa-arrow-right-from-bracket me-2"></i> Sair
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</nav>

<?php if ($mcpChatAvailable): ?>
<div class="offcanvas offcanvas-end" tabindex="-1" id="mcpChatOffcanvas" aria-labelledby="mcpChatOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="mcpChatOffcanvasLabel"><i class="fas fa-robot me-2"></i>Assistente MCP</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <div id="mcpChatMessages" class="flex-grow-1 border rounded p-2 mb-2 overflow-auto" style="max-height: 60vh; background-color: #f8f9fa;">
            <div class="text-muted small">Inicie uma conversa com o assistente digitando sua pergunta abaixo.</div>
        </div>
        <form id="mcpChatForm" class="mt-1">
            <div class="input-group">
                <textarea class="form-control" id="mcpChatInput" rows="2" placeholder="Digite sua pergunta..." aria-label="Mensagem para o assistente"></textarea>
                <button class="btn btn-primary" type="submit" id="mcpChatSendBtn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    const form = document.getElementById('mcpChatForm');
    const input = document.getElementById('mcpChatInput');
    const messagesEl = document.getElementById('mcpChatMessages');
    const sendBtn = document.getElementById('mcpChatSendBtn');
    if (!form || !input || !messagesEl || !sendBtn) return;

    function appendMessage(text, from) {
        const wrapper = document.createElement('div');
        wrapper.className = 'mb-2 d-flex ' + (from === 'user' ? 'justify-content-end' : 'justify-content-start');
        const bubble = document.createElement('div');
        bubble.className = 'p-2 rounded ' + (from === 'user' ? 'bg-primary text-white' : 'bg-light border');
        bubble.style.maxWidth = '80%';
        bubble.innerText = text;
        wrapper.appendChild(bubble);
        messagesEl.appendChild(wrapper);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    async function sendMessage(message) {
        appendMessage(message, 'user');
        input.value = '';
        input.focus();
        sendBtn.disabled = true;
        appendMessage('Pensando...', 'assistant');

        try {
            const response = await fetch("<?= rtrim($_ENV['URL_ADM'], '/') ?>/mcp-chat-api.php", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message })
            });
            const data = await response.json().catch(() => null);
            // remover último "Pensando..."
            const bubbles = messagesEl.querySelectorAll('div');
            if (bubbles.length) {
                const last = bubbles[bubbles.length - 1];
                if (last.textContent === 'Pensando...') {
                    last.remove();
                }
            }
            if (!data || !data.success) {
                let errorText = 'Não foi possível obter resposta do assistente.';
                if (data && data.message) {
                    errorText = data.message;
                    if (data.http_code) {
                        errorText += ' (HTTP ' + data.http_code + ')';
                    }
                }
                if (data && data.raw) {
                    errorText += '\nDetalhes: ' + (typeof data.raw === 'string' ? data.raw : JSON.stringify(data.raw));
                }
                appendMessage(errorText, 'assistant');
                return;
            }

            // Tratamento amigável da resposta: tenta interpretar JSON para exibir somente o campo principal
            let reply = data.reply;
            let displayText = '';

            function formatParsed(parsed) {
                // Se vier no formato { resposta: "..." } prioriza esse campo
                if (parsed && typeof parsed === 'object' && !Array.isArray(parsed) && parsed.resposta) {
                    return String(parsed.resposta);
                }
                // Se for um array de registros, formata cada um em linhas legíveis
                if (Array.isArray(parsed)) {
                    return parsed.map(function (item, idx) {
                        if (item && typeof item === 'object') {
                            // Junta campos chave: valor em uma linha
                            const parts = [];
                            for (const k in item) {
                                if (Object.prototype.hasOwnProperty.call(item, k)) {
                                    parts.push(k + ': ' + String(item[k]));
                                }
                            }
                            return (parsed.length > 1 ? ('[' + (idx + 1) + '] ') : '') + parts.join(' | ');
                        }
                        return String(item);
                    }).join('\n');
                }
                // fallback: JSON formatado
                return JSON.stringify(parsed, null, 2);
            }

            if (typeof reply === 'string') {
                let parsed = null;
                try {
                    parsed = JSON.parse(reply);
                } catch (e) {
                    // não é JSON, usa texto puro
                }
                if (parsed !== null) {
                    displayText = formatParsed(parsed);
                } else {
                    displayText = reply;
                }
            } else if (reply && typeof reply === 'object') {
                displayText = formatParsed(reply);
            } else {
                displayText = String(reply ?? '');
            }

            appendMessage(displayText, 'assistant');
        } catch (e) {
            const bubbles = messagesEl.querySelectorAll('div');
            if (bubbles.length) {
                const last = bubbles[bubbles.length - 1];
                if (last.textContent === 'Pensando...') {
                    last.remove();
                }
            }
            appendMessage('Erro ao comunicar com o assistente MCP.', 'assistant');
        } finally {
            sendBtn.disabled = false;
        }
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const text = input.value.trim();
        if (!text) return;
        sendMessage(text);
    });
})();
</script>
<?php endif; ?>

<style>
/* Mobile: balão de notificações centralizado, usando a área central (não corta nas laterais) */
@media (max-width: 767.98px) {
    .navbar-notifications-dropdown {
        position: fixed !important;
        left: 50% !important;
        right: auto !important;
        transform: translateX(-50%) !important;
        top: 56px !important;
        width: calc(100vw - 2rem) !important;
        min-width: 280px !important;
        max-width: 400px !important;
    }
    .navbar-notifications-dropdown .dropdown-header .fw-bold {
        font-size: 0.9rem;
    }
    .navbar-notifications-dropdown .dropdown-header .badge {
        font-size: 0.75rem;
    }
    .navbar-notifications-dropdown .dropdown-item {
        font-size: 0.9rem;
        white-space: normal;
    }
}
@media (max-width: 576px) {
    .navbar-notifications-dropdown {
        width: calc(100vw - 1.5rem) !important;
        min-width: 260px !important;
    }
}
</style>