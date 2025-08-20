<?php

namespace App\adms\Controllers\users;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\TrainingUsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para exclusão de usuário
 *
 * Esta classe gerencia o processo de exclusão de usuários no sistema. Ela lida com a validação dos dados
 * do formulário, a exclusão do usuário do banco de dados e o registro de logs para operações bem-sucedidas ou
 * falhas. Além disso, redireciona o usuário para a página de listagem de usuários com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\users
 * @author Rafael Mendes <raffaell_mendez@hotmail.com>
 */
class DeleteUser
{
    /** @var array|string|null $data Recebe os dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do usuário e processar a exclusão.
     *
     * Este método verifica a validade do token CSRF e a existência do ID do usuário. Se válido, recupera os
     * detalhes do usuário do banco de dados e tenta excluir o usuário. Redireciona o usuário para a página de 
     * listagem de usuários com mensagens apropriadas baseadas no sucesso ou falha da operação.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Acessar o IF se existir o CSRF e for valido o CSRF
        if (!isset($this->data['form']['csrf_token']) or !CSRFHelper::validateCSRFToken('form_delete_user', $this->data['form']['csrf_token']) or !isset($this->data['form']['id'])) {

            // Chamar o método para salvar o log
            GenerateLog::generateLog("error", "Usuário não encontrado.", []);

            // Criar a mensagem de erro 
            $_SESSION['error'] = "Usuário não encontrado.";

            // Redirecionar o usuário para página listar
            header("Location: {$_ENV['URL_ADM']}list-users");
            return;
        }

        // Instanciar o Repository para recuperar o registro do banco de dados
        $deleteUser = new UsersRepository();
        $this->data['user'] = $deleteUser->getUser((int) $this->data['form']['id']);

        // Verificar se existe o registro no banco de dados
        if (!$this->data['user']) {

            // Chamar o método para salvar o log
            GenerateLog::generateLog("error", "Usuário não encontrado.", ['id' => (int) $this->data['form']['id']]);

            // Criar a mensagem de erro 
            $_SESSION['error'] = "Usuário não encontrado.";

            // Redirecionar o usuário para página listar
            header("Location: {$_ENV['URL_ADM']}list-users");
            return;
        }

        // Instanciar o repositório para apagar o registro do banco de dados
        $result = $deleteUser->deleteUser($this->data['form']['id']);

        // Acessa o IF se o repository retornou TRUE
        if ($result) {
            $trainingUsersRepo = new TrainingUsersRepository();
            $trainingUsersRepo->deleteByUserAndNotInTrainings($this->data['form']['id'], []);
            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Usuário apagado com suscesso!";
        } else {
            // Criar a mensagem de erro
            $_SESSION['error'] = "Usuário não apagado!";
        }

        // Redirecionar o usuário para a pagina view - visualizar usuario
        header("Location: {$_ENV['URL_ADM']}list-users");
        return;
    }
}
