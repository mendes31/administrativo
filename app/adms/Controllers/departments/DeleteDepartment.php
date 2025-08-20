<?php

namespace App\adms\Controllers\departments;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\DepartmentsRepository;

/**
 * Controller para exclusão de departamento
 *
 * Esta classe gerencia o processo de exclusão de departamento no sistema. Ela lida com a validação dos dados
 * do formulário, a exclusão do departamento do banco de dados e o registro de logs para operações bem-sucedidas ou
 * falhas. Além disso, redireciona o departamento para a página de listagem de departamentos com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\departments
 * @author Rafael Mendes de Oliveira
 */
class DeleteDepartment
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
        
        // Verificar a validade do token CSRF e a existência do ID do Departamento
        if (!isset($this->data['form']['csrf_token']) 
            || !CSRFHelper::validateCSRFToken('form_delete_departments', $this->data['form']['csrf_token']) 
            || !isset($this->data['form']['id'])) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Departamento não encontrado.", []);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Departamento não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-departments");
            return;
        }

        // Instanciar o Repository para recuperar o Departamento
        $deleteDepartment = new DepartmentsRepository();
        $this->data['user'] = $deleteDepartment->getDepartment((int) $this->data['form']['id']);

        // Verificar se o deleteDepartment foi encontrado
        if (!$this->data['user']) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "deleteDepartment não encontrado.", ['id' => (int) $this->data['form']['id']]);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "deleteDepartment não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-departments");
            return;
        }

        // Tentar excluir o Departamento
        $result = $deleteDepartment->deleteDepartment($this->data['form']['id']);

        // Verificar se a exclusão foi bem-sucedida
        if ($result) {
            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Departamento apagado com sucesso!";
        } else {
            // Criar a mensagem de erro
            $_SESSION['error'] = "Departamento não apagado!";
        }

        // Redirecionar para a página de listagem de Departamento
        header("Location: {$_ENV['URL_ADM']}list-departments");
        return;
    }
}
