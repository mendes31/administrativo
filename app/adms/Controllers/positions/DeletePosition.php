<?php

namespace App\adms\Controllers\positions;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PositionsRepository;

/**
 * Controller para exclusão de cargo
 *
 * Esta classe gerencia o processo de exclusão de cargo no sistema. Ela lida com a validação dos dados
 * do formulário, a exclusão do cargo do banco de dados e o registro de logs para operações bem-sucedidas ou
 * falhas. Além disso, redireciona o cargo para a página de listagem de cargos com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\positions
 * @author Rafael Mendes de Oliveira
 */
class DeletePosition
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do departamento e processar a exclusão.
     *
     * Este método verifica a validade do token CSRF e a existência do ID do departamento.  Se válido, recupera os
     * detalhes do departamento do banco de dados e tenta excluir o departamento.  Redireciona o departamento para a página de 
     * listagem de departamentos com mensagens apropriadas baseadas no sucesso ou falha da operação.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        
        // Verificar a validade do token CSRF e a existência do ID do Cargo
        if (!isset($this->data['form']['csrf_token']) 
            || !CSRFHelper::validateCSRFToken('form_delete_positions', $this->data['form']['csrf_token']) 
            || !isset($this->data['form']['id'])) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Cargo não encontrado.", []);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Cargo não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-positions");
            return;
        }

        // Instanciar o Repository para recuperar o Cargo
        $deletePosition = new PositionsRepository();
        $this->data['position'] = $deletePosition->getPosition((int) $this->data['form']['id']);

        // Verificar se o deletePosition foi encontrado
        if (!$this->data['position']) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Cargo não encontrado.", ['id' => (int) $this->data['form']['id']]);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Cargo não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-positions");
            return;
        }

        // Tentar excluir o Cargo
        $result = $deletePosition->deletePosition($this->data['form']['id']);

        // Verificar se a exclusão foi bem-sucedida
        if ($result) {
            $matrixService = new \App\adms\Controllers\trainings\TrainingMatrixService();
            $matrixService->updateMatrixForAllUsers();
            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Cargo apagado com sucesso!";
        } else {
            // Criar a mensagem de erro
            $_SESSION['error'] = "Cargo não apagado!";
        }

        // Redirecionar para a página de listagem de Cargo
        header("Location: {$_ENV['URL_ADM']}list-positions");
        return;
    }
}
