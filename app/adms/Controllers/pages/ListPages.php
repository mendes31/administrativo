<?php

namespace App\adms\Controllers\pages;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\PagesRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Helpers\FiltersHelper;

/**
 * Controller para listar páginas
 *
 * Esta classe é responsável por recuperar e exibir uma lista de páginas no sistema. Utiliza um repositório
 * para obter dados das páginas e um serviço de paginação para gerenciar a navegação entre páginas de resultados.
 * Em seguida, carrega a visualização correspondente com os dados recuperados.
 * 
 * @package App\adms\Controllers\pages
 * @author Rafael Mendes
 */
class ListPages
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var int $limitResult Limite de registros por página */
    private int $limitResult = 1000; // Ajuste conforme necessário

    /**
     * Recuperar e listar páginas com paginação.
     * 
     * Este método recupera as páginas a partir do repositório de páginas com base na página atual e no limite
     * de registros por página. Gera os dados de paginação e carrega a visualização para exibir a lista de páginas.
     * 
     * Padrão recomendado: use FiltersHelper::getFilters para ler filtros e paginação de $_GET.
     *
     * @param string|int $page Página atual para a exibição dos resultados. O padrão é 1.
     * 
     * @return void
     */
    public function index(string|int $page = 1): void
    {
        // Padrão robusto para filtros e paginação
        $params = FiltersHelper::getFilters(['nome', 'controller', 'status', 'publica']);
        $perPage = $params['per_page'];
        $currentPage = $params['page'];
        $filters = $params['filters'];

        // Instanciar o Repository para recuperar os registros do banco de dados
        $listPages = new PagesRepository();

        // Calcular total de registros filtrados
        $totalRegistros = (int) $listPages->getAmountPages($filters);
        $totalPaginas = max(1, ceil($totalRegistros / max(1, $perPage)));
        // Se a página atual for maior que o total de páginas, voltar para a página 1
        if ($currentPage > $totalPaginas) {
            header('Location: ' . $_ENV['URL_ADM'] . 'list-pages?page=1');
            exit;
        }

        // Recuperar as páginas para a página atual com filtros
        $this->data['pages'] = $listPages->getAllPages($currentPage, (int) $perPage, $filters);

        // Gerar dados de paginação
        $this->data['pagination'] = PaginationService::generatePagination(
            $totalRegistros,
            (int) $perPage,
            (int) $currentPage,
            'list-pages'
        );

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Listar Páginas',
            'menu' => 'list-pages',
            'buttonPermission' => ['CreatePage', 'ViewPage', 'UpdatePage', 'DeletePage'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Apresentar ou ocultar item de menu
        // $menu = ['Dashboard', 'ListUsers', 'ListDepartments', 'ListPositions', 'ListAccessLevels', 'ListPackages', 'ListGroupsPages', 'ListPages'];
        // $menuPermission = new MenuPermissionUserRepository();
        // $this->data['menuPermission'] = $menuPermission->menuPermission($menu);

        // Carregar a VIEW com os dados
        $loadView = new LoadViewService("adms/Views/pages/list", $this->data);
        $loadView->loadView();
    }
}
