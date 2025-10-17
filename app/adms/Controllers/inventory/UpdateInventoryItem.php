<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\inventory\InvCategoriesRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Repository\inventory\InvUnitsRepository;
use App\adms\Views\Services\LoadViewService;

class UpdateInventoryItem
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_update_inventory_item', $this->data['form']['csrf_token'])) {
            $this->editItem((int)$id);
        } else {
            $repo = new InvItemsRepository();
            $this->data['form'] = $repo->getOne((int)$id);
            if (!$this->data['form']) {
                GenerateLog::generateLog('error', 'Item não encontrado', ['id' => (int)$id]);
                $_SESSION['error'] = 'Item não encontrado!';
                header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-items');
                return;
            }
            $this->viewUpdate();
        }
    }

    private function loadSelects(): void
    {
        $unitsRepo = new InvUnitsRepository();
        $categoriesRepo = new InvCategoriesRepository();
        $this->data['listUnits'] = $unitsRepo->getAllForSelect();
        $this->data['listCategories'] = $categoriesRepo->getAllForSelect();
    }

    private function viewUpdate(): void
    {
        $this->loadSelects();

        // Força a seleção do menu correspondente (override de sessão usado pelo menu.php)
        $_SESSION['menu_override'] = 'ListInventoryItems';

        $pageElements = [
            'title_head' => 'Editar Item de Estoque',
            // Aponta diretamente para o link de Itens para marcar ativo
            'menu' => 'ListInventoryItems',
            'buttonPermission' => ['ListInventoryItems', 'ViewInventoryItem'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/items/update', $this->data);
        $loadView->loadView();

        unset($_SESSION['menu_override']);
    }

    private function editItem(int $id): void
    {
        $form = $this->data['form'] ?? [];

        $errors = [];
        if (empty($form['code'])) { $errors['code'] = 'Código é obrigatório.'; }
        if (empty($form['description'])) { $errors['description'] = 'Descrição é obrigatória.'; }
        if (empty($form['inv_unit_id'])) { $errors['inv_unit_id'] = 'Unidade é obrigatória.'; }
        if (!in_array(($form['admin_type'] ?? 'none'), ['none','serial','lot'], true)) { $errors['admin_type'] = 'Tipo de administração inválido.'; }

        if ($errors) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Verifique os campos obrigatórios.</div>";
            $this->data['errors'] = $errors;
            $this->viewUpdate();
            return;
        }

        $repo = new InvItemsRepository();
        if ($repo->existsCode(trim($form['code']), $id)) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Código já existente.</div>";
            $this->data['errors'] = ['code' => 'Código já existente.'];
            $this->viewUpdate();
            return;
        }
        $updated = $repo->update($id, [
            'code' => trim($form['code']),
            'description' => trim($form['description']),
            'inv_unit_id' => (int)$form['inv_unit_id'],
            'inv_category_id' => !empty($form['inv_category_id']) ? (int)$form['inv_category_id'] : null,
            'admin_type' => $form['admin_type'] ?? 'none',
            'average_cost' => (float)($form['average_cost'] ?? 0),
            'last_cost' => (float)($form['last_cost'] ?? 0),
            'min_stock' => (float)($form['min_stock'] ?? 0),
            'max_stock' => (float)($form['max_stock'] ?? 0),
            'active' => isset($form['active']) ? 1 : 0,
        ]);

        if ($updated) {
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Item atualizado com sucesso.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'view-inventory-item/' . $id);
            return;
        }

        $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro ao atualizar o item.</div>";
        $this->viewUpdate();
    }
}



