<?php

namespace App\adms\Controllers\login;

use App\adms\Controllers\Services\Validation\ValidationLoginService;
use App\adms\Controllers\Services\ValidationUserLogin;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\LogsRepository;
use App\adms\Models\Repository\LogAcessosRepository;
use App\adms\Views\Services\LoadViewService;

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
        if (isset($_SESSION['user_id'])) {
            header("Location: {$_ENV['URL_ADM']}dashboard");
            exit;
        }
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar se o token CSRF é valido
        if (isset($this->data['form']['csrf_token']) and CSRFHelper::validateCSRFToken('form_login', $this->data['form']['csrf_token'])) {

            // Chamar o método Login
             $this->login();

        } else {
            // Chamar método para carregar a viewLogin
            $this->viewLogin();
        }
        
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

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/login/login", $this->data);
        $loadView->loadViewLogin();
    }

    private function login(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        file_put_contents(__DIR__ . '/../../../logs/session_debug.log', date('Y-m-d H:i:s') . ' - [login] INICIO - session_id: ' . session_id() . ' - ' . json_encode($_SESSION) . "\n", FILE_APPEND);
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
                $sessionRepo->invalidateAllSessionsByUserId((int)$result['id']);
                $sessionRepo->saveSession((int)$result['id'], session_id());
                header("Location: {$_ENV['URL_ADM']}force-password-change");
                exit;
            }
            file_put_contents(__DIR__ . '/../../../logs/login_debug.log', date('Y-m-d H:i:s') . " - Redirecionando para dashboard\n", FILE_APPEND);
            $logAcessosRepo = new LogAcessosRepository();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $logAcessosRepo->registrarAcesso((int)$result['id'], 'LOGIN', $ip, $userAgent);
            if($_ENV['APP_LOGS'] == 'Sim'){
            $dataLogs = [
                'table_name' => 'adms_users',
                'action' => 'login',
                'record_id' => 0,
                'description' => 'login',
            ];
            $insertLogs = new LogsRepository();
            $insertLogs->insertLogs($dataLogs);
            }
            $sessionRepo = new \App\adms\Models\Repository\AdmsSessionsRepository();
            $sessionRepo->invalidateAllSessionsByUserId((int)$result['id']);
            $_SESSION['session_id'] = session_id();
            $sessionRepo->saveSession((int)$result['id'], session_id());
            file_put_contents(__DIR__ . '/../../../logs/session_debug.log', date('Y-m-d H:i:s') . ' - [login] SALVOU SESSION NO BANCO: ' . session_id() . ' - $_SESSION: ' . json_encode($_SESSION) . "\n", FILE_APPEND);
            header("Location: {$_ENV['URL_ADM']}dashboard");
            exit;
        } else {
            file_put_contents(__DIR__ . '/../../../logs/login_debug.log', date('Y-m-d H:i:s') . " - Falha no login\n", FILE_APPEND);
            $this->viewLogin();
            return;
        }
    }

}
