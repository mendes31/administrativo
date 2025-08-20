<?php

namespace App\adms\Controllers\documents;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\DocumentsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar um documento
 *
 * Esta classe é responsável por exibir as informações detalhadas de um documento específico. Ela recupera os dados
 * do documento a partir do repositório, valida se o documento existe e carrega a visualização apropriada. Se o documento
 * não for encontrado, uma mensagem de erro é exibida e o usuário é redirecionado para a página de lista de documentos.
 *
 * @package App\adms\Controllers\documents
 * @author Rafael Mendes de Oliveira
 */
class ViewDocument
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do documento.
     *
     * Este método gerencia a recuperação e exibição dos detalhes de um documento específico. Ele valida o ID fornecido,
     * recupera os dados do documento do repositório e carrega a visualização. Se o documento não for encontrado, registra
     * um erro, exibe uma mensagem e redireciona para a página de lista de documentos.
     *
     * @param int|string $id ID do documento a ser visualizado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Validar se o ID é um valor inteiro
        if (!(int) $id) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Documento não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Documento não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-documents");
            return;
        }

        // Instanciar o Repository para recuperar o registro do banco de dados
        $viewDocuments = new DocumentsRepository();
        $this->data['document'] = $viewDocuments->getDocument((int) $id);

        // Verificar se encontrou o registro no banco de dados
        if (!$this->data['document']) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Documento não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Documento não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-documents");
            return;
        }

        // Registrar a visualização do documento
        GenerateLog::generateLog("info", "Visualizado o documento.", ['id' => (int) $id]);

        // Definir o título da página
        $this->data['title_head'] = " Documento";

        // Ativar o item de menu
        $this->data['menu'] = "list-documents";

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Visualizar Documento',
            'menu' => 'list-documents',
            'buttonPermission' => ['ListDocuments', 'UpdateDocument', 'DeleteDocument'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/documents/view", $this->data);
        $loadView->loadView();
    }
}
