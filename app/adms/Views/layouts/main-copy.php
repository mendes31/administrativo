<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
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
</head>

<body id="page-top">
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo $_ENV['URL_ADM'] ?>public/adms/js/sbadmin.js"></script>
    <script src="<?php echo $_ENV['URL_ADM'] ?>public/adms/js/script_admin.js"></script>
    <script src="<?php echo $_ENV['URL_ADM'] ?>public/adms/DataTables/datatables.min.js"></script>
    <script src="<?php echo $_ENV['URL_ADM'] ?>public/adms/js/telefone-mascara.js"></script>
    <script src="<?php echo $_ENV['URL_ADM'] ?>public/adms/js/mascaras.js"></script>
    
    <!-- Ajax para funcionar Mascaras JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.11/jquery.mask.min.js"></script>
    
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
