<?php

namespace App\adms\Controllers\users;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationUserPasswordForceChangeService;
use App\adms\Controllers\Services\SecurityService;
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
        // Garantir que a sessão esteja ativa e estável
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Aguardar um momento para a sessão se estabilizar após redirecionamento
        usleep(100000); // 0.1 segundo
        
        // Verificar se a sessão está válida
        if (empty($_SESSION['user_id']) || empty($_SESSION['force_password_change'])) {
            file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log', date('Y-m-d H:i:s') . " - Sessão inválida ou não autorizada para alteração de senha\n", FILE_APPEND);
            $_SESSION['error'] = 'Sessão inválida ou não autorizada para alteração de senha! Faça login novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }
        
        // Log para debug
        file_put_contents(__DIR__ . '/../../../logs/session_debug.log', date('Y-m-d H:i:s') . ' - [force_password_change] INICIO - session_id: ' . session_id() . ' - ' . json_encode($_SESSION) . "\n", FILE_APPEND);
        file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log', date('Y-m-d H:i:s') . " - Início do método index\n", FILE_APPEND);
        file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log', date('Y-m-d H:i:s') . " - Sessão recebida: " . json_encode($_SESSION) . "\n", FILE_APPEND);
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        
        // Verificar se é uma submissão de formulário
        if (isset($this->data['form']['csrf_token']) && !empty($this->data['form']['csrf_token'])) {
            file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log', date('Y-m-d H:i:s') . " - Tentativa de submissão com token: " . $this->data['form']['csrf_token'] . "\n", FILE_APPEND);
            file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log', date('Y-m-d H:i:s') . " - Tokens na sessão: " . json_encode($_SESSION['csrf_tokens'] ?? []) . "\n", FILE_APPEND);
            
            // Validar token CSRF
            if (CSRFHelper::validateCSRFToken('form_force_password_change', $this->data['form']['csrf_token'])) {
                file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log', date('Y-m-d H:i:s') . " - Token CSRF válido, processando alteração de senha\n", FILE_APPEND);
                $this->editPasswordUser();
                return;
            } else {
                file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log', date('Y-m-d H:i:s') . " - Token CSRF inválido - Token recebido: " . $this->data['form']['csrf_token'] . "\n", FILE_APPEND);
                file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log', date('Y-m-d H:i:s') . " - Tokens disponíveis na sessão: " . json_encode($_SESSION['csrf_tokens'] ?? []) . "\n", FILE_APPEND);
                $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            }
        } else {
            file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log', date('Y-m-d H:i:s') . " - Nenhum token CSRF recebido no formulário\n", FILE_APPEND);
        }
        
        // Carregar dados do usuário para exibir na view
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

        // Validar também contra a política dinâmica completa, incluindo histórico de senhas.
        try {
            $securityService = new SecurityService();
            // Aqui a troca é feita pelo próprio usuário, então o histórico DEVE ser considerado.
            $politica = $securityService->validarPoliticaSenha(
                (string)($this->data['form']['password'] ?? ''),
                (int)($_SESSION['user_id'] ?? 0),
                false
            );
            if (!$politica['valid']) {
                foreach ($politica['errors'] as $msg) {
                    $this->data['errors']['password_policy'] = $msg;
                    break;
                }
            }
        } catch (\Throwable $e) {
            file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log',
                date('Y-m-d H:i:s') . " - Erro ao validar política de senha: " . $e->getMessage() . "\n",
                FILE_APPEND
            );
            GenerateLog::generateLog('error', 'Erro ao validar política de senha na troca obrigatória.', [
                'user_id' => $_SESSION['user_id'] ?? null,
                'exception' => $e->getMessage(),
            ]);
        }
        if (!empty($this->data['errors'])) {
            file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log', date('Y-m-d H:i:s') . " - Erros de validação: " . json_encode($this->data['errors']) . "\n", FILE_APPEND);
            $this->viewUser();
            return;
        }
        $this->data['form']['id'] = $_SESSION['user_id'];
        // Troca obrigatória feita pelo próprio usuário: registrar histórico de senhas
        $this->data['form']['salvar_historico'] = true;
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