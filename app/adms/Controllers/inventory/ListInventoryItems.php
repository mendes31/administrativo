<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Services\InventorySapSyncService;
use App\adms\Views\Services\LoadViewService;

class ListInventoryItems
{
    private array|string|null $data = null;

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_sap_items'])) {
            $this->handleSyncRequest();
            return;
        }
        $filters = [
            'code' => $_GET['code'] ?? '',
            'description' => $_GET['description'] ?? '',
            'active' => $_GET['active'] ?? ''
        ];
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

        $repo = new InvItemsRepository();
        $this->data['items'] = $repo->getAll($page, $perPage, $filters);
        $total = $repo->countAll($filters);

        $pagination = \App\adms\Controllers\Services\PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'list-inventory-items',
            array_filter([
                'code' => $filters['code'],
                'description' => $filters['description'],
                'active' => $filters['active'],
                'per_page' => $perPage
            ])
        );
        $this->data['paginator'] = $pagination['html'];

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
        $token = (string)($_POST['csrf_token'] ?? '');
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
            $message = htmlspecialchars((string)($result['message'] ?? 'Erro desconhecido na sincronização SAP.'), ENT_QUOTES, 'UTF-8');
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>{$message}</div>";
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-items');
    }
}









