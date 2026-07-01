<?php

namespace App\adms\Controllers\inventory;

use App\adms\Models\Repository\inventory\InvCostPeriodsRepository;
use App\adms\Models\Services\InvCostPeriodSkuResultsService;

class ExportInvCostPeriodSkuResults
{
    public function index(int|string $id = 0): void
    {
        $periodId = (int)$id;
        if ($periodId <= 0) {
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-cost-periods');
            exit;
        }

        if (empty($_SESSION['user_id']) && empty($_SESSION['user_name']) && empty($_SESSION['user_email'])) {
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        $period = (new InvCostPeriodsRepository())->getOne($periodId);
        if ($period === false) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Período não encontrado.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-cost-periods');
            exit;
        }

        $filter = trim((string)($_GET['sku_filter'] ?? ''));
        $rows = (new InvCostPeriodSkuResultsService())->listForPeriod(
            periodId: $periodId,
            filter: $filter !== '' ? $filter : null
        );

        $filename = 'custeio-periodo-' . $periodId . '-skus-' . date('Ymd-His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }

        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, [
            'sku',
            'descricao',
            'qtd',
            'lote_padrao_sap',
            'lote_adotado',
            'eficiencia_pct',
            'cvar_mp_unit',
            'cvar_mae_unit',
            'cvar_unit',
            'cvar_energia_unit',
            'cvar_periodo_total',
            'cfix_total',
            'cfix_unit',
            'custo_pleno_unit',
            'custo_pleno_periodo_total',
            'preco_liquido',
            'markup_pct',
        ], ';');

        foreach ($rows as $row) {
            fputcsv($out, [
                (string)($row['erp_code'] ?? ''),
                (string)($row['item_description'] ?? ''),
                $row['total_qty'] ?? '',
                $row['standard_batch_size'] ?? '',
                $row['costing_batch_size'] ?? '',
                $row['efficiency_pct'] ?? '',
                $row['cvar_mp_unit'] ?? '',
                $row['cvar_mae_unit'] ?? '',
                $row['cvar_sim_unit'] ?? '',
                $row['cvar_energy_unit'] ?? '',
                $row['cvar_period_total'] ?? $row['cvar_batch'] ?? '',
                $row['cfix_total'] ?? '',
                $row['cfix_unit'] ?? '',
                $row['full_cost_unit'] ?? '',
                $row['full_cost_period_total'] ?? $row['full_cost_batch'] ?? '',
                $row['sale_price_net'] ?? '',
                $row['markup_pct'] ?? '',
            ], ';');
        }

        fclose($out);
        exit;
    }
}
