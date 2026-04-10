<?php

namespace App\adms\Controllers\login;

use App\adms\Controllers\Services\Validation\ValidationLoginService;
use App\adms\Controllers\Services\ValidationUserLogin;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\LogsRepository;
use App\adms\Models\Repository\LogAcessosRepository;
use App\adms\Controllers\Services\RequestHelper;
use App\adms\Views\Services\LoadViewService;
use App\adms\Models\Repository\LgpdTermosRepository;
use App\adms\Models\Services\TrainingStatusUpdaterService;
use App\adms\Models\Services\CandidateRetentionService;
use App\adms\Models\Services\PayrollDocumentRemindersService;

/**
 * Controller login
 * 
 * @author Rafael Mendes <raffaell_mendez@hotmail.com>
 */
class Login
{
    /** @var array|string|null $data Recebe os dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;
    /**
     * Pagian login
     * 
     * @return void
     */
    public function index(): void
    {
        // Log para debug
        file_put_contents(__DIR__ . '/../../../logs/login_debug.log', 
            date('Y-m-d H:i:s') . " - [index] INICIO - SESSION: " . json_encode($_SESSION) . "\n", 
            FILE_APPEND
        );
        
        // Verificar se já está logado ANTES de qualquer processamento
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            $sessionRepo = new \App\adms\Models\Repository\AdmsSessionsRepository();
            $sessionData = $sessionRepo->getSessionByUserIdAndSessionId((int)$_SESSION['user_id'], session_id());
            
            if ($sessionData && isset($sessionData['status']) && $sessionData['status'] === 'ativa') {
                header("Location: {$_ENV['URL_ADM']}dashboard");
                exit;
            }
            
            // Limpar sessão inválida
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params['path'], $params['domain'],
                    $params['secure'], $params['httponly']
                );
            }
        }
        
        // Receber dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        
        // Log dos dados recebidos
        file_put_contents(__DIR__ . '/../../../logs/login_debug.log', 
            date('Y-m-d H:i:s') . " - [index] DADOS POST: " . json_encode($this->data['form']) . "\n", 
            FILE_APPEND
        );
        
        // Se não há dados POST, apenas mostrar a view
        if (empty($this->data['form'])) {
            file_put_contents(__DIR__ . '/../../../logs/login_debug.log', 
                date('Y-m-d H:i:s') . " - [index] SEM DADOS POST - MOSTRANDO VIEW\n", 
                FILE_APPEND
            );
            $this->viewLogin();
            return;
        }
        
        // Verificar se já foi processado (proteção contra duplo submit)
        if (isset($_SESSION['login_processed']) && $_SESSION['login_processed'] === true) {
            file_put_contents(__DIR__ . '/../../../logs/login_debug.log', 
                date('Y-m-d H:i:s') . " - [index] DUPLO SUBMIT DETECTADO - REDIRECIONANDO\n", 
                FILE_APPEND
            );
            // Limpar flag e redirecionar para dashboard
            unset($_SESSION['login_processed']);
            header("Location: {$_ENV['URL_ADM']}dashboard");
            exit;
        }
        
        // Marcar como processado
        $_SESSION['login_processed'] = true;
        
        // Processar login
        $this->login();
    }

    /**
     * Carregar a visualização de login.
     * 
     * Este método configura os dados necessários e carrega a view para login.
     * 
     * @return void
     */
    private function viewLogin(): void
    {
        // Criar o título da página
        $this->data['title_head'] =  "Login";
        
        // Headers para prevenir cache
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/login/login", $this->data);
        $loadView->loadViewLogin();
    }

    /**
     * Processar o login do usuário
     */
    private function login(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        file_put_contents(__DIR__ . '/../../../logs/session_debug.log', date('Y-m-d H:i:s') . ' - [login] INICIO - session_id: ' . session_id() . ' - ' . json_encode($_SESSION) . "\n", FILE_APPEND);
        @file_put_contents(__DIR__ . '/../../../logs/session_investigar.log',
            date('Y-m-d H:i:s') . ' [Login::login] INICIO php_session_id=' . session_id() . ' $_SESSION=' . json_encode($_SESSION) . PHP_EOL,
            FILE_APPEND
        );
        file_put_contents(__DIR__ . '/../../../logs/login_debug.log', date('Y-m-d H:i:s') . " - Início do método login\n", FILE_APPEND);
        $validationLogin = new ValidationLoginService();
        $this->data['errors'] = $validationLogin->validate($this->data['form']);
        file_put_contents(__DIR__ . '/../../../logs/login_debug.log', date('Y-m-d H:i:s') . " - Após validação: " . json_encode($this->data['errors']) . "\n", FILE_APPEND);
        if (!empty($this->data['errors'])) {
            file_put_contents(__DIR__ . '/../../../logs/login_debug.log', date('Y-m-d H:i:s') . " - Erro de validação\n", FILE_APPEND);
            $this->viewLogin();
            return;
        }
        $validationUserLogin = new ValidationUserLogin();
        $result = $validationUserLogin->validationUserLogin($this->data['form']);
        file_put_contents(__DIR__ . '/../../../logs/login_debug.log', date('Y-m-d H:i:s') . " - Após autenticação: " . json_encode($result) . "\n", FILE_APPEND);
        if($result && isset($result['id']) && is_numeric($result['id'])){
            if (isset($result['modificar_senha_proximo_logon']) && $result['modificar_senha_proximo_logon'] === 'Sim') {
                file_put_contents(__DIR__ . '/../../../logs/session_debug2.log', date('Y-m-d H:i:s') . ' - [login] ANTES HEADER force-password-change - session_id: ' . session_id() . ' - $_SESSION: ' . json_encode($_SESSION) . "\n", FILE_APPEND);
                $_SESSION['force_password_change'] = true;
                $_SESSION['session_id'] = session_id();
                // Salvar a sessão no banco ANTES do redirecionamento
                $sessionRepo = new \App\adms\Models\Repository\AdmsSessionsRepository();
                // Registrar LOGOUT_CONCURRENT para sessões antigas antes de invalidar
                $activeSessions = $sessionRepo->getActiveSessionsByUserId((int)$result['id']);
                if (!empty($activeSessions)) {
                    $logRepo = new LogAcessosRepository();
                    $ipConc = RequestHelper::getClientIp();
                    $uaConc = RequestHelper::getUserAgent();
                    $hostnameConc = RequestHelper::getClientHostname();
                    foreach ($activeSessions as $old) {
                        $logRepo->registrarAcesso((int)$result['id'], 'LOGOUT_CONCURRENT', $ipConc, $uaConc, 'Sessão anterior: ' . ($old['session_id'] ?? ''), $hostnameConc); 
                    }
                }
                $sessionRepo->invalidateAllSessionsByUserId((int)$result['id']);
                $sessionRepo->saveSession((int)$result['id'], session_id());
                header("Location: {$_ENV['URL_ADM']}force-password-change");
                exit;
            }
            file_put_contents(__DIR__ . '/../../../logs/login_debug.log', date('Y-m-d H:i:s') . " - Login OK - verificando consentimento LGPD\n", FILE_APPEND);
            $logAcessosRepo = new LogAcessosRepository();
            $ip = RequestHelper::getClientIp();
            $userAgent = RequestHelper::getUserAgent();
            $hostname = RequestHelper::getClientHostname();
            $logAcessosRepo->registrarAcesso((int)$result['id'], 'LOGIN', $ip, $userAgent, null, $hostname);
            if($_ENV['APP_LOGS'] == 'Sim'){
            $dataLogs = [
                'table_name' => 'adms_users',
                'table_name' => 'adms_users',
                'action' => 'login',
                'record_id' => 0,
                'description' => 'login',
            ];
            $insertLogs = new LogsRepository();
            $insertLogs->insertLogs($dataLogs);
            }
            $sessionRepo = new \App\adms\Models\Repository\AdmsSessionsRepository();
            // Registrar LOGOUT_CONCURRENT para sessões antigas antes de invalidar
            $activeSessions = $sessionRepo->getActiveSessionsByUserId((int)$result['id']);
            if (!empty($activeSessions)) {
                $logRepo = new LogAcessosRepository();
                $ipConc = RequestHelper::getClientIp();
                $uaConc = RequestHelper::getUserAgent();
                $hostnameConc = RequestHelper::getClientHostname();
                foreach ($activeSessions as $old) {
                    $logRepo->registrarAcesso((int)$result['id'], 'LOGOUT_CONCURRENT', $ipConc, $uaConc, 'Sessão anterior: ' . ($old['session_id'] ?? ''), $hostnameConc); 
                }
            }
            @file_put_contents(__DIR__ . '/../../../logs/session_investigar.log',
                date('Y-m-d H:i:s') . ' [Login::login] BEFORE invalidate/save user_id=' . (int)$result['id'] . ' php_session_id=' . session_id() . PHP_EOL,
                FILE_APPEND
            );
            $sessionRepo->invalidateAllSessionsByUserId((int)$result['id']);
            $_SESSION['session_id'] = session_id();
            $sessionRepo->saveSession((int)$result['id'], session_id());
            file_put_contents(__DIR__ . '/../../../logs/session_debug.log', date('Y-m-d H:i:s') . ' - [login] SALVOU SESSION NO BANCO: ' . session_id() . ' - $_SESSION: ' . json_encode($_SESSION) . "\n", FILE_APPEND);
            @file_put_contents(__DIR__ . '/../../../logs/session_investigar.log',
                date('Y-m-d H:i:s') . ' [Login::login] AFTER saveSession user_id=' . (int)$result['id'] . ' php_session_id=' . session_id() . ' $_SESSION=' . json_encode($_SESSION) . PHP_EOL,
                FILE_APPEND
            );

            // Serviços diários disparados no primeiro login de qualquer usuário
            // - Atualização de status dinâmicos de treinamentos
            // - Retenção/anonimização de currículos (LGPD)
            // - Lembretes de ciência em documentos de folha (RH), no máximo 1× por 24 h
            TrainingStatusUpdaterService::ensureUpdated(false);
            CandidateRetentionService::ensureUpdated(false);
            PayrollDocumentRemindersService::ensureUpdated(false);

            // Verificar consentimento LGPD antes de liberar acesso
            // Exceções:
            // 1. Usuário "manager" não precisa de consentimento
            // 2. Se não houver termos cadastrados ou ativos, não solicitar consentimento
            
            $username = $result['username'] ?? '';
            $isManager = (strtolower($username) === 'manager');
            
            // Se for manager, pular verificação de consentimento
            if ($isManager) {
                file_put_contents(__DIR__ . '/../../../logs/login_debug.log', 
                    date('Y-m-d H:i:s') . " - Usuario manager detectado - pulando verificação de consentimento LGPD\n", 
                    FILE_APPEND
                );
            } else {
                // Verificar se existe termo ativo antes de solicitar consentimento
                $lgpdTermosRepo = new LgpdTermosRepository();
                $termoLogin = $lgpdTermosRepo->getTermoAtivoPorTipo('login');
                
                // Se não encontrar termo do tipo login, tentar último termo ativo como fallback
                if (!$termoLogin) {
                    $termoLogin = $lgpdTermosRepo->getLastActiveTerm();
                }
                
                // Se não houver termo ativo, não solicitar consentimento
                if (!$termoLogin) {
                    file_put_contents(__DIR__ . '/../../../logs/login_debug.log', 
                        date('Y-m-d H:i:s') . " - Nenhum termo LGPD ativo encontrado - não solicitando consentimento\n", 
                        FILE_APPEND
                    );
                } else {
                    // Há termo ativo, verificar consentimento
                    $consentVersionAtual = $termoLogin['versao'] ?? ($_ENV['LGPD_CONSENT_VERSION'] ?? '1.0');
                    $temConsentimentoValido = false;
                    $emailLogin = $result['email'] ?? '';

                    $consentRepo = new \App\adms\Models\Repository\LgpdConsentimentosRepository();
                    // Preferencial: buscar pelo ID do usuário (adms_user_id)
                    $ultimoConsent = $consentRepo->getUltimoConsentimentoAtivoPorUsuario((int)$result['id'], 'sistema_login');

                    // Fallback para bases antigas: buscar por e-mail se não encontrar por usuário
                    if (!$ultimoConsent && !empty($emailLogin)) {
                        $ultimoConsent = $consentRepo->getUltimoConsentimentoAtivoPorEmail($emailLogin, 'sistema_login');
                    }

                    // Log detalhado para depuração
                    file_put_contents(
                        __DIR__ . '/../../../logs/login_debug.log',
                        date('Y-m-d H:i:s') . ' - Verificando consentimento LGPD - email=' . $emailLogin .
                        ' | versao_atual=' . $consentVersionAtual .
                        ' | ultimoConsent=' . json_encode($ultimoConsent) . PHP_EOL,
                        FILE_APPEND
                    );

                    if ($ultimoConsent && !empty($ultimoConsent['versao_termo']) && $ultimoConsent['versao_termo'] === $consentVersionAtual) {
                        $temConsentimentoValido = true;
                    }

                    if (!$temConsentimentoValido) {
                        file_put_contents(__DIR__ . '/../../../logs/login_debug.log', 
                            date('Y-m-d H:i:s') . " - Consentimento LGPD pendente/versão diferente - redirecionando para lgpd-consentimento-login\n", 
                            FILE_APPEND
                        );
                        header("Location: {$_ENV['URL_ADM']}lgpd-consentimento-login");
                        exit;
                    }
                }
            }

            // Redirecionar para URL salva ou dashboard padrão
            $redirectUrl = $this->getRedirectUrlAfterLogin();
            header("Location: " . $redirectUrl);
            exit;
        } else {
            file_put_contents(__DIR__ . '/../../../logs/login_debug.log', date('Y-m-d H:i:s') . " - Falha no login\n", FILE_APPEND);
            $this->viewLogin();
            return;
        }
    }

    /**
     * Determinar URL de redirecionamento após login
     */
    private function getRedirectUrlAfterLogin(): string
    {
        // Verificar se há uma URL de retorno na sessão
        if (isset($_SESSION['return_url']) && !empty($_SESSION['return_url'])) {
            $returnUrl = $_SESSION['return_url'];
            unset($_SESSION['return_url']); // Limpar após uso
            return $returnUrl;
        }
        
        // Verificar se há uma URL de retorno no POST
        if (isset($_POST['return_url']) && !empty($_POST['return_url'])) {
            return $_POST['return_url'];
        }
        
        // Verificar se há uma URL específica da aba no localStorage (via JavaScript)
        // Esta verificação será feita no frontend após o login
        // Por enquanto, vamos para o dashboard padrão
        
        // Padrão: ir para dashboard
        return $_ENV['URL_ADM'] . 'dashboard';
    }

}
