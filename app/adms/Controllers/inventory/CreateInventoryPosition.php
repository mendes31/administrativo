<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvPositionsRepository;
use App\adms\Models\Repository\inventory\InvStocksRepository;
use App\adms\Views\Services\LoadViewService;

class CreateInventoryPosition
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_inventory_position', $this->data['form']['csrf_token'])) {
            $this->save();
        } else {
            $this->view();
        }
    }

    private function view(): void
    {
        $stocksRepo = new InvStocksRepository();
        $this->data['stocks'] = $stocksRepo->getAllForSelect();
        $pageElements = [
            'title_head' => 'Cadastrar Posição Interna',
            'menu' => 'estoque',
            'buttonPermission' => ['ListInventoryPositions'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));
        $loadView = new LoadViewService('adms/Views/inventory/positions/create', $this->data);
        $loadView->loadView();
    }

    private function save(): void
    {
        $form = $this->data['form'] ?? [];
        if (empty($form['inv_stock_id']) || empty($form['code']) || empty($form['description'])) { $_SESSION['msg'] = "<div class='alert alert-danger'>Preencha todos os campos.</div>"; $this->view(); return; }
        $repo = new InvPositionsRepository();
        $id = $repo->create(['inv_stock_id' => (int)$form['inv_stock_id'], 'code' => trim($form['code']), 'description' => trim($form['description'])]);
        if ($id) { $_SESSION['msg'] = "<div class='alert alert-success'>Posição criada.</div>"; header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-positions'); exit; }
        $_SESSION['msg'] = "<div class='alert alert-danger'>Erro ao criar posição.</div>"; $this->view();
    }
}








