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

// Força cabeçalhos/ambiente UTF-8 na resposta HTML
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

// Expor user_id atual para o front (comparação de sessão entre abas)
if (!headers_sent() && isset($_SESSION['user_id'])) {
    echo '<meta name="current-user-id" content="' . (int)$_SESSION['user_id'] . '">';
    echo '<script>window.currentUserId = ' . (int)$_SESSION['user_id'] . ';</script>';
}

if (!isset($_ENV['DB_HOST'])) {
    require_once __DIR__ . '/../../Helpers/EnvLoader.php';
    \App\adms\Helpers\EnvLoader::load();
}

$urlAdm = getenv('URL_ADM');

// Sincronizar session_id se necessário
if (!isset($_SESSION['session_id']) || $_SESSION['session_id'] !== session_id()) {
    $_SESSION['session_id'] = session_id();
}

// Verificação inicial de sessão apenas se o usuário estiver logado
if (isset($_SESSION['user_id']) && isset($_SESSION['session_id'])) {
    $sessionRepo = new \App\adms\Models\Repository\AdmsSessionsRepository();
    $currentSessionId = $_SESSION['session_id'];
    $sess = $sessionRepo->getSessionByUserIdAndSessionId($_SESSION['user_id'], $currentSessionId);
    
    // Auto-bootstrap: se a sessão não existir no banco, criar
    if (!$sess) {
        try {
            $sessionRepo->saveSession($_SESSION['user_id'], $currentSessionId);
            $sess = $sessionRepo->getSessionByUserIdAndSessionId($_SESSION['user_id'], $currentSessionId);
            if ($sess && $sess['status'] === 'ativa') {
                error_log("Session auto-bootstrapped for user {$_SESSION['user_id']} at " . date('Y-m-d H:i:s'));
            }
        } catch (\Throwable $e) {
            error_log("Session auto-bootstrap failed for user {$_SESSION['user_id']}: " . $e->getMessage());
        }
    }
    
    // Verificar se a sessão está ativa no banco
    if ($sess && $sess['status'] === 'ativa') {
        // Atualizar atividade apenas uma vez por requisição
        $sessionRepo->updateSessionActivity($_SESSION['user_id'], $currentSessionId);
    } else {
        // Sessão inválida - forçar logout
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        header("Location: {$_ENV['URL_ADM']}login?msg=" . urlencode('Sessão inválida. Faça login novamente.'));
        exit;
    }
}

// Buscar política de senha para configuração JavaScript
$policyRepo = new \App\adms\Models\Repository\AdmsPasswordPolicyRepository();
$policy = $policyRepo->getPolicy();
$expirarPorTempo = ($policy && isset($policy->expirar_sessao_por_tempo) && $policy->expirar_sessao_por_tempo === 'Sim');
?>
<!DOCTYPE html>
<html lang="<?php echo $_ENV['APP_LOCALE']; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo $this->data['title_head'] ?? 'Sistema Administrativo'; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo $_ENV['URL_ADM']; ?>public/adms/img/favicon.ico">
    
    <!-- CSS Reset -->
    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM']; ?>public/adms/css/reset.css?v=<?php echo time(); ?>">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM'] ?>public/adms/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM']; ?>public/adms/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM']; ?>public/adms/DataTables/datatables.min.css">
    
    <!-- CSS Custom -->
    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM']; ?>public/adms/css/menu-styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM']; ?>public/adms/css/styles_admin.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM']; ?>public/adms/css/custom-ajustes.css?v=<?php echo time(); ?>">
    
    <!-- CSS específico da página -->
    <?php if (isset($this->data['css'])): ?>
        <?php foreach ($this->data['css'] as $css): ?>
            <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM']; ?>public/adms/css/<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
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

<body id="page-top">
    <div id="admsInlineFeedbackGlobal" class="adms-inline-feedback-global" aria-live="polite" aria-atomic="true"></div>
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include './app/adms/Views/partials/menu.php'; ?>
        
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <?php include './app/adms/Views/partials/navbar.php'; ?>
                
                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <?php if (isset($this->data['title_head'])): ?>
                        <div class="d-sm-flex align-items-center justify-content-between mb-4">
                            <h1 class="h3 mb-0 text-gray-800"><?php echo $this->data['title_head']; ?></h1>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Content -->
                    <?php include $this->view; ?>
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->
            
            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Sistema Administrativo <?php echo date('Y'); ?></span>
                    </div>
                </div>
            </footer>

        </div>
    </div>

    <script src="<?php echo $_ENV['URL_ADM'] ?>public/adms/js/bootstrap.bundle.min.js"></script>
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
    <script src="<?php echo $_ENV['URL_ADM'] ?>public/adms/js/swal-lite.js?v=20260420-1"></script>
    <script src="<?php echo $_ENV['URL_ADM'] ?>public/adms/js/sbadmin.js"></script>
    <script src="<?php echo $_ENV['URL_ADM'] ?>public/adms/js/script_admin.js"></script>
    <script src="<?php echo $_ENV['URL_ADM'] ?>public/adms/DataTables/datatables.min.js"></script>
    <script src="<?php echo $_ENV['URL_ADM'] ?>public/adms/js/telefone-mascara.js"></script>
    <script src="<?php echo $_ENV['URL_ADM'] ?>public/adms/js/mascaras.js"></script>
    
    <!-- Plugin de máscara externo removido para evitar bloqueios de Tracking Prevention -->
    
    <!-- Sistema Responsivo para Diferentes Resoluções (opcional) -->
    <?php if (($_ENV['USE_SCREEN_RESOLUTION'] ?? 'Não') === 'Sim'): ?>
    <script src="<?php echo $_ENV['URL_ADM']; ?>public/adms/js/screen-resolution.js"></script>
    <?php endif; ?>

    <!-- Configurações de Sessão da Política de Senhas -->
    <script>
        window.sessionConfig = {
            enabled: <?php echo json_encode($expirarPorTempo ?? false); ?>,
            timeoutMinutes: <?php echo json_encode(($policy && isset($policy->tempo_expiracao_sessao)) ? (int)$policy->tempo_expiracao_sessao : 30); ?>,
            warningTime: 60000 // 1 minuto em ms
        };
    </script>

    <!-- Verificação Automática de Sessão -->
    <script src="<?php echo $_ENV['URL_ADM']; ?>public/adms/js/session-checker.js"></script>

    <!-- Responsividade genérica de listas (desktop x mobile) -->
    <script src="<?php echo $_ENV['URL_ADM']; ?>public/adms/js/responsive-list.js"></script>

    <!-- JavaScript específico para página de permissões -->
    <?php if (strpos($this->view, 'permission/list.php') !== false): ?>
    <script src="<?php echo $_ENV['URL_ADM']; ?>public/adms/js/permission-list.js?v=<?php echo time(); ?>"></script>
    <?php endif; ?>

    <!-- Intercepta todas as respostas fetch -->
    <script>
    (function() {
        if (!window.fetch) return;
        const originalFetch = window.fetch;
        window.fetch = function() {
            return originalFetch.apply(this, arguments).then(async response => {
                const cloned = response.clone();
                try {
                    const data = await cloned.json();
                    if (data && data.logout) {
                        console.log('LOGOUT DETECTADO VIA FETCH:', data);
                        alert(data.message || "Sua sessão foi encerrada. Faça login novamente.");
                        window.location.href = "<?php echo $_ENV['URL_ADM']; ?>login";
                        return Promise.reject("Sessão encerrada");
                    }
                } catch (e) { /* Não é JSON, ignora */ }
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
                    console.log('LOGOUT DETECTADO VIA AJAX:', data);
                    alert(data.message || "Sua sessão foi encerrada. Faça login novamente.");
                    window.location.href = "<?php echo $_ENV['URL_ADM']; ?>login";
                }
            } catch (e) { /* Não é JSON, ignora */ }
        });
    }
    </script>

</body>
</html>
