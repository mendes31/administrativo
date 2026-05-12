<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdInventoryRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class LgpdInventoryView
{
    private array|string|null $data = null;

    public function index($id): void
    {
        $repo = new LgpdInventoryRepository();
        $this->data['inventory'] = $repo->getById($id);
        
        if (!$this->data['inventory']) {
            $_SESSION['error'] = "Inventário não encontrado!";
            header("Location: {$_ENV['URL_ADM']}lgpd-inventory");
            return;
        }
        $idInt = (int) $id;
        $returnUrl = $_ENV['URL_ADM'] . 'lgpd-inventory-view/' . $idInt;
        $this->data['log_resumo'] = LogResumoService::getResumo('lgpd_inventory', $idInt, $returnUrl);

        // Buscar os grupos de dados associados a este inventário
        $this->data['data_groups'] = $repo->getDataGroupsByInventoryId($id);

        $pageElements = [
            'title_head' => 'Visualizar Inventário',
            'menu' => 'ListLgpdInventory',
            'buttonPermission' => ['ListLgpdInventory', 'EditLgpdInventory', 'DeleteLgpdInventory', 'LgpdRopaCreate'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/inventory/view", $this->data);
        $loadView->loadView();
    }
}