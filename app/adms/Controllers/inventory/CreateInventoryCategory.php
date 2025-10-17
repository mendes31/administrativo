<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvCategoriesRepository;
use App\adms\Views\Services\LoadViewService;

class CreateInventoryCategory
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_inventory_category', $this->data['form']['csrf_token'])) {
            $this->save();
        } else {
            $this->view();
        }
    }

    private function view(): void
    {
        $pageElements = [
            'title_head' => 'Cadastrar Categoria de Item',
            'menu' => 'estoque',
            'buttonPermission' => ['ListInventoryCategories'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));
        $loadView = new LoadViewService('adms/Views/inventory/categories/create', $this->data);
        $loadView->loadView();
    }

    private function save(): void
    {
        $form = $this->data['form'] ?? [];
        if (empty($form['name'])) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Nome é obrigatório.</div>";
            $this->view();
            return;
        }
        $repo = new InvCategoriesRepository();
        $id = $repo->create(['name' => trim($form['name'])]);
        if ($id) { $_SESSION['msg'] = "<div class='alert alert-success'>Categoria criada.</div>"; header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-categories'); exit; }
        $_SESSION['msg'] = "<div class='alert alert-danger'>Erro ao criar.</div>"; $this->view();
    }
}








