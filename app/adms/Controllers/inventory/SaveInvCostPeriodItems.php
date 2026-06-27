<?php

namespace App\adms\Controllers\inventory;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvCostPeriodItemsRepository;
use App\adms\Models\Repository\inventory\InvCostPeriodsRepository;

class SaveInvCostPeriodItems
{
    public function index(int|string $id = 0): void
    {
        $periodId = (int)$id;
        $redirect = $_ENV['URL_ADM'] . 'view-inventory-cost-period/' . max(1, $periodId) . '?tab=skus';
        $skuFilter = trim((string)($_POST['sku_filter'] ?? ''));
        if ($skuFilter !== '') {
            $redirect .= '&sku_filter=' . rawurlencode($skuFilter);
        }

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
        if (!CSRFHelper::validateCSRFToken('form_save_inv_cost_period_items', $token, false)) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Sessão expirada ou token inválido. Recarregue a página e tente novamente.</div>";
            header('Location: ' . $redirect);
            exit;
        }

        $periodRepo = new InvCostPeriodsRepository();
        if ($periodRepo->isClosed($periodId)) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Período fechado: parâmetros não podem ser alterados.</div>";
            header('Location: ' . $redirect);
            exit;
        }

        $input = $_POST['period_items'] ?? [];
        if (!is_array($input)) {
            $input = [];
        }

        $repo = new InvCostPeriodItemsRepository();
        $saved = 0;
        foreach ($input as $itemIdRaw => $fields) {
            $itemId = (int)$itemIdRaw;
            if ($itemId <= 0 || !is_array($fields)) {
                continue;
            }
            $repo->upsertAnalysisCount($periodId, $itemId, max(0, (int)($fields['analysis_count'] ?? 0)));
            $saved++;
        }

        CSRFHelper::validateCSRFToken('form_save_inv_cost_period_items', $token, true);

        $_SESSION['msg'] = $saved > 0
            ? "<div class='alert alert-success'>Análises do período salvas para {$saved} SKU(s).</div>"
            : "<div class='alert alert-warning'>Nenhum SKU foi alterado.</div>";
        header('Location: ' . $redirect);
        exit;
    }
}
