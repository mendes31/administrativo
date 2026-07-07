<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvCostProductionBatchesRepository;
use App\adms\Models\Repository\inventory\InvCostProductionWarehousesRepository;
use App\adms\Models\Services\InvCostProductionEfficiencyService;
use App\adms\Models\Services\InventorySapProductionSyncService;
use App\adms\Views\Services\LoadViewService;

class ListInvCostProductionBatches
{
    private array|string|null $data = null;

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_sap_production_batches'])) {
            $this->handleSyncRequest();
            return;
        }

        $filters = [
            'erp_code' => trim((string)($_GET['erp_code'] ?? '')),
            'batch_number' => trim((string)($_GET['batch_number'] ?? '')),
            'warehouse_code' => trim((string)($_GET['warehouse_code'] ?? '')),
            'date_from' => trim((string)($_GET['date_from'] ?? '')),
            'date_to' => trim((string)($_GET['date_to'] ?? '')),
        ];
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [20, 50, 100], true)
            ? (int)$_GET['per_page']
            : 20;

        $repo = new InvCostProductionBatchesRepository();
        $this->data['rows'] = (new InvCostProductionEfficiencyService())->enrichBatchRows(
            $repo->getAll($page, $perPage, $filters)
        );
        $total = $repo->countAll($filters);
        $this->data['filters'] = $filters;
        $this->data['warehouses'] = (new InvCostProductionWarehousesRepository())->getAllActive();
        $this->data['total_rows'] = $total;
        $this->data['production_sync_status'] = (new InventorySapProductionSyncService())->getSyncStatusSummary();

        $pagination = PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'list-inventory-cost-production-batches',
            array_filter(array_merge($filters, ['per_page' => $perPage]))
        );
        $this->data['pagination'] = $pagination;
        $this->data['per_page'] = $perPage;

        $pageElements = [
            'title_head' => 'Lotes Produzidos',
            'menu' => 'estoque',
            'buttonPermission' => ['ListInvCostProductionBatches', 'ListInvCostPeriods', 'SimulateInventoryCost'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/costs/production_batches_list', $this->data);
        $loadView->loadView();
    }

    private function handleSyncRequest(): void
    {
        $token = (string)($_POST['csrf_token'] ?? '');
        if (!CSRFHelper::validateCSRFToken('form_sync_production_batches', $token)) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Token CSRF inválido.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-cost-production-batches');
            return;
        }

        $forceFull = !empty($_POST['sync_force_full']);
        $service = new InventorySapProductionSyncService();
        $result = $service->syncSap(null, $forceFull);

        if (!empty($result['success'])) {
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>" . htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8') . '</div>';
        } else {
            $message = htmlspecialchars((string)($result['message'] ?? 'Erro na sincronização.'), ENT_QUOTES, 'UTF-8');
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>{$message}</div>";
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-cost-production-batches');
    }
}
