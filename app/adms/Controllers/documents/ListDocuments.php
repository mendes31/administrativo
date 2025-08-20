<?php

namespace App\adms\Controllers\documents;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\DocumentsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar documentos
 *
 * Esta classe é responsável por recuperar e exibir uma lista de documentos no sistema. Utiliza um repositório
 * para obter dados dos documentos e um serviço de paginação para gerenciar a navegação entre documentos de resultados.
 * Em seguida, carrega a visualização correspondente com os dados recuperados.
 * 
 * @package App\adms\Controllers\documents
 * @author Rafael Mendes
 */
class ListDocuments
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var int $limitResult Limite de registros por página */
    private int $limitResult = 10; // Padrão 10 por página

    /**
     * Recuperar e listar documentos com paginação.
     * 
     * Este método recupera as documentos a partir do repositório de documentos com base na página atual e no limite
     * de registros por página. Gera os dados de paginação e carrega a visualização para exibir a lista de documentos.
     * 
     * @param string|int $page Página atual para a exibição dos resultados. O padrão é 1.
     * 
     * @return void
     */
    public function index(string|int $page = 1): void
    {
        // Capturar o parâmetro page da URL, se existir
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int)$_GET['page'];
        }
        // Tratar per_page
        if (isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [10, 20, 50, 100])) {
            $this->limitResult = (int)$_GET['per_page'];
        }
        // Capturar filtros
        $filterCodDoc = isset($_GET['cod_doc']) ? trim($_GET['cod_doc']) : '';
        $filterName = isset($_GET['name']) ? trim($_GET['name']) : '';
        $filterVersion = isset($_GET['version']) ? trim($_GET['version']) : '';
        $filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
        // Instanciar o Repository para recuperar os registros do banco de dados
        $listDocuments = new DocumentsRepository();
        $totalDocuments = $listDocuments->getAmountDocuments($filterCodDoc, $filterName, $filterVersion, $filterStatus);
        $this->data['documents'] = $listDocuments->getAllDocuments((int) $page, (int) $this->limitResult, $filterCodDoc, $filterName, $filterVersion, $filterStatus);
        $pagination = PaginationService::generatePagination((int) $totalDocuments, (int) $this->limitResult, (int) $page, 'list-documents', ['per_page' => $this->limitResult, 'cod_doc' => $filterCodDoc, 'name' => $filterName, 'version' => $filterVersion, 'status' => $filterStatus]);
        $this->data['pagination'] = $pagination;
        $this->data['per_page'] = $this->limitResult;
        $this->data['filter_cod_doc'] = $filterCodDoc;
        $this->data['filter_name'] = $filterName;
        $this->data['filter_version'] = $filterVersion;
        $this->data['filter_status'] = $filterStatus;

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Listar Documentos',
            'menu' => 'list-documents',
            'buttonPermission' => ['CreateDocument', 'ViewDocument', 'UpdateDocument', 'DeleteDocument', 'ListDocumentPositions'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW com os dados
        $loadView = new LoadViewService("adms/Views/documents/list", $this->data);
        $loadView->loadView();
    }
}
