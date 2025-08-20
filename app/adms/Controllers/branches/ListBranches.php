<?php

namespace App\adms\Controllers\branches;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\BranchesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar Filiais
 *
 * Esta classe é responsável por recuperar e exibir uma lista de Filiais no sistema. Utiliza um repositório
 * para obter dados das Filiais e um serviço de paginação para gerenciar a navegação entre páginas de resultados.
 * Em seguida, carrega a visualização correspondente com os dados recuperados.
 */
class ListBranches
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var int $limitResult Limite de registros por página */
    private int $limitResult = 10; // Ajuste conforme necessário

    /**
     * Recuperar e listar Filiais com paginação.
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
            'email' => $_GET['email'] ?? '',
            'active' => $_GET['active'] ?? '',
        ];
        // Instanciar o Repository para recuperar os registros do banco de dados
        $listBranches = new BranchesRepository();
        $this->data['branches'] = $listBranches->getAllBranches((int) $page, (int) $this->limitResult, $filtros);
        $totalBranches = $listBranches->getAmountBranches($filtros);
        $pagination = PaginationService::generatePagination((int) $totalBranches, (int) $this->limitResult, (int) $page, 'list-branches', array_merge($filtros, ['per_page' => $this->limitResult]));
        $this->data['pagination'] = $pagination;
        $this->data['per_page'] = $this->limitResult;
        $this->data['filtros'] = $filtros;

        // Definir o título da página
        $pageElements = [
            'title_head' => 'Listar Filiais',
            'menu' => 'list-branches',
            'buttonPermission' => ['CreateBranch', 'ViewBranch', 'UpdateBranch', 'DeleteBranch'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW com os dados
        $loadView = new LoadViewService("adms/Views/branches/list", $this->data);
        $loadView->loadView();
    }
} 