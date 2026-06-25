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
use App\adms\Models\Repository\inventory\InvLaborRolesRepository;
use App\adms\Models\Repository\inventory\InvOperationsRepository;
use App\adms\Models\Repository\inventory\InvProductionResourcesRepository;
use App\adms\Helpers\InvCostProjectHelper;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class UpdateInventoryItem
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_update_inventory_item', $this->data['form']['csrf_token'])) {
            $this->editItem((int)$id);
        } elseif ($this->isAjaxRequest() && strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) === 'POST') {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Sessão expirada ou token inválido. Recarregue a página.',
                'csrf_token' => CSRFHelper::generateCSRFToken('form_update_inventory_item'),
            ], 403);
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

        $operationsRepo = new InvOperationsRepository();
        $this->data['listOperations'] = $operationsRepo->getAllForSelect();

        $resourcesRepo = new InvProductionResourcesRepository();
        $this->data['listProductionResources'] = $resourcesRepo->getAllForSelect();

        $laborRolesRepo = new InvLaborRolesRepository();
        $this->data['listLaborRoles'] = $laborRolesRepo->getAllForSelect();
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

        $this->data['is_project_item'] = InvCostProjectHelper::isProjectItem($this->data['form'] ?? []);

        // Custo médio e último custo = materiais + rota (valores do lote; rateio por SKU na simulação)
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

        if ($itemId > 0) {
            $returnUrl = $_ENV['URL_ADM'] . 'update-inventory-item/' . $itemId;
            $this->data['log_resumo'] = LogResumoService::getResumoInventoryItemContext($itemId, $returnUrl);
        }

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
        $isProjectItem = $this->resolveIsProjectItemFromForm($form, $id);
        $bomLines = $this->buildBomLinesFromForm($form, $isProjectItem, $errors);
        $opLines  = $this->buildOperationLinesFromForm($form);

        if ($errors) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Verifique os campos obrigatórios.',
                    'errors' => $errors,
                    'csrf_token' => CSRFHelper::generateCSRFToken('form_update_inventory_item'),
                ], 422);
                return;
            }
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Verifique os campos obrigatórios.</div>";
            $this->data['errors'] = $errors;
            $this->viewUpdate();
            return;
        }

        $repo = new InvItemsRepository();
        if ($repo->existsCode(trim($form['code']), $id)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Código já existente.',
                    'errors' => ['code' => 'Código já existente.'],
                    'csrf_token' => CSRFHelper::generateCSRFToken('form_update_inventory_item'),
                ], 422);
                return;
            }
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

            if ($this->isAjaxRequest()) {
                $tab = trim((string)($form['active_tab'] ?? 'pane-dados-gerais'));
                $allowedTabs = ['pane-dados-gerais', 'pane-bom', 'pane-operations'];
                if (!in_array($tab, $allowedTabs, true)) {
                    $tab = 'pane-dados-gerais';
                }
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Item atualizado com sucesso.',
                    'active_tab' => $tab,
                    'csrf_token' => CSRFHelper::generateCSRFToken('form_update_inventory_item'),
                ]);
                return;
            }

            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Item atualizado com sucesso.</div>";
            $tab = trim((string)($form['active_tab'] ?? 'pane-dados-gerais'));
            $redirect = $_ENV['URL_ADM'] . 'update-inventory-item/' . $id . '?tab=' . rawurlencode($tab);
            header('Location: ' . $redirect);
            return;
        }

        if ($this->isAjaxRequest()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Erro ao atualizar o item.',
                'csrf_token' => CSRFHelper::generateCSRFToken('form_update_inventory_item'),
            ], 500);
            return;
        }

        $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro ao atualizar o item.</div>";
        $this->data['form'] = $repo->getOne($id) ?: [];
        $this->viewUpdate();
    }

    private function isAjaxRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strcasecmp((string)$_SERVER['HTTP_X_REQUESTED_WITH'], 'XMLHttpRequest') === 0;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonResponse(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param array<string, mixed> $form
     */
    private function resolveIsProjectItemFromForm(array $form, int $itemId): bool
    {
        if (!empty($form['inv_category_id'])) {
            $cat = (new InvCategoriesRepository())->getOne((int)$form['inv_category_id']);
            if (is_array($cat)) {
                return InvCostProjectHelper::isProjectCategoryName((string)($cat['name'] ?? ''));
            }
        }

        if ($itemId > 0) {
            $item = (new InvItemsRepository())->getOne($itemId);
            if (is_array($item)) {
                return InvCostProjectHelper::isProjectItem($item);
            }
        }

        return false;
    }

    /**
     * Monta as linhas de BOM a partir do formulário.
     *
     * @param array<string, mixed> $form
     * @param list<string> $errors
     * @return array|null null se não houver dados de BOM no formulário.
     */
    private function buildBomLinesFromForm(array $form, bool $isProjectItem, array &$errors): ?array
    {
        $sources = $form['bom_line_source'] ?? [];
        if ($sources === [] && empty($form['bom_component_item_id'])) {
            return [];
        }

        $components = $form['bom_component_item_id'] ?? [];
        $quantities = $form['bom_quantity_per_batch'] ?? [];
        $scraps = $form['bom_scrap_percent'] ?? [];
        $manualDescriptions = $form['bom_manual_description'] ?? [];
        $manualTypes = $form['bom_manual_component_type'] ?? [];
        $manualUnits = $form['bom_manual_unit'] ?? [];
        $manualCosts = $form['bom_manual_unit_cost'] ?? [];

        $lines = [];
        foreach ($sources as $idx => $sourceRaw) {
            $source = (string)$sourceRaw === 'manual' ? 'manual' : 'catalog';
            $qty = isset($quantities[$idx]) ? (float)$quantities[$idx] : 0;
            if ($qty <= 0) {
                continue;
            }
            $scrap = isset($scraps[$idx]) ? (float)$scraps[$idx] : 0;

            if ($source === 'manual') {
                if (!$isProjectItem) {
                    $errors['bom'] = 'Linhas manuais na lista de materiais são permitidas apenas para itens da categoria PA - PROJETO.';
                    continue;
                }
                $description = trim((string)($manualDescriptions[$idx] ?? ''));
                $type = strtoupper(trim((string)($manualTypes[$idx] ?? 'MP')));
                if (!in_array($type, ['MP', 'EMB', 'OTHER'], true)) {
                    $type = 'MP';
                }
                $unit = strtoupper(trim((string)($manualUnits[$idx] ?? 'UN')));
                $unitCost = isset($manualCosts[$idx]) ? $this->parseFormDecimal($manualCosts[$idx]) : 0.0;
                if ($description === '') {
                    $errors['bom'] = 'Informe a descrição de todas as linhas manuais da lista de materiais.';
                    continue;
                }
                if ($unitCost <= 0) {
                    $errors['bom'] = 'Informe o custo unitário maior que zero nas linhas manuais.';
                    continue;
                }
                $lines[] = [
                    'line_source' => 'manual',
                    'component_item_id' => null,
                    'quantity_per_batch' => $qty,
                    'scrap_percent' => $scrap,
                    'manual_description' => $description,
                    'manual_component_type' => $type,
                    'manual_unit' => $unit !== '' ? $unit : 'UN',
                    'manual_unit_cost' => $unitCost,
                ];
                continue;
            }

            $componentId = isset($components[$idx]) ? (int)$components[$idx] : 0;
            if ($componentId <= 0) {
                continue;
            }
            $lines[] = [
                'line_source' => 'catalog',
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
        $machineCosts = $form['op_machine_cost_per_min'] ?? [];
        $energyCosts = $form['op_energy_cost_per_min'] ?? [];
        $notes      = $form['op_notes'] ?? [];
        $resourceIdsByOp = $form['op_resource_id'] ?? [];
        $resourceQtyByOp = $form['op_resource_qty'] ?? [];
        $resourceMachineByOp = $form['op_resource_machine_cost'] ?? [];
        $resourceEnergyByOp = $form['op_resource_energy_cost'] ?? [];
        $laborRolesByOp = $form['op_labor_role_id'] ?? [];
        $laborQtyByOp = $form['op_labor_qty'] ?? [];
        $laborCostByOp = $form['op_labor_cost_per_min'] ?? [];

        $lines = [];
        foreach ($operations as $idx => $operationId) {
            $operationId = (int)$operationId;
            if ($operationId <= 0) {
                continue;
            }
            $seq  = isset($sequences[$idx]) ? (int)$sequences[$idx] : ($idx + 1);
            $time = isset($times[$idx]) ? $this->parseFormDecimal($times[$idx]) : 0;
            $unit = isset($units[$idx]) ? strtoupper((string)$units[$idx]) : 'MIN';
            $machineCostPerMin = isset($machineCosts[$idx]) ? $this->parseFormDecimal($machineCosts[$idx]) : 0;
            $energyCostPerMin = isset($energyCosts[$idx]) ? $this->parseFormDecimal($energyCosts[$idx]) : 0;
            if (!in_array($unit, ['MIN', 'H'], true)) {
                $unit = 'MIN';
            }
            $note = isset($notes[$idx]) ? trim((string)$notes[$idx]) : null;

            $resources = [];
            $resIds = is_array($resourceIdsByOp[$idx] ?? null) ? array_values($resourceIdsByOp[$idx]) : [];
            $resQtys = is_array($resourceQtyByOp[$idx] ?? null) ? array_values($resourceQtyByOp[$idx]) : [];
            $resMachines = is_array($resourceMachineByOp[$idx] ?? null) ? array_values($resourceMachineByOp[$idx]) : [];
            $resEnergies = is_array($resourceEnergyByOp[$idx] ?? null) ? array_values($resourceEnergyByOp[$idx]) : [];
            if ($resIds !== []) {
                foreach ($resIds as $rIdx => $resId) {
                    $resId = (int)$resId;
                    if ($resId <= 0) {
                        continue;
                    }
                    $resources[] = [
                        'inv_production_resource_id' => $resId,
                        'qty' => max(1, (int)($resQtys[$rIdx] ?? 1)),
                        'machine_cost_per_min' => max(0, $this->parseFormDecimal($resMachines[$rIdx] ?? 0)),
                        'energy_cost_per_min' => max(0, $this->parseFormDecimal($resEnergies[$rIdx] ?? 0)),
                    ];
                }
            }

            $labor = [];
            $roleIds = is_array($laborRolesByOp[$idx] ?? null) ? array_values($laborRolesByOp[$idx]) : [];
            $qtys = is_array($laborQtyByOp[$idx] ?? null) ? array_values($laborQtyByOp[$idx]) : [];
            $costs = is_array($laborCostByOp[$idx] ?? null) ? array_values($laborCostByOp[$idx]) : [];
            if ($roleIds !== []) {
                foreach ($roleIds as $lIdx => $roleId) {
                    $roleId = (int)$roleId;
                    if ($roleId <= 0) {
                        continue;
                    }
                    $labor[] = [
                        'inv_labor_role_id' => $roleId,
                        'qty' => max(1, (int)($qtys[$lIdx] ?? 1)),
                        'cost_per_min' => max(0, $this->parseFormDecimal($costs[$lIdx] ?? 0)),
                    ];
                }
            }

            $lines[] = [
                'inv_operation_id' => $operationId,
                'sequence' => $seq,
                'time_per_batch_hours' => $time,
                'time_unit' => $unit,
                'operators_qty' => 1,
                'labor_cost_per_min' => 0,
                'machine_cost_per_min' => max(0, $machineCostPerMin),
                'energy_cost_per_min' => max(0, $energyCostPerMin),
                'notes' => $note ?: null,
                'resources' => $resources,
                'labor' => $labor,
            ];
        }

        return $lines;
    }

    private function parseFormDecimal(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        $s = trim((string) $value);
        if ($s === '') {
            return 0.0;
        }
        if (preg_match('/^\d{1,3}(\.\d{3})*(,\d+)?$/', $s)) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } elseif (str_contains($s, ',') && !str_contains($s, '.')) {
            $s = str_replace(',', '.', $s);
        }

        return is_numeric($s) ? (float) $s : 0.0;
    }

    /**
     * Calcula o custo total a partir da Lista de materiais (BOM) e da Rota.
     * Mesma lógica do InventoryCostService (materiais + operações).
     *
     * @param array $bom        Linhas retornadas por InvItemBomRepository::getByItem
     * @param array $operations Linhas retornadas por InvItemOperationsRepository::getByItem
     * @return float Custo total do lote (materiais + rota)
     */
    private function calculateCostFromBomAndRoute(array $bom, array $operations): float
    {
        $materialCost = 0.0;
        foreach ($bom as $row) {
            $materialCost += InvItemBomRepository::computeLineMaterialCost($row);
        }

        $operationsCost = 0.0;
        foreach ($operations as $row) {
            $rawTime  = (float)($row['time_per_batch_hours'] ?? 0);
            $timeUnit = strtoupper((string)($row['time_unit'] ?? 'MIN'));
            $costHour = (float)($row['operation_cost_per_hour'] ?? 0);
            $machineCostPerMin = (float)($row['machine_cost_per_min'] ?? 0);
            $energyCostPerMin = (float)($row['energy_cost_per_min'] ?? 0);
            $laborLines = $row['labor_lines'] ?? [];
            $laborMoPerMin = InvItemOperationsRepository::sumLaborCostPerMinute($laborLines);
            if ($laborMoPerMin <= 0) {
                $operatorsQty = max(1, (int)($row['operators_qty'] ?? 1));
                $laborCostPerMin = (float)($row['labor_cost_per_min'] ?? 0);
                $laborMoPerMin = $laborCostPerMin * $operatorsQty;
            }
            if (!in_array($timeUnit, ['MIN', 'H'], true)) {
                $timeUnit = 'MIN';
            }
            $timeMinutes = $timeUnit === 'H' ? $rawTime * 60.0 : $rawTime;
            if ($timeMinutes <= 0) {
                continue;
            }
            $costPerMinuteFromRoute = $laborMoPerMin + $machineCostPerMin + $energyCostPerMin;
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



