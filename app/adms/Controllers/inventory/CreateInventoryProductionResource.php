<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvProductionResourcesRepository;
use App\adms\Views\Services\LoadViewService;

class CreateInventoryProductionResource
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($this->data['form']['csrf_token']) &&
            CSRFHelper::validateCSRFToken('form_create_inventory_production_resource', $this->data['form']['csrf_token'])) {
            $this->save();
        } else {
            $this->view();
        }
    }

    private function view(): void
    {
        $pageElements = [
            'title_head' => 'Cadastrar Recurso de Produção',
            'menu' => 'estoque',
            'buttonPermission' => ['ListInventoryProductionResources'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pls->configurePageElements($pageElements));
        $loadView = new LoadViewService('adms/Views/inventory/production_resources/create', $this->data);
        $loadView->loadView();
    }

    private function save(): void
    {
        $form = $this->data['form'] ?? [];
        $errors = [];
        if (empty($form['erp_code'])) {
            $errors['erp_code'] = 'Código ERP é obrigatório.';
        }
        if (empty($form['name'])) {
            $errors['name'] = 'Nome é obrigatório.';
        }

        $repo = new InvProductionResourcesRepository();
        if (!empty($form['erp_code']) && $repo->existsErpCode(trim((string) $form['erp_code']))) {
            $errors['erp_code'] = 'Código ERP já existente.';
        }

        if ($errors) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Verifique os campos obrigatórios.</div>";
            $this->data['errors'] = $errors;
            $this->view();
            return;
        }

        $id = $repo->create([
            'erp_code' => trim((string) $form['erp_code']),
            'name' => trim((string) $form['name']),
            'resource_type' => (string) ($form['resource_type'] ?? 'MACHINE'),
            'labor_cost_per_min' => (float) ($form['labor_cost_per_min'] ?? 0),
            'machine_cost_per_min' => (float) ($form['machine_cost_per_min'] ?? 0),
            'energy_cost_per_min' => (float) ($form['energy_cost_per_min'] ?? 0),
            'power_kw' => $form['power_kw'] ?? null,
            'active' => isset($form['active']) ? 1 : 0,
        ]);

        if ($id) {
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Recurso cadastrado com sucesso.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-production-resources');
            exit;
        }

        $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro ao cadastrar o recurso.</div>";
        $this->view();
    }
}
