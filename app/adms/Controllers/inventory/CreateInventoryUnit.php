<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvUnitsRepository;
use App\adms\Views\Services\LoadViewService;

class CreateInventoryUnit
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_inventory_unit', $this->data['form']['csrf_token'])) {
            $this->save();
        } else {
            $this->view();
        }
    }

    private function view(): void
    {
        $pageElements = [
            'title_head' => 'Cadastrar Unidade de Medida',
            'menu' => 'estoque',
            'buttonPermission' => ['ListInventoryUnits'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/units/create', $this->data);
        $loadView->loadView();
    }

    private function save(): void
    {
        $form = $this->data['form'] ?? [];
        if (empty($form['code']) || empty($form['name'])) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Código e Nome são obrigatórios.</div>";
            $this->view();
            return;
        }
        $repo = new InvUnitsRepository();
        $id = $repo->create(['code' => trim($form['code']), 'name' => trim($form['name'])]);
        if ($id) {
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Unidade criada.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-units');
            exit;
        }
        $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro ao criar unidade.</div>";
        $this->view();
    }
}








