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
            'lotes',
            'eficiencia_pct',
            'cvar_sim_unit',
            'cvar_energia_unit',
            'cfix_total',
            'cfix_unit',
            'custo_pleno_unit',
        ], ';');

        foreach ($rows as $row) {
            fputcsv($out, [
                (string)($row['erp_code'] ?? ''),
                (string)($row['item_description'] ?? ''),
                $row['total_qty'] ?? '',
                (int)($row['batches_count'] ?? 0),
                $row['efficiency_pct'] ?? '',
                $row['cvar_sim_unit'] ?? '',
                $row['cvar_energy_unit'] ?? '',
                $row['cfix_total'] ?? '',
                $row['cfix_unit'] ?? '',
                $row['full_cost_unit'] ?? '',
            ], ';');
        }

        fclose($out);
        exit;
    }
}
