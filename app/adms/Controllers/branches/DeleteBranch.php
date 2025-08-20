<?php

namespace App\adms\Controllers\branches;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\BranchesRepository;
use App\adms\Models\Repository\LogsRepository;

/**
 * Controller para exclusão de Filial
 *
 * Esta classe gerencia o processo de exclusão de Filial no sistema. Ela lida com a validação dos dados
 * do formulário, a exclusão da Filial do banco de dados e o registro de logs para operações bem-sucedidas ou
 * falhas. Além disso, redireciona o usuário para a página de listagem de Filiais com mensagens de sucesso ou erro.
 */
class DeleteBranch
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes da filial e processar a exclusão.
     *
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        
        // Verificar a validade do token CSRF e a existência do ID da Filial
        if (!isset($this->data['form']['csrf_token']) 
            || !CSRFHelper::validateCSRFToken('form_delete_branch', $this->data['form']['csrf_token']) 
            || !isset($this->data['form']['id'])) {

            GenerateLog::generateLog("error", "Filial não encontrada.", []);
            $_SESSION['error'] = "Filial não encontrada!";
            header("Location: {$_ENV['URL_ADM']}list-branches");
            return;
        }

        // Instanciar o Repository para recuperar a Filial
        $deleteBranch = new BranchesRepository();
        $this->data['branch'] = $deleteBranch->getBranch((int) $this->data['form']['id']);

        // Verificar se a filial foi encontrada
        if (!$this->data['branch']) {
            GenerateLog::generateLog("error", "Filial não encontrada.", ['id' => (int) $this->data['form']['id']]);
            $_SESSION['error'] = "Filial não encontrada!";
            header("Location: {$_ENV['URL_ADM']}list-branches");
            return;
        }

        // Tentar excluir a Filial
        $result = $deleteBranch->deleteBranch($this->data['form']['id']);

        if ($result) {
            if ($_ENV['APP_LOGS'] == 'Sim') {
                $dataLogs = [
                    'table_name' => 'adms_branches',
                    'action' => 'exclusão',
                    'record_id' => $this->data['form']['id'],
                    'description' => $this->data['branch']['name'],
                ];
                $insertLogs = new LogsRepository();
                $insertLogs->insertLogs($dataLogs);
            }
            $_SESSION['success'] = "Filial apagada com sucesso!";
        } else {
            $_SESSION['error'] = "Filial não apagada!";
        }

        header("Location: {$_ENV['URL_ADM']}list-branches");
        return;
    }
} 