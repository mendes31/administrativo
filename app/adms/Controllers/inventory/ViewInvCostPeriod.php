<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvCostAllocationRulesRepository;
use App\adms\Models\Repository\inventory\InvCostDreImportsRepository;
use App\adms\Models\Repository\inventory\InvCostExpensePoolsRepository;
use App\adms\Models\Repository\inventory\InvCostPeriodsRepository;
use App\adms\Models\Repository\inventory\InvCostPeriodScenarioProductionRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Services\InvCostCriterionDriversService;
use App\adms\Models\Services\InvCostDreImportService;
use App\adms\Models\Services\InvCostEnergyDriversService;
use App\adms\Models\Services\InvCostEnergyRedistributionService;
use App\adms\Models\Services\InvCostFixedAllocationEngine;
use App\adms\Models\Services\InvCostPeriodProductionItemsService;
use App\adms\Models\Services\InvCostPeriodSkuResultsService;
use App\adms\Models\Services\InvCostProductionAggregationService;
use App\adms\Views\Services\LoadViewService;

class ViewInvCostPeriod
{
    private array $data = [];

    public function index(int|string $id = 0): void
    {
        $periodId = (int)$id;
        if ($periodId <= 0) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Período inválido.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-cost-periods');
            return;
        }

        $repo = new InvCostPeriodsRepository();
        $period = $repo->getOne($periodId);
        if ($period === false) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Período não encontrado.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-cost-periods');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!empty($_FILES['dre_file']['tmp_name'])) {
                $this->handleImportPost($periodId);
                return;
            }
            if (isset($_POST['rules']) && is_array($_POST['rules'])) {
                $this->handleSaveRulesPost($periodId);
                return;
            }
            if (!empty($_POST['apply_energy_split'])) {
                $this->handleApplyEnergySplitPost($periodId);
                return;
            }
        }

        $poolsRepo = new InvCostExpensePoolsRepository();
        $importsRepo = new InvCostDreImportsRepository();
        $engine = new InvCostFixedAllocationEngine();

        $this->data['period'] = $period;
        $this->data['expense_pools'] = $poolsRepo->getByPeriodWithRules($periodId);
        $this->data['total_expense'] = $poolsRepo->sumAmountByPeriod($periodId);
        $this->data['dre_imports'] = $importsRepo->getByPeriod($periodId);
        $this->data['allocation_summary'] = $engine->allocateByPeriod($periodId, null);
        $this->data['criterion_labels'] = $this->criterionLabels();

        $energyService = new InvCostEnergyRedistributionService();
        $energyTotal = $poolsRepo->sumEnergyAccountsForSplit($periodId);
        $this->data['energy_split_preview'] = $energyTotal > 0
            ? $energyService->previewSplit($energyTotal, $period, $periodId)
            : null;
        $this->data['energy_pending_total'] = $energyTotal;
        $this->data['energy_direct_kwh_computed'] = (new InvCostEnergyDriversService())->sumDirectKwhByPeriod($periodId);
        $isClosed = (string)($period['status'] ?? '') === 'closed';
        $skuFilter = trim((string)($_GET['sku_filter'] ?? ''));
        $activeTab = (string)($_GET['tab'] ?? 'despesas');
        if (!in_array($activeTab, ['despesas', 'skus', 'resultados'], true)) {
            $activeTab = 'despesas';
        }

        $productionAggService = new InvCostProductionAggregationService();
        $this->data['production_items_all_count'] = $productionAggService->countSkusInPeriod($periodId);
        $this->data['sku_filter'] = $skuFilter;
        $this->data['active_tab'] = $activeTab;

        $productionAggregation = null;
        $criterionAggregation = null;
        if (in_array($activeTab, ['skus', 'resultados'], true)) {
            $productionAggregation = $productionAggService->aggregateByPeriod($periodId);
            $criterionAggregation = (new InvCostCriterionDriversService())->aggregateAllCriteria($periodId);
        }

        $productionService = new InvCostPeriodProductionItemsService();
        if ($activeTab === 'skus') {
            $this->data['production_items'] = $productionService->listForPeriod(
                $periodId,
                null,
                $skuFilter !== '' ? $skuFilter : null,
                $productionAggregation,
                $criterionAggregation
            );
        } else {
            $this->data['production_items'] = [];
        }

        $this->data['scenario_rows'] = !$isClosed && $activeTab === 'skus'
            ? (new InvCostPeriodScenarioProductionRepository())->getByPeriod($periodId)
            : [];
        $this->data['project_items'] = $activeTab === 'skus'
            ? (new InvItemsRepository())->getProjectItemsForSelect()
            : [];

        $pageElements = [
            'title_head' => 'Período de Custeio',
            'menu' => 'estoque',
            'buttonPermission' => [
                'ListInvCostPeriods',
                'ViewInvCostPeriod',
                'UpdateInvCostPeriod',
                'ImportInvCostDre',
                'SaveInvCostAllocationRules',
                'SaveInvCostPeriodItems',
                'SaveInvCostPeriodScenarioProduction',
                'ExportInvCostPeriodSkuResults',
                'DownloadInvCostDreTemplate',
            ],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $this->data['can_save_scenario'] = in_array('SaveInvCostPeriodScenarioProduction', $this->data['buttonPermission'] ?? [], true)
            || in_array('ViewInvCostPeriod', $this->data['buttonPermission'] ?? [], true);

        $this->data['sku_results_all_count'] = $this->data['production_items_all_count'];
        if ($activeTab === 'resultados') {
            $this->data['sku_results'] = (new InvCostPeriodSkuResultsService())->listForPeriod(
                periodId: $periodId,
                filter: $skuFilter !== '' ? $skuFilter : null,
                productionAggregation: $productionAggregation,
                criterionAggregation: $criterionAggregation
            );
        } else {
            $this->data['sku_results'] = [];
        }

        $loadView = new LoadViewService('adms/Views/inventory/costs/period_view', $this->data);
        $loadView->loadView();
    }

    private function handleImportPost(int $periodId): void
    {
        $redirect = $_ENV['URL_ADM'] . 'view-inventory-cost-period/' . $periodId;
        $token = (string)($_POST['csrf_token'] ?? '');
        if (!CSRFHelper::validateCSRFToken('form_import_inv_cost_dre', $token, false)) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Sessão expirada ou token inválido. Recarregue a página e tente novamente.</div>";
            header('Location: ' . $redirect);
            exit;
        }

        if (empty($_FILES['dre_file']['tmp_name']) || !is_uploaded_file($_FILES['dre_file']['tmp_name'])) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Selecione um arquivo CSV para importar.</div>";
            header('Location: ' . $redirect);
            exit;
        }

        $result = (new InvCostDreImportService())->importCsvForPeriod(
            $periodId,
            $_FILES['dre_file']['tmp_name'],
            (string)($_FILES['dre_file']['name'] ?? 'dre.csv'),
            !empty($_POST['replace_previous']),
            isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null,
            !isset($_POST['apply_suggested_criteria']) || !empty($_POST['apply_suggested_criteria'])
        );

        if ($result['success']) {
            CSRFHelper::validateCSRFToken('form_import_inv_cost_dre', $token, true);
        }

        $class = $result['success'] ? 'success' : 'danger';
        $_SESSION['msg'] = "<div class='alert alert-{$class}'>" . htmlspecialchars($result['message']) . '</div>';
        header('Location: ' . $redirect);
        exit;
    }

    private function handleSaveRulesPost(int $periodId): void
    {
        $redirect = $_ENV['URL_ADM'] . 'view-inventory-cost-period/' . $periodId;
        $token = (string)($_POST['csrf_token'] ?? '');
        if (!CSRFHelper::validateCSRFToken('form_save_inv_cost_allocation_rules', $token, false)) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Sessão expirada ou token inválido. Recarregue a página e tente novamente.</div>";
            header('Location: ' . $redirect);
            exit;
        }

        $periodRepo = new InvCostPeriodsRepository();
        if ($periodRepo->isClosed($periodId)) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Período fechado: critérios não podem ser alterados.</div>";
            header('Location: ' . $redirect);
            exit;
        }

        $pools = (new InvCostExpensePoolsRepository())->getByPeriodWithRules($periodId);
        $rulesInput = $_POST['rules'] ?? [];
        $rulesByPool = [];
        foreach ($pools as $pool) {
            $poolId = (int)$pool['id'];
            $raw = is_array($rulesInput) ? ($rulesInput[$poolId] ?? $rulesInput[(string)$poolId] ?? null) : null;
            if (!is_array($raw)) {
                $rulesByPool[$poolId] = [];
                continue;
            }
            $criterion = (int)($raw['criterion'] ?? 0);
            $weightRaw = $raw['weight_pct'] ?? 100;
            if (is_string($weightRaw)) {
                $weightRaw = str_replace(',', '.', trim($weightRaw));
            }
            $weight = is_numeric($weightRaw) ? round((float)$weightRaw, 4) : 100.0;
            $rulesByPool[$poolId] = ($criterion >= 1 && $criterion <= 8)
                ? [['criterion' => $criterion, 'weight_pct' => $weight > 0 ? $weight : 100.0]]
                : [];
        }

        (new InvCostAllocationRulesRepository())->replaceRulesForPeriodPools($rulesByPool);
        CSRFHelper::validateCSRFToken('form_save_inv_cost_allocation_rules', $token, true);

        $_SESSION['msg'] = "<div class='alert alert-success'>Critérios de rateio salvos.</div>";
        header('Location: ' . $redirect);
        exit;
    }

    private function handleApplyEnergySplitPost(int $periodId): void
    {
        $redirect = $_ENV['URL_ADM'] . 'view-inventory-cost-period/' . $periodId;
        $token = (string)($_POST['csrf_token'] ?? '');
        if (!CSRFHelper::validateCSRFToken('form_apply_inv_cost_energy_split', $token, false)) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Sessão expirada ou token inválido. Recarregue a página e tente novamente.</div>";
            header('Location: ' . $redirect);
            exit;
        }

        $periodRepo = new InvCostPeriodsRepository();
        if ($periodRepo->isClosed($periodId)) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Período fechado: redistribuição bloqueada.</div>";
            header('Location: ' . $redirect);
            exit;
        }

        $result = (new InvCostEnergyRedistributionService())->applyForPeriod($periodId);
        if ($result['success']) {
            CSRFHelper::validateCSRFToken('form_apply_inv_cost_energy_split', $token, true);
        }

        $class = $result['success'] ? 'success' : 'danger';
        $_SESSION['msg'] = "<div class='alert alert-{$class}'>" . htmlspecialchars($result['message']) . '</div>';
        header('Location: ' . $redirect);
        exit;
    }

    /**
     * @return array<int, string>
     */
    private function criterionLabels(): array
    {
        return [
            1 => '1 — Qty produzida',
            2 => '2 — Homem-hora (HH)',
            3 => '3 — Horas-máquina (HM)',
            4 => '4 — Complexidade (CQ/P&D/DA)',
            5 => '5 — Nº matérias-primas',
            6 => '6 — Complexidade × análises (CQ/P&D/DA)',
            7 => '7 — Energia direta (kWh, linha TIARAJU)',
            8 => '8 — HVAC (CM/Prob/Outro)',
        ];
    }
}
