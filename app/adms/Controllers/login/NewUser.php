<?php

namespace App\adms\Controllers\login;

use App\adms\Controllers\Services\Validation\ValidationUserRakitService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class NewUser
{
    /** @var array|string|null $data Recebe os dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    public function index(): void 
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar se o token CSRF é valido
        if (isset($this->data['form']['csrf_token']) and CSRFHelper::validateCSRFToken('form_new_user', $this->data['form']['csrf_token'])) {

            // Chamar o método para cadastrar o usuário 
            $this->addUser();
        } else {
            // Chamar método carregar a view de criação de usuário
            $this->viewUser();
        }
    
    }

    /**
     * Carregar a visualização de criação de usuário.
     * 
     * Este método configura os dados necessários e carrega a view para a criação de um novo usuário.
     * 
     * @return void
     */
    private function viewUser(): void
    {
        // Criar o título da página
        $this->data['title_head'] =  "Novo Usuário";

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/login/newUser", $this->data);
        $loadView->loadViewLogin();
    }

    private function addUser(): void
    {
        // Instanciar a classe validar os dados do fromuláriose com Rakit
        $validationUser = new ValidationUserRakitService();
        $this->data['errors'] = $validationUser->validate($this->data['form']);

        // Acessa o IF quando existir campo com dados incorretos
        if (!empty($this->data['errors'])) {
            // Chamar método carregar a view
            $this->viewUser();
            return;
        }

        // Instanciar o Repository para Criar o usuário
        $userCreate = new UsersRepository();
        $result = $userCreate->createUser($this->data['form']);

        // Acessa o IF se o repository retornou TRUE
        if ($result) {

            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Usuário cadastrado com suscesso!";

            // Redirecionar o usuário para a pagina listar
            header("Location: {$_ENV['URL_ADM']}login");
            return;
        } else {
            // Criar a mensagem de erro
            // $_SESSION['error'] = "Usuário não cadastrado!";
            $this->data['errors'][] = "Usuário não cadastrado!";

            // Chamar método carregar a view
            $this->viewUser();
        }
    }
}