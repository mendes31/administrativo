<?php

namespace App\adms\Controllers\users;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationUserProfilePasswordService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para alterar senha do usuário logado (perfil)
 *
 * Esta classe é responsável por permitir que o usuário logado altere sua própria senha
 * através da área de perfil. É uma versão simplificada da UpdatePasswordUser.
 *
 * @package App\adms\Controllers\users
 * @author Rafael Mendes <raffaell_mendez@hotmail.com>
 */
class UpdatePassword
{
    /** @var array|string|null $data Recebe os dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Construtor da classe
     */
    public function __construct()
    {
        // Verificar se o usuário está logado
        if (empty($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Sessão inválida! Faça login para continuar.';
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }
    }

    /**
     * Exibir formulário para alterar senha.
     *
     * Este método carrega as informações do usuário logado e exibe o formulário de alteração de senha.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Acessar o IF se existir o CSRF e for válido o CSRF
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_update_password_profile', $this->data['form']['csrf_token'])) {
            // Chamar o método editar senha
            $this->editPasswordUser();
        } else {
            // Instanciar o Repository para recuperar o registro do banco de dados
            $viewUser = new UsersRepository();
            $this->data['form'] = $viewUser->getUser($_SESSION['user_id']);

            // Verificar se existe o registro no banco de dados
            if (!$this->data['form']) {
                // Chamar o método para salvar o log
                GenerateLog::generateLog("error", "Usuário não encontrado.", ['id' => $_SESSION['user_id']]);

                // Criar a mensagem de erro 
                $_SESSION['error'] = "Usuário não encontrado.";

                // Redirecionar o usuário para página de login
                header("Location: {$_ENV['URL_ADM']}login");
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
        $pageElements = [
            'title_head' => 'Alterar Senha',
            'menu' => 'profile',
            'buttonPermission' => ['Profile'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/profile/updatePassword", $this->data);
        $loadView->loadView();
    }

    /**
     * Editar a senha do usuário.
     *
     * Este método valida os dados do formulário, atualiza a senha do usuário no repositório e lida com o resultado
     * da operação. Se a atualização for bem-sucedida, o usuário é redirecionado para a página de perfil.
     * 
     * @return void
     */
    private function editPasswordUser(): void 
    {
        // Instanciar a classe validar os dados do formulário
        $validationUser = new ValidationUserProfilePasswordService();
        $this->data['errors'] = $validationUser->validate($this->data['form']);

        // Acessa o IF quando existir campo com dados incorretos
        if (!empty($this->data['errors'])) {
            // Chamar método carregar a view
            $this->viewUser();
            return;
        }

        // Adicionar o ID do usuário logado
        $this->data['form']['id'] = $_SESSION['user_id'];

        // Instanciar Repository para editar a senha do usuário
        $userUpdate = new UsersRepository();
        $result = $userUpdate->updatePasswordUser($this->data['form']);

        // Acessa o IF se o repository retornou TRUE
        if($result){
            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Senha alterada com sucesso!";

            // Redirecionar o usuário para a página de perfil
            header("Location: {$_ENV['URL_ADM']}profile");
            return;
        }else {
            // Criar a mensagem de erro
            $this->data['errors'][] = "Senha não foi alterada!";

            // Chamar método carregar a view
            $this->viewUser();
        }
    }
}
