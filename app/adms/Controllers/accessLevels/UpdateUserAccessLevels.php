<?php

namespace App\adms\Controllers\accessLevels;

use App\adms\Controllers\Services\Validation\ValidationUserAccessLevelService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LogsRepository;
use App\adms\Models\Repository\UsersAccessLevelsRepository;

class UpdateUserAccessLevels
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;


    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        
        // var_dump($this->data['form']);
        // exit;
        // var_dump($this->data['form']);

        // Validar o CSRF token e a existência do ID do nível de acesso
        if (
            isset($this->data['form']['csrf_token']) &&
            CSRFHelper::validateCSRFToken('form_update_access_level', $this->data['form']['csrf_token'])
        ) {

            // Editar o nível de acesso do usuário
            $this->editUserAccessLevel();
        } else {
        }
    }
    private function viewUserAccessLevel(): void
    {

        // Registrar o erro e redirecionar
        GenerateLog::generateLog("error", "Nível de acesso do usuário não editado.", ['id' => (int) $this->data['form']['adms_user_id']]);

        $_SESSION['error'] = "Nível de acesso do usuário não editado!";
        header("Location: {$_ENV['URL_ADM']}view-user/{$this->data['form']['adms_user_id']}");
        return;
    }


    private function editUserAccessLevel(): void
    {

        // Validar os dados do formulário
        $validationUserAccessLevel = new ValidationUserAccessLevelService();
        $_SESSION['errors'] = $validationUserAccessLevel->validate($this->data['form']);

        // Se houver erros de validação, recarregar a visualização
        if (!empty($_SESSION['errors'])) {
            $this->viewUserAccessLevel();
            return;
        }

        // Atualizar o nível de acesso
        $userAccessLevelsUpdate = new UsersAccessLevelsRepository();
        $result = $userAccessLevelsUpdate->updateUserAccessLevel($this->data['form']);

        // var_dump($this->data['form']);
        // var_dump($result);
        // exit;

        // Verificar o resultado da atualização
        if ($result) {

            // gravar logs na tabela adms-logs
            if ($_ENV['APP_LOGS'] == 'Sim') {
                $dataLogs = [
                    'table_name' => 'adms_users_access_levels',
                    'action' => 'edição',
                    'record_id' => $this->data['form']['adms_user_id'],
                    // 'description' => $this->data['form']['userAccessLevelsArray'],
                    'description' => 'Alteração de níveis de acesso do usuário',
    
                ];
                // Instanciar a classe validar  o usuário
                $insertLogs = new LogsRepository();
                $insertLogs->insertLogs($dataLogs);
            }
            
            $_SESSION['success'] = "Nível de acesso do usuário editado com sucesso!";
            header("Location: {$_ENV['URL_ADM']}view-user/{$this->data['form']['adms_user_id']}");
        } else {
            $this->data['errors'][] = "Nível de acesso do usuário não editado!";
            $this->viewUserAccessLevel();
        }


    }
}
