<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvStocksRepository;
use App\adms\Views\Services\LoadViewService;

class CreateInventoryStock
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_inventory_stock', $this->data['form']['csrf_token'])) {
            $this->save();
        } else {
            $this->view();
        }
    }

    private function view(): void
    {
        $pageElements = [
            'title_head' => 'Cadastrar Estoque',
            'menu' => 'estoque',
            'buttonPermission' => ['ListInventoryStocks'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));
        $loadView = new LoadViewService('adms/Views/inventory/stocks/create', $this->data);
        $loadView->loadView();
    }

    private function save(): void
    {
        $form = $this->data['form'] ?? [];
        if (empty($form['name']) || empty($form['code'])) { $_SESSION['msg'] = "<div class='alert alert-danger'>Nome e Código são obrigatórios.</div>"; $this->view(); return; }
        $repo = new InvStocksRepository();
        $id = $repo->create([
            'name' => trim($form['name']),
            'code' => trim($form['code']),
            'adms_branch_id' => !empty($form['adms_branch_id']) ? (int)$form['adms_branch_id'] : null,
            'active' => isset($form['active']) ? 1 : 0,
        ]);
        if ($id) { $_SESSION['msg'] = "<div class='alert alert-success'>Estoque criado.</div>"; header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-stocks'); exit; }
        $_SESSION['msg'] = "<div class='alert alert-danger'>Erro ao criar estoque.</div>"; $this->view();
    }
}








