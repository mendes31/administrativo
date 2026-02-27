<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvOperationsRepository;
use App\adms\Views\Services\LoadViewService;

class CreateInventoryOperation
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($this->data['form']['csrf_token']) &&
            CSRFHelper::validateCSRFToken('form_create_inventory_operation', $this->data['form']['csrf_token'])) {
            $this->save();
        } else {
            $this->view();
        }
    }

    private function view(): void
    {
        $pageElements = [
            'title_head' => 'Cadastrar Operação de Produção',
            'menu' => 'estoque',
            'buttonPermission' => ['ListInventoryOperations'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));
        $loadView = new LoadViewService('adms/Views/inventory/operations/create', $this->data);
        $loadView->loadView();
    }

    private function save(): void
    {
        $form = $this->data['form'] ?? [];
        $errors = [];
        if (empty($form['name'])) {
            $errors['name'] = 'Nome é obrigatório.';
        }

        $repo = new InvOperationsRepository();
        if (!empty($form['code']) && $repo->existsCode(trim($form['code']))) {
            $errors['code'] = 'Código já existente.';
        }

        if ($errors) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Verifique os campos obrigatórios.</div>";
            $this->data['errors'] = $errors;
            $this->view();
            return;
        }

        $id = $repo->create([
            'code' => trim((string)($form['code'] ?? '')),
            'name' => trim((string)$form['name']),
            'description' => trim((string)($form['description'] ?? '')),
            'default_cost_per_hour' => (float)($form['default_cost_per_hour'] ?? 0),
            'active' => isset($form['active']) ? 1 : 0,
        ]);

        if ($id) {
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Operação cadastrada com sucesso.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-operations');
            exit;
        }

        $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro ao cadastrar a operação.</div>";
        $this->view();
    }
}

