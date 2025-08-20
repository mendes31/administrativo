<?php

namespace App\adms\Controllers\users;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationUserPasswordService;
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
        $this->data['user_info'] = [
            'id' => $this->data['form']['id'],
            'name' => $this->data['form']['name'],
            'email' => $this->data['form']['email']
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
        // Instanciar a classe validar os dados do formulario
        $validationUser = new ValidationUserPasswordService();
        $this->data['errors'] = $validationUser->validate($this->data['form']);

        // Acessa o IF quando existir campo com dados incorretos
        if (!empty($this->data['errors'])) {
            // Chamar método carregar a view
            $this->viewUser();
            return;
        }

        // Instanciar Repository para editar o usuário
        $userUpdate = new UsersRepository();
        $result = $userUpdate->updatePasswordUser($this->data['form']);

        // Acessa o IF se o repository retornou TRUE
        if($result){
            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Senha alterada com sucesso! Agora você pode acessar o sistema normalmente.";

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
