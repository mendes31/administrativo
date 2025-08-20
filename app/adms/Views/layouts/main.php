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

// Teste de execução do layout
// echo "<!-- LAYOUT MAIN EXECUTADO -->";

// Log alternativo com caminho absoluto
// file_put_contents('C:/wamp64/www/administrativo2/app/logs/session_debug2.log',
//     date('Y-m-d H:i:s') . ' - [main] session_id(): ' . session_id() .
//     ' | $_SESSION[session_id]: ' . ($_SESSION['session_id'] ?? 'null') .
//     ' | Cookie PHPSESSID: ' . ($_COOKIE['PHPSESSID'] ?? 'null') .
//     ' | $_SESSION: ' . json_encode($_SESSION) . "\n",
//     FILE_APPEND
// );

// Log de início do layout para capturar erros fatais
// file_put_contents('C:/wamp64/www/administrativo2/app/logs/session_debug2.log',
//     date('Y-m-d H:i:s') . ' - [LAYOUT] INICIO RENDERIZACAO - session_id: ' . (session_id() ?: 'null') .
//     ' | _SESSION: ' . json_encode($_SESSION) .
//     ' | URL: ' . ($_SERVER['REQUEST_URI'] ?? 'null') .
//     ' | GET: ' . json_encode($_GET) .
//     ' | POST: ' . json_encode($_POST) . "\n",
//     FILE_APPEND
// );



// Verificar se há erros fatais
$error = error_get_last();
if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
    file_put_contents('C:/wamp64/www/administrativo2/app/logs/session_debug2.log',
        date('Y-m-d H:i:s') . ' - [LAYOUT] ERRO FATAL DETECTADO - ' . json_encode($error) . "\n",
        FILE_APPEND
    );
}

if (!isset($_ENV['DB_HOST'])) {
    require_once __DIR__ . '/../../Helpers/EnvLoader.php';
    \App\adms\Helpers\EnvLoader::load();
}
// Supondo que o .env já esteja carregado via alguma lib tipo vlucas/phpdotenv
$urlAdm = getenv('URL_ADM');

if (!isset($_SESSION['session_id']) || $_SESSION['session_id'] !== session_id()) {
    $_SESSION['session_id'] = session_id();
}

// Checagem de sessão invalidada
if (isset($_SESSION['user_id']) && isset($_SESSION['session_id'])) {
    $sessionRepo = new \App\adms\Models\Repository\AdmsSessionsRepository();
    $sess = $sessionRepo->getSessionByUserIdAndSessionId($_SESSION['user_id'], session_id());
    
    // Log da consulta ao banco com caminho absoluto
    // file_put_contents('C:/wamp64/www/administrativo2/app/logs/session_debug2.log',
    //     date('Y-m-d H:i:s') . ' - [main] CONSULTA BANCO - user_id: ' . $_SESSION['user_id'] . 
    //     ' | session_id(): ' . session_id() . 
    //     ' | Resultado: ' . json_encode($sess) . "\n",
    //     FILE_APPEND
    // );
    
    $motivos = [];
    
    if (!$sess) {
        $motivos[] = 'Sessão não encontrada no banco';
    } elseif ($sess['status'] !== 'ativa') {
        $motivos[] = 'Sessão inativa';
    }
    
    // Log antes da checagem de queda de sessão
    // file_put_contents('C:/wamp64/www/administrativo2/app/logs/session_debug2.log',
    //     date('Y-m-d H:i:s') . ' - [main] PRE-CHECAGEM QUEDA - user_id: ' . ($_SESSION['user_id'] ?? 'null') .
    //     ' | session_id(): ' . session_id() .
    //     ' | Motivos: ' . (isset($motivos) ? implode(', ', $motivos) : 'ainda não definido') .
    //     ' | Status da sessão: ' . ($sess['status'] ?? 'null') .
    //     ' | URL: ' . ($_SERVER['REQUEST_URI'] ?? 'null') .
    //     ' | GET: ' . json_encode($_GET) . "\n",
    //     FILE_APPEND
    // );
    
    if (!empty($motivos) || ($sess && $sess['status'] === 'invalidada')) {
        $msg = !empty($motivos) ? implode(' e ', $motivos) . '! Contate o Administrador do sistema.' : 'Sessão invalidada. Faça login novamente.';
        
        // Log detalhado do motivo da queda da sessão
        file_put_contents('C:/wamp64/www/administrativo2/app/logs/session_debug2.log',
            date('Y-m-d H:i:s') . ' - [main] QUEDA DE SESSÃO - user_id: ' . $_SESSION['user_id'] . 
            ' | session_id(): ' . session_id() . 
            ' | Motivos: ' . implode(', ', $motivos) . 
            ' | Status da sessão: ' . ($sess['status'] ?? 'null') . 
            ' | URL: ' . ($_SERVER['REQUEST_URI'] ?? 'null') . 
            ' | GET: ' . json_encode($_GET) . "\n",
            FILE_APPEND
        );
        
        // Limpar apenas a sessão do usuário impactado
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        // session_destroy(); // Removido para evitar destruição global da sessão
        header("Location: {$_ENV['URL_ADM']}login?msg=" . urlencode($msg));
        exit;
    }
    // Buscar política de senha para expiração dinâmica
    $policyRepo = new \App\adms\Models\Repository\AdmsPasswordPolicyRepository();
    $policy = $policyRepo->getPolicy();
    $expirarPorTempo = ($policy && isset($policy->expirar_sessao_por_tempo) && $policy->expirar_sessao_por_tempo === 'Sim');
    $limite = ($policy && isset($policy->tempo_expiracao_sessao)) ? ((int)$policy->tempo_expiracao_sessao * 60) : 1800;
    if ($expirarPorTempo) {
        $agora = time();
        $ultimaAtividade = strtotime($sess['updated_at'] ?? $sess['created_at']);
        if ($agora - $ultimaAtividade > $limite) {
            // Log de expiração por tempo
            file_put_contents('C:/wamp64/www/administrativo2/app/logs/session_debug2.log',
                date('Y-m-d H:i:s') . ' - [main] EXPIRAÇÃO POR TEMPO - user_id: ' . $_SESSION['user_id'] . 
                ' | session_id(): ' . session_id() . 
                ' | Tempo limite: ' . $limite . 's' .
                ' | Tempo decorrido: ' . ($agora - $ultimaAtividade) . 's' .
                ' | URL: ' . ($_SERVER['REQUEST_URI'] ?? 'null') . "\n",
                FILE_APPEND
            );
            
            $sessionRepo->invalidateSessionByUserIdAndSessionId($_SESSION['user_id'], $_SESSION['session_id']);
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params['path'], $params['domain'],
                    $params['secure'], $params['httponly']
                );
            }
            // session_destroy(); // Removido para evitar destruição global da sessão
            header('Location: /administrativo2/login?error=' . urlencode('Sua sessão expirou por inatividade. Faça login novamente.'));
            exit;
        }
    }
    // Atualiza o updated_at da sessão ativa
    $sessionRepo->updateSessionActivity($_SESSION['user_id'], $_SESSION['session_id']);
}

file_put_contents('caminho_do_log', 'session_id: ' . session_id() . ' - ' . json_encode($_SESSION) . PHP_EOL, FILE_APPEND);
?>
<!DOCTYPE html>
<html lang="<?php echo $_ENV['APP_LOCALE']; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="shortcut icon" href="<?php echo $_ENV['URL_ADM']; ?>public/adms/image/icon/favicon.ico">

    <!-- <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM'] ?>public/adms/css/custom_adms.css"> -->

    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM'] ?>public/adms/css/sbadmin.css">

    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM'] ?>public/adms/css/bootstrap.min.css">

    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM'] ?>public/adms/css/styles_admin.css">

    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM'] ?>public/adms/DataTables/datatables.min.css">

    <script src="https://use.fontawesome.com/releases/v6.6.0/js/all.js" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- CSS Reset e Ajustes de Padronização -->
    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM']; ?>adms/css/reset.css">
    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM']; ?>adms/css/custom-ajustes.css">
    
    <!-- Sistema Responsivo para Diferentes Resoluções -->
    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM']; ?>public/adms/css/responsive-screens.css">

    <!-- CSS específico para página de permissões -->
    <?php if (strpos($this->view, 'permission/list.php') !== false): ?>
    <link rel="stylesheet" href="<?php echo $_ENV['URL_ADM']; ?>public/adms/css/permission-list.css">
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


</head>

<body class="sb-nav-fixed">

    <?php include 'app/adms/Views/partials/navbar.php'; ?>

    <div id="layoutSidenav">

        <?php include 'app/adms/Views/partials/menu.php'; ?>

        <div id="layoutSidenav_content">
            <main>

                <?php

                // Inclui o conteúdo principal da página, que é especificado pela propriedade $this->view. Este arquivo é dinâmico e pode variar conforme a lógica do controlador ou o contexto da página.
                include $this->view;

                ?>
            </main>

            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; <?php echo $_ENV['APP_NAME'] . " " . date("Y"); ?></div>
                        <div>
                            <a href="#" class="text-decoration-none">Política de Privacidade</a>
                            &middot;
                            <a href="#" class="text-decoration-none">Termos de Uso</a>
                        </div>
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
    
    <!-- Sistema Responsivo para Diferentes Resoluções -->
    <script src="<?php echo $_ENV['URL_ADM']; ?>public/adms/js/screen-resolution.js"></script>

    <!-- JavaScript específico para página de permissões -->
    <?php if (strpos($this->view, 'permission/list.php') !== false): ?>
    <script src="<?php echo $_ENV['URL_ADM']; ?>public/adms/js/permission-list.js?v=<?php echo time(); ?>"></script>
    <?php endif; ?>

    <!-- Bootstrap Bundle com Popper.js -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->

    <?php if (isset($_SESSION['user_id'])): ?>
        <li class="nav-item">
            <a class="nav-link" href="<?= $_ENV['URL_ADM'] ?>minhas-avaliacoes">
                <i class="fas fa-clipboard-list me-2"></i>Minhas Avaliações
            </a>
        </li>
    <?php endif; ?>

    <script>
    // Intercepta todas as respostas fetch
    (function() {
        if (!window.fetch) return;
        const originalFetch = window.fetch;
        window.fetch = function() {
            return originalFetch.apply(this, arguments).then(async response => {
                const cloned = response.clone();
                try {
                    const data = await cloned.json();
                    // Log de todas as respostas fetch para debug
                    console.log('FETCH RESPONSE:', data);
                    if (data && data.logout) {
                        console.log('LOGOUT DETECTADO VIA FETCH:', data);
                        alert(data.message || "Sua sessão foi encerrada. Faça login novamente.");
                        window.location.href = "/administrativo2/login";
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
                // Log de todas as respostas AJAX para debug
                console.log('AJAX RESPONSE:', data);
                if (data && data.logout) {
                    console.log('LOGOUT DETECTADO VIA AJAX:', data);
                    alert(data.message || "Sua sessão foi encerrada. Faça login novamente.");
                    window.location.href = "/administrativo2/login";
                }
            } catch (e) { /* Não é JSON, ignora */ }
        });
    }
    </script>

</body>

</html>