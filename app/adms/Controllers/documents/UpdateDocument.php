<?php

namespace App\adms\Controllers\documents;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationPageService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\GroupsPagesRepository;
use App\adms\Models\Repository\PackagesRepository;
use App\adms\Models\Repository\DocumentsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar documentos
 *
 * Esta classe é responsável por gerenciar a edição de informações de um documento existente. Inclui a validação dos dados
 * do formulário, a atualização das informações do documento no repositório e a renderização da visualização apropriada.
 * Caso haja algum problema, como um documento não encontrado ou dados inválidos, mensagens de erro são exibidas e registradas.
 *
 * @package App\adms\Controllers\documents
 * @author Rafael Mendes
 */
class UpdateDocument
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Editar o documento.
     *
     * Este método gerencia o processo de edição de um documento. Recebe os dados do formulário, valida o CSRF token e
     * a existência do documento, e chama o método adequado para editar o documento ou carregar a visualização de edição.
     *
     * @param int|string $id ID do documento a ser editado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar a validade do token CSRF e a existência do ID do documento
        if (!isset($this->data['form']['csrf_token']) 
            || !CSRFHelper::validateCSRFToken('form_update_document', $this->data['form']['csrf_token']) 
            || !isset($this->data['form']['id'])) {

            // Registrar um log de erro
            GenerateLog::generateLog("error", "Documento não encontrado.", ['id' => (int) $this->data['form']['id']]);
            header("Location: {$_ENV['URL_ADM']}list-documents");
            return;
        }

        // Recuperar o registro do documento
            $viewDocument = new DocumentsRepository();
            $this->data['form'] = $viewDocument->getDocument((int) $id);

            // Verificar se o documento foi encontrado
            if (!$this->data['form']) {
                // Registrar o erro e redirecionar
                GenerateLog::generateLog("error", "Documento não encontrado", ['id' => (int) $id]);
                $_SESSION['error'] = "Documento não encontrado!";
                header("Location: {$_ENV['URL_ADM']}list-documents");
                return;
            }

        // Carregar a visualização para edição do documento
        $this->viewDocument();
    }

    /**
    * Carregar a visualização para edição do documento.
     *
     * Este método define o título do documento e
     * 
     * @return void
     */
    private function viewDocument(): void
    {
        // Instanciar o repositório para recuperar os pacotes
        $listPackagesPages = new PackagesRepository();
        $this->data['listPackagesPages'] = $listPackagesPages->getAllPackagesSelect();

        // Instanciar o repositório para recuperar os grupos
        $listgroupsPages = new GroupsPagesRepository();
        $this->data['listgroupsPages'] = $listgroupsPages->getAllGroupsPagesSelect();

        // Definir o título do documento
        // Ativar o item de menu
        // Apresentar ou ocultar botão 

        $pageElements = [
            'title_head' => 'Editar Documento',
            'menu' => 'list-documents',
            'buttonPermission' => ['ListDocuments', 'ViewDocument'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/documents/update", $this->data);
        $loadView->loadView();
    }

    /**
     * Editar o documento.
     *
     * Este método valida os dados do formulário, atualiza as informações do documento no repositório e lida com o resultado
     * da operação. Se a atualização for bem-sucedida, o documento é redirecionado para a visualização do documento editado.
     * Caso contrário, uma mensagem de erro é exibida e a visualização de edição é recarregada.
     * 
     * @return void
     */
    private function editDocument(): void
    {
        // Validar os dados do formulário
        $validationPage = new ValidationPageService();
        $this->data['errors'] = $validationPage->validate($this->data['form']);

        // Se houver erros de validação, recarregar a visualização
        if (!empty($this->data['errors'])) {
            $this->viewDocument();
            return;
        }

        // Atualizar o documento
        $documentUpdate = new DocumentsRepository();
        $result = $documentUpdate->updateDocument($this->data['form']);

        // Verificar o resultado da atualização
        if ($result) {
            $_SESSION['success'] = "Documento editado com sucesso!";
            header("Location: {$_ENV['URL_ADM']}view-document/{$this->data['form']['id']}");
        } else {
            $this->data['errors'][] = "Documento não editado!";
            $this->viewDocument();
        }
    }
}
