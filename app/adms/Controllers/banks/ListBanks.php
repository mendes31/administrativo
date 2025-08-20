<?php

namespace App\adms\Controllers\banks;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\BanksRepository;
use App\adms\Models\Repository\MenuPermissionUserRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar Bancos
 *
 * Esta classe é responsável por recuperar e exibir uma lista de Bancos no sistema. Utiliza um repositório
 * para obter dados dos Bancos e um serviço de paginação para gerenciar a navegação entre páginas de resultados.
 * Em seguida, carrega a visualização correspondente com os dados recuperados.
 * 
 * @package App\adms\Controllers\banks
 * @author Rafael Mendes
 */
class ListBanks
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var int $limitResult Limite de registros por página */
    private int $limitResult = 10; // Ajuste conforme necessário

    /**
     * Recuperar e listar Bancos com paginação.
     * 
     * Este método recupera os Bancos a partir do repositório de Bancos com base na página atual e no limite
     * de registros por página. Gera os dados de paginação e carrega a visualização para exibir a lista de Bancos.
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
            'bank_name' => $_GET['bank_name'] ?? '',
            'bank_code' => $_GET['bank_code'] ?? '',
            'agency' => $_GET['agency'] ?? '',
            'account' => $_GET['account'] ?? '',
        ];
        // Instanciar o Repository para recuperar os registros do banco de dados
        $listBanks = new BanksRepository();
        $this->data['banks'] = $listBanks->getAllBanks((int) $page, (int) $this->limitResult, $filtros);
        $totalBanks = $listBanks->getAmountBanks($filtros);
        $pagination = PaginationService::generatePagination((int) $totalBanks, (int) $this->limitResult, (int) $page, 'list-banks', array_merge($filtros, ['per_page' => $this->limitResult]));
        $this->data['pagination'] = $pagination;
        $this->data['per_page'] = $this->limitResult;
        $this->data['filtros'] = $filtros;

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Listar Bancos',
            'menu' => 'list-banks',
            'buttonPermission' => ['CreateBank', 'ViewBank', 'UpdateBank', 'DeleteBank'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Apresentar ou ocultar item de menu
        // $menu = ['Dashboard', 'ListUsers', 'ListDepartments', 'ListPositions', 'ListAccessLevels', 'ListPackages', 'ListGroupsPages', 'ListPages'];
        // $menuPermission = new MenuPermissionUserRepository();
        // $this->data['menuPermission'] = $menuPermission->menuPermission($menu);

        // Carregar a VIEW com os dados
        $loadView = new LoadViewService("adms/Views/banks/list", $this->data);
        $loadView->loadView();
    }
}
