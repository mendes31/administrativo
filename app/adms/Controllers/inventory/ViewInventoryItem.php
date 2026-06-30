<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\InvCostProjectHelper;
use App\adms\Models\Repository\inventory\InvItemBomRepository;
use App\adms\Models\Repository\inventory\InvItemOperationsRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Repository\inventory\InvBalancesRepository;
use App\adms\Models\Repository\inventory\InvMovementsRepository;
use App\adms\Models\Services\InventoryCostService;
use App\adms\Models\Services\InventorySapSyncService;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class ViewInventoryItem
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $itemId = (int)$id;
        $repo = new InvItemsRepository();
        $item = $repo->getOne($itemId);

        if (!$item) {
            $_SESSION['error'] = 'Item não encontrado!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-items');
            return;
        }

        if (
            isset($_POST['recalculate_cost'])
            && CSRFHelper::validateCSRFToken('form_view_inventory_item_cost', $_POST['csrf_token'] ?? '')
        ) {
            InventoryCostService::recalculateStandardCost($itemId);
            $_SESSION['success'] = 'Custo padrão recalculado com base na lista de materiais e na rota.';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-inventory-item/' . $itemId);
            return;
        }

        if (isset($_POST['sync_sap_structure_item_id'])) {
            $this->handleSyncStructureRequest($itemId);
            return;
        }

        $this->data['item'] = $item;
        $balancesRepo = new InvBalancesRepository();
        $balances = $balancesRepo->getBalancesByItem((int)$id);
        $this->data['balances'] = $balances;
        // Calcula custo médio ponderado geral para exibir no cabeçalho
        $totalQty = 0.0; $totalVal = 0.0;
        foreach ($balances as $b) {
            $q = (float)($b['qty'] ?? 0);
            $ac = (float)($b['average_cost'] ?? 0);
            $totalQty += $q;
            $totalVal += $q * $ac;
        }
        $this->data['header_average_cost'] = $totalQty > 0 ? ($totalVal / $totalQty) : (float)($item['average_cost'] ?? 0);

        // Último custo: obter da última entrada registrada
        $movRepo = new InvMovementsRepository();
        $lastCost = $movRepo->getLastEntryUnitCost((int)$id);
        if ($lastCost !== null) {
            $item['last_cost'] = $lastCost;
        }
        $this->data['item'] = $item;

        $bomRepo = new InvItemBomRepository();
        $opsRepo = new InvItemOperationsRepository();
        $this->data['bom'] = $bomRepo->getByItem($itemId);
        $this->data['operations'] = $opsRepo->getByItem($itemId);
        $this->data['cost_breakdown'] = InventoryCostService::calculateBreakdown($itemId);
        $this->data['has_structure'] = !empty($this->data['bom']) || !empty($this->data['operations']);
        $this->data['is_project_item'] = InvCostProjectHelper::isProjectItem($item);

        $pageElements = [
            'title_head' => 'Visualizar Item de Estoque',
            'menu' => 'estoque',
            'buttonPermission' => ['ListInventoryItems', 'UpdateInventoryItem', 'SimulateInventoryCost'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $itemId = (int) $id;
        if ($itemId > 0) {
            $returnUrl = $_ENV['URL_ADM'] . 'view-inventory-item/' . $itemId;
            $this->data['log_resumo'] = LogResumoService::getResumoInventoryItemContext($itemId, $returnUrl);
        }

        $loadView = new LoadViewService('adms/Views/inventory/items/view', $this->data);
        $loadView->loadView();
    }

    private function handleSyncStructureRequest(int $itemId): void
    {
        $redirect = $_ENV['URL_ADM'] . 'view-inventory-item/' . $itemId;
        $token = (string)($_POST['csrf_token'] ?? '');
        if (!CSRFHelper::validateCSRFToken('form_sync_inventory_structure', $token)) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Token CSRF inválido para sincronização da estrutura.</div>";
            header('Location: ' . $redirect);
            return;
        }

        $postedId = (int)($_POST['sync_sap_structure_item_id'] ?? 0);
        if ($postedId <= 0 || $postedId !== $itemId) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Item inválido para sincronização da estrutura.</div>";
            header('Location: ' . $redirect);
            return;
        }

        $service = new InventorySapSyncService();
        $result = $service->syncItemStructureById($itemId);
        if (!empty($result['success'])) {
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>{$result['message']}</div>";
        } else {
            $message = htmlspecialchars((string)($result['message'] ?? 'Erro desconhecido na sincronização de estrutura SAP.'), ENT_QUOTES, 'UTF-8');
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>{$message}</div>";
        }

        header('Location: ' . $redirect);
    }
}



