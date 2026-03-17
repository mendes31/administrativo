<?php

namespace App\adms\Controllers\users;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationUserPasswordForceChangeService;
use App\adms\Controllers\Services\SecurityService;
use App\adms\Models\Repository\AdmsPasswordPolicyRepository;
use App\adms\Models\Repository\LgpdTermosRepository;
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
        $this->data['title_head'] = 'Troca Obrigatória de Senha';

        // Carregar a política de senha para exibir requisitos na tela
        try {
            $policyRepo = new AdmsPasswordPolicyRepository();
            $this->data['password_policy'] = $policyRepo->getPolicy();
        } catch (\Throwable $e) {
            $this->data['password_policy'] = null;
        }

        // Usar o layout de login para manter a tela "limpa" (sem menu lateral)
        $loadView = new LoadViewService('adms/Views/users/forcePasswordChange', $this->data);
        $loadView->loadViewLogin();
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
            file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log', date('Y-m-d H:i:s') . " - Senha alterada com sucesso, avaliando redirecionamento (LGPD / dashboard)\n", FILE_APPEND);

            // Atualizar atividade da sessão ao concluir a troca obrigatória de senha
            try {
                $sessionRepo = new \App\adms\Models\Repository\AdmsSessionsRepository();
                $sessionId = $_SESSION['session_id'] ?? session_id();
                $sessionRepo->updateSessionActivity((int)($_SESSION['user_id'] ?? 0), (string)$sessionId);
            } catch (\Throwable $e) {
                file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log',
                    date('Y-m-d H:i:s') . " - Erro ao atualizar atividade da sessão após troca obrigatória de senha: " . $e->getMessage() . "\n",
                    FILE_APPEND
                );
            }

            // Após trocar a senha, não é mais necessário manter o flag de troca obrigatória
            unset($_SESSION['force_password_change']);

            // Replicar a lógica de verificação de consentimento LGPD usada no Login::login
            try {
                $userRepo = new UsersRepository();
                $user = $userRepo->getUser((int)($_SESSION['user_id'] ?? 0));

                if ($user && !empty($user['username'])) {
                    $username = $user['username'];
                    $isManager = (strtolower($username) === 'manager');

                    if ($isManager) {
                        file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log',
                            date('Y-m-d H:i:s') . " - Usuario manager detectado após troca obrigatória - pulando verificação de consentimento LGPD\n",
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

                        if ($termoLogin) {
                            $consentVersionAtual = $termoLogin['versao'] ?? ($_ENV['LGPD_CONSENT_VERSION'] ?? '1.0');
                            $temConsentimentoValido = false;
                            $emailLogin = $user['email'] ?? '';

                            $consentRepo = new \App\adms\Models\Repository\LgpdConsentimentosRepository();
                            $ultimoConsent = $consentRepo->getUltimoConsentimentoAtivoPorUsuario((int)$user['id'], 'sistema_login');

                            if (!$ultimoConsent && !empty($emailLogin)) {
                                $ultimoConsent = $consentRepo->getUltimoConsentimentoAtivoPorEmail($emailLogin, 'sistema_login');
                            }

                            file_put_contents(
                                __DIR__ . '/../../../logs/force_password_change_debug.log',
                                date('Y-m-d H:i:s') . ' - Verificando consentimento LGPD após troca obrigatória - email=' . $emailLogin .
                                ' | versao_atual=' . $consentVersionAtual .
                                ' | ultimoConsent=' . json_encode($ultimoConsent) . PHP_EOL,
                                FILE_APPEND
                            );

                            if ($ultimoConsent && !empty($ultimoConsent['versao_termo']) && $ultimoConsent['versao_termo'] === $consentVersionAtual) {
                                $temConsentimentoValido = true;
                            }

                            if (!$temConsentimentoValido) {
                                file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log',
                                    date('Y-m-d H:i:s') . " - Consentimento LGPD pendente após troca obrigatória - redirecionando para lgpd-consentimento-login\n",
                                    FILE_APPEND
                                );
                                $_SESSION['success'] = 'Senha alterada com sucesso! Antes de continuar, revise e aceite o termo de uso de dados pessoais.';
                                header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-consentimento-login');
                                exit;
                            }
                        } else {
                            file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log',
                                date('Y-m-d H:i:s') . " - Nenhum termo LGPD ativo encontrado após troca obrigatória - seguindo para dashboard\n",
                                FILE_APPEND
                            );
                        }
                    }
                }
            } catch (\Throwable $e) {
                file_put_contents(__DIR__ . '/../../../logs/force_password_change_debug.log',
                    date('Y-m-d H:i:s') . " - Erro ao verificar consentimento LGPD após troca obrigatória: " . $e->getMessage() . "\n",
                    FILE_APPEND
                );
            }

            // Caso já tenha consentimento válido (ou não haja termo ativo), seguir para dashboard normalmente
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