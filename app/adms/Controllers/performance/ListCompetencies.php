<?php

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\CompetenciesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar competências
 */
class ListCompetencies
{
    private array|string|null $data = null;
    private int $limitResult = 20;

    public function index(string|int $page = 1): void
    {
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int)$_GET['page'];
        }

        // Limpar filtros
        if (isset($_GET['limpar'])) {
            unset($_SESSION['list_competencies_filters']);
            header('Location: ' . $_ENV['URL_ADM'] . 'list-competencies');
            exit;
        }

        // Filtros
        $filters = [];
        if (isset($_GET['competency_type']) && !empty($_GET['competency_type'])) {
            $filters['competency_type'] = $_GET['competency_type'];
        }
        if (isset($_GET['category']) && !empty($_GET['category'])) {
            $filters['category'] = $_GET['category'];
        }
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }

        if (!empty($filters)) {
            $_SESSION['list_competencies_filters'] = $filters;
        } elseif (isset($_SESSION['list_competencies_filters'])) {
            $filters = $_SESSION['list_competencies_filters'];
        }

        // Buscar competências
        $repository = new CompetenciesRepository();
        $this->data['competencies'] = $repository->getAll($filters);

        // Dados para a view
        $this->data['filters'] = $filters;
        $this->data['page'] = $page;

        // Configurar elementos da página
        $pageElements = [
            'title_head' => 'Listar Competências',
            'menu' => 'list-competencies',
            'buttonPermission' => [
                'CreateCompetency',
                'ViewCompetency',
                'UpdateCompetency',
                'DeleteCompetency',
                'CompetencyMatrix',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/performance/list_competencies', $this->data);
        $loadView->loadView();
    }
}

