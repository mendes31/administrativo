<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvLaborRolesRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Views\Services\LoadViewService;

class CreateInventoryLaborRole
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($this->data['form']['csrf_token']) &&
            CSRFHelper::validateCSRFToken('form_create_inventory_labor_role', $this->data['form']['csrf_token'])) {
            $this->save();
        } else {
            $this->view();
        }
    }

    private function view(): void
    {
        $positionsRepo = new PositionsRepository();
        $this->data['listPositions'] = $positionsRepo->getAllPositionsSelect();

        $pageElements = [
            'title_head' => 'Cadastrar Papel de MO',
            'menu' => 'estoque',
            'buttonPermission' => ['ListInventoryLaborRoles'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pls->configurePageElements($pageElements));
        $loadView = new LoadViewService('adms/Views/inventory/labor_roles/create', $this->data);
        $loadView->loadView();
    }

    private function save(): void
    {
        $form = $this->data['form'] ?? [];
        if (empty($form['name'])) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Nome é obrigatório.</div>";
            $this->data['errors'] = ['name' => 'Nome é obrigatório.'];
            $this->view();
            return;
        }

        $repo = new InvLaborRolesRepository();
        $id = $repo->create([
            'code' => trim((string) ($form['code'] ?? '')),
            'name' => trim((string) $form['name']),
            'default_cost_per_min' => (float) ($form['default_cost_per_min'] ?? 0),
            'adms_position_id' => !empty($form['adms_position_id']) ? (int) $form['adms_position_id'] : null,
            'active' => isset($form['active']) ? 1 : 0,
        ]);

        if ($id) {
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Papel cadastrado com sucesso.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-labor-roles');
            exit;
        }

        $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro ao cadastrar.</div>";
        $this->view();
    }
}
