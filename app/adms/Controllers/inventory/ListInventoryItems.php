<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvCategoriesRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Services\InventorySapSyncService;
use App\adms\Views\Services\LoadViewService;

class ListInventoryItems
{
    private array|string|null $data = null;

    private int $limitResult = 10;

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_sap_items'])) {
            $this->handleSyncRequest();
            return;
        }

        if (!isset($_SESSION['filtros_list_inventory_items'])) {
            $_SESSION['filtros_list_inventory_items'] = [];
        }

        if (isset($_GET['limpar_filtros']) && $_GET['limpar_filtros'] == '1') {
            unset($_SESSION['filtros_list_inventory_items']);
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-items');
            exit;
        }

        $page = 1;
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }

        $filtros = [
            'code' => $_GET['code'] ?? $_SESSION['filtros_list_inventory_items']['code'] ?? '',
            'description' => $_GET['description'] ?? $_SESSION['filtros_list_inventory_items']['description'] ?? '',
            'active' => $_GET['active'] ?? $_SESSION['filtros_list_inventory_items']['active'] ?? '',
            'categoria_id' => $_GET['categoria_id'] ?? $_SESSION['filtros_list_inventory_items']['categoria_id'] ?? '',
        ];

        if (isset($_GET['code']) || isset($_GET['description']) || isset($_GET['active']) || isset($_GET['categoria_id'])) {
            $_SESSION['filtros_list_inventory_items'] = $filtros;
        }

        if (isset($_GET['per_page']) && in_array((int) $_GET['per_page'], [10, 20, 50, 100], true)) {
            $this->limitResult = (int) $_GET['per_page'];
            $_SESSION['filtros_list_inventory_items']['per_page'] = $this->limitResult;
        } elseif (isset($_SESSION['filtros_list_inventory_items']['per_page'])) {
            $this->limitResult = (int) $_SESSION['filtros_list_inventory_items']['per_page'];
        }

        $repo = new InvItemsRepository();
        $categoriesRepo = new InvCategoriesRepository();
        $this->data['categories'] = $categoriesRepo->getAllForSelect();

        $total = $repo->countAll($filtros);
        $this->data['items'] = $repo->getAll($page, $this->limitResult, $filtros);

        $pagination = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            $page,
            'list-inventory-items',
            array_merge($filtros, ['per_page' => $this->limitResult])
        );
        $this->data['pagination'] = $pagination;
        $this->data['per_page'] = $this->limitResult;
        $this->data['filtros'] = $filtros;

        $pageElements = [
            'title_head' => 'Itens de Estoque',
            'menu' => 'estoque',
            'buttonPermission' => ['CreateInventoryItem', 'UpdateInventoryItem', 'ViewInventoryItem', 'DeleteInventoryItem'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/items/list', $this->data);
        $loadView->loadView();
    }

    private function handleSyncRequest(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!CSRFHelper::validateCSRFToken('form_sync_inventory_items', $token)) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Token CSRF inválido para sincronização.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-items');
            return;
        }

        $service = new InventorySapSyncService();
        $result = $service->syncItemsAndCosts();
        if (!empty($result['success'])) {
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>{$result['message']}</div>";
        } else {
            $message = htmlspecialchars((string) ($result['message'] ?? 'Erro desconhecido na sincronização SAP.'), ENT_QUOTES, 'UTF-8');
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>{$message}</div>";
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-items');
    }
}
