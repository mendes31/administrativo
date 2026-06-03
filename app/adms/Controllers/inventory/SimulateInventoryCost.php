<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Services\InventoryCostService;
use App\adms\Views\Services\LoadViewService;

class SimulateInventoryCost
{
    private array|string|null $data = null;

    public function index(int|string $id = 0): void
    {
        $itemId = (int)$id;
        if ($itemId <= 0 && isset($_GET['inv_item_id'])) {
            $legacyId = (int)$_GET['inv_item_id'];
            if ($legacyId > 0) {
                header('Location: ' . $_ENV['URL_ADM'] . 'simulate-inventory-cost/' . $legacyId);
                return;
            }
        }

        if ($itemId <= 0) {
            $_SESSION['error'] = 'Informe o item para simular o custo.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-items');
            return;
        }

        $itemsRepo = new InvItemsRepository();
        $item = $itemsRepo->getOne($itemId);
        if (!$item) {
            $_SESSION['error'] = 'Item não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-items');
            return;
        }

        $scenario = [
            'material_adjust_pct' => $this->parsePct($_REQUEST['material_adjust_pct'] ?? '0'),
            'operations_adjust_pct' => $this->parsePct($_REQUEST['operations_adjust_pct'] ?? '0'),
            'global_adjust_pct' => $this->parsePct($_REQUEST['global_adjust_pct'] ?? '0'),
        ];

        $this->data['selected_item_id'] = $itemId;
        $this->data['selected_item'] = $item;
        $this->data['scenario'] = $scenario;
        $this->data['breakdown'] = InventoryCostService::calculateBreakdown($itemId, $scenario);

        $_SESSION['menu_override'] = 'ListInventoryItems';

        $pageElements = [
            'title_head' => 'Simulação de Custos',
            'menu' => 'ListInventoryItems',
            'buttonPermission' => ['ListInventoryItems', 'ViewInventoryItem'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/costs/simulate', $this->data);
        $loadView->loadView();

        unset($_SESSION['menu_override']);
    }

    private function parsePct(mixed $value): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
        }
        if (!is_numeric($value)) {
            return 0.0;
        }

        return round((float)$value, 4);
    }
}
