<?php

namespace App\adms\Controllers\customer;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\CustomerRepository;
use App\adms\Models\Repository\FrequencyRepository;

/**
 * Controller para exclusão de Cliente
 *
 * Esta classe gerencia o processo de exclusão de Cliente no sistema. Ela lida com a validação dos dados
 * do formulário, a exclusão do Cliente do banco de dados e o registro de logs para operações bem-sucedidas ou
 * falhas. Além disso, redireciona o Cliente para a página de listagem de Clientes com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\customer
 * @author Rafael Mendes de Oliveira
 */
class DeleteCustomer
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do Cliente e processar a exclusão.
     *
     * Este método verifica a validade do token CSRF e a existência do ID do Cliente.  Se válido, recupera os
     * detalhes do Cliente do banco de dados e tenta excluir o Cliente.  Redireciona o Cliente para a página de 
     * listagem de Clientes com mensagens apropriadas baseadas no sucesso ou falha da operação.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        
        // Verificar a validade do token CSRF e a existência do ID do Cliente
        if (!isset($this->data['form']['csrf_token']) 
            || !CSRFHelper::validateCSRFToken('form_delete_customer', $this->data['form']['csrf_token']) 
            || !isset($this->data['form']['id'])) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Cliente não encontrado.", []);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Cliente não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-customers");
            return;
        }

        // Instanciar o Repository para recuperar o Cliente
        $deleteCustomer = new CustomerRepository();
        $this->data['customer'] = $deleteCustomer->getCustomer((int) $this->data['form']['id']);

        // Verificar se o deleteCostCenter foi encontrado
        if (!$this->data['customer']) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Cliente não encontrado.", ['id' => (int) $this->data['form']['id']]);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Cliente não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-customers");
            return;
        }

        // Tentar excluir o Cliente
        $result = $deleteCustomer->deleteCustomer($this->data['form']['id']);

        // Verificar se a exclusão foi bem-sucedida
        if ($result) {
            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Cliente apagado com sucesso!";
        } else {
            // Criar a mensagem de erro
            $_SESSION['error'] = "Cliente não apagado!";
        }

        // Redirecionar para a página de listagem de Cliente
        header("Location: {$_ENV['URL_ADM']}list-customers");
        return;
    }
}
