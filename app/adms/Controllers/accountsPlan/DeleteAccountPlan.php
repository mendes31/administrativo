<?php

namespace App\adms\Controllers\accountsPlan;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\AccountPlanRepository;
use App\adms\Models\Repository\FrequencyRepository;

/**
 * Controller para exclusão de PLano de Contas
 *
 * Esta classe gerencia o processo de exclusão de Plano de Contas no sistema. Ela lida com a validação dos dados
 * do formulário, a exclusão do Plano de Contas do banco de dados e o registro de logs para operações bem-sucedidas ou
 * falhas. Além disso, redireciona o Plano de Contas para a página de listagem de Plano de Contass com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\frequency
 * @author Rafael Mendes de Oliveira
 */
class DeleteAccountPlan
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do Plano de Contas e processar a exclusão.
     *
     * Este método verifica a validade do token CSRF e a existência do ID do Plano de Contas.  Se válido, recupera os
     * detalhes do Plano de Contas do banco de dados e tenta excluir o Plano de Contas.  Redireciona o Plano de Contas para a página de 
     * listagem de Plano de Contass com mensagens apropriadas baseadas no sucesso ou falha da operação.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        
        // Verificar a validade do token CSRF e a existência do ID do Plano de Contas
        if (!isset($this->data['form']['csrf_token']) 
            || !CSRFHelper::validateCSRFToken('form_delete_account_plan', $this->data['form']['csrf_token']) 
            || !isset($this->data['form']['id'])) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Plano de Contas não encontrado.", []);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Plano de Contas não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-accounts-plan");
            return;
        }

        // Instanciar o Repository para recuperar o Plano de Contas
        $deleteAccountPlan = new AccountPlanRepository();
        $this->data['accountPlan'] = $deleteAccountPlan->getAccountPlan((int) $this->data['form']['id']);

        // Verificar se o deleteCostCenter foi encontrado
        if (!$this->data['accountPlan']) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Plano de Contas não encontrado.", ['id' => (int) $this->data['form']['id']]);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Plano de Contas não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-accounts-plan");
            return;
        }

        // Tentar excluir o Plano de Contas
        $result = $deleteAccountPlan->deleteAccountPlan($this->data['form']['id']);

        // Verificar se a exclusão foi bem-sucedida
        if ($result) {
            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Plano de Contas apagado com sucesso!";
        } else {
            // Criar a mensagem de erro
            $_SESSION['error'] = "Plano de Contas não apagado!";
        }

        // Redirecionar para a página de listagem de Plano de Contas
        header("Location: {$_ENV['URL_ADM']}list-accounts-plan");
        return;
    }
}
