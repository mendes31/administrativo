<?php

namespace App\adms\Controllers\inventory;

use App\adms\Helpers\InvCostProductionLineHelper;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvCategoriesRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Repository\inventory\InvPharmaFormsRepository;
use App\adms\Models\Repository\inventory\InvUnitsRepository;
use App\adms\Views\Services\LoadViewService;

class CreateInventoryItem
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_inventory_item', $this->data['form']['csrf_token'])) {
            $this->addItem();
        } else {
            $this->viewCreate();
        }
    }

    private function viewCreate(): void
    {
        // Carregar selects
        $unitsRepo = new InvUnitsRepository();
        $categoriesRepo = new InvCategoriesRepository();
        $this->data['listUnits'] = $unitsRepo->getAllForSelect();
        $this->data['listCategories'] = $categoriesRepo->getAllForSelect();
        $this->data['listPharmaForms'] = (new InvPharmaFormsRepository())->getAllForSelect();

        $pageElements = [
            'title_head' => 'Cadastrar Item de Estoque',
            'menu' => 'estoque',
            'buttonPermission' => ['ListInventoryItems'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/items/create', $this->data);
        $loadView->loadView();
    }

    private function addItem(): void
    {
        $form = $this->data['form'] ?? [];

        // Validações mínimas
        $errors = [];
        if (empty($form['code'])) { $errors['code'] = 'Código é obrigatório.'; }
        if (empty($form['description'])) { $errors['description'] = 'Descrição é obrigatória.'; }
        if (empty($form['inv_unit_id'])) { $errors['inv_unit_id'] = 'Unidade é obrigatória.'; }
        if (!in_array(($form['admin_type'] ?? 'none'), ['none','serial','lot'], true)) { $errors['admin_type'] = 'Tipo de administração inválido.'; }

        // Regras numéricas
        $minStock = (float)($form['min_stock'] ?? 0);
        $maxStock = (float)($form['max_stock'] ?? 0);
        $avgCost = (float)($form['average_cost'] ?? 0);
        $lastCost = (float)($form['last_cost'] ?? 0);
        if ($minStock < 0) { $errors['min_stock'] = 'Estoque mínimo não pode ser negativo.'; }
        if ($maxStock < 0) { $errors['max_stock'] = 'Estoque máximo não pode ser negativo.'; }
        if ($maxStock && $minStock > $maxStock) { $errors['max_stock'] = 'Máximo deve ser maior ou igual ao mínimo.'; }
        if ($avgCost < 0) { $errors['average_cost'] = 'Custo médio não pode ser negativo.'; }
        if ($lastCost < 0) { $errors['last_cost'] = 'Último custo não pode ser negativo.'; }

        // Unicidade de código
        $repo = new InvItemsRepository();
        if (!empty($form['code']) && $repo->existsCode(trim($form['code']))) {
            $errors['code'] = 'Código já existente.';
        }

        if ($errors) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Verifique os campos obrigatórios.</div>";
            $this->data['errors'] = $errors;
            $this->viewCreate();
            return;
        }

        $created = $repo->create([
            'code' => trim($form['code']),
            'erp_code' => isset($form['erp_code']) ? trim((string)$form['erp_code']) : null,
            'description' => trim($form['description']),
            'inv_unit_id' => (int)$form['inv_unit_id'],
            'inv_category_id' => !empty($form['inv_category_id']) ? (int)$form['inv_category_id'] : null,
            'admin_type' => $form['admin_type'] ?? 'none',
            'average_cost' => (float)($form['average_cost'] ?? 0),
            'last_cost' => (float)($form['last_cost'] ?? 0),
            'min_stock' => (float)($form['min_stock'] ?? 0),
            'max_stock' => (float)($form['max_stock'] ?? 0),
            'standard_batch_size' => 1,
            'production_line' => InvCostProductionLineHelper::normalize($form['production_line'] ?? null),
            'inv_pharma_form_id' => !empty($form['inv_pharma_form_id']) ? (int)$form['inv_pharma_form_id'] : null,
            'active' => isset($form['active']) ? 1 : 0,
        ]);

        if ($created) {
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Item cadastrado com sucesso.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-items');
            exit;
        }

        $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro ao cadastrar o item.</div>";
        $this->viewCreate();
    }
}


