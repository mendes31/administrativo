<?php

namespace App\adms\Controllers\users;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\UsersAccessLevelsRepository;
use App\adms\Models\Repository\UsersDepartmentsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar um usuário
 *
 * Esta classe é responsável por exibir as informações detalhadas de um usuário específico. Ela recupera os dados
 * do usuário a partir do repositório, valida se o usuário existe e carrega a visualização apropriada. Se o usuário
 * não for encontrado, uma mensagem de erro é exibida e o usuário é redirecionado para a página de lista.
 *
 * @package App\adms\Controllers\users
 * @author Rafael Mendes <raffaell_mendez@hotmail.com>
 */
class ViewUser
{
    /** @var array|string|null $data Recebe os dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;
    
    /**
     * Recuperar os detalhes do usuário.
     *
     * Este método gerencia a recuperação e exibição dos detalhes de um usuário específico. Ele valida o ID fornecido,
     * recupera os dados do usuário do repositório e carrega a visualização. Se o usuário não for encontrado, registra
     * um erro, exibe uma mensagem e redireciona para a página de lista de usuários.
     *
     * @param int|string $id ID do usuário a ser visualizado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Acessa o IF se o id for valor do tipo inteiro
        if (!(int) $id) {

            // Chamar o método para salvar o log
            GenerateLog::generateLog("error", "Usuário não encontrado.", ['id' => (int) $id]);

            // Criar a mensagem de erro 
            $_SESSION['error'] = "Usuário não encontrado.";

            // Redirecionar o usuário para página listar
            header("Location: {$_ENV['URL_ADM']}list-users");
            return;
        }

        // Instanciar o Repository para recuperar o registro do banco de dados
        $viewUser = new UsersRepository();
        $this->data['user'] = $viewUser->getUser((int) $id);


        // Verificar se existe o registro no banco de dados
        if (!$this->data['user']) {

            // Chamar o método para salvar o log
            GenerateLog::generateLog("error", "Usuário não encontrado.", ['id' => (int) $id]);

            // Criar a mensagem de erro 
            $_SESSION['error'] = "Usuário não encontrado.";

            // Redirecionar o usuário para página listar
            header("Location: {$_ENV['URL_ADM']}list-users");
            return;
        }

        // Instanciar o Repository para recuperar os niveis de acesso do usuário
        $viewUserAccessLevels = new UsersAccessLevelsRepository();
        $this->data['userAccessLevels'] = $viewUserAccessLevels->getUserAccessLevel((int) $id);
        
        // Instanciar o Repository para recuperar os niveis de acesso do usuário no formato de array
        $this->data['userAccessLevelsArray'] = $viewUserAccessLevels->getUserAccessLevelArray((int) $id);

        // Instanciar o Repository para recuperar todos os niveis de acesso no formato de array
        $this->data['userAllAccessLevelsArray'] = $viewUserAccessLevels->getAllAccessLevels();


        // Instanciar o Repository para recuperar os departamentos do usuário
        $viewUserDepartments = new UsersDepartmentsRepository();
        $this->data['userDepartments'] = $viewUserDepartments->getUserDepartments((int) $id);

        // Instanciar o Repository para recuperar os departamentos do usuário
        $viewDepartment = new UsersRepository();
        $this->data['userDepartment'] = $viewDepartment->getUserDepartments((int) $id);

        // Chamar o método para salvar o log
        GenerateLog::generateLog("error", "Usuário não encontrado.", ['id' => (int) $id]);

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Visualizar Usuário',
            'menu' => 'list-users',
            'buttonPermission' => ['ListUsers', 'UpdateUser', 'UpdateUserImage', 'UpdatePasswordUser', 'DeleteUser', 'UpdateUserAccessLevels'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/users/view", $this->data);
        $loadView->loadView();
    }
}
