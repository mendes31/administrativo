<?php

namespace App\adms\Controllers\login;

use App\adms\Controllers\Services\Validation\ValidationUserPasswordService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\ResetPasswordRepository;
use App\adms\Views\Services\LoadViewService;

class ResetPassword
{
    /** @var array|string|null $data Recebe os dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    public function index(string|null $recoverPassword): void
    {
        // Receber os dados do formulário (POST)
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?? [];

        // Quando acessar via link (GET), pré-preencher o e-mail a partir da URL
        // Ex.: reset-password/{key}?email=usuario@dominio.com
        if (empty($this->data['form']['email']) && isset($_GET['email'])) {
            $this->data['form']['email'] = $_GET['email'];
        }

        // Receber o código recuperar a senha
        $this->data['form']['recover_password'] = (string) $recoverPassword;

        // Verificar se o token CSRF é valido
        if (isset($this->data['form']['csrf_token']) and CSRFHelper::validateCSRFToken('form_reset_password', $this->data['form']['csrf_token'])) {

            // Chamar o método esqueceu a senha 
            $this->resetPassword();
        } else {
            // Chamar método carregar a view nova a senha
        $this->viewResetPassword();
        }
        
    }

    private function viewResetPassword(): void
    {
        // Criar o título da página
        $this->data['title_head'] =  "Nova Senha";

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/login/resetPassword", $this->data);
        $loadView->loadViewLogin();
    }

    private function resetPassword(): void
    {
        // Instanciar a classe validar os dados do fromuláriose com Rakit
        $validationUser = new ValidationUserPasswordService();
        $this->data['errors'] = $validationUser->validate($this->data['form']);

        // Acessa o IF quando existir campo com dados incorretos
        if (!empty($this->data['errors'])) {
            // Chamar método carregar a view
            $this->viewResetPassword();
            return;
        }

        // Instanciar o Repository para recuperar o registro do banco de dados
        $viewUser = new ResetPasswordRepository();
        // Identificador pode ser e-mail ou CPF
        $this->data['user'] = $viewUser->getUser((string) ($this->data['form']['email'] ?? ''));


        // Verificar se existe o registro no banco de dados
        if (!$this->data['user']) {

            // Chamar o método para salvar o log
            GenerateLog::generateLog("error", "Usuário não encontrado.", ['email' => $this->data['form']['email']]);

            // Criar a mensagem de erro 
            $_SESSION['error'] = "Usuário não encontrado.";

            $this->viewResetPassword();

            return;
        }

        // Verificar se o código recuperar a senha é valido 
        if(($this->data['form']['recover_password'] ?? false) and ($this->data['user']['recover_password'] ?? false) and (!password_verify($this->data['form']['recover_password'], $this->data['user']['recover_password']))){

             // Chamar o método para salvar o log
             GenerateLog::generateLog("error", "Código recuperar senha inválido.", ['email' => $this->data['form']['email']]);

             // Criar a mensagem de erro 
             $_SESSION['error'] = "Código recuperar senha inválido.";
 
             $this->viewResetPassword();
 
             return;
        }

        // Verificar se a data de validade da chave recuperar é menor que a data atual
        if($this->data['user']['validate_recover_password'] < date('Y-m-d H:i:s')){

            // Chamar o método para salvar o log
            GenerateLog::generateLog("error", "Código recuperar senha expirado.", ['email' => $this->data['form']['email']]);

            // Criar a mensagem de erro 
            $_SESSION['error'] = "Código recuperar senha expirado.";

            $this->viewResetPassword();

            return;
        }

        // Instanciar Repository para resetar a senha
        // Passar o ID do usuário para o Repository atualizar pelo ID (não depende de e-mail)
        $userUpdate = new ResetPasswordRepository();
        $this->data['form']['user_id'] = $this->data['user']['id'];
        $result = $userUpdate->updatePassword($this->data['form']);

        // Acessa o IF se o repository retornou TRUE
        if($result){
            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Senha editada com sucesso!";

            // Redirecionar o usuário para a pagina view - visualizar usuario
            header("Location: {$_ENV['URL_ADM']}login");
            return;
        }else {
            // Criar a mensagem de erro
            $this->data['errors'][] = "Senha não editada.";

            // Chamar método carregar a view
            $this->viewResetPassword();
        }

    }
}
