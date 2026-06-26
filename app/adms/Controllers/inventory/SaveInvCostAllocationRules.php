<?php

namespace App\adms\Controllers\inventory;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvCostAllocationRulesRepository;
use App\adms\Models\Repository\inventory\InvCostExpensePoolsRepository;
use App\adms\Models\Repository\inventory\InvCostPeriodsRepository;

class SaveInvCostAllocationRules
{
    public function index(int|string $id = 0): void
    {
        $periodId = (int)$id;
        $redirect = $_ENV['URL_ADM'] . 'view-inventory-cost-period/' . max(1, $periodId);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $redirect);
            exit;
        }

        if ($periodId <= 0) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Período inválido.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-cost-periods');
            exit;
        }

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

        $poolsRepo = new InvCostExpensePoolsRepository();
        $pools = $poolsRepo->getByPeriodWithRules($periodId);
        $rulesInput = $_POST['rules'] ?? [];
        if (!is_array($rulesInput)) {
            $rulesInput = [];
        }

        $rulesByPool = [];
        foreach ($pools as $pool) {
            $poolId = (int)$pool['id'];
            $raw = $rulesInput[$poolId] ?? $rulesInput[(string)$poolId] ?? null;
            if (!is_array($raw)) {
                $rulesByPool[$poolId] = [];
                continue;
            }

            $criterion = (int)($raw['criterion'] ?? 0);
            $weight = $this->parsePct($raw['weight_pct'] ?? 100);
            if ($criterion >= 1 && $criterion <= 8) {
                $rulesByPool[$poolId] = [
                    ['criterion' => $criterion, 'weight_pct' => $weight > 0 ? $weight : 100.0],
                ];
            } else {
                $rulesByPool[$poolId] = [];
            }
        }

        (new InvCostAllocationRulesRepository())->replaceRulesForPeriodPools($rulesByPool);
        CSRFHelper::validateCSRFToken('form_save_inv_cost_allocation_rules', $token, true);

        $_SESSION['msg'] = "<div class='alert alert-success'>Critérios de rateio salvos.</div>";
        header('Location: ' . $redirect);
        exit;
    }

    private function parsePct(mixed $value): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
        }

        return is_numeric($value) ? round((float)$value, 4) : 100.0;
    }
}
