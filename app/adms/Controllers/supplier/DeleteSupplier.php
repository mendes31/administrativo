<?php

namespace App\adms\Controllers\supplier;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\SupplierRepository;

/**
 * Controller para exclusão de Fornecedor
 *
 * Esta classe gerencia o processo de exclusão de Fornecedor no sistema. Ela lida com a validação dos dados
 * do formulário, a exclusão do Fornecedor do banco de dados e o registro de logs para operações bem-sucedidas ou
 * falhas. Além disso, redireciona o Fornecedor para a página de listagem de Fornecedors com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\supplier
 * @author Rafael Mendes de Oliveira
 */
class DeleteSupplier
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do Fornecedor e processar a exclusão.
     *
     * Este método verifica a validade do token CSRF e a existência do ID do Fornecedor.  Se válido, recupera os
     * detalhes do Fornecedor do banco de dados e tenta excluir o Fornecedor.  Redireciona o Fornecedor para a página de 
     * listagem de Fornecedors com mensagens apropriadas baseadas no sucesso ou falha da operação.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        
        // Verificar a validade do token CSRF e a existência do ID do Fornecedor
        if (!isset($this->data['form']['csrf_token']) 
            || !CSRFHelper::validateCSRFToken('form_delete_supplier', $this->data['form']['csrf_token']) 
            || !isset($this->data['form']['id'])) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Fornecedor não encontrado.", []);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Fornecedor não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-suppliers");
            return;
        }

        // Instanciar o Repository para recuperar o Fornecedor
        $deleteSupplier = new SupplierRepository();
        $this->data['supplier'] = $deleteSupplier->getSupplier((int) $this->data['form']['id']);

        // Verificar se o deleteCostCenter foi encontrado
        if (!$this->data['supplier']) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Fornecedor não encontrado.", ['id' => (int) $this->data['form']['id']]);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Fornecedor não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-suppliers");
            return;
        }

        // Tentar excluir o Fornecedor
        $result = $deleteSupplier->deleteSupplier($this->data['form']['id']);

        // Verificar se a exclusão foi bem-sucedida
        if ($result) {
            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Fornecedor apagado com sucesso!";
        } else {
            // Criar a mensagem de erro
            $_SESSION['error'] = "Fornecedor não apagado!";
        }

        // Redirecionar para a página de listagem de Fornecedor
        header("Location: {$_ENV['URL_ADM']}list-suppliers");
        return;
    }
}
