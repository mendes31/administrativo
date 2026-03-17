<?php

namespace App\adms\Controllers\users;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationUserPasswordService;
use App\adms\Controllers\Services\SecurityService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar a senha do usuário
 *
 * Esta classe é responsável por gerenciar a edição da senha de um usuário. Inclui a validação dos dados de entrada,
 * a atualização da senha no repositório de usuários e a renderização da visualização apropriada. Caso haja
 * algum problema, como um usuário não encontrado ou dados inválidos, as mensagens de erro são geradas e registradas.
 *
 * @package App\adms\Controllers\users
 * @author Rafael Mendes <raffaell_mendez@hotmail.com>
 */
class UpdatePasswordUser
{

    /** @var array|string|null $data Recebe os dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Editar a senha do usuário.
     *
     * Este método gerencia o processo de edição da senha do usuário. Se o CSRF token for válido e os dados do formulário
     * forem corretos, a senha do usuário é atualizada. Caso contrário, a visualização de edição é carregada com
     * as informações necessárias.
     *
     * @param int|string $id ID do usuário cuja senha deve ser editada.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Verificar se o usuário está logado
        if (empty($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Sessão inválida! Faça login para continuar.';
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Acessar o IF se existir o CSRF e for valido o CSRF
        if (isset($this->data['form']['csrf_token']) and CSRFHelper::validateCSRFToken('form_update_password_user', $this->data['form']['csrf_token'])) {

            // Chamar o método editar
            $this->editPasswordUser();
           

        } else {
            
            // Instanciar o Repository para recuperar o registro do banco de dados
            $viewUser = new UsersRepository();
            $this->data['form'] = $viewUser->getUser((int) $id);

            // Verificar se existe o registro no banco de dados
            if (!$this->data['form']) {

                // Chamar o método para salvar o log
                GenerateLog::generateLog("error", "Usuário não encontrado.", ['id' => (int) $id]);

                // Criar a mensagem de erro 
                $_SESSION['error'] = "Usuário não encontrado.";

                // Redirecionar o usuário para página listar
                header("Location: {$_ENV['URL_ADM']}list-users");
                return;
            }

            // Chamar método carregar a view
            $this->viewUser();
        }
    }

    /**
     * Carregar a visualização para edição da senha do usuário.
     *
     * Este método define o título da página e carrega a visualização de edição de senha com os dados necessários.
     * 
     * @return void
     */
    private function viewUser(): void
    {
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Editar Senha do Usuário',
            'menu' => 'list-users',
            'buttonPermission' => ['ListUsers', 'ViewUser'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        // Adicionar informações do usuário para o cabeçalho
        // Garantir que temos nome/e-mail do usuário mesmo após erro de validação (POST parcial)
        $userId = isset($this->data['form']['id']) ? (int)$this->data['form']['id'] : 0;
        $name   = $this->data['form']['name']  ?? null;
        $email  = $this->data['form']['email'] ?? null;

        if ($userId > 0 && ($name === null || $email === null)) {
            $repo = new UsersRepository();
            $u = $repo->getUser($userId);
            if ($u) {
                $name  = $name  ?? ($u['name']  ?? null);
                $email = $email ?? ($u['email'] ?? null);
            }
        }

        $this->data['user_info'] = [
            'id'    => $userId,
            'name'  => $name,
            'email' => $email,
        ];

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/users/updatePassword", $this->data);
        $loadView->loadView();
    }

    /**
     * Editar a senha do usuário.
     *
     * Este método valida os dados do formulário, atualiza a senha do usuário no repositório e lida com o resultado
     * da operação. Se a atualização for bem-sucedida, o usuário é redirecionado para a página de visualização do usuário.
     * Caso contrário, uma mensagem de erro é exibida e a visualização de edição é recarregada.
     * 
     * @return void
     */
    private function editPasswordUser(): void 
    {
        // Instanciar Repository para editar o usuário
        $userUpdate = new UsersRepository();

        // Gerar senha automática a partir da data de nascimento, se solicitado,
        // antes da validação (assim não exige preenchimento manual dos campos).
        $plainPassword = null;
        if (!empty($this->data['form']['gerar_senha']) && $this->data['form']['gerar_senha'] === '1') {
            $userData = $userUpdate->getUser((int)$this->data['form']['id']);
            $dataNascimento = $userData['data_nascimento'] ?? null;
            if (!empty($dataNascimento)) {
                $ts = strtotime($dataNascimento);
                if ($ts !== false) {
                    $plainPassword = date('dmY', $ts);
                    $this->data['form']['password'] = $plainPassword;
                    $this->data['form']['confirm_password'] = $plainPassword;
                }
            }
        }

        // Instanciar a classe validar os dados do formulario
        $validationUser = new ValidationUserPasswordService();
        $this->data['errors'] = $validationUser->validate($this->data['form']);

        // Validar também contra a política dinâmica (comprimento, complexidade extra, histórico, etc.)
        // Apenas quando não for geração automática de senha provisória.
        if (empty($this->data['form']['gerar_senha']) || $this->data['form']['gerar_senha'] !== '1') {
            try {
                $securityService = new SecurityService();
                // Ignorar histórico de senhas quando a troca é feita pelo administrador
                $politica = $securityService->validarPoliticaSenha(
                    (string)($this->data['form']['password'] ?? ''),
                    (int)($this->data['form']['id'] ?? 0),
                    true // ignorar histórico aqui
                );
                if (!$politica['valid']) {
                    foreach ($politica['errors'] as $msg) {
                        $this->data['errors']['password_policy'] = $msg;
                        break; // mostrar apenas a primeira mensagem para o usuário
                    }
                }
            } catch (\Throwable $e) {
                GenerateLog::generateLog('error', 'Erro ao validar política de senha em UpdatePasswordUser.', [
                    'user_id' => $this->data['form']['id'] ?? null,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        // Acessa o IF quando existir campo com dados incorretos
        if (!empty($this->data['errors'])) {
            // Chamar método carregar a view
            $this->viewUser();
            return;
        }

        // Se o admin marcou "Modificar senha no próximo logon" aqui,
        // persistir essa flag junto com a alteração de senha.
        $updateData = $this->data['form'];
        $updateData['modificar_senha_proximo_logon'] =
            (!empty($this->data['form']['modificar_senha_proximo_logon']) && $this->data['form']['modificar_senha_proximo_logon'] === 'Sim')
                ? 'Sim'
                : 'Não';

        $result = $userUpdate->updatePasswordUser($updateData);

        // Acessa o IF se o repository retornou TRUE
        if($result){
            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Senha alterada com sucesso! Agora você pode acessar o sistema normalmente.";

            // Notificações opcionais (e-mail / WhatsApp) com contexto configurável
            if (!empty($this->data['form']['enviar_notificacao_email']) || !empty($this->data['form']['enviar_notificacao_whatsapp'])) {
                try {
                    // Descobrir o contexto desejado para a mensagem
                    $context = 'unlock';
                    if (!empty($this->data['form']['tipo_mensagem']) && $this->data['form']['tipo_mensagem'] === 'welcome') {
                        $context = 'welcome';
                    }

                    $updatedUser = $userUpdate->getUser((int)$this->data['form']['id']);
                    if ($updatedUser) {
                        $updatedUser['enviar_boas_vindas_email'] = !empty($this->data['form']['enviar_notificacao_email']) ? 1 : 0;
                        $updatedUser['enviar_boas_vindas_whatsapp'] = !empty($this->data['form']['enviar_notificacao_whatsapp']) ? 1 : 0;
                        $updatedUser['celular'] = $updatedUser['celular'] ?? '';
                        \App\adms\Controllers\Services\WelcomeMessageService::sendForNewUser(
                            $updatedUser,
                            isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null,
                            $context,
                            $plainPassword ?? ($this->data['form']['password'] ?? '')
                        );
                    }
                } catch (\Throwable $e) {
                    GenerateLog::generateLog('error', 'Erro ao enviar notificação de senha provisória.', [
                        'user_id' => $this->data['form']['id'] ?? null,
                        'exception' => $e->getMessage(),
                    ]);
                }
            }

            // Se for troca obrigatória, redirecionar para o dashboard
            if (!empty($_GET['force'])) {
                header("Location: {$_ENV['URL_ADM']}dashboard");
                exit;
            }

            // Redirecionar o usuário para a pagina view - visualizar usuario
            header("Location: {$_ENV['URL_ADM']}view-user/{$this->data['form']['id']}");
            return;
        }else {
            // Criar a mensagem de erro
            $this->data['errors'][] = "Senha não editada!";

            // Chamar método carregar a view
            $this->viewUser();
        }

    }
}
