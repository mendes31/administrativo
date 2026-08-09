<?php

use App\adms\Helpers\NavbarLayoutCacheHelper;

$userInfo = null;
$navbarNotifCount = 0;
$navbarNotifList = [];
$navbarInternalCount = 0;
$navbarInternalList = [];
$mcpChatAvailable = false;
$navbarTotalCount = 0;

if (!empty($_SESSION['user_id'])) {
    $userId = (int) $_SESSION['user_id'];
    $menuPermission = is_array($this->data['menuPermission'] ?? null) ? $this->data['menuPermission'] : [];
    $nav = NavbarLayoutCacheHelper::get($userId, $menuPermission);
    $userInfo = $nav['user_info'] ?? null;
    $navbarNotifCountInformativos = (int) ($nav['navbar_notif_count_informativos'] ?? 0);
    $navbarNotifListInformativos = $nav['navbar_notif_list_informativos'] ?? [];
    $navbarNotifCountPolicies = (int) ($nav['navbar_notif_count_policies'] ?? 0);
    $navbarNotifListPolicies = $nav['navbar_notif_list_policies'] ?? [];
    $navbarInternalCount = (int) ($nav['navbar_internal_count'] ?? 0);
    $navbarInternalList = $nav['navbar_internal_list'] ?? [];
    $navbarTotalCount = (int) ($nav['navbar_total_count'] ?? 0);
    $mcpChatAvailable = !empty($nav['mcp_chat_available']);
}
?>

<nav class="sb-topnav navbar navbar-expand navbar-dark bg-nav">
    <a class="navbar-brand ps-3 d-flex align-items-center gap-2" href="<?php echo $_ENV['URL_ADM']; ?>dashboard" aria-label="Home">
        <i class="fas fa-home text-white d-inline d-md-none" aria-hidden="true" title="Home"></i>
        <span>Tiaraju</span>
    </a>
    <?php
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $path = parse_url($requestUri, PHP_URL_PATH) ?: $requestUri;
    // Detecta dashboard considerando que o path pode vir como "/administrativo/dashboard"
    $pathTrimmed = rtrim($path, '/');
    $segments = array_values(array_filter(explode('/', $pathTrimmed)));
    $lastSegment = end($segments) ?: '';
    $isDashboardPage = strtolower($lastSegment) === 'dashboard';
    $showMobileBackInNavbar = !empty($requestUri) && strpos($requestUri, 'login') === false && !$isDashboardPage;
    ?>
    <?php if ($showMobileBackInNavbar): ?>
        <button type="button"
                class="btn btn-link btn-sm d-inline d-md-none me-2"
                aria-label="Voltar"
                title="Voltar"
                onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href='<?php echo $_ENV['URL_ADM']; ?>dashboard'; }">
            <i class="fas fa-arrow-left text-white" aria-hidden="true"></i>
        </button>
    <?php endif; ?>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" type="button" aria-label="Alternar menu lateral" title="Alternar menu lateral">
        <i class="fas fa-bars" aria-hidden="true"></i>
    </button>
    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
        
    </form>
    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4 align-items-center">
        <?php if ($mcpChatAvailable): ?>
        <?php $tjzAvatarUrl = rtrim($_ENV['URL_ADM'] ?? '', '/') . '/public/adms/images/chat/tiarajuzinho.png'; ?>
        <li class="nav-item me-2">
            <button class="btn tjz-nav-btn position-relative" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#mcpChatOffcanvas" aria-controls="mcpChatOffcanvas"
                    title="Tiarajuzinho" aria-label="Abrir Tiarajuzinho">
                <img src="<?= htmlspecialchars($tjzAvatarUrl) ?>" alt="Tiarajuzinho" width="32" height="32">
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
                    <?php
                    $navbarNotifIcon = static function (string $type): string {
                        return match ($type) {
                            'timeline_mention', 'comentario_mencao' => 'fas fa-at text-warning',
                            'timeline_comment' => 'far fa-comment text-primary',
                            'timeline_comment_reaction' => 'fas fa-heart text-danger',
                            'timeline_reaction' => 'fas fa-heart text-danger',
                            'timeline_share' => 'fas fa-retweet text-success',
                            'projeto_etapa' => 'fas fa-tasks text-primary',
                            default => 'far fa-bell text-secondary',
                        };
                    };
                    ?>
                    <?php foreach ($navbarInternalList as $intNotif): ?>
                    <li>
                        <?php
                        $intLink = !empty($intNotif['link_url']) ? trim((string)$intNotif['link_url']) : '';
                        if ($intLink === '') {
                            $intLink = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/notificacoes';
                        }
                        // Marca como lida ao abrir o destino (exceto quando o próprio destino já é a central).
                        if (strpos($intLink, 'notificacoes') === false && (int)($intNotif['id'] ?? 0) > 0) {
                            $intLink .= (strpos($intLink, '?') !== false ? '&' : '?') . 'mark_notification=' . (int)$intNotif['id'];
                        }
                        $nType = (string)($intNotif['type'] ?? '');
                        $isMentionNotif = $nType === 'timeline_mention' || $nType === 'comentario_mencao'
                            || (int)($intNotif['priority'] ?? 0) >= 100;
                        ?>
                        <a class="dropdown-item py-2 d-block<?php echo $isMentionNotif ? ' border-start border-warning border-3' : ''; ?>" href="<?php echo htmlspecialchars($intLink); ?>">
                            <span class="d-block fw-semibold small"><i class="<?php echo htmlspecialchars($navbarNotifIcon($nType)); ?> me-1"></i><?php echo htmlspecialchars($intNotif['title'] ?? ''); ?></span>
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
                <li><a class="dropdown-item small text-center" href="<?php echo $_ENV['URL_ADM']; ?>notificacoes"><i class="fas fa-bell me-1"></i>Ver todas as notificações</a></li>
            </ul>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu do usuário">
                <?php
                $navbarUserSubline = '—';
                if (is_array($userInfo)) {
                    $navDep = trim((string)($userInfo['dep_name'] ?? ''));
                    $navPos = \App\adms\Helpers\PositionDisplayHelper::formatForDisplay((string)($userInfo['pos_name'] ?? ''));
                    $navParts = [];
                    if ($navDep !== '') {
                        $navParts[] = $navDep;
                    }
                    if ($navPos !== '') {
                        $navParts[] = $navPos;
                    }
                    if ($navParts !== []) {
                        $navbarUserSubline = implode(' · ', $navParts);
                    }
                }
                $navbarAvatarPath = null;
                if (\App\adms\Helpers\ImageHelper::userImageExists((int)($userInfo['id'] ?? 0), (string)($userInfo['image'] ?? ''))) {
                    $navbarAvatarPath = 'users/' . $userInfo['id'] . '/' . $userInfo['image'];
                }
                if ($navbarAvatarPath !== null) {
                    echo \App\adms\Helpers\ImageHelper::displayNavbarUserAvatar($navbarAvatarPath);
                } else {
                    echo \App\adms\Helpers\ImageHelper::renderInitialsAvatar((string)($userInfo['name'] ?? 'Usuário'), 32, [
                        'class' => 'me-2',
                    ]);
                }
                ?>
                
                <div class="d-none d-md-block text-start me-2">
                    <div class="text-white fw-bold" style="font-size: 0.9rem; line-height: 1.1;">
                        <?php echo htmlspecialchars($userInfo['name'] ?? 'Usuário'); ?>
                    </div>
                    <div class="text-white-50" style="font-size: 0.75rem; line-height: 1.1;">
                        <?php echo htmlspecialchars($navbarUserSubline); ?>
                    </div>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                <li class="dropdown-header">
                    <div class="d-flex align-items-center">
                        <?php
                        $navbarAvatarPathLg = null;
                        if (\App\adms\Helpers\ImageHelper::userImageExists((int)($userInfo['id'] ?? 0), (string)($userInfo['image'] ?? ''))) {
                            $navbarAvatarPathLg = 'users/' . $userInfo['id'] . '/' . $userInfo['image'];
                        }
                        if ($navbarAvatarPathLg !== null) {
                            echo \App\adms\Helpers\ImageHelper::displayNavbarUserAvatar($navbarAvatarPathLg, [
                                'style' => 'width: 40px; height: 40px; object-fit: cover;',
                            ]);
                        } else {
                            echo \App\adms\Helpers\ImageHelper::renderInitialsAvatar((string)($userInfo['name'] ?? 'Usuário'), 40, [
                                'class' => 'me-2',
                            ]);
                        }
                        ?>
                        
                        <div>
                            <div class="fw-bold"><?php echo htmlspecialchars($userInfo['name'] ?? 'Usuário'); ?></div>
                            <div class="text-muted small"><?php echo htmlspecialchars($navbarUserSubline); ?></div>
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
                <?php
                $menuPermsNavbar = $this->data['menuPermission'] ?? [];
                if (in_array('MyEvaluations', $menuPermsNavbar, true)) :
                ?>
                <li>
                    <a class="dropdown-item" href="<?php echo $_ENV['URL_ADM']; ?>minhas-avaliacoes">
                        <i class="fas fa-clipboard-list me-2"></i>Minhas Avaliações
                    </a>
                </li>
                <?php endif; ?>
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
<?php $tjzAvatarUrl = $tjzAvatarUrl ?? (rtrim($_ENV['URL_ADM'] ?? '', '/') . '/public/adms/images/chat/tiarajuzinho.png'); ?>
<link rel="stylesheet" href="<?= rtrim($_ENV['URL_ADM'], '/') ?>/public/adms/css/tiarajuzinho-chat.css?v=3">
<script src="<?= rtrim($_ENV['URL_ADM'], '/') ?>/public/adms/vendor/chartjs/chart.umd.min.js" defer></script>
<div class="offcanvas offcanvas-end tiarajuzinho-chat" tabindex="-1" id="mcpChatOffcanvas" aria-labelledby="mcpChatOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="mcpChatOffcanvasLabel">
            <button type="button" class="tjz-avatar-zoom-btn p-0 border-0 bg-transparent" data-tjz-zoom="<?= htmlspecialchars($tjzAvatarUrl) ?>" title="Ampliar Tiarajuzinho" aria-label="Ampliar imagem do Tiarajuzinho">
                <img class="tjz-header-avatar" src="<?= htmlspecialchars($tjzAvatarUrl) ?>" alt="Tiarajuzinho" width="42" height="42">
            </button>
            <span class="tjz-header-copy">
                <span>Tiarajuzinho</span>
                <small>Assistente do Portal Tiaraju</small>
            </span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <div id="mcpChatMessages" class="tjz-messages">
            <div class="tjz-welcome">
                <button type="button" class="tjz-avatar-zoom-btn p-0 border-0 bg-transparent" data-tjz-zoom="<?= htmlspecialchars($tjzAvatarUrl) ?>" title="Ampliar Tiarajuzinho" aria-label="Ampliar imagem do Tiarajuzinho">
                    <img src="<?= htmlspecialchars($tjzAvatarUrl) ?>" alt="Tiarajuzinho" width="48" height="48">
                </button>
                <div>
                    <strong>Olá! Eu sou o Tiarajuzinho</strong>
                    <p>Pergunte sobre colaboradores, bloqueios, relatórios do chat ou headcount. Estou aqui para ajudar.</p>
                </div>
            </div>
        </div>
        <form id="mcpChatForm" class="tjz-composer">
            <div class="input-group">
                <textarea class="form-control" id="mcpChatInput" rows="2" placeholder="Pergunte ao Tiarajuzinho..." aria-label="Mensagem para o Tiarajuzinho"></textarea>
                <button class="btn" type="submit" id="mcpChatSendBtn" title="Enviar" aria-label="Enviar">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="tjzAvatarZoomModal" tabindex="-1" aria-labelledby="tjzAvatarZoomModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-transparent border-0 shadow-none">
            <div class="modal-header border-0 pb-0 justify-content-end">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body text-center pt-0">
                <img id="tjzAvatarZoomImg" src="<?= htmlspecialchars($tjzAvatarUrl) ?>" alt="Tiarajuzinho" class="img-fluid rounded-circle tjz-zoom-preview">
                <p id="tjzAvatarZoomModalLabel" class="text-white mt-3 mb-0 fw-semibold">Tiarajuzinho</p>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const form = document.getElementById('mcpChatForm');
    const input = document.getElementById('mcpChatInput');
    const messagesEl = document.getElementById('mcpChatMessages');
    const sendBtn = document.getElementById('mcpChatSendBtn');
    if (!form || !input || !messagesEl || !sendBtn) return;

    <?php
    $chatUserName = trim((string) ($userInfo['name'] ?? $_SESSION['user_name'] ?? 'Você'));
    $chatUserAvatarUrl = '';
    $chatUid = (int) ($userInfo['id'] ?? $_SESSION['user_id'] ?? 0);
    $chatImg = (string) ($userInfo['image'] ?? $_SESSION['user_image'] ?? '');
    if (\App\adms\Helpers\ImageHelper::userImageExists($chatUid, $chatImg)) {
        $chatUserAvatarUrl = \App\adms\Helpers\ImageHelper::getImageUrl('users/' . $chatUid . '/' . $chatImg);
    }
    $chatInitials = '';
    foreach (preg_split('/\s+/u', $chatUserName) ?: [] as $part) {
        if ($part === '') {
            continue;
        }
        $chatInitials .= mb_substr($part, 0, 1, 'UTF-8');
        if (mb_strlen($chatInitials, 'UTF-8') >= 2) {
            break;
        }
    }
    if ($chatInitials === '') {
        $chatInitials = 'U';
    }
    ?>
    const chatUser = {
        name: <?= json_encode($chatUserName, JSON_UNESCAPED_UNICODE) ?>,
        avatarUrl: <?= json_encode($chatUserAvatarUrl, JSON_UNESCAPED_UNICODE) ?>,
        initials: <?= json_encode(mb_strtoupper($chatInitials, 'UTF-8'), JSON_UNESCAPED_UNICODE) ?>
    };
    const tjzBotAvatarUrl = <?= json_encode($tjzAvatarUrl, JSON_UNESCAPED_UNICODE) ?>;

    let chartSeq = 0;
    const chartInstances = [];

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function removeThinking() {
        const thinking = messagesEl.querySelector('.mcp-chat-thinking');
        if (thinking) thinking.remove();
    }

    function createUserAvatarEl() {
        if (chatUser.avatarUrl) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'tjz-avatar-zoom-btn p-0 border-0 bg-transparent flex-shrink-0';
            btn.setAttribute('data-tjz-zoom', chatUser.avatarUrl);
            btn.title = 'Ampliar foto';
            btn.setAttribute('aria-label', 'Ampliar foto de ' + chatUser.name);
            const img = document.createElement('img');
            img.src = chatUser.avatarUrl;
            img.alt = chatUser.name;
            img.className = 'rounded-circle mcp-chat-avatar';
            img.width = 32;
            img.height = 32;
            img.style.objectFit = 'cover';
            img.loading = 'lazy';
            btn.appendChild(img);
            return btn;
        }
        const div = document.createElement('div');
        div.className = 'rounded-circle flex-shrink-0 d-inline-flex align-items-center justify-content-center fw-bold text-secondary mcp-chat-avatar';
        div.style.cssText = 'width:32px;height:32px;background:#ececec;font-size:12px;';
        div.title = chatUser.name;
        div.textContent = chatUser.initials;
        return div;
    }

    function createBotAvatarEl() {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'tjz-avatar-zoom-btn p-0 border-0 bg-transparent flex-shrink-0';
        btn.setAttribute('data-tjz-zoom', tjzBotAvatarUrl);
        btn.title = 'Ampliar Tiarajuzinho';
        btn.setAttribute('aria-label', 'Ampliar imagem do Tiarajuzinho');
        const img = document.createElement('img');
        img.src = tjzBotAvatarUrl;
        img.alt = 'Tiarajuzinho';
        img.className = 'rounded-circle mcp-chat-avatar';
        img.width = 34;
        img.height = 34;
        img.style.objectFit = 'cover';
        img.loading = 'lazy';
        btn.appendChild(img);
        return btn;
    }

    function openTjzAvatarZoom(src) {
        const modalEl = document.getElementById('tjzAvatarZoomModal');
        const imgEl = document.getElementById('tjzAvatarZoomImg');
        if (!modalEl || !imgEl || typeof bootstrap === 'undefined') {
            return;
        }
        imgEl.src = src || tjzBotAvatarUrl;
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-tjz-zoom]');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        openTjzAvatarZoom(btn.getAttribute('data-tjz-zoom') || tjzBotAvatarUrl);
    });

    function appendUserMessage(text) {
        const wrapper = document.createElement('div');
        wrapper.className = 'mb-2 d-flex justify-content-end align-items-end gap-2';
        const bubble = document.createElement('div');
        bubble.className = 'tjz-bubble-user';
        bubble.style.maxWidth = '78%';
        bubble.style.whiteSpace = 'pre-wrap';
        bubble.textContent = text;
        wrapper.appendChild(bubble);
        wrapper.appendChild(createUserAvatarEl());
        messagesEl.appendChild(wrapper);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function appendThinking() {
        const wrapper = document.createElement('div');
        wrapper.className = 'mb-2 d-flex justify-content-start align-items-end gap-2 mcp-chat-thinking';
        const bubble = document.createElement('div');
        bubble.className = 'tjz-bubble-thinking px-3 py-2';
        bubble.textContent = 'Pensando...';
        wrapper.appendChild(createBotAvatarEl());
        wrapper.appendChild(bubble);
        messagesEl.appendChild(wrapper);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function appendAssistantText(text) {
        const wrapper = document.createElement('div');
        wrapper.className = 'mb-2 d-flex justify-content-start align-items-start gap-2';
        const bubble = document.createElement('div');
        bubble.className = 'tjz-bubble-bot';
        bubble.style.maxWidth = '85%';
        bubble.style.whiteSpace = 'pre-wrap';
        bubble.textContent = text;
        wrapper.appendChild(createBotAvatarEl());
        wrapper.appendChild(bubble);
        messagesEl.appendChild(wrapper);
        messagesEl.scrollTop = messagesEl.scrollHeight;
        return bubble;
    }

    function buildTableHtml(rows) {
        if (!Array.isArray(rows) || !rows.length || typeof rows[0] !== 'object' || rows[0] === null) {
            return '';
        }
        const cols = Object.keys(rows[0]);
        let html = '<div class="table-responsive mt-2"><table class="table table-sm table-bordered mb-0 bg-white">';
        html += '<thead><tr>' + cols.map(function (c) {
            return '<th class="small">' + escapeHtml(c) + '</th>';
        }).join('') + '</tr></thead><tbody>';
        rows.slice(0, 15).forEach(function (row) {
            html += '<tr>';
            cols.forEach(function (c) {
                let v = row[c];
                if (v !== null && typeof v === 'object') {
                    v = JSON.stringify(v);
                }
                html += '<td class="small">' + escapeHtml(v == null ? '' : v) + '</td>';
            });
            html += '</tr>';
        });
        html += '</tbody></table></div>';
        return html;
    }

    function mapChartType(type) {
        const t = String(type || 'bar').toLowerCase();
        if (t === 'pie' || t === 'doughnut') return t === 'doughnut' ? 'doughnut' : 'pie';
        if (t === 'line') return 'line';
        return 'bar';
    }

    function renderChart(container, chart) {
        if (!chart || !chart.labels || !chart.values || typeof Chart === 'undefined') {
            return;
        }
        if (!chart.labels.length || chart.labels.length !== chart.values.length) {
            return;
        }
        const wrap = document.createElement('div');
        wrap.className = 'mt-2 p-2 bg-white border rounded';
        wrap.style.height = '220px';
        const canvas = document.createElement('canvas');
        canvas.id = 'mcpChatChart_' + (++chartSeq);
        wrap.appendChild(canvas);
        container.appendChild(wrap);

        const chartType = mapChartType(chart.type);
        const colors = [
            '#0d6efd', '#198754', '#fd7e14', '#6f42c1', '#20c997',
            '#dc3545', '#0dcaf0', '#6610f2', '#ffc107', '#6c757d'
        ];
        const bg = chart.labels.map(function (_, i) { return colors[i % colors.length]; });

        const instance = new Chart(canvas.getContext('2d'), {
            type: chartType,
            data: {
                labels: chart.labels,
                datasets: [{
                    label: chart.title || 'Valores',
                    data: chart.values,
                    backgroundColor: chartType === 'line' ? 'rgba(13,110,253,0.25)' : bg,
                    borderColor: chartType === 'line' ? '#0d6efd' : bg,
                    borderWidth: 1,
                    fill: chartType === 'line'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: chartType === 'pie' || chartType === 'doughnut' },
                    title: { display: !!chart.title, text: chart.title || '' }
                },
                scales: (chartType === 'pie' || chartType === 'doughnut') ? {} : {
                    x: { ticks: { maxRotation: 45, minRotation: 0, font: { size: 10 } } },
                    y: { beginAtZero: true }
                }
            }
        });
        chartInstances.push(instance);
    }

    function resolvePayload(data) {
        if (data && data.payload && typeof data.payload === 'object') {
            return data.payload;
        }
        let reply = data ? data.reply : null;
        if (typeof reply === 'string') {
            try { return JSON.parse(reply); } catch (e) { return { resposta: reply }; }
        }
        if (reply && typeof reply === 'object') {
            return reply;
        }
        return { resposta: String(reply ?? '') };
    }

    function appendAssistantPayload(payload) {
        const text = (payload && payload.resposta) ? String(payload.resposta) : JSON.stringify(payload, null, 2);
        const bubble = appendAssistantText(text);
        const data = payload && payload.data ? payload.data : null;
        if (!data || typeof data !== 'object') {
            return;
        }

        if (Array.isArray(data.rows) && data.rows.length) {
            bubble.insertAdjacentHTML('beforeend', buildTableHtml(data.rows));
        }

        if (data.report_url) {
            const linkWrap = document.createElement('div');
            linkWrap.className = 'mt-2';
            const a = document.createElement('a');
            a.href = String(data.report_url);
            a.className = 'btn btn-sm btn-outline-primary';
            a.target = '_blank';
            a.rel = 'noopener';
            a.textContent = 'Abrir relatório completo';
            linkWrap.appendChild(a);
            bubble.appendChild(linkWrap);
        }

        let chart = data.chart || null;
        if (!chart && Array.isArray(data.by_department) && data.by_department.length) {
            chart = {
                type: 'bar',
                title: 'Por departamento',
                labels: data.by_department.map(function (r) { return r.departamento || ''; }),
                values: data.by_department.map(function (r) { return Number(r.total || 0); })
            };
        }
        if (chart) {
            renderChart(bubble, chart);
        }
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    async function sendMessage(message) {
        appendUserMessage(message);
        input.value = '';
        input.focus();
        sendBtn.disabled = true;
        appendThinking();

        try {
            const response = await fetch("<?= rtrim($_ENV['URL_ADM'], '/') ?>/mcp-chat-api.php", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message })
            });
            const data = await response.json().catch(() => null);
            removeThinking();
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
                appendAssistantText(errorText);
                return;
            }
            appendAssistantPayload(resolvePayload(data));
        } catch (e) {
            removeThinking();
            appendAssistantText('Erro ao comunicar com o assistente MCP.');
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

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.dispatchEvent(new Event('submit', { cancelable: true }));
        }
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
