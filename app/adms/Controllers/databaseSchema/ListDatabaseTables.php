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
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refresh_catalog'])) {
            $this->handleRefreshCatalog();

            return;
        }

        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        if (isset($_GET['per_page']) && in_array((int) $_GET['per_page'], [10, 25, 50, 100], true)) {
            $this->limitResult = (int) $_GET['per_page'];
        }

        $filterModule = isset($_GET['module']) ? trim((string) $_GET['module']) : '';
        $filterSearch = isset($_GET['q']) ? trim((string) $_GET['q']) : '';

        $repo = new DatabaseSchemaRepository();

        try {
            set_time_limit(120);
            $repo->ensureCatalogCache();
        } catch (\Throwable $e) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Não foi possível carregar o catálogo de tabelas. Tente novamente.</div>';
        }

        $allTables = $repo->listTables($filterModule !== '' ? $filterModule : null, $filterSearch);
        $total = count($allTables);
        $offset = max(0, ((int) $page - 1) * $this->limitResult);
        $this->data['tables'] = array_slice($allTables, $offset, $this->limitResult);
        $this->data['modules'] = $repo->listModules();
        $this->data['database_name'] = $repo->getDatabaseName();
        $this->data['catalog_updated_at'] = $repo->getCatalogUpdatedAt();
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

    private function handleRefreshCatalog(): void
    {
        $pageLayoutService = new PageLayoutService();
        $layout = $pageLayoutService->configurePageElements([
            'title_head' => 'Biblioteca — Base de dados do sistema',
            'menu' => 'list-database-tables',
            'buttonPermission' => ['ListDatabaseTables'],
        ]);
        if (!in_array('ListDatabaseTables', $layout['menuPermission'] ?? [], true)) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Sem permissão para atualizar o catálogo.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-database-tables');
            exit;
        }

        set_time_limit(180);

        try {
            $result = (new DatabaseSchemaRepository())->refreshCatalogCache();
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Catálogo sincronizado — '
                . (int) $result['table_count'] . ' tabela(s). Novas tabelas e colunas serão carregadas ao abrir cada tabela.</div>';
        } catch (\Throwable $e) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Falha ao atualizar o catálogo. Tente novamente.</div>';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'list-database-tables');
        exit;
    }
}
