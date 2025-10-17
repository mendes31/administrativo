<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvMovementsRepository;
use App\adms\Models\Repository\inventory\InvStocksRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Views\Services\LoadViewService;

class CreateInventoryAdjust
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_inventory_adjust', $this->data['form']['csrf_token'])) {
            $this->save();
        } else {
            $this->view();
        }
    }

    private function view(): void
    {
        $stocksRepo = new InvStocksRepository();
        $this->data['listStocks'] = $stocksRepo->getAllForSelect();
        $itemsRepo = new InvItemsRepository();
        $this->data['listItems'] = $itemsRepo->getAllForSelectWithAdminType();

        $pageElements = [
            'title_head' => 'Ajuste de Inventário',
            'menu' => 'estoque',
            'buttonPermission' => ['ListInventoryItems'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/movements/adjust', $this->data);
        $loadView->loadView();
    }

    private function save(): void
    {
        $form = $this->data['form'] ?? [];
        $items = $form['items'] ?? [];
        if (empty($items) || (empty($form['to_stock_id']) && empty($form['from_stock_id']))) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Preencha os campos obrigatórios.</div>";
            $this->view();
            return;
        }

        $movement = [
            'type' => 'adjust',
            'user_id' => (int)($_SESSION['user_id'] ?? 0),
            'from_stock_id' => !empty($form['from_stock_id']) ? (int)$form['from_stock_id'] : null,
            'from_position_id' => !empty($form['from_position_id']) ? (int)$form['from_position_id'] : null,
            'to_stock_id' => !empty($form['to_stock_id']) ? (int)$form['to_stock_id'] : null,
            'to_position_id' => !empty($form['to_position_id']) ? (int)$form['to_position_id'] : null,
            'reason_id' => !empty($form['reason_id']) ? (int)$form['reason_id'] : null,
            'notes' => $form['notes'] ?? null,
        ];

        $repo = new InvMovementsRepository();
        try {
            $repo->registerMovement($movement, $items);
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Ajuste registrado com sucesso.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'create-inventory-adjust');
            exit;
        } catch (\Throwable $e) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>" . htmlspecialchars($e->getMessage()) . "</div>";
            $this->view();
        }
    }
}


