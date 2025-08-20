<?php

namespace App\adms\Controllers\users;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationUserPasswordForceChangeService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para troca obrigatória de senha
 */
class ForcePasswordChange
{
    private array|string|null $data = null;

    public function index(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        file_put_contents(__DIR__ . '/../../../logs/session_debug.log', date('Y-m-d H:i:s') . ' - [force_password_change] INICIO - session_id: ' . session_id() . ' - ' . json_encode($_SESSION) . "\n", FILE_APPEND);
        file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log', date('Y-m-d H:i:s') . " - Início do método index\n", FILE_APPEND);
        file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log', date('Y-m-d H:i:s') . " - Sessão recebida: " . json_encode($_SESSION) . "\n", FILE_APPEND);
        if (empty($_SESSION['user_id'])) {
            file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log', date('Y-m-d H:i:s') . " - Sessão inválida\n", FILE_APPEND);
            $_SESSION['error'] = 'Sessão inválida! Faça login para continuar.';
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_force_password_change', $this->data['form']['csrf_token'])) {
            file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log', date('Y-m-d H:i:s') . " - Submissão do formulário\n", FILE_APPEND);
            $this->editPasswordUser();
        } else {
            $viewUser = new UsersRepository();
            $this->data['form'] = $viewUser->getUser((int)$_SESSION['user_id']);
            if (!$this->data['form']) {
                file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log', date('Y-m-d H:i:s') . " - Usuário não encontrado\n", FILE_APPEND);
                GenerateLog::generateLog('error', 'Usuário não encontrado.', ['id' => (int)$_SESSION['user_id']]);
                $_SESSION['error'] = 'Usuário não encontrado.';
                header('Location: ' . $_ENV['URL_ADM'] . 'login');
                return;
            }
            $this->viewUser();
        }
    }

    private function viewUser(): void
    {
        $pageElements = [
            'title_head' => 'Troca Obrigatória de Senha',
            'menu' => '',
            'buttonPermission' => [],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        $loadView = new LoadViewService('adms/Views/users/forcePasswordChange', $this->data);
        $loadView->loadView();
    }

    private function editPasswordUser(): void
    {
        file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log', date('Y-m-d H:i:s') . " - Início do editPasswordUser\n", FILE_APPEND);
        $validationUser = new ValidationUserPasswordForceChangeService();
        $this->data['errors'] = $validationUser->validate($this->data['form']);
        if (!empty($this->data['errors'])) {
            file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log', date('Y-m-d H:i:s') . " - Erros de validação: " . json_encode($this->data['errors']) . "\n", FILE_APPEND);
            $this->viewUser();
            return;
        }
        $this->data['form']['id'] = $_SESSION['user_id'];
        $userUpdate = new UsersRepository();
        $result = $userUpdate->updatePasswordUser($this->data['form']);
        file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log', date('Y-m-d H:i:s') . " - Resultado updatePasswordUser: " . json_encode($result) . "\n", FILE_APPEND);
        if ($result) {
            file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log', date('Y-m-d H:i:s') . " - Senha alterada, redirecionando para dashboard\n", FILE_APPEND);
            $_SESSION['success'] = 'Senha alterada com sucesso! Agora você pode acessar o sistema normalmente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'dashboard');
            exit;
        } else {
            file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log', date('Y-m-d H:i:s') . " - Falha ao alterar senha\n", FILE_APPEND);
            $this->data['errors'][] = 'Senha não editada!';
            $this->viewUser();
        }
    }
} 