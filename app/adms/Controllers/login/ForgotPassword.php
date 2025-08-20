<?php

namespace App\adms\Controllers\login;

use App\adms\Controllers\Services\GenerateKeyService;
use App\adms\Controllers\Services\RecoverPassword;
use App\adms\Controllers\Services\Validation\ValidationEmailService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\SendEmailService;
use App\adms\Models\Repository\LoginRepository;
use App\adms\Models\Repository\ResetPasswordRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;
use PDO;

class ForgotPassword
{
    /** @var array|string|null $data Recebe os dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    public function index(): void
    {
        // $sendEmail = new SendEmailService();
        // $sendEmail->sendEmail('rafael.oliveira@tiaraju.com.br', 'Rafael', 'Recuperar senha', 'Abaixo segue o link para recuperação de sua senha, o mesmo irá expirar em 60 minutos!</b>', 'Abaixo segue o link para recuperação de sua senha, o mesmo irá expirar em 60 minutos!');

        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar se o token CSRF é valido
        if (isset($this->data['form']['csrf_token']) and CSRFHelper::validateCSRFToken('form_forgot_password', $this->data['form']['csrf_token'])) {

            // Chamar o método esqueceu a senha 
            $this->forgotPassword();
        } else {
            // Chamar método carregar a view esqueceu a senha
            $this->viewForgotPassword();
        }
    }

    private function viewForgotPassword(): void
    {
        // Criar o título da página
        $this->data['title_head'] =  "Recuperar Senha";

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/login/forgotPassword", $this->data);
        $loadView->loadViewLogin();
    }
    


    private function forgotPassword(): void 
    {
        // Instanciar a classe validar os dados do fromuláriose com Rakit
        $validationUser = new ValidationEmailService();
        $this->data['errors'] = $validationUser->validate($this->data['form']);

        // Acessa o IF quando existir campo com dados incorretos
        if (!empty($this->data['errors'])) {
            // Chamar método carregar a view
            $this->viewForgotPassword();
            return;
        }

        // Instanciar o Repository para recuperar o registro do banco de dados
        $viewUser = new ResetPasswordRepository();
        $this->data['user'] = $viewUser->getUser((string) $this->data['form']['email']);


        // Verificar se existe o registro no banco de dados
        if (!$this->data['user']) {

            // Chamar o método para salvar o log
            GenerateLog::generateLog("error", "Usuário não encontrado.", ['email' => $this->data['form']['email']]);

            // Criar a mensagem de erro 
            $_SESSION['error'] = "Usuário não encontrado.";

            $this->viewForgotPassword();

            return;
        }

        // Instaciar o serviço para recuperar a senha
        $recoverPassword = new RecoverPassword();
        $resultRecoverPassword = $recoverPassword->recoverPassword($this->data);

        // Verificar se enviou o email com sucesso  
        if(!$resultRecoverPassword){

            // Chamar o método para salvar o log
            GenerateLog::generateLog("error", "Email com instruções para recuperar a senha não enviado.", ['email' => (string) $this->data['form']['email']]);

            // Criar a mensagem de erro 
            $_SESSION['error'] = "Email com instruções para recuperar a senha não enviado, tente novamente ou entre em contato com o e-mail {$_ENV['EMAIL_ADM']}.";

            // Chamar o método carregar a view
            $this->viewForgotPassword();

            return;

        }

        // Criar mensagem de sucesso
        $_SESSION['success'] = "Enviado email com as intruções para recuperar a senha. Acesse a sua caixa de email para recuperar a sua senha.";
        
        // Redirecionar o usuário para página de login
        header("Location: {$_ENV['URL_ADM']}");

    }


}
