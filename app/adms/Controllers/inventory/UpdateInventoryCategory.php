<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvCategoriesRepository;
use App\adms\Views\Services\LoadViewService;

class UpdateInventoryCategory
{
    private array|string|null $data = null;

    public function index(string $id = ''): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        $repo = new InvCategoriesRepository();
        $categoryId = (int)($id ?: ($_GET['id'] ?? 0));
        if (!$categoryId) { $_SESSION['msg'] = "<div class='alert alert-danger'>ID inválido.</div>"; header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-categories'); return; }
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_update_inventory_category', $this->data['form']['csrf_token'])) {
            $this->save($categoryId, $repo);
            return;
        }
        $this->data['category'] = $repo->getOne($categoryId);
        $this->view();
    }

    private function view(): void
    {
        $pageElements = [
            'title_head' => 'Editar Categoria de Item',
            'menu' => 'estoque',
            'buttonPermission' => ['ListInventoryCategories'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));
        $loadView = new LoadViewService('adms/Views/inventory/categories/update', $this->data);
        $loadView->loadView();
    }

    private function save(int $id, InvCategoriesRepository $repo): void
    {
        $form = $this->data['form'] ?? [];
        if (empty($form['name'])) { $_SESSION['msg'] = "<div class='alert alert-danger'>Nome é obrigatório.</div>"; $this->view(); return; }
        if ($repo->update($id, ['name' => trim($form['name'])])) { $_SESSION['msg'] = "<div class='alert alert-success'>Categoria atualizada.</div>"; header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-categories'); exit; }
        $_SESSION['msg'] = "<div class='alert alert-danger'>Erro ao atualizar.</div>"; $this->view();
    }
}








