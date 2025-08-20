<?php

namespace App\adms\Controllers\users;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para gerenciar o perfil do usuário logado
 *
 * Esta classe é responsável por permitir que o usuário visualize e edite suas informações pessoais,
 * incluindo nome, email e foto. Todas as operações são restritas ao usuário logado.
 *
 * @package App\adms\Controllers\users
 * @author Rafael Mendes <raffaell_mendez@hotmail.com>
 */
class Profile
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
     * Exibir o perfil do usuário logado.
     *
     * Este método carrega as informações do usuário logado e exibe a visualização do perfil.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Acessar o IF se existir o CSRF e for válido o CSRF
        if (isset($this->data['form']['csrf_token'])) {
            // Verificar se é para remover foto ou atualizar
            if (isset($this->data['form']['remove_photo']) && CSRFHelper::validateCSRFToken('form_remove_photo', $this->data['form']['csrf_token'])) {
                // Chamar o método para remover foto
                $this->removePhoto();
            } elseif (CSRFHelper::validateCSRFToken('form_update_profile', $this->data['form']['csrf_token'])) {
                // Chamar o método editar
                $this->updateProfile();
            } else {
                // CSRF inválido
                $_SESSION['error'] = 'Token de segurança inválido!';
                $this->loadUserData();
            }
        } else {
            // Carregar dados do usuário
            $this->loadUserData();
        }
    }

    /**
     * Carregar dados do usuário do banco de dados.
     *
     * Este método recupera as informações do usuário logado e verifica se o registro existe.
     * 
     * @return void
     */
    private function loadUserData(): void
    {
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
        $this->viewProfile();
    }

    /**
     * Carregar a visualização do perfil do usuário.
     *
     * Este método define o título da página e carrega a visualização do perfil com os dados necessários.
     * 
     * @return void
     */
    private function viewProfile(): void
    {
        // Definir o título da página
        $pageElements = [
            'title_head' => 'Meu Perfil',
            'menu' => 'profile',
            'buttonPermission' => ['Profile'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/profile/index", $this->data);
        $loadView->loadView();
    }

    /**
     * Atualizar o perfil do usuário.
     *
     * Este método valida os dados do formulário, atualiza as informações do usuário no repositório e lida com o resultado
     * da operação. Se a atualização for bem-sucedida, o usuário é redirecionado para a página de perfil.
     * Caso contrário, uma mensagem de erro é exibida e a visualização é recarregada.
     * 
     * @return void
     */
    private function updateProfile(): void 
    {
        // Verificar se há imagem para upload
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['success'] = "Nenhuma alteração foi feita.";
            header("Location: {$_ENV['URL_ADM']}profile");
            return;
        }

        // Adicionar o ID do usuário logado e a imagem
        $this->data['form']['id'] = $_SESSION['user_id'];
        $this->data['form']['image'] = $_FILES['image'];

        // Instanciar UserImageRepository para atualizar a imagem
        $userImageRepo = new \App\adms\Models\Repository\UserImageRepository();
        $result = $userImageRepo->updateUserImage($this->data['form']);

        // Acessa o IF se o repository retornou TRUE
        if($result){
            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Foto do perfil atualizada com sucesso!";

            // Redirecionar o usuário para a página de perfil
            header("Location: {$_ENV['URL_ADM']}profile");
            return;
        }else {
            // Criar a mensagem de erro
            $this->data['errors'][] = "Foto do perfil não foi atualizada!";

            // Chamar método carregar a view
            $this->viewProfile();
        }
    }

    /**
     * Remover a foto do perfil do usuário.
     *
     * Este método remove a foto atual do usuário e define a foto padrão do sistema.
     * 
     * @return void
     */
    private function removePhoto(): void
    {
        // Adicionar o ID do usuário logado
        $this->data['form']['id'] = $_SESSION['user_id'];
        
        // Definir a foto padrão (apenas o nome do arquivo)
        $this->data['form']['image'] = 'icon_user.png';

        // Instanciar UsersRepository para atualizar apenas a imagem no banco
        $userUpdate = new UsersRepository();
        $result = $userUpdate->updateUserProfileImage($this->data['form']);

        // Acessa o IF se o repository retornou TRUE
        if($result){
            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Foto do perfil removida com sucesso!";

            // Redirecionar o usuário para a página de perfil
            header("Location: {$_ENV['URL_ADM']}profile");
            return;
        }else {
            // Criar a mensagem de erro
            $this->data['errors'][] = "Foto do perfil não foi removida!";

            // Chamar método carregar a view
            $this->viewProfile();
        }
    }
}
