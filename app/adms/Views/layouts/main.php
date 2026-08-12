<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Flash legado: expira em 1 request para evitar "vazamento" em páginas aleatórias.
$legacyFlashKeys = ['success', 'error', 'errors', 'msg', 'msg_type', 'msg_warning', 'sucesso', 'erro'];
if (!isset($_SESSION['__adms_request_seq']) || !is_int($_SESSION['__adms_request_seq'])) {
    $_SESSION['__adms_request_seq'] = 0;
}
$_SESSION['__adms_request_seq']++;
$admsCurrentRequestSeq = (int)$_SESSION['__adms_request_seq'];
$hasLegacyFlash = false;
foreach ($legacyFlashKeys as $legacyKey) {
    if (isset($_SESSION[$legacyKey])) {
        $hasLegacyFlash = true;
        break;
    }
}
if ($hasLegacyFlash) {
    if (!isset($_SESSION['__adms_legacy_flash_request_seq'])) {
        $_SESSION['__adms_legacy_flash_request_seq'] = $admsCurrentRequestSeq;
    } elseif ((int)$_SESSION['__adms_legacy_flash_request_seq'] < $admsCurrentRequestSeq) {
        foreach ($legacyFlashKeys as $legacyKey) {
            unset($_SESSION[$legacyKey]);
        }
        unset($_SESSION['__adms_legacy_flash_request_seq']);
    }
} else {
    unset($_SESSION['__adms_legacy_flash_request_seq']);
}

\App\adms\Helpers\SlowRequestProfilerHelper::registerRequestStart();
\App\adms\Helpers\SlowRequestProfilerHelper::registerShutdownProfiler();

/**
 * Logs de sessão (diagnóstico) devem ficar desligados em produção por padrão,
 * pois escrita em disco a cada requisição degrada navegação.
 */
$sessionDebugLogsEnabled = \App\adms\Helpers\LogSettingsHelper::isSessionDebugEnabled();

if ($sessionDebugLogsEnabled) {
    @file_put_contents(__DIR__ . '/../../logs/php_session_config.log',
        date('Y-m-d H:i:s') .
        ' gc_maxlifetime=' . ini_get('session.gc_maxlifetime') .
        ' cookie_lifetime=' . ini_get('session.cookie_lifetime') .
        ' save_path=' . ini_get('session.save_path') .
        PHP_EOL,
        FILE_APPEND
    );
}

// ForÃ§a cabeÃ§alhos/ambiente UTF-8 na resposta HTML
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}
if (function_exists('ini_set')) {
    ini_set('default_charset', 'UTF-8');
}
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}
if (function_exists('mb_http_output')) {
    mb_http_output('UTF-8');
}

// Expor dados básicos do usuário logado para o front-end (comparações e tela de bloqueio)
// Importante: não imprimir nada antes do <!DOCTYPE> para não quebrar o "standards mode".
$currentUserMetaTag = '';
$currentUserScriptTag = '';
$isPermissionPage = strpos($this->view, 'permission/list.php') !== false;
$viewPath = (string)$this->view;
$viewFile = strtolower((string)basename($viewPath));
$viewDir = strtolower((string)basename((string)dirname($viewPath)));

// Matriz explícita: páginas que normalmente usam DataTables/máscaras.
$dataTablesViewFiles = [
    'list.php', 'relatorioresultado.php', 'traininghistory.php',
    'kpidashboard.php', 'people_analytics.php', 'view.php'
];
$dataTablesViewDirs = [
    'informativos', 'policies', 'reports', 'trainings', 'analytics', 'performance',
    'pay', 'receive', 'users', 'logs', 'rooms', 'crm', 'lgpd'
];

$inputMaskViewFiles = [
    'create.php', 'update.php', 'profile.php', 'index.php', 'view.php'
];
$inputMaskViewDirs = [
    'profile', 'pay', 'receive', 'rooms', 'users', 'portal', 'crm', 'departments',
    'positions', 'costcenter', 'accesslevels', 'policies', 'informativos', 'lgpd'
];

$loadDataTables = in_array($viewFile, $dataTablesViewFiles, true) || in_array($viewDir, $dataTablesViewDirs, true);
$loadInputMasks = in_array($viewFile, $inputMaskViewFiles, true) || in_array($viewDir, $inputMaskViewDirs, true);

// Fallback defensivo para não quebrar telas fora da matriz.
if (!$loadDataTables) {
    $loadDataTables = (bool)preg_match('/\/(list|relatorio|report|analytics|matrix|history|kpi)/i', $viewPath);
}
if (!$loadInputMasks) {
    $loadInputMasks = (bool)preg_match('/\/(create|update|profile|pay|receive|rooms|users|portal|crm|departments|positions|costCenter|accessLevels)/i', $viewPath);
}
$userAvatarPreloadUrl = null;
if (!headers_sent() && isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
    $userName = $_SESSION['user_name'] ?? 'Usuário';
    $userImageName = trim((string)($_SESSION['user_image'] ?? ''));
    if (\App\adms\Helpers\ImageHelper::userImageExists($userId, $userImageName)) {
        $userAvatarPreloadUrl = \App\adms\Helpers\ImageHelper::getImageUrl(
            'users/' . $userId . '/' . $userImageName
        );
    }
    $currentUserMetaTag = '<meta name="current-user-id" content="' . $userId . '">';
    $currentUserScriptTag = '<script>' .
        'window.currentUserId = ' . $userId . ';' .
        'window.currentUserName = ' . json_encode($userName, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';' .
    '</script>';
}

$pushSessionSyncInit = null;
if (!empty($_SESSION['user_id'])) {
    $pushSessionSyncInit = [
        'urlAdm' => rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/'),
        'csrfToken' => \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_push_subscribe'),
        'userId' => (int) $_SESSION['user_id'],
    ];
}

// Teste de execuÃ§Ã£o do layout
// echo "<!-- LAYOUT MAIN EXECUTADO -->";

// Log alternativo com caminho absoluto
// file_put_contents(__DIR__ . '/../../logs/session_debug2.log',
//     date('Y-m-d H:i:s') . ' - [main] session_id(): ' . session_id() .
//     ' | $_SESSION[session_id]: ' . ($_SESSION['session_id'] ?? 'null') .
//     ' | Cookie PHPSESSID: ' . ($_COOKIE['PHPSESSID'] ?? 'null') .
//     ' | $_SESSION: ' . json_encode($_SESSION) . "\n",
//     FILE_APPEND
// );

// Log de inÃ­cio do layout para capturar erros fatais
// file_put_contents(__DIR__ . '/../../logs/session_debug2.log',
//     date('Y-m-d H:i:s') . ' - [LAYOUT] INICIO RENDERIZACAO - session_id: ' . (session_id() ?: 'null') .
//     ' | _SESSION: ' . json_encode($_SESSION) .
//     ' | URL: ' . ($_SERVER['REQUEST_URI'] ?? 'null') .
//     ' | GET: ' . json_encode($_GET) .
//     ' | POST: ' . json_encode($_POST) . "\n",
//     FILE_APPEND
// );



// Verificar se hÃ¡ erros fatais
$error = error_get_last();
if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
    file_put_contents(__DIR__ . '/../../logs/session_debug2.log',
        date('Y-m-d H:i:s') . ' - [LAYOUT] ERRO FATAL DETECTADO - ' . json_encode($error) . "\n",
        FILE_APPEND
    );
}

if (!isset($_ENV['DB_HOST'])) {
    require_once __DIR__ . '/../../Helpers/EnvLoader.php';
    \App\adms\Helpers\EnvLoader::load();
}
// Supondo que o .env jÃ¡ esteja carregado via alguma lib tipo vlucas/phpdotenv
$urlAdm = getenv('URL_ADM');

if (!isset($_SESSION['session_id']) || $_SESSION['session_id'] !== session_id()) {
    $_SESSION['session_id'] = session_id();
}

// Defaults para evitar variáveis indefinidas em telas sem sessão autenticada.
$limite = 1800;
$lockOffsetMinutes = 1;

// Validação de sessão + heartbeat (cache leve — evita SELECT/UPDATE em toda navegação)
if (isset($_SESSION['user_id'], $_SESSION['session_id'])) {
    $sessionLayout = \App\adms\Helpers\AdmsLayoutSessionHelper::validateAndTouchSession();
    $limite = (int) ($sessionLayout['limite'] ?? 1800);
    $lockOffsetMinutes = (int) ($sessionLayout['lock_offset_minutes'] ?? 1);
}

// Log temporÃ¡rio desativado no servidor (evita erro quando sem diretÃ³rio logs)
// file_put_contents('caminho_do_log', 'session_id: ' . session_id() . ' - ' . json_encode($_SESSION) . PHP_EOL, FILE_APPEND);
?>
<!DOCTYPE html>
<html lang="<?php echo $_ENV['APP_LOCALE']; ?>">

<head>
    <meta charset="UTF-8">
    <?php
    // Renderiza após o <!DOCTYPE> para manter o documento em standards mode
    // (TinyMCE exige standards mode para inicializar).
    if (!empty($currentUserMetaTag)) {
        echo $currentUserMetaTag . PHP_EOL . $currentUserScriptTag;
    }
    ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="<?php echo $_ENV['URL_ADM']; ?>public/adms/image/icon/favicon.ico">
    <link rel="apple-touch-icon" href="<?php echo $_ENV['URL_ADM']; ?>public/adms/uploads/users/1/pwa-icon-512.png">
    <link rel="manifest" href="<?php echo $_ENV['URL_ADM']; ?>public/adms/manifest.json?v=20260520-4">
    <?php if (!empty($userAvatarPreloadUrl)): ?>
    <link rel="preload" as="image" href="<?php echo htmlspecialchars($userAvatarPreloadUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <meta name="theme-color" content="#2E9263">
    <?php if (!empty($_SESSION['user_id'])): ?>
    <?php
    $admsPageMenuSlug = htmlspecialchars((string) ($this->data['menu'] ?? ''), ENT_QUOTES, 'UTF-8');
    $admsHelpBaseUrl = htmlspecialchars(rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/context-help', ENT_QUOTES, 'UTF-8');
    ?>
    <meta name="adms-page-menu" content="<?= $admsPageMenuSlug ?>">
    <script>
    window.__admsPageMenu = <?= json_encode((string) ($this->data['menu'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    window.__admsHelpBaseUrl = <?= json_encode(rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/context-help', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script>
    window.__admsDeferredInstallPrompt = null;
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        window.__admsDeferredInstallPrompt = e;
        window.dispatchEvent(new Event('adms-pwa-installable'));
    });
    window.addEventListener('appinstalled', function () {
        window.__admsDeferredInstallPrompt = null;
    });
    </script>
    <?php endif; ?>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Tiaraju">

    

    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM'] ?>public/adms/css/sbadmin.css?v=20250822">

    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM'] ?>public/adms/css/bootstrap.min.css">

    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM'] ?>public/adms/css/styles_admin.css">

    <?php if ($loadDataTables): ?>
    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM'] ?>public/adms/DataTables/datatables.min.css">
    <?php endif; ?>

    <!-- Removido Font Awesome JS externo para evitar aviso de header nosniff; CSS jÃ¡ cobre os Ã­cones -->

    <!-- Font Awesome local (self-host) -->
    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM']; ?>public/adms/fontawesome/css/all.min.css?v=20250822">

    <!-- CSS Reset e Ajustes de PadronizaÃ§Ã£o -->
    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM']; ?>public/adms/css/reset.css?v=20250822">
    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM']; ?>public/adms/css/custom-ajustes.css?v=20250822">
    
    <!-- CSS personalizado do projeto (deve ficar por último para sobrescrever) -->
    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM'] ?>public/adms/css/custom_adms.css?v=20260731f">
    
    <!-- Menu Modernizado -->
    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM'] ?>public/adms/css/menu-modern.css?v=20260731g">
    
    <!-- Sistema Responsivo para Diferentes ResoluÃ§Ãµes -->
    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM']; ?>public/adms/css/responsive-screens.css">

    <!-- CSS específico para página de permissões (após menu-modern: sticky com !important) -->
    <?php if ($isPermissionPage): ?>
    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM']; ?>public/adms/css/permission-list.css?v=20260729d">
    <?php endif; ?>

    <!-- JQ por CDN -->
    <!-- <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script> -->

    <!-- JQuery local -->
    <script src="<?php echo $_ENV['URL_ADM'] ?>public/adms/jquery/jquery-3.7.1.min.js"></script>


    <!-- <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->



    <title>
        <?php
        echo $_ENV['APP_NAME'] . " - " . ($this->data['title_head'] ?? "");
        ?>
    </title>
    <style>
        .adms-inline-alert { border-radius: 12px; border: 0; box-shadow: 0 4px 14px rgba(0,0,0,.08); }
        .adms-inline-feedback-global {
            position: fixed;
            top: 74px;
            right: 14px;
            z-index: 1080;
            width: min(460px, calc(100vw - 28px));
            display: flex;
            flex-direction: column;
            gap: .5rem;
            pointer-events: none;
        }
        .adms-inline-feedback-global .alert { pointer-events: auto; margin-bottom: 0; }
        @media (max-width: 767.98px) {
            .adms-inline-feedback-global { top: 66px; right: 10px; width: calc(100vw - 20px); }
        }
    </style>


</head>

<body class="sb-nav-fixed">
    <div id="admsInlineFeedbackGlobal" class="adms-inline-feedback-global" aria-live="polite" aria-atomic="true"></div>

    <?php include 'app/adms/Views/partials/navbar.php'; ?>

    <div id="layoutSidenav">

        <?php include 'app/adms/Views/partials/menu.php'; ?>

        <div id="layoutSidenav_content">
            <main>

                <?php
                if ($pushSessionSyncInit !== null) {
                    include 'app/adms/Views/partials/pwa_dashboard_cards.php';
                }
                // Inclui o conteÃºdo principal da pÃ¡gina, que Ã© especificado pela propriedade $this->view. Este arquivo Ã© dinÃ¢mico e pode variar conforme a lÃ³gica do controlador ou o contexto da pÃ¡gina.
                include $this->view;

                ?>
            </main>

            <footer class="py-4 bg-light mt-auto adms-footer">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small adms-footer-row">
                        <div class="adms-footer-links small">
                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd" class="text-decoration-none">LGPD</a>
                            &middot;
                            <a href="<?php echo $_ENV['URL_ADM']; ?>politica-privacidade" class="text-decoration-none">Políticas de Privacidade</a>
                            &middot;
                            <a href="<?php echo $_ENV['URL_ADM']; ?>termos-de-uso" class="text-decoration-none">Termos de Uso</a>
                            <?php if (!empty($_SESSION['user_id'])): ?>
                            &middot;
                            <?php include __DIR__ . '/../partials/context_help_hint.php'; ?>
                            <?php endif; ?>
                        </div>
                        <div class="text-muted adms-footer-copyright small">
                            Copyright &copy; <?php echo $_ENV['APP_NAME'] . " " . date("Y"); ?>
                        </div>
                    </div>
                </div>
            </footer>

    <?php
    // Botão flutuante de "Voltar" foi removido para evitar aparecer no final da página.
    // O retorno em mobile deve ser feito sempre via botão na navbar (ver navbar.php).
    ?>

        </div>
    </div>

    <script defer src="<?php echo $_ENV['URL_ADM'] ?>public/adms/js/bootstrap.bundle.min.js"></script>
    <script>
    (function () {
        function esc(str) {
            return String(str || '').replace(/[&<>"']/g, function (c) {
                return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];
            });
        }
        function iconFor(type) {
            if (type === 'success') return 'fa-check-circle';
            if (type === 'danger') return 'fa-circle-exclamation';
            if (type === 'info') return 'fa-circle-info';
            return 'fa-triangle-exclamation';
        }
        function ensureWrap() {
            var el = document.getElementById('admsInlineFeedbackGlobal');
            if (!el) {
                el = document.createElement('div');
                el.id = 'admsInlineFeedbackGlobal';
                el.className = 'adms-inline-feedback-global';
                el.setAttribute('aria-live', 'polite');
                el.setAttribute('aria-atomic', 'true');
                document.body.appendChild(el);
            }
            return el;
        }
        window.AdmsFeedback = window.AdmsFeedback || {
            show: function (message, type, timeoutMs) {
                var safeType = ['success', 'warning', 'danger', 'info'].indexOf(type) >= 0 ? type : 'warning';
                var wrap = ensureWrap();
                var item = document.createElement('div');
                item.innerHTML =
                    '<div class="alert alert-' + safeType + ' adms-inline-alert d-flex align-items-start gap-2" role="alert">' +
                        '<i class="fas ' + iconFor(safeType) + ' mt-1" aria-hidden="true"></i>' +
                        '<div class="flex-grow-1">' + esc(message || 'Ocorreu uma notificação.') + '</div>' +
                        '<button type="button" class="btn-close ms-2" aria-label="Fechar"></button>' +
                    '</div>';
                var node = item.firstChild;
                wrap.prepend(node);
                var closeBtn = node.querySelector('.btn-close');
                if (closeBtn) {
                    closeBtn.addEventListener('click', function () { node.remove(); });
                }
                var ttl = typeof timeoutMs === 'number' ? timeoutMs : 7000;
                if (ttl > 0) {
                    setTimeout(function () { if (node && node.parentNode) node.remove(); }, ttl);
                }
            }
        };
        window.alert = function (message) {
            window.AdmsFeedback.show(message || 'Atenção.', 'warning');
        };
    })();
    </script>

    <script defer src="<?php echo $_ENV['URL_ADM'] ?>public/adms/js/swal-lite.js?v=20260420-1"></script>
    <script defer src="<?php echo $_ENV['URL_ADM'] ?>public/adms/js/sbadmin.js"></script>
    <script defer src="<?php echo $_ENV['URL_ADM'] ?>public/adms/js/script_admin.js"></script>
    <?php if (!empty($_SESSION['user_id'])): ?>
    <script defer src="<?php echo $_ENV['URL_ADM'] ?>public/adms/js/context-help.js?v=20260626d"></script>
    <?php endif; ?>
    
    <!-- Fix para garantir funcionamento do menu toggle -->
    <script defer src="<?php echo $_ENV['URL_ADM'] ?>public/adms/js/menu-toggle-fix.js"></script>

    <!-- Fix para modais travados em mobile (inclui mover .modal para body — stacking context) -->
    <script defer src="<?php echo $_ENV['URL_ADM'] ?>public/adms/js/modal-fix.js?v=20260213"></script>
    
    <!-- Fix para garantir que formulários funcionem em mobile -->
    <!-- TEMPORARIAMENTE DESABILITADO para testar modais -->
    <!-- <script src="<?php echo $_ENV['URL_ADM'] ?>public/adms/js/forms-mobile-fix.js"></script> -->

    <!-- Script para rolar automaticamente para o item ativo do menu -->
    <script defer src="<?php echo $_ENV['URL_ADM'] ?>public/adms/js/menu-scroll.js?v=20260617d"></script>
    
    <!-- Pesquisa no menu -->
    <script defer src="<?php echo $_ENV['URL_ADM'] ?>public/adms/js/menu-search.js"></script>
    
    <!-- Otimizações para dispositivos móveis -->
    <!-- TEMPORARIAMENTE DESABILITADO para testes -->
    <!-- <script src="<?php echo $_ENV['URL_ADM'] ?>public/adms/js/menu-mobile.js"></script> -->

    <?php if ($loadDataTables): ?>
    <script defer src="<?php echo $_ENV['URL_ADM'] ?>public/adms/DataTables/datatables.min.js"></script>
    <?php endif; ?>

    <?php if ($loadInputMasks): ?>
    <script defer src="<?php echo $_ENV['URL_ADM'] ?>public/adms/js/telefone-mascara.js"></script>
    <script defer src="<?php echo $_ENV['URL_ADM'] ?>public/adms/js/mascaras.js"></script>
    <?php endif; ?>
    
    <!-- Sistema Responsivo para Diferentes ResoluÃ§Ãµes (opcional) -->
    <?php if (($_ENV['USE_SCREEN_RESOLUTION'] ?? 'NÃ£o') === 'Sim'): ?>
    <script defer src="<?php echo $_ENV['URL_ADM']; ?>public/adms/js/screen-resolution.js"></script>
    <?php endif; ?>

    <!-- Configurações de Sessão da Política de Senhas -->
    <script>
        window.sessionConfig = {
            enabled: <?php echo json_encode($expirarPorTempo ?? false); ?>,
            timeoutMinutes: <?php echo json_encode(($policy && isset($policy->tempo_expiracao_sessao)) ? (int)$policy->tempo_expiracao_sessao : 30); ?>,
            warningTime: <?php echo json_encode($limite * 1000); ?>, // Converter para milissegundos
            lockOffsetMinutes: <?php echo json_encode($lockOffsetMinutes); ?>
        };
    </script>

    <!-- Verificação Automática de Sessão -->
    <script defer src="<?php echo $_ENV['URL_ADM']; ?>public/adms/js/session-checker.js?v=20260416"></script>

    <!-- Responsividade genÃ©rica de listas (desktop x mobile) -->
    <script defer src="<?php echo $_ENV['URL_ADM']; ?>public/adms/js/responsive-list.js"></script>

    <script>
    // Controle global de histórico para modais (apenas mobile/PWA em Android/iOS)
    (function() {
        // Dependemos de Bootstrap 5 estar carregado
        if (typeof bootstrap === 'undefined') {
            return;
        }

        // Em desktop (navegação web ou PWA desktop) não usamos history para modais,
        // pois o botão "Voltar" do navegador já é acessível e o comportamento
        // automático estava fechando janelas indevidamente.
        var ua = navigator.userAgent || '';
        var isMobileLike = /Android|iPhone|iPad|iPod/i.test(ua);
        if (!isMobileLike) {
            return;
        }

        let closingFromPopstate = false;
        let hasSeenFirstPopstate = false;

        function getOpenModalsInOrder() {
            return Array.from(document.querySelectorAll('.modal.show'));
        }

        // Quando uma modal é aberta, empilha uma entrada no histórico
        document.addEventListener('shown.bs.modal', function (event) {
            const el = event.target;
            if (!el || el.dataset.historyPushed === '1') {
                return;
            }
            try {
                history.pushState(
                    { modal: true, modalId: el.id || null },
                    '',
                    window.location.href
                );
                el.dataset.historyPushed = '1';
            } catch (e) {
                console.warn('pushState modal falhou:', e);
            }
        });

        // Quando uma modal é fechada normalmente (botão X, etc.)
        document.addEventListener('hidden.bs.modal', function (event) {
            const el = event.target;
            if (!el || el.dataset.historyPushed !== '1') {
                return;
            }

            if (closingFromPopstate) {
                // Fechamento já veio de um popstate; não chamar history.back de novo
                delete el.dataset.historyPushed;
                closingFromPopstate = false;
                return;
            }

            delete el.dataset.historyPushed;
            try {
                history.back();
            } catch (e) {
                console.warn('history.back ao fechar modal falhou:', e);
            }
        });

        // Botão físico "Voltar" (PWA / mobile) → fecha apenas a modal do topo
        window.addEventListener('popstate', function (event) {
            // Alguns WebViews disparam um popstate “fantasma” na carga (state null, sem modais).
            // Não ignorar o primeiro evento se já houver modal aberta: ao voltar, o estado
            // ativo costuma ser null (entrada anterior ao pushState), não { modal: true }.
            if (!hasSeenFirstPopstate) {
                hasSeenFirstPopstate = true;
                if (event.state == null && getOpenModalsInOrder().length === 0) {
                    return;
                }
            }

            const openModals = getOpenModalsInOrder();
            if (openModals.length === 0) {
                return;
            }

            const topModal = openModals[openModals.length - 1];
            if (topModal.dataset.historyPushed !== '1') {
                return;
            }

            closingFromPopstate = true;
            try {
                const inst = bootstrap.Modal.getInstance(topModal)
                    || bootstrap.Modal.getOrCreateInstance(topModal);
                inst.hide();
            } catch (e) {
                console.warn('Erro ao fechar modal via popstate:', e);
                closingFromPopstate = false;
            }
        });
    })();
    </script>

    <?php if ($pushSessionSyncInit !== null): ?>
    <script>
    window.__PushSessionSyncInit = <?php echo json_encode($pushSessionSyncInit, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    window.__PwaAppInit = {
        urlAdm: <?php echo json_encode(rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/'), JSON_UNESCAPED_SLASHES); ?>,
        csrfToken: <?php echo json_encode($pushSessionSyncInit['csrfToken'], JSON_UNESCAPED_UNICODE); ?>
    };
    </script>
    <script src="<?php echo htmlspecialchars($pushSessionSyncInit['urlAdm'], ENT_QUOTES, 'UTF-8'); ?>/public/adms/js/push-session-sync.js?v=5"></script>
    <script src="<?php echo htmlspecialchars($pushSessionSyncInit['urlAdm'], ENT_QUOTES, 'UTF-8'); ?>/public/adms/js/pwa-app.js?v=11"></script>
    <?php endif; ?>

    <script>
    // Fallbacks para carregamento tardio sem quebrar páginas
    window.admsLoadScriptOnce = function (url, marker) {
        return new Promise(function (resolve, reject) {
            if (marker && document.querySelector('script[data-marker="' + marker + '"]')) {
                resolve();
                return;
            }
            var s = document.createElement('script');
            s.src = url;
            s.defer = true;
            if (marker) s.setAttribute('data-marker', marker);
            s.onload = function () { resolve(); };
            s.onerror = reject;
            document.head.appendChild(s);
        });
    };
    window.ensureDataTablesLoaded = function () {
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable) {
            return Promise.resolve();
        }
        return window.admsLoadScriptOnce('<?php echo $_ENV['URL_ADM'] ?>public/adms/DataTables/datatables.min.js', 'adms-datatables');
    };
    window.ensureMasksLoaded = function () {
        return Promise.all([
            window.admsLoadScriptOnce('<?php echo $_ENV['URL_ADM'] ?>public/adms/js/telefone-mascara.js', 'adms-tel-mask'),
            window.admsLoadScriptOnce('<?php echo $_ENV['URL_ADM'] ?>public/adms/js/mascaras.js', 'adms-masks')
        ]);
    };
    </script>

    <!-- JavaScript específico para página de permissões -->
    <?php if ($isPermissionPage): ?>
    <script defer src="<?php echo $_ENV['URL_ADM']; ?>public/adms/js/permission-list.js?v=20260729d"></script>
    <?php endif; ?>

    <!-- Bootstrap Bundle com Popper.js -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->

    <!-- (Removido) item "Minhas Avaliações" que estava sendo renderizado fora do navbar -->

    <script>
    // Intercepta todas as respostas fetch
    (function() {
        if (!window.fetch) return;
        const originalFetch = window.fetch;
        window.fetch = function() {
            return originalFetch.apply(this, arguments).then(async response => {
                try {
                    const contentType = (response.headers.get('content-type') || '').toLowerCase();
                    if (contentType.indexOf('application/json') === -1) {
                        return response;
                    }
                    const cloned = response.clone();
                    const data = await cloned.json();
                    if (data && data.logout) {
                        if (window.AdmsFeedback && typeof window.AdmsFeedback.show === 'function') {
                            window.AdmsFeedback.show(data.message || "Sua sessão foi encerrada. Faça login novamente.", 'warning', 5000);
                        }
                        window.location.href = "<?php echo $_ENV['URL_ADM']; ?>login";
                        return Promise.reject("SessÃ£o encerrada");
                    }
                } catch (e) { /* NÃ£o Ã© JSON, ignora */ }
                return response;
            });
        };
    })();
    // Intercepta todas as respostas AJAX do jQuery
    if (window.jQuery) {
        $(document).ajaxSuccess(function(event, xhr, settings) {
            try {
                const data = JSON.parse(xhr.responseText);
                if (data && data.logout) {
                    if (window.AdmsFeedback && typeof window.AdmsFeedback.show === 'function') {
                        window.AdmsFeedback.show(data.message || "Sua sessão foi encerrada. Faça login novamente.", 'warning', 5000);
                    }
                    window.location.href = "<?php echo $_ENV['URL_ADM']; ?>login";
                }
            } catch (e) { /* NÃ£o Ã© JSON, ignora */ }
        });
    }
    </script>

    <script>
        window.ADMS_DEBUG_LOGS = <?php echo \App\adms\Helpers\LogSettingsHelper::isFrontendDebugEnabled() ? 'true' : 'false'; ?>;
        const ADMS_DEBUG_LOGS = window.ADMS_DEBUG_LOGS === true;
        function admsLog() {
            if (!ADMS_DEBUG_LOGS) return;
            console.log.apply(console, arguments);
        }
        function admsWarn() {
            if (!ADMS_DEBUG_LOGS) return;
            console.warn.apply(console, arguments);
        }
        function admsError() {
            if (!ADMS_DEBUG_LOGS) return;
            console.error.apply(console, arguments);
        }

        // Restaurar contexto da aplicação após login
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar se há contexto salvo para esta aba específica
            const tabId = getTabIdFromAnyStorage();
            if (tabId) {
                restoreApplicationContextForTab(tabId);
            }
        });

        // Função robusta para obter Tab ID de qualquer storage
        function getTabIdFromAnyStorage() {
            admsLog('Buscando Tab ID em todos os storages disponíveis...');
            
            // Detectar navegador
            const userAgent = navigator.userAgent;
            const isFirefox = userAgent.includes('Firefox');
            
            if (isFirefox) {
                admsLog('Firefox detectado - usando estratégia específica');
                return getTabIdFromFirefox();
            }
            
            // Estratégia padrão para outros navegadores
            const storages = [
                { name: 'localStorage', get: () => localStorage.getItem('current_tab_id') },
                { name: 'sessionStorage', get: () => sessionStorage.getItem('current_tab_id') },
                { name: 'cookies', get: () => {
                    const cookies = document.cookie.split(';');
                    for (let cookie of cookies) {
                        const [cookieKey, cookieValue] = cookie.trim().split('=');
                        if (cookieKey === 'current_tab_id') {
                            return decodeURIComponent(cookieValue);
                        }
                    }
                    return null;
                }},
                { name: 'global', get: () => window.currentTabId }
            ];
            
            for (let storage of storages) {
                try {
                    const tabId = storage.get();
                    if (tabId) {
                        admsLog(`Tab ID encontrado em ${storage.name}:`, tabId);
                        return tabId;
                    }
                } catch (error) {
                    admsWarn(`Erro ao ler de ${storage.name}:`, error);
                }
            }
            
            admsLog('Nenhum Tab ID encontrado em nenhum storage');
            return null;
        }
        
        // Estratégia específica para Firefox
        function getTabIdFromFirefox() {
            admsLog('Firefox: Buscando Tab ID em todos os métodos disponíveis...');
            
            const firefoxMethods = [
                { name: 'localStorage', get: () => localStorage.getItem('current_tab_id') },
                { name: 'sessionStorage', get: () => sessionStorage.getItem('current_tab_id') },
                { name: 'cookies', get: () => {
                    const cookies = document.cookie.split(';');
                    for (let cookie of cookies) {
                        const [cookieKey, cookieValue] = cookie.trim().split('=');
                        if (cookieKey === 'current_tab_id') {
                            return decodeURIComponent(cookieValue);
                        }
                    }
                    return null;
                }},
                { name: 'global', get: () => window.currentTabId },
                { name: 'firefoxGlobal', get: () => window.firefoxTabId },
                { name: 'DOM', get: () => document.documentElement.getAttribute('data-tab-id') },
                { name: 'metaTag', get: () => document.querySelector('meta[name="firefox-tab-id"]')?.content },
                { name: 'title', get: () => {
                    const titleMatch = document.title.match(/\[TAB:([^\]]+)\]/);
                    return titleMatch ? titleMatch[1] : null;
                }}
            ];
            
            for (let method of firefoxMethods) {
                try {
                    const tabId = method.get();
                    if (tabId) {
                        admsLog(`Firefox: Tab ID encontrado em ${method.name}:`, tabId);
                        return tabId;
                    }
                } catch (error) {
                    admsWarn(`Firefox: Erro ao ler de ${method.name}:`, error);
                }
            }
            
            admsLog('Firefox: Nenhum Tab ID encontrado em nenhum método');
            return null;
        }

        // Função para restaurar contexto da aplicação para uma aba específica
        function restoreApplicationContextForTab(tabId) {
            try {
                admsLog('Restaurando contexto para aba:', tabId);
                
                // Restaurar posição de scroll
                const savedScroll = getDataFromAnyStorage(`scroll_position_${tabId}`);
                if (savedScroll) {
                    const scrollData = savedScroll;
                    const timeDiff = Date.now() - scrollData.timestamp;
                    
                    // Restaurar apenas se não for muito antigo (menos de 1 hora)
                    if (timeDiff < 3600000) {
                        setTimeout(() => {
                            window.scrollTo(scrollData.x, scrollData.y);
                            admsLog('Posição de scroll restaurada para aba:', tabId);
                        }, 500);
                    }
                    
                    // Limpar dados antigos
                    clearDataFromAnyStorage(`scroll_position_${tabId}`);
                }

                // Restaurar dados de formulários
                const formKeys = getAllFormKeys(tabId);
                formKeys.forEach(key => {
                    const formData = getDataFromAnyStorage(key);
                    if (formData) {
                        const timeDiff = Date.now() - formData.timestamp;
                        
                        // Restaurar apenas se não for muito antigo (menos de 1 hora)
                        if (timeDiff < 3600000) {
                            // Tentar encontrar o formulário correspondente
                            const forms = document.querySelectorAll('form');
                            const formIndex = parseInt(key.replace(`form_state_${tabId}_`, ''));
                            
                            if (forms[formIndex] && formData.action === forms[formIndex].action) {
                                // Restaurar dados do formulário
                                Object.keys(formData.data).forEach(fieldName => {
                                    const field = forms[formIndex].querySelector(`[name="${fieldName}"]`);
                                    if (field && field.type !== 'password') { // Não restaurar senhas
                                        field.value = formData.data[fieldName];
                                    }
                                });
                                
                                admsLog(`Formulário ${formIndex} restaurado para aba:`, tabId);
                            }
                        }
                        
                        // Limpar dados antigos
                        clearDataFromAnyStorage(key);
                    }
                });

                // Limpar URL salva após restaurar
                clearDataFromAnyStorage(`current_url_${tabId}`);
                
                admsLog('Contexto da aplicação restaurado com sucesso para aba:', tabId);
            } catch (error) {
                admsError('Erro ao restaurar contexto:', error);
            }
        }
        
        // Função robusta para obter dados de qualquer storage
        function getDataFromAnyStorage(key) {
            // Estratégia 1: localStorage
            try {
                const data = localStorage.getItem(key);
                if (data) {
                    admsLog(`Dados encontrados no localStorage: ${key}`);
                    return JSON.parse(data);
                }
            } catch (error) {
                admsWarn(`Erro ao ler do localStorage para ${key}:`, error);
            }
            
            // Estratégia 2: sessionStorage
            try {
                const data = sessionStorage.getItem(key);
                if (data) {
                    admsLog(`Dados encontrados no sessionStorage: ${key}`);
                    return JSON.parse(data);
                }
            } catch (error) {
                admsWarn(`Erro ao ler do sessionStorage para ${key}:`, error);
            }
            
            // Estratégia 3: Cookies
            try {
                const cookies = document.cookie.split(';');
                for (let cookie of cookies) {
                    const [cookieKey, cookieValue] = cookie.trim().split('=');
                    if (cookieKey === key) {
                        const data = decodeURIComponent(cookieValue);
                        admsLog(`Dados encontrados em cookie: ${key}`);
                        return JSON.parse(data);
                    }
                }
            } catch (error) {
                admsWarn(`Erro ao ler de cookie para ${key}:`, error);
            }
            
            // Estratégia 4: Variável global
            try {
                const data = window[`storage_${key}`];
                if (data) {
                    admsLog(`Dados encontrados em variável global: ${key}`);
                    return JSON.parse(data);
                }
            } catch (error) {
                admsWarn(`Erro ao ler de variável global para ${key}:`, error);
            }
            
            return null;
        }
        
        // Função robusta para limpar dados de qualquer storage
        function clearDataFromAnyStorage(key) {
            // Estratégia 1: localStorage
            try {
                localStorage.removeItem(key);
                admsLog(`Dados removidos do localStorage: ${key}`);
            } catch (error) {
                admsWarn(`Erro ao remover do localStorage para ${key}:`, error);
            }
            
            // Estratégia 2: sessionStorage
            try {
                sessionStorage.removeItem(key);
                admsLog(`Dados removidos do sessionStorage: ${key}`);
            } catch (error) {
                admsWarn(`Erro ao remover do sessionStorage para ${key}:`, error);
            }
            
            // Estratégia 3: Cookies
            try {
                document.cookie = `${key}=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT`;
                admsLog(`Dados removidos de cookie: ${key}`);
            } catch (error) {
                admsWarn(`Erro ao remover de cookie para ${key}:`, error);
            }
            
            // Estratégia 4: Variável global
            try {
                delete window[`storage_${key}`];
                admsLog(`Dados removidos de variável global: ${key}`);
            } catch (error) {
                admsWarn(`Erro ao remover de variável global para ${key}:`, error);
            }
        }
        
        // Função para obter todas as chaves de formulário de qualquer storage
        function getAllFormKeys(tabId) {
            const keys = [];
            const prefix = `form_state_${tabId}_`;
            
            // Estratégia 1: localStorage
            try {
                for (let i = 0; i < localStorage.length; i++) {
                    const key = localStorage.key(i);
                    if (key && key.startsWith(prefix)) {
                        keys.push(key);
                    }
                }
            } catch (error) {
                admsWarn('Erro ao ler chaves do localStorage:', error);
            }
            
            // Estratégia 2: sessionStorage
            try {
                for (let i = 0; i < sessionStorage.length; i++) {
                    const key = sessionStorage.key(i);
                    if (key && key.startsWith(prefix)) {
                        keys.push(key);
                    }
                }
            } catch (error) {
                admsWarn('Erro ao ler chaves do sessionStorage:', error);
            }
            
            // Estratégia 3: Cookies
            try {
                const cookies = document.cookie.split(';');
                for (let cookie of cookies) {
                    const [cookieKey] = cookie.trim().split('=');
                    if (cookieKey && cookieKey.startsWith(prefix)) {
                        keys.push(cookieKey);
                    }
                }
            } catch (error) {
                admsWarn('Erro ao ler chaves de cookie:', error);
            }
            
            // Estratégia 4: Variável global
            try {
                for (let key in window) {
                    if (key.startsWith(`storage_${prefix}`)) {
                        keys.push(key.replace('storage_', ''));
                    }
                }
            } catch (error) {
                admsWarn('Erro ao ler chaves de variável global:', error);
            }
            
            return keys;
        }
    </script>

    <?php if (!empty($_SESSION['user_id'])): ?>
    <?php include __DIR__ . '/../partials/pwa_install_modals.php'; ?>
    <script>
    (function () {
        function markNavbarAvatarReady(img) {
            img.classList.add('is-ready');
        }
        document.querySelectorAll('img.navbar-user-avatar').forEach(function (img) {
            if (img.complete && img.naturalWidth > 0) {
                markNavbarAvatarReady(img);
                return;
            }
            img.addEventListener('load', function () { markNavbarAvatarReady(img); }, { once: true });
            img.addEventListener('error', function () { markNavbarAvatarReady(img); }, { once: true });
        });
    })();
    </script>
    <?php endif; ?>

</body>

</html>

