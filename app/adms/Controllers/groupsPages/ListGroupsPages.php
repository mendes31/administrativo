<?php

namespace App\adms\Controllers\groupsPages;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\GroupsPagesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar grupos de página
 *
 * Esta classe é responsável por recuperar e exibir uma lista de grupos de página no sistema. Utiliza um repositório
 * para obter dados dos grupos de página e um serviço de paginação para gerenciar a navegação entre páginas de resultados.
 * Em seguida, carrega a visualização correspondente com os dados recuperados.
 * 
 * @package App\adms\Controllers\groups
 * @author Rafael Mendes
 */
class ListGroupsPages
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var int $limitResult Limite de registros por página */
    private int $limitResult = 10; // Padrão 10 por página

    /**
     * Recuperar e listar grupos de página com paginação.
     * 
     * Este método recupera os grupos de página a partir do repositório de grupos de página com base na página atual e no limite
     * de registros por página. Gera os dados de paginação e carrega a visualização para exibir a lista de grupos de página.
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
        } else {
            $this->limitResult = 10; // Garante o padrão se não vier nada
        }
        // Capturar filtro de nome
        $filterName = isset($_GET['name']) ? trim($_GET['name']) : '';
        // Instanciar o Repository para recuperar os registros do banco de dados
        $listGroupsPages = new GroupsPagesRepository();
        $totalGroupsPages = $listGroupsPages->getAmountGroupsPages($filterName);
        $this->data['groupsPages'] = $listGroupsPages->getAllGroupsPages((int) $page, (int) $this->limitResult, $filterName);
        $pagination = PaginationService::generatePagination((int) $totalGroupsPages, (int) $this->limitResult, (int) $page, 'list-groups-pages', ['per_page' => $this->limitResult, 'name' => $filterName]);
        $this->data['pagination'] = $pagination;
        $this->data['per_page'] = $this->limitResult;
        $this->data['filter_name'] = $filterName;
        
        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Listar Grupos de Páginas',
            'menu' => 'list-groups-pages',
            'buttonPermission' => ['CreateGroupPage', 'ViewGroupPage', 'UpdateGroupPage', 'DeleteGroupPage'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Apresentar ou ocultar item de menu
        // $menu = ['Dashboard', 'ListUsers', 'ListDepartments', 'ListPositions', 'ListAccessLevels', 'ListPackages', 'ListGroupsPages', 'ListPages'];
        // $menuPermission = new MenuPermissionUserRepository();
        // $this->data['menuPermission'] = $menuPermission->menuPermission($menu);
        
        // Carregar a VIEW com os dados
        $loadView = new LoadViewService("adms/Views/groupsPages/list", $this->data);
        $loadView->loadView();
    }
}
