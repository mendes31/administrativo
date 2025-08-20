<?php

namespace App\adms\Controllers\documents;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\DocumentsRepository;

/**
 * Controller para exclusão de documento
 *
 * Esta classe gerencia o processo de exclusão de documentos no sistema. Ela lida com a validação dos dados
 * do formulário, a exclusão do documento do banco de dados e o registro de logs para operações bem-sucedidas ou
 * falhas. Além disso, redireciona o usuário para a página de listagem de documentos com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\documents
 * @author Rafael Mendes
 */
class DeleteDocument    
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do documento e processar a exclusão.
     *
     * Este método verifica a validade do token CSRF e a existência do ID do documento. Se válido, recupera os
     * detalhes do documento do banco de dados e tenta excluir o documento. Redireciona o usuário para a página de 
     * listagem de documentos com mensagens apropriadas baseadas no sucesso ou falha da operação.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        
        // Verificar a validade do token CSRF e a existência do ID do documento
        if (!isset($this->data['form']['csrf_token']) 
            || !CSRFHelper::validateCSRFToken('form_delete_document', $this->data['form']['csrf_token']) 
            || !isset($this->data['form']['id'])) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Documento não encontrado.", []);
            header("Location: {$_ENV['URL_ADM']}list-documents");
            return;
        }

        // Instanciar o Repository para recuperar o documento
        $deleteDocument = new DocumentsRepository();
        $this->data['document'] = $deleteDocument->getDocument((int) $this->data['form']['id']);

        // Verificar se o documento foi encontrado
        if (!$this->data['document']) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Documento não encontrado.", ['id' => (int) $this->data['form']['id']]);

            // Criar a mensagem de erro e redirecionar
            $_SESSION['error'] = "Documento não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-documents");
            return;
        }

        // Tentar excluir o documento
        $result = $deleteDocument->deleteDocument($this->data['form']['id']);

        // Verificar se a exclusão foi bem-sucedida
        if ($result) {
            // Criar a mensagem de sucesso
            $_SESSION['success'] = "Documento apagado com sucesso!";
        } else {
            // Criar a mensagem de erro
            $_SESSION['error'] = "Documento não apagado!";
        }

        // Redirecionar para a página de listagem de documentos
        header("Location: {$_ENV['URL_ADM']}list-documents");
        return;
    }
}
