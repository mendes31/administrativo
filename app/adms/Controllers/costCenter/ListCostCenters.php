<?php

namespace App\adms\Controllers\costCenter;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\CostCentersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar Centros de Custo
 *
 * Esta classe é responsável por recuperar e exibir uma lista de Centros de Custo no sistema. Utiliza um repositório
 * para obter dados dos Centros de Custo e um serviço de paginação para gerenciar a navegação entre páginas de resultados.
 * Em seguida, carrega a visualização correspondente com os dados recuperados.
 * 
 * @package App\adms\Controllers\costCenter
 * @author Rafael Mendes
 */
class ListCostCenters
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var int $limitResult Limite de registros por página */
    private int $limitResult = 10; // Padrão 10 por página

    /**
     * Recuperar e listar Centros de Custo com paginação.
     * 
     * Este método recupera os Centros de Custo a partir do repositório de Centros de Custo com base na página atual e no limite
     * de registros por página. Gera os dados de paginação e carrega a visualização para exibir a lista de Centros de Custo.
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
        // Filtros de busca
        $filtros = [
            'name' => $_GET['name'] ?? '',
            'code' => $_GET['code'] ?? '',
            'type' => $_GET['type'] ?? '',
        ];
        // Instanciar o Repository para recuperar os registros do banco de dados
        $listCostCenters = new CostCentersRepository();
        $this->data['costCenters'] = $listCostCenters->getAllCostCenters((int) $page, (int) $this->limitResult, $filtros);
        $totalCostCenters = $listCostCenters->getAmountCostCenters($filtros);
        $pagination = PaginationService::generatePagination((int) $totalCostCenters, (int) $this->limitResult, (int) $page, 'list-cost-centers', array_merge($filtros, ['per_page' => $this->limitResult]));
        $this->data['pagination'] = $pagination;
        $this->data['per_page'] = $this->limitResult;
        $this->data['filtros'] = $filtros;

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Listar Centros de Custo',
            'menu' => 'list-cost-centers',
            'buttonPermission' => ['CreateCostCenter', 'ViewCostCenter', 'UpdateCostCenter', 'DeleteCostCenter'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW com os dados
        $loadView = new LoadViewService("adms/Views/costCenter/list", $this->data);
        $loadView->loadView();
    }
}
