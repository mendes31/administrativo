<?php

namespace App\adms\Controllers\inventory;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvCostPeriodScenarioProductionRepository;
use App\adms\Models\Repository\inventory\InvCostPeriodsRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Services\InvCostPeriodSnapshotService;

class SaveInvCostPeriodScenarioProduction
{
    public function index(int|string $id = 0): void
    {
        $periodId = (int)$id;
        $redirect = $_ENV['URL_ADM'] . 'view-inventory-cost-period/' . max(1, $periodId) . '?tab=skus';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $redirect);
            exit;
        }

        $token = (string)($_POST['csrf_token'] ?? '');
        if (!CSRFHelper::validateCSRFToken('form_save_inv_cost_scenario_production', $token, false)) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Sessão expirada ou token inválido.</div>";
            header('Location: ' . $redirect);
            exit;
        }

        if ($periodId <= 0 || (new InvCostPeriodsRepository())->isClosed($periodId)) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Período inválido ou fechado.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-cost-periods');
            exit;
        }

        if (!empty($_POST['delete_scenario_id'])) {
            $deleted = (new InvCostPeriodScenarioProductionRepository())->delete(
                (int)$_POST['delete_scenario_id'],
                $periodId
            );
            CSRFHelper::validateCSRFToken('form_save_inv_cost_scenario_production', $token, true);
            InvCostPeriodSnapshotService::tryRecalculate($periodId);
            $_SESSION['msg'] = $deleted
                ? "<div class='alert alert-success'>Produção simulada removida.</div>"
                : "<div class='alert alert-warning'>Registro não encontrado.</div>";
            header('Location: ' . $redirect);
            exit;
        }

        $itemId = (int)($_POST['inv_item_id'] ?? 0);
        $erpCode = trim((string)($_POST['erp_code'] ?? ''));
        $description = trim((string)($_POST['item_description'] ?? ''));
        $batches = max(0, (int)($_POST['batches_count'] ?? 0));
        $qty = max(0.0, (float)($_POST['qty_produced'] ?? 0));

        if ($itemId > 0) {
            $item = (new InvItemsRepository())->getOne($itemId);
            if (is_array($item)) {
                if ($erpCode === '') {
                    $erpCode = trim((string)($item['erp_code'] ?? ''));
                }
                if ($description === '') {
                    $description = trim((string)($item['description'] ?? ''));
                }
            }
        }

        if ($erpCode === '' || ($batches <= 0 && $qty <= 0)) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Informe código ERP e lotes ou quantidade produzida.</div>";
            header('Location: ' . $redirect);
            exit;
        }

        (new InvCostPeriodScenarioProductionRepository())->insert($periodId, [
            'inv_item_id' => $itemId > 0 ? $itemId : null,
            'erp_code' => $erpCode,
            'item_description' => $description,
            'batches_count' => $batches,
            'qty_produced' => $qty,
            'notes' => trim((string)($_POST['notes'] ?? '')),
            'created_by' => (int)($_SESSION['user_id'] ?? 0) ?: null,
        ]);

        CSRFHelper::validateCSRFToken('form_save_inv_cost_scenario_production', $token, true);
        InvCostPeriodSnapshotService::tryRecalculate($periodId);
        $_SESSION['msg'] = "<div class='alert alert-success'>Produção simulada incluída no rateio do período (rascunho).</div>";
        header('Location: ' . $redirect);
        exit;
    }
}
