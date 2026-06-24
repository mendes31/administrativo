<?php

declare(strict_types=1);

namespace App\adms\Controllers\databaseSchema;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\DatabaseSchemaRepository;
use App\adms\Views\Services\LoadViewService;

class ListDatabaseTables
{
    private array|string|null $data = null;

    private int $limitResult = 25;

    public function index(string|int $page = 1): void
    {
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        if (isset($_GET['per_page']) && in_array((int) $_GET['per_page'], [10, 25, 50, 100], true)) {
            $this->limitResult = (int) $_GET['per_page'];
        }

        $filterModule = isset($_GET['module']) ? trim((string) $_GET['module']) : '';
        $filterSearch = isset($_GET['q']) ? trim((string) $_GET['q']) : '';

        $repo = new DatabaseSchemaRepository();
        $allTables = $repo->listTables($filterModule !== '' ? $filterModule : null, $filterSearch);
        $total = count($allTables);
        $offset = max(0, ((int) $page - 1) * $this->limitResult);
        $this->data['tables'] = array_slice($allTables, $offset, $this->limitResult);
        $this->data['modules'] = $repo->extractModules(
            $filterModule === '' && $filterSearch === ''
                ? $allTables
                : $repo->listTables()
        );
        $this->data['database_name'] = $repo->getDatabaseName();
        $this->data['filter_module'] = $filterModule;
        $this->data['filter_search'] = $filterSearch;
        $this->data['per_page'] = $this->limitResult;
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            (int) $page,
            'list-database-tables',
            [
                'per_page' => $this->limitResult,
                'module' => $filterModule,
                'q' => $filterSearch,
            ]
        );

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements([
            'title_head' => 'Biblioteca — Base de dados do sistema',
            'menu' => 'list-database-tables',
            'buttonPermission' => ['ViewDatabaseTable'],
        ]));

        (new LoadViewService('adms/Views/databaseSchema/list', $this->data))->loadView();
    }
}
