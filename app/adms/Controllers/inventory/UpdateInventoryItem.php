<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\inventory\InvCategoriesRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Repository\inventory\InvUnitsRepository;
use App\adms\Models\Repository\inventory\InvItemBomRepository;
use App\adms\Models\Repository\inventory\InvItemOperationsRepository;
use App\adms\Models\Repository\inventory\InvOperationsRepository;
use App\adms\Models\Services\InventoryCostService;
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
        $itemsRepo = new InvItemsRepository();
        $this->data['listUnits'] = $unitsRepo->getAllForSelect();
        $this->data['listCategories'] = $categoriesRepo->getAllForSelect();
        // Itens ativos para seleção na ficha técnica (Lista de Materiais)
        $this->data['listBomItems'] = $itemsRepo->getAllForSelectWithAdminType();

        // Operações de produção para a aba de Rota
        $operationsRepo = new InvOperationsRepository();
        $this->data['listOperations'] = $operationsRepo->getAllForSelect();
    }

    private function viewUpdate(): void
    {
        $this->loadSelects();

        // Carregar ficha técnica (BOM) e rota do item
        $itemId = (int)($this->data['form']['id'] ?? 0);
        if ($itemId > 0) {
            $bomRepo = new InvItemBomRepository();
            $opsRepo = new InvItemOperationsRepository();
            $this->data['bom'] = $bomRepo->getByItem($itemId);
            $this->data['operations'] = $opsRepo->getByItem($itemId);
        } else {
            $this->data['bom'] = [];
            $this->data['operations'] = [];
        }

        // Custo médio e último custo = soma dos totais da Lista de materiais e da Rota
        $calculatedCost = $this->calculateCostFromBomAndRoute(
            $this->data['bom'] ?? [],
            $this->data['operations'] ?? []
        );
        $this->data['form']['average_cost'] = $calculatedCost;
        $this->data['form']['last_cost'] = $calculatedCost;

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

        // Validação simples da ficha técnica (se enviada)
        $bomLines = $this->buildBomLinesFromForm($form);
        $opLines  = $this->buildOperationLinesFromForm($form);

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
            'erp_code' => isset($form['erp_code']) ? trim((string)$form['erp_code']) : null,
            'description' => trim($form['description']),
            'inv_unit_id' => (int)$form['inv_unit_id'],
            'inv_category_id' => !empty($form['inv_category_id']) ? (int)$form['inv_category_id'] : null,
            'admin_type' => $form['admin_type'] ?? 'none',
            'min_stock' => (float)($form['min_stock'] ?? 0),
            'max_stock' => (float)($form['max_stock'] ?? 0),
            'active' => isset($form['active']) ? 1 : 0,
        ]);

        if ($updated) {
            // Persistir ficha técnica (BOM) e rota
            if ($bomLines !== null) {
                $bomRepo = new InvItemBomRepository();
                $bomRepo->replaceForItem($id, $bomLines);
            }
            if ($opLines !== null) {
                $opsRepo = new InvItemOperationsRepository();
                $opsRepo->replaceForItem($id, $opLines);
            }

            // Recalcular custo padrão com base na ficha técnica e rota
            InventoryCostService::recalculateStandardCost($id);

            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Item atualizado com sucesso.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'view-inventory-item/' . $id);
            return;
        }

        $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro ao atualizar o item.</div>";
        $this->data['form'] = $repo->getOne($id) ?: [];
        $this->viewUpdate();
    }

    /**
     * Monta as linhas de BOM a partir do formulário.
     *
     * @param array $form
     * @return array|null null se não houver dados de BOM no formulário.
     */
    private function buildBomLinesFromForm(array $form): ?array
    {
        if (empty($form['bom_component_item_id'])) {
            return [];
        }

        $components = $form['bom_component_item_id'];
        $quantities = $form['bom_quantity_per_batch'] ?? [];
        $scraps     = $form['bom_scrap_percent'] ?? [];

        $lines = [];
        foreach ($components as $idx => $componentId) {
            $componentId = (int)$componentId;
            if ($componentId <= 0) {
                continue;
            }
            $qty = isset($quantities[$idx]) ? (float)$quantities[$idx] : 0;
            if ($qty <= 0) {
                continue;
            }
            $scrap = isset($scraps[$idx]) ? (float)$scraps[$idx] : 0;
            $lines[] = [
                'component_item_id' => $componentId,
                'quantity_per_batch' => $qty,
                'scrap_percent' => $scrap,
            ];
        }

        return $lines;
    }

    /**
     * Monta as linhas de operações (rota) a partir do formulário.
     *
     * @param array $form
     * @return array|null null se não houver dados de rota no formulário.
     */
    private function buildOperationLinesFromForm(array $form): ?array
    {
        if (empty($form['op_operation_id'])) {
            return [];
        }

        $operations = $form['op_operation_id'];
        $sequences  = $form['op_sequence'] ?? [];
        $times      = $form['op_time_per_batch_hours'] ?? [];
        $units      = $form['op_time_unit'] ?? [];
        $operators  = $form['op_operators_qty'] ?? [];
        $laborCosts = $form['op_labor_cost_per_min'] ?? [];
        $machineCosts = $form['op_machine_cost_per_min'] ?? [];
        $energyCosts = $form['op_energy_cost_per_min'] ?? [];
        $notes      = $form['op_notes'] ?? [];

        $lines = [];
        foreach ($operations as $idx => $operationId) {
            $operationId = (int)$operationId;
            if ($operationId <= 0) {
                continue;
            }
            $seq  = isset($sequences[$idx]) ? (int)$sequences[$idx] : ($idx + 1);
            $time = isset($times[$idx]) ? (float)$times[$idx] : 0;
            $unit = isset($units[$idx]) ? strtoupper((string)$units[$idx]) : 'MIN';
            $operatorsQty = isset($operators[$idx]) ? (int)$operators[$idx] : 1;
            $laborCostPerMin = isset($laborCosts[$idx]) ? (float)$laborCosts[$idx] : 0;
            $machineCostPerMin = isset($machineCosts[$idx]) ? (float)$machineCosts[$idx] : 0;
            $energyCostPerMin = isset($energyCosts[$idx]) ? (float)$energyCosts[$idx] : 0;
            if (!in_array($unit, ['MIN', 'H'], true)) {
                $unit = 'MIN';
            }
            $note = isset($notes[$idx]) ? trim((string)$notes[$idx]) : null;

            $lines[] = [
                'inv_operation_id' => $operationId,
                'sequence' => $seq,
                'time_per_batch_hours' => $time,
                'time_unit' => $unit,
                'operators_qty' => max(1, $operatorsQty),
                'labor_cost_per_min' => max(0, $laborCostPerMin),
                'machine_cost_per_min' => max(0, $machineCostPerMin),
                'energy_cost_per_min' => max(0, $energyCostPerMin),
                'notes' => $note ?: null,
            ];
        }

        return $lines;
    }

    /**
     * Calcula o custo total a partir da Lista de materiais (BOM) e da Rota.
     * Mesma lógica do InventoryCostService (materiais + operações).
     *
     * @param array $bom        Linhas retornadas por InvItemBomRepository::getByItem
     * @param array $operations Linhas retornadas por InvItemOperationsRepository::getByItem
     * @return float
     */
    private function calculateCostFromBomAndRoute(array $bom, array $operations): float
    {
        $materialCost = 0.0;
        foreach ($bom as $row) {
            $qty   = (float)($row['quantity_per_batch'] ?? 0);
            $scrap = (float)($row['scrap_percent'] ?? 0);
            $cost  = (float)($row['component_cost'] ?? 0);
            if ($qty <= 0 || $cost <= 0) {
                continue;
            }
            $effectiveQty = $qty * (1 + $scrap / 100.0);
            $materialCost += $effectiveQty * $cost;
        }

        $operationsCost = 0.0;
        foreach ($operations as $row) {
            $rawTime  = (float)($row['time_per_batch_hours'] ?? 0);
            $timeUnit = strtoupper((string)($row['time_unit'] ?? 'MIN'));
            $costHour = (float)($row['operation_cost_per_hour'] ?? 0);
            $operatorsQty = max(1, (int)($row['operators_qty'] ?? 1));
            $laborCostPerMin = (float)($row['labor_cost_per_min'] ?? 0);
            $machineCostPerMin = (float)($row['machine_cost_per_min'] ?? 0);
            $energyCostPerMin = (float)($row['energy_cost_per_min'] ?? 0);
            if (!in_array($timeUnit, ['MIN', 'H'], true)) {
                $timeUnit = 'MIN';
            }
            $timeMinutes = $timeUnit === 'H' ? $rawTime * 60.0 : $rawTime;
            if ($timeMinutes <= 0) {
                continue;
            }
            $costPerMinuteFromRoute = ($laborCostPerMin * $operatorsQty) + $machineCostPerMin + $energyCostPerMin;
            if ($costPerMinuteFromRoute > 0) {
                $operationsCost += $timeMinutes * $costPerMinuteFromRoute;
                continue;
            }
            $timeHours = $timeMinutes / 60.0;
            if ($costHour <= 0) {
                continue;
            }
            $operationsCost += $timeHours * $costHour;
        }

        return $materialCost + $operationsCost;
    }
}



