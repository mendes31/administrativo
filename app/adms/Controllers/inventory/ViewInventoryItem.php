<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Repository\inventory\InvBalancesRepository;
use App\adms\Models\Repository\inventory\InvMovementsRepository;
use App\adms\Views\Services\LoadViewService;

class ViewInventoryItem
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $repo = new InvItemsRepository();
        $item = $repo->getOne((int)$id);

        if (!$item) {
            $_SESSION['error'] = 'Item não encontrado!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-items');
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

        // Força a seleção do menu correspondente (override de sessão usado pelo menu.php)
        $_SESSION['menu_override'] = 'ListInventoryItems';

        $pageElements = [
            'title_head' => 'Visualizar Item de Estoque',
            // Aponta diretamente para o link de Itens para marcar ativo
            'menu' => 'ListInventoryItems',
            'buttonPermission' => ['ListInventoryItems', 'UpdateInventoryItem'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/items/view', $this->data);
        $loadView->loadView();

        // Limpa override após exibir
        unset($_SESSION['menu_override']);
    }
}



