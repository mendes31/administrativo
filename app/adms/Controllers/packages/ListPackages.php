<?php

namespace App\adms\Controllers\packages;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\MenuPermissionUserRepository;
use App\adms\Models\Repository\PackagesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar pacotes
 *
 * Esta classe é responsável por recuperar e exibir uma lista de pacotes no sistema. Utiliza um repositório
 * para obter dados dos pacotes e um serviço de paginação para gerenciar a navegação entre páginas de resultados.
 * Em seguida, carrega a visualização correspondente com os dados recuperados.
 * 
 * @package App\adms\Controllers\packages
 * @author Rafael Mendes
 */
class ListPackages
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var int $limitResult Limite de registros por página */
    private int $limitResult = 10; // Ajuste conforme necessário

    /**
     * Recuperar e listar pacotes com paginação.
     * 
     * Este método recupera os pacotes a partir do repositório de pacotes com base na página atual e no limite
     * de registros por página. Gera os dados de paginação e carrega a visualização para exibir a lista de pacotes.
     * 
     * @param string|int $page Página atual para a exibição dos resultados. O padrão é 1.
     * 
     * @return void
     */
    public function index(string|int $page = 1): void
    {
        // Instanciar o Repository para recuperar os registros do banco de dados
        $listPackages = new PackagesRepository();

        // Recuperar os pacotes para a página atual
        $this->data['packages'] = $listPackages->getAllPackages((int) $page, (int) $this->limitResult);

        // Gerar dados de paginação
        $this->data['pagination'] = PaginationService::generatePagination(
            (int) $listPackages->getAmountPackages(), 
            (int) $this->limitResult, 
            (int) $page, 
            'list-packages'
        );

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Listar Pacotes',
            'menu' => 'list-packages',
            'buttonPermission' => ['CreatePackage', 'ViewPackage', 'UpdatePackage', 'DeletePackage'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Apresentar ou ocultar item de menu
        // $menu = ['Dashboard', 'ListUsers', 'ListDepartments', 'ListPositions', 'ListAccessLevels', 'ListPackages', 'ListGroupsPages', 'ListPages'];
        // $menuPermission = new MenuPermissionUserRepository();
        // $this->data['menuPermission'] = $menuPermission->menuPermission($menu);

        // Carregar a VIEW com os dados
        $loadView = new LoadViewService("adms/Views/packages/list", $this->data);
        $loadView->loadView();
    }
}