<?php

namespace App\adms\Controllers\costCenter;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\CostCentersRepository;

/**
 * Controller para exclusão de centro de custo
 *
 * Esta classe gerencia o processo de exclusão de Centro de Custo no sistema. Ela lida com a validação dos dados
 * do formulário, a exclusão do Centro de Custo do banco de dados e o registro de logs para operações bem-sucedidas ou
 * falhas. Além disso, redireciona o Centro de Custo para a página de listagem de Centro de Custos com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\costCenter
 * @author Rafael Mendes de Oliveira
 */
class DeleteCostCenter
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do Centro de Custo e processar a exclusão.
     *
     * Este método verifica a validade do token CSRF e a existência do ID do Centro de Custo.  Se válido, recupera os
     * detalhes do Centro de Custo do banco de dados e tenta excluir o Centro de Custo.  Redireciona o Centro de Custo para a página de 
     * listagem de Centro de Custos com mensagens apropriadas baseadas no sucesso ou falha da operação.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        
        // Verificar a validade do token CSRF e a existência do ID do Centro de Custo
        if (!isset($this->data['form']['csrf_token']) 
            || !CSRFHelper::validateCSRFToken('form_delete_cost_center', $this->data['form']['csrf_token']) 
            || !isset($this->data['form']['id'])) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Centro de Custo não encontrado.", []);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Centro de Custo não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-cost-centers");
            return;
        }

        // Instanciar o Repository para recuperar o Centro de Custo
        $deleteCostCenter = new CostCentersRepository();
        $this->data['user'] = $deleteCostCenter->getCostCenter((int) $this->data['form']['id']);

        // Verificar se o deleteCostCenter foi encontrado
        if (!$this->data['user']) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Centro de Custo não encontrado.", ['id' => (int) $this->data['form']['id']]);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Centro de Custo não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-cost-centers");
            return;
        }

        // Tentar excluir o Centro de Custo
        $result = $deleteCostCenter->deleteCostCenter($this->data['form']['id']);

        // Verificar se a exclusão foi bem-sucedida
        if ($result) {
            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Centro de Custo apagado com sucesso!";
        } else {
            // Criar a mensagem de erro
            $_SESSION['error'] = "Centro de Custo não apagado!";
        }

        // Redirecionar para a página de listagem de Centro de Custo
        header("Location: {$_ENV['URL_ADM']}list-cost-centers");
        return;
    }
}
