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
                id="navbarMobileBackBtn"
                class="btn btn-link btn-sm d-inline d-md-none me-2"
                aria-label="Voltar"
                title="Voltar"
                onclick="if (window.tjzTryCloseChat && window.tjzTryCloseChat()) { return; } if (window.history.length > 1) { window.history.back(); } else { window.location.href='<?php echo $_ENV['URL_ADM']; ?>dashboard'; }">
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
        <li class="nav-item me-2 d-none d-md-block">
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
<link rel="stylesheet" href="<?= rtrim($_ENV['URL_ADM'], '/') ?>/public/adms/css/tiarajuzinho-chat.css?v=20">
<script src="<?= rtrim($_ENV['URL_ADM'], '/') ?>/public/adms/vendor/chartjs/chart.umd.min.js" defer></script>
<button class="btn tjz-fab" type="button" id="tjzFabBtn"
        data-bs-toggle="offcanvas" data-bs-target="#mcpChatOffcanvas" aria-controls="mcpChatOffcanvas"
        title="Tiarajuzinho" aria-label="Abrir Tiarajuzinho">
    <img src="<?= htmlspecialchars($tjzAvatarUrl) ?>" alt="Tiarajuzinho" width="52" height="52">
</button>
<script>
(function () {
    var fab = document.getElementById('tjzFabBtn');
    if (fab && fab.parentElement !== document.body) {
        document.body.appendChild(fab);
    }
})();
</script>
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
                    <p>Pergunte sobre colaboradores, salas («agendar» / «salas»), relatórios do chat ou headcount. Estou aqui para ajudar.</p>
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

<div class="modal fade" id="tjzChartZoomModal" tabindex="-1" aria-labelledby="tjzChartZoomModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-md-down">
        <div class="modal-content border-0 bg-dark bg-opacity-75">
            <div class="modal-header border-0">
                <h5 class="modal-title text-white fs-6" id="tjzChartZoomModalLabel">Gráfico</h5>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body d-flex align-items-center justify-content-center p-2 p-md-3">
                <img id="tjzChartZoomImg" src="" alt="Gráfico ampliado" class="img-fluid tjz-chart-zoom-preview">
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

    function appendAssistantText(text, meta) {
        const wrapper = document.createElement('div');
        wrapper.className = 'mb-2 d-flex justify-content-start align-items-start gap-2';
        const bubble = document.createElement('div');
        bubble.className = 'tjz-bubble-bot';
        bubble.style.maxWidth = '85%';
        bubble.style.whiteSpace = 'pre-wrap';
        bubble.style.overflowWrap = 'anywhere';
        bubble.style.wordBreak = 'break-word';
        if (meta && meta.tool) {
            const badge = document.createElement('div');
            badge.className = 'tjz-tool-badge';
            badge.textContent = 'tool: ' + String(meta.tool);
            if (meta.provider) {
                badge.title = 'provider: ' + String(meta.provider);
            }
            bubble.appendChild(badge);
        }
        const body = document.createElement('div');
        body.className = 'tjz-bubble-text';
        body.textContent = text;
        bubble.appendChild(body);
        wrapper.appendChild(createBotAvatarEl());
        wrapper.appendChild(bubble);
        messagesEl.appendChild(wrapper);
        messagesEl.scrollTop = messagesEl.scrollHeight;
        return bubble;
    }

    function buildCompactListHtml(rows, options) {
        if (!Array.isArray(rows) || !rows.length || typeof rows[0] !== 'object' || rows[0] === null) {
            return '';
        }
        const opts = options || {};
        const maxRows = opts.maxRows || 40;
        const cols = Object.keys(rows[0]);
        const titleKey = cols.find(function (c) {
            return /^(nome|name|descri[cç][aã]o|titulo|t[ií]tulo|item)$/i.test(c);
        }) || cols[0];
        const metaKeys = cols.filter(function (c) { return c !== titleKey; });

        let html = '<div class="tjz-list-wrap mt-2">';
        rows.slice(0, maxRows).forEach(function (row, idx) {
            let titleVal = row[titleKey];
            if (titleVal !== null && typeof titleVal === 'object') {
                titleVal = JSON.stringify(titleVal);
            }
            html += '<article class="tjz-list-item">';
            html += '<div class="tjz-list-item-head">';
            html += '<span class="tjz-list-idx">' + (idx + 1) + '</span>';
            html += '<strong class="tjz-list-title">' + escapeHtml(titleVal == null || titleVal === '' ? '—' : titleVal) + '</strong>';
            html += '</div>';
            if (metaKeys.length) {
                html += '<dl class="tjz-list-fields">';
                metaKeys.forEach(function (k) {
                    let v = row[k];
                    if (v !== null && typeof v === 'object') {
                        v = JSON.stringify(v);
                    }
                    if (v == null || v === '' || v === '—') {
                        return;
                    }
                    html += '<div class="tjz-list-field"><dt>' + escapeHtml(k) + '</dt><dd>' + escapeHtml(v) + '</dd></div>';
                });
                html += '</dl>';
            }
            html += '</article>';
        });
        if (rows.length > maxRows) {
            html += '<div class="tjz-table-more">… e mais ' + (rows.length - maxRows) + ' linha(s) no Excel/CSV</div>';
        }
        html += '</div>';
        return html;
    }

    function buildTableHtml(rows, options) {
        if (!Array.isArray(rows) || !rows.length || typeof rows[0] !== 'object' || rows[0] === null) {
            return '';
        }
        const opts = options || {};
        if (opts.compact) {
            return buildCompactListHtml(rows, opts);
        }
        const maxRows = opts.maxRows || 15;
        const cols = Object.keys(rows[0]);
        let html = '<div class="tjz-table-wrap mt-2" tabindex="0" role="region" aria-label="Tabela do resultado (deslize horizontalmente se necessário)"><table class="table table-sm table-bordered mb-0 bg-white tjz-data-table">';
        html += '<thead><tr>' + cols.map(function (c) {
            return '<th>' + escapeHtml(c) + '</th>';
        }).join('') + '</tr></thead><tbody>';
        rows.slice(0, maxRows).forEach(function (row) {
            html += '<tr>';
            cols.forEach(function (c, colIdx) {
                let v = row[c];
                if (v !== null && typeof v === 'object') {
                    v = JSON.stringify(v);
                }
                const wrapClass = colIdx === 0 ? ' class="tjz-cell-primary"' : '';
                html += '<td' + wrapClass + '>' + escapeHtml(v == null ? '' : v) + '</td>';
            });
            html += '</tr>';
        });
        html += '</tbody></table>';
        if (rows.length > maxRows) {
            html += '<div class="tjz-table-more">… e mais ' + (rows.length - maxRows) + ' linha(s) no Excel/CSV</div>';
        }
        html += '</div>';
        return html;
    }

    function mapChartType(type) {
        const t = String(type || 'bar').toLowerCase();
        if (t === 'pie' || t === 'doughnut') return t === 'doughnut' ? 'doughnut' : 'pie';
        if (t === 'line') return 'line';
        return 'bar';
    }

    function slugifyFilename(name) {
        const base = String(name || 'tiarajuzinho')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-zA-Z0-9_-]+/g, '_')
            .replace(/^_+|_+$/g, '')
            .slice(0, 60);
        return (base || 'tiarajuzinho') + '_' + new Date().toISOString().slice(0, 10);
    }

    function triggerBlobDownload(blob, filename) {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(function () { URL.revokeObjectURL(url); }, 1500);
    }

    function rowsToCsv(rows) {
        if (!Array.isArray(rows) || !rows.length) {
            return '';
        }
        const cols = Object.keys(rows[0]);
        const escapeCell = function (v) {
            if (v === null || v === undefined) return '';
            if (typeof v === 'object') v = JSON.stringify(v);
            const s = String(v);
            if (/[",\n\r;]/.test(s)) {
                return '"' + s.replace(/"/g, '""') + '"';
            }
            return s;
        };
        const lines = [cols.map(escapeCell).join(';')];
        rows.forEach(function (row) {
            lines.push(cols.map(function (c) { return escapeCell(row[c]); }).join(';'));
        });
        return lines.join('\r\n');
    }

    function downloadCsv(rows, filename) {
        const csv = rowsToCsv(rows);
        if (!csv) return;
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' });
        triggerBlobDownload(blob, filename.endsWith('.csv') ? filename : filename + '.csv');
    }

    /** Planilha XML simples (.xls) aberta pelo Excel sem biblioteca externa. */
    function downloadExcelXml(rows, filename, sheetTitle) {
        if (!Array.isArray(rows) || !rows.length) return;
        const cols = Object.keys(rows[0]);
        const xmlEscape = function (v) {
            if (v === null || v === undefined) return '';
            if (typeof v === 'object') v = JSON.stringify(v);
            return String(v)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        };
        // Cabeçalho XML montado em partes (short_open_tag no servidor).
        let xml = '<' + '?xml version="1.0" encoding="UTF-8"?>'
            + '<' + '?mso-application progid="Excel.Sheet"?>'
            + '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"'
            + ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
            + '<Worksheet ss:Name="' + xmlEscape((sheetTitle || 'Dados').slice(0, 31)) + '"><Table>';
        xml += '<Row>' + cols.map(function (c) {
            return '<Cell><Data ss:Type="String">' + xmlEscape(c) + '</Data></Cell>';
        }).join('') + '</Row>';
        rows.forEach(function (row) {
            xml += '<Row>';
            cols.forEach(function (c) {
                const raw = row[c];
                const num = typeof raw === 'number' || (typeof raw === 'string' && raw !== '' && !isNaN(Number(raw)));
                if (num && raw !== null && raw !== '') {
                    xml += '<Cell><Data ss:Type="Number">' + xmlEscape(raw) + '</Data></Cell>';
                } else {
                    xml += '<Cell><Data ss:Type="String">' + xmlEscape(raw) + '</Data></Cell>';
                }
            });
            xml += '</Row>';
        });
        xml += '</Table></Worksheet></Workbook>';
        const blob = new Blob([xml], { type: 'application/vnd.ms-excel;charset=utf-8' });
        triggerBlobDownload(blob, filename.endsWith('.xls') ? filename : filename + '.xls');
    }

    /** Exporta o gráfico com fundo branco (canvas transparente vira preto no PNG). */
    function chartToPngDataUrl(chartInstance) {
        if (!chartInstance || !chartInstance.canvas) {
            return '';
        }
        const src = chartInstance.canvas;
        const w = src.width || src.offsetWidth || 0;
        const h = src.height || src.offsetHeight || 0;
        if (!w || !h) {
            return typeof chartInstance.toBase64Image === 'function'
                ? chartInstance.toBase64Image('image/png', 1)
                : '';
        }
        const out = document.createElement('canvas');
        out.width = w;
        out.height = h;
        const ctx = out.getContext('2d');
        if (!ctx) {
            return typeof chartInstance.toBase64Image === 'function'
                ? chartInstance.toBase64Image('image/png', 1)
                : '';
        }
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, w, h);
        ctx.drawImage(src, 0, 0);
        try {
            return out.toDataURL('image/png', 1);
        } catch (e) {
            return typeof chartInstance.toBase64Image === 'function'
                ? chartInstance.toBase64Image('image/png', 1)
                : '';
        }
    }

    function downloadChartPng(chartInstance, filename) {
        const href = chartToPngDataUrl(chartInstance);
        if (!href) {
            return;
        }
        const a = document.createElement('a');
        a.href = href;
        a.download = filename.endsWith('.png') ? filename : filename + '.png';
        document.body.appendChild(a);
        a.click();
        a.remove();
    }

    function openChartZoom(chartInstance, title) {
        const modalEl = document.getElementById('tjzChartZoomModal');
        const imgEl = document.getElementById('tjzChartZoomImg');
        const titleEl = document.getElementById('tjzChartZoomModalLabel');
        if (!modalEl || !imgEl || typeof bootstrap === 'undefined') {
            return;
        }
        const href = chartToPngDataUrl(chartInstance);
        if (!href) {
            return;
        }
        imgEl.src = href;
        imgEl.alt = title || 'Gráfico ampliado';
        if (titleEl) {
            titleEl.textContent = title || 'Gráfico';
        }
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    function normalizeExportRows(data, chart) {
        if (Array.isArray(data.rows) && data.rows.length) {
            return data.rows;
        }
        if (Array.isArray(data.by_month) && data.by_month.length) {
            return data.by_month.map(function (r) {
                return { mes: r.rotulo || r.mes || '', total: r.total };
            });
        }
        if (Array.isArray(data.by_department) && data.by_department.length) {
            return data.by_department.map(function (r) {
                return { departamento: r.departamento || '', total: r.total };
            });
        }
        if (chart && Array.isArray(chart.labels) && Array.isArray(chart.values)) {
            return chart.labels.map(function (label, i) {
                return { item: label, valor: chart.values[i] };
            });
        }
        return [];
    }

    function attachDownloadBar(bubble, opts) {
        const options = opts || {};
        const rows = Array.isArray(options.rows) ? options.rows : [];
        const chartInstance = options.chartInstance || null;
        const reportId = parseInt(options.reportId || 0, 10);
        const title = options.title || 'tiarajuzinho';
        const baseName = slugifyFilename(title);
        const urlAdm = <?= json_encode(rtrim((string)($_ENV['URL_ADM'] ?? ''), '/'), JSON_UNESCAPED_SLASHES) ?> + '/';

        if (!rows.length && !chartInstance && !(reportId > 0)) {
            return;
        }

        const bar = document.createElement('div');
        bar.className = 'tjz-download-bar mt-2';
        bar.setAttribute('role', 'group');
        bar.setAttribute('aria-label', 'Baixar resultado');

        const label = document.createElement('div');
        label.className = 'tjz-download-label';
        label.textContent = 'Baixar';
        bar.appendChild(label);

        const btns = document.createElement('div');
        btns.className = 'tjz-download-actions';

        const addBtn = function (cfg) {
            const btn = document.createElement(cfg.href ? 'a' : 'button');
            if (cfg.href) {
                btn.href = cfg.href;
                btn.target = '_blank';
                btn.rel = 'noopener';
            } else {
                btn.type = 'button';
                btn.addEventListener('click', cfg.onClick);
            }
            btn.className = 'btn btn-sm tjz-download-btn ' + (cfg.className || '');
            btn.title = cfg.title || cfg.label;
            btn.innerHTML = '<i class="' + cfg.icon + ' me-1"></i>' + escapeHtml(cfg.label);
            btns.appendChild(btn);
        };

        if (chartInstance) {
            addBtn({
                label: 'Gráfico PNG',
                icon: 'fas fa-image',
                className: 'btn-outline-secondary',
                title: 'Baixar imagem do gráfico',
                onClick: function () { downloadChartPng(chartInstance, baseName + '_grafico'); }
            });
        }

        if (rows.length) {
            addBtn({
                label: 'Excel',
                icon: 'fas fa-file-excel',
                className: 'btn-outline-success',
                title: 'Baixar planilha Excel (.xls) com os dados do chat',
                onClick: function () { downloadExcelXml(rows, baseName, title); }
            });
            addBtn({
                label: 'CSV',
                icon: 'fas fa-file-csv',
                className: 'btn-outline-secondary',
                title: 'Baixar CSV (abre no Excel)',
                onClick: function () { downloadCsv(rows, baseName); }
            });
        }

        if (reportId > 0) {
            addBtn({
                label: 'Excel completo',
                icon: 'fas fa-file-excel',
                className: 'btn-success',
                title: 'Exportar relatório completo em Excel (.xlsx)',
                href: urlAdm + 'export-dynamic-report-excel/' + reportId
            });
            addBtn({
                label: 'CSV completo',
                icon: 'fas fa-file-csv',
                className: 'btn-outline-secondary',
                title: 'Exportar relatório completo em CSV',
                href: urlAdm + 'export-dynamic-report-csv/' + reportId
            });
            addBtn({
                label: 'PDF',
                icon: 'fas fa-file-pdf',
                className: 'btn-outline-danger',
                title: 'Exportar relatório em PDF',
                href: urlAdm + 'export-dynamic-report-pdf/' + reportId
            });
        }

        bar.appendChild(btns);
        bubble.appendChild(bar);
    }

    function renderChart(container, chart) {
        if (!chart || !chart.labels || !chart.values || typeof Chart === 'undefined') {
            return null;
        }
        if (!chart.labels.length || chart.labels.length !== chart.values.length) {
            return null;
        }
        const wrap = document.createElement('div');
        wrap.className = 'mt-2 p-2 bg-white border rounded tjz-chart-wrap';
        wrap.style.height = '220px';
        wrap.title = 'Clique para ampliar o gráfico';
        wrap.setAttribute('role', 'button');
        wrap.setAttribute('tabindex', '0');
        wrap.setAttribute('aria-label', 'Ampliar gráfico' + (chart.title ? ': ' + chart.title : ''));
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
                backgroundColor: '#ffffff',
                color: '#212529',
                plugins: {
                    legend: {
                        display: chartType === 'pie' || chartType === 'doughnut',
                        labels: { color: '#212529' }
                    },
                    title: {
                        display: !!chart.title,
                        text: chart.title || '',
                        color: '#212529'
                    }
                },
                scales: (chartType === 'pie' || chartType === 'doughnut') ? {} : {
                    x: {
                        ticks: { maxRotation: 45, minRotation: 0, font: { size: 10 }, color: '#495057' },
                        grid: { color: 'rgba(0,0,0,0.08)' }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#495057' },
                        grid: { color: 'rgba(0,0,0,0.08)' }
                    }
                },
                onClick: function () {
                    openChartZoom(instance, chart.title || 'Gráfico');
                }
            }
        });
        wrap.addEventListener('click', function (e) {
            e.preventDefault();
            openChartZoom(instance, chart.title || 'Gráfico');
        });
        wrap.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openChartZoom(instance, chart.title || 'Gráfico');
            }
        });
        chartInstances.push(instance);
        return instance;
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

    function renderRoomsWizardUi(bubble, ui) {
        if (!ui || ui.type !== 'rooms_wizard') {
            return;
        }
        const options = Array.isArray(ui.options) ? ui.options : [];
        const nav = Array.isArray(ui.nav) ? ui.nav : [];
        const room = ui.room && typeof ui.room === 'object' ? ui.room : null;
        if (!options.length && !nav.length && !room) {
            return;
        }
        const wrap = document.createElement('div');
        wrap.className = 'tjz-wizard mt-2';
        if (ui.step === 'pick_room') {
            wrap.classList.add('tjz-wizard-rooms');
        }
        if (ui.layout === 'chips' || ui.step === 'pick_slots') {
            wrap.classList.add('tjz-wizard-chips');
        }
        if (ui.layout === 'row' || ui.step === 'pick_date') {
            wrap.classList.add('tjz-wizard-row');
        }

        if (room && (room.name || room.image_url)) {
            const roomHeader = document.createElement('div');
            roomHeader.className = 'tjz-wizard-room-selected';
            if (room.image_url) {
                const img = document.createElement('img');
                img.src = String(room.image_url);
                img.alt = String(room.name || 'Sala');
                img.className = 'tjz-wizard-room-selected-img';
                img.loading = 'lazy';
                roomHeader.appendChild(img);
            }
            const nameEl = document.createElement('div');
            nameEl.className = 'tjz-wizard-room-selected-name';
            nameEl.textContent = String(room.name || 'Sala');
            roomHeader.appendChild(nameEl);
            wrap.appendChild(roomHeader);
        }

        if (options.length) {
            const optsWrap = document.createElement('div');
            optsWrap.className = (ui.layout === 'chips' || ui.step === 'pick_slots')
                ? 'tjz-wizard-chips-grid'
                : ((ui.layout === 'row' || ui.step === 'pick_date') ? 'tjz-wizard-row-grid' : 'tjz-wizard-list');
            options.forEach(function (opt) {
                const btn = document.createElement('button');
                btn.type = 'button';
                const isChip = ui.layout === 'chips' || ui.step === 'pick_slots' || optsWrap.classList.contains('tjz-wizard-chips-grid');
                const status = String(opt.status || '');
                const disabled = !!opt.disabled || status === 'busy' || status === 'past';
                btn.className = isChip
                    ? 'tjz-wizard-chip'
                    : ('tjz-wizard-option' + (opt.image_url ? ' tjz-wizard-option-room' : ''));
                if (isChip && status === 'busy') {
                    btn.classList.add('tjz-wizard-chip-busy');
                }
                if (isChip && status === 'past') {
                    btn.classList.add('tjz-wizard-chip-past');
                }
                btn.title = String(opt.title || opt.sub || opt.label || opt.value || '');
                if (disabled) {
                    btn.disabled = true;
                    btn.setAttribute('aria-disabled', 'true');
                } else {
                    btn.setAttribute('data-tjz-send', String(opt.value || ''));
                }
                if (!isChip && opt.image_url) {
                    const img = document.createElement('img');
                    img.src = String(opt.image_url);
                    img.alt = String(opt.label || 'Sala');
                    img.className = 'tjz-wizard-room-img';
                    img.loading = 'lazy';
                    btn.appendChild(img);
                }
                if (isChip) {
                    const main = document.createElement('span');
                    main.className = 'tjz-wizard-chip-main';
                    if (status !== 'busy' && status !== 'past' && opt.index != null && opt.time) {
                        const idx = document.createElement('span');
                        idx.className = 'tjz-wizard-chip-index';
                        idx.textContent = String(opt.index);
                        const time = document.createElement('span');
                        time.className = 'tjz-wizard-chip-time';
                        time.textContent = String(opt.time);
                        main.appendChild(idx);
                        main.appendChild(time);
                    } else if (status === 'busy' || status === 'past') {
                        const time = document.createElement('span');
                        time.className = 'tjz-wizard-chip-time';
                        time.textContent = String(opt.time || opt.label || '');
                        main.appendChild(time);
                    } else {
                        main.textContent = String(opt.label || opt.value || '');
                    }
                    btn.appendChild(main);
                    if (status === 'busy') {
                        const whoEl = document.createElement('span');
                        whoEl.className = 'tjz-wizard-chip-who';
                        whoEl.textContent = String(opt.reserved_by || 'Ocupado');
                        btn.appendChild(whoEl);
                        if (opt.department || opt.sub) {
                            const deptEl = document.createElement('span');
                            deptEl.className = 'tjz-wizard-chip-sub';
                            deptEl.textContent = String(opt.department || opt.sub);
                            btn.appendChild(deptEl);
                        }
                    } else if (opt.sub && status === 'past') {
                        const sub = document.createElement('span');
                        sub.className = 'tjz-wizard-chip-sub';
                        sub.textContent = String(opt.sub);
                        btn.appendChild(sub);
                    }
                } else {
                    const copy = document.createElement('span');
                    copy.className = 'tjz-wizard-option-copy';
                    const title = document.createElement('strong');
                    title.textContent = String(opt.label || opt.value || '');
                    copy.appendChild(title);
                    if (opt.sub) {
                        const sub = document.createElement('small');
                        sub.textContent = String(opt.sub);
                        copy.appendChild(sub);
                    }
                    btn.appendChild(copy);
                }
                optsWrap.appendChild(btn);
            });
            wrap.appendChild(optsWrap);
        }

        if (nav.length) {
            const navWrap = document.createElement('div');
            navWrap.className = 'tjz-wizard-nav';
            nav.forEach(function (item) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'tjz-wizard-nav-btn';
                btn.setAttribute('data-tjz-send', String(item.value || ''));
                btn.textContent = String(item.label || item.value || '');
                navWrap.appendChild(btn);
            });
            wrap.appendChild(navWrap);
        }

        if (ui.multi) {
            const hint = document.createElement('div');
            hint.className = 'tjz-wizard-hint';
            hint.textContent = 'Vários horários: digite 3,4,5 ou 3-5.';
            wrap.appendChild(hint);
        }
        bubble.appendChild(wrap);
    }

    function appendAssistantPayload(payload) {
        const text = (payload && payload.resposta) ? String(payload.resposta) : JSON.stringify(payload, null, 2);
        const bubble = appendAssistantText(text, {
            tool: payload && payload.tool ? payload.tool : null,
            provider: payload && payload.provider ? payload.provider : null
        });
        const data = payload && payload.data ? payload.data : null;
        if (!data || typeof data !== 'object') {
            return;
        }

        if (data.ui && data.ui.type === 'rooms_wizard') {
            renderRoomsWizardUi(bubble, data.ui);
        }

        // Tabelas só para indicadores/relatórios — salas/reservas usam texto ou cards.
        const skipTable = !!(data.ui && data.ui.type === 'rooms_wizard')
            || (typeof payload.tool === 'string' && payload.tool.indexOf('rooms.') === 0);
        if (!skipTable && Array.isArray(data.rows) && data.rows.length) {
            const compact = !!(data.ui && data.ui.compact_table);
            bubble.classList.add('tjz-bubble-bot--data');
            bubble.style.maxWidth = '';
            bubble.insertAdjacentHTML('beforeend', buildTableHtml(data.rows, { compact: compact }));
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
        if (!chart && Array.isArray(data.by_month) && data.by_month.length) {
            chart = {
                type: 'bar',
                title: 'Por mês',
                labels: data.by_month.map(function (r) { return r.rotulo || String(r.mes || ''); }),
                values: data.by_month.map(function (r) { return Number(r.total || 0); })
            };
        }
        if (!chart && Array.isArray(data.by_department) && data.by_department.length) {
            chart = {
                type: 'bar',
                title: 'Por departamento',
                labels: data.by_department.map(function (r) { return r.departamento || ''; }),
                values: data.by_department.map(function (r) { return Number(r.total || 0); })
            };
        }
        let chartInstance = null;
        if (chart) {
            chartInstance = renderChart(bubble, chart);
        }

        if (!skipTable) {
            const exportRows = normalizeExportRows(data, chart);
            const title = (data.name || (chart && chart.title) || 'resultado_chat');
            attachDownloadBar(bubble, {
                rows: exportRows,
                chartInstance: chartInstance,
                reportId: data.report_id || 0,
                title: title
            });
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

    messagesEl.addEventListener('click', function (e) {
        const btn = e.target && e.target.closest ? e.target.closest('[data-tjz-send]') : null;
        if (!btn || sendBtn.disabled) return;
        const value = (btn.getAttribute('data-tjz-send') || '').trim();
        if (!value) return;
        e.preventDefault();
        sendMessage(value);
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.dispatchEvent(new Event('submit', { cancelable: true }));
        }
    });

})();
</script>
<script>
(function () {
    /* Voltar do celular: fecha só o chat (sem history.back extra, que saía do app). */
    var offcanvasEl = document.getElementById('mcpChatOffcanvas');
    if (!offcanvasEl) {
        return;
    }
    var chatMarker = false;
    var closingFromPopstate = false;

    window.tjzTryCloseChat = function () {
        if (!offcanvasEl.classList.contains('show') || typeof bootstrap === 'undefined') {
            return false;
        }
        var inst = bootstrap.Offcanvas.getInstance(offcanvasEl)
            || bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl);
        inst.hide();
        return true;
    };

    offcanvasEl.addEventListener('show.bs.offcanvas', function () {
        if (!chatMarker) {
            try {
                history.pushState({ tjzChat: 1 }, '', location.href);
            } catch (e) { /* ignore */ }
            chatMarker = true;
        }
    });

    offcanvasEl.addEventListener('hidden.bs.offcanvas', function () {
        if (closingFromPopstate) {
            closingFromPopstate = false;
            chatMarker = false;
            return;
        }
        /* Fechou pelo X: limpa a marca sem navegar (evita fechar o Portal). */
        if (chatMarker && history.state && history.state.tjzChat) {
            try {
                history.replaceState(null, '', location.href);
            } catch (e) { /* ignore */ }
        }
        chatMarker = false;
    });

    window.addEventListener('popstate', function () {
        if (!offcanvasEl.classList.contains('show') || typeof bootstrap === 'undefined') {
            return;
        }
        closingFromPopstate = true;
        chatMarker = false;
        var inst = bootstrap.Offcanvas.getInstance(offcanvasEl)
            || bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl);
        inst.hide();
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
