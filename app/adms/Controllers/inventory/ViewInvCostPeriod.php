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
use App\adms\Models\Services\InvCostDreImportService;
use App\adms\Models\Services\InvCostEnergyDriversService;
use App\adms\Models\Services\InvCostEnergyRedistributionService;
use App\adms\Models\Services\InvCostFixedAllocationEngine;
use App\adms\Models\Services\InvCostPeriodProductionItemsService;
use App\adms\Models\Services\InvCostPeriodSnapshotService;
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
            if (!empty($_POST['recalculate_snapshots'])) {
                $this->handleRecalculateSnapshotsPost($periodId);
                return;
            }
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
        $snapshotService = new InvCostPeriodSnapshotService();

        $this->data['period'] = $period;
        $this->data['total_expense'] = $poolsRepo->sumAmountByPeriod($periodId);
        $this->data['criterion_labels'] = $this->criterionLabels();

        $isClosed = (string)($period['status'] ?? '') === 'closed';
        $skuFilter = trim((string)($_GET['sku_filter'] ?? ''));
        $activeTab = (string)($_GET['tab'] ?? 'despesas');
        if (!in_array($activeTab, ['despesas', 'skus', 'resultados'], true)) {
            $activeTab = 'despesas';
        }

        $this->data['sku_filter'] = $skuFilter;
        $this->data['active_tab'] = $activeTab;
        $this->data['snapshot_computed_at'] = $period['snapshot_computed_at'] ?? null;
        $this->data['snapshot_row_count'] = (int)($period['snapshot_row_count'] ?? 0);

        if ($activeTab === 'despesas') {
            $this->data['expense_pools'] = $poolsRepo->getByPeriodWithRules($periodId);
            $this->data['dre_imports'] = $importsRepo->getByPeriod($periodId);
            $cachedSummary = $snapshotService->getAllocationSummaryFromSnapshot($periodId, (float)$this->data['total_expense']);
            if ($cachedSummary !== null) {
                $this->data['allocation_summary'] = $cachedSummary;
            } else {
                $this->data['allocation_summary'] = (new InvCostFixedAllocationEngine())->allocateByPeriod($periodId, null);
            }

            $energyService = new InvCostEnergyRedistributionService();
            $energyTotal = $poolsRepo->sumEnergyAccountsForSplit($periodId);
            $this->data['energy_split_preview'] = $energyTotal > 0
                ? $energyService->previewSplit($energyTotal, $period, $periodId)
                : null;
            $this->data['energy_pending_total'] = $energyTotal;
            $this->data['energy_direct_kwh_computed'] = (new InvCostEnergyDriversService())->sumDirectKwhByPeriod($periodId);
        } else {
            $this->data['expense_pools'] = [];
            $this->data['dre_imports'] = [];
            $cachedSummary = $snapshotService->getAllocationSummaryFromSnapshot($periodId, (float)$this->data['total_expense']);
            $this->data['allocation_summary'] = $cachedSummary ?? [
                'total_expense' => (float)$this->data['total_expense'],
                'total_cfix_allocated' => 0.0,
                'pools_without_criterion' => 0.0,
                'unallocated_without_recipient' => 0.0,
                'unallocated_expense' => (float)$this->data['total_expense'],
            ];
            $this->data['energy_split_preview'] = null;
            $this->data['energy_pending_total'] = 0.0;
            $this->data['energy_direct_kwh_computed'] = 0.0;
        }

        if ($this->data['snapshot_row_count'] > 0) {
            $this->data['production_items_all_count'] = $this->data['snapshot_row_count'];
        } else {
            $this->data['production_items_all_count'] = (new InvCostProductionAggregationService())->countSkusInPeriod($periodId);
        }

        if (in_array($activeTab, ['skus', 'resultados'], true)) {
            if (!$snapshotService->hasSnapshot($periodId) && $this->data['production_items_all_count'] > 0) {
                $recalc = $snapshotService->recalculate($periodId);
                if (!empty($recalc['success'])) {
                    $period = $repo->getOne($periodId) ?: $period;
                    $this->data['snapshot_computed_at'] = $period['snapshot_computed_at'] ?? null;
                    $this->data['snapshot_row_count'] = (int)($period['snapshot_row_count'] ?? 0);
                    $this->data['production_items_all_count'] = $this->data['snapshot_row_count'];
                    $cachedSummary = $snapshotService->getAllocationSummaryFromSnapshot($periodId, (float)$this->data['total_expense']);
                    if ($cachedSummary !== null) {
                        $this->data['allocation_summary'] = $cachedSummary;
                    }
                }
            }
        }

        $productionService = new InvCostPeriodProductionItemsService();
        if ($activeTab === 'skus') {
            $this->data['production_items'] = $productionService->listForPeriod(
                $periodId,
                null,
                $skuFilter !== '' ? $skuFilter : null
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
            $this->data['sku_results'] = $snapshotService->listForPeriod(
                $periodId,
                $skuFilter !== '' ? $skuFilter : null
            );
        } else {
            $this->data['sku_results'] = [];
        }

        $this->data['can_recalculate_snapshots'] = true;

        $loadView = new LoadViewService('adms/Views/inventory/costs/period_view', $this->data);
        $loadView->loadView();
    }

    private function handleRecalculateSnapshotsPost(int $periodId): void
    {
        $tab = in_array((string)($_POST['return_tab'] ?? ''), ['skus', 'resultados'], true)
            ? (string)$_POST['return_tab']
            : 'skus';
        $redirect = $_ENV['URL_ADM'] . 'view-inventory-cost-period/' . $periodId . '?tab=' . $tab;
        $skuFilter = trim((string)($_POST['sku_filter'] ?? ''));
        if ($skuFilter !== '') {
            $redirect .= '&sku_filter=' . rawurlencode($skuFilter);
        }

        $token = (string)($_POST['csrf_token'] ?? '');
        if (!CSRFHelper::validateCSRFToken('form_recalculate_inv_cost_snapshots', $token, false)) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Sessão expirada ou token inválido. Recarregue a página e tente novamente.</div>";
            header('Location: ' . $redirect);
            exit;
        }

        $result = (new InvCostPeriodSnapshotService())->recalculate($periodId);
        if (!empty($result['success'])) {
            CSRFHelper::validateCSRFToken('form_recalculate_inv_cost_snapshots', $token, true);
        }

        $class = !empty($result['success']) ? 'success' : 'danger';
        $_SESSION['msg'] = "<div class='alert alert-{$class}'>" . htmlspecialchars((string)($result['message'] ?? '')) . '</div>';
        header('Location: ' . $redirect);
        exit;
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
        InvCostPeriodSnapshotService::tryRecalculate($periodId);

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
            InvCostPeriodSnapshotService::tryRecalculate($periodId);
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
