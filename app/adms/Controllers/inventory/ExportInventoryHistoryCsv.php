<?php

namespace App\adms\Controllers\inventory;

use App\adms\Models\Repository\inventory\InvMovementsRepository;

class ExportInventoryHistoryCsv
{
    public function index(): void
    {
        $filters = [
            'type' => $_GET['type'] ?? '',
            'from' => $_GET['from'] ?? '',
            'to' => $_GET['to'] ?? '',
            'inv_item_id' => $_GET['inv_item_id'] ?? '',
            'inv_stock_id' => $_GET['inv_stock_id'] ?? ''
        ];
        $repo = new InvMovementsRepository();
        $rows = $repo->reportMovements(1, 1000000, $filters);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="historico_movimentacoes.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, ['Data', 'Tipo', 'Item', 'Qtd', 'Lote', 'De', 'Para'], ';');
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['movement_date'] ?? '',
                $r['type'] ?? '',
                $r['inv_item_id'] ?? '',
                number_format((float)($r['qty'] ?? 0), 4, ',', '.'),
                $r['batch_code'] ?? '',
                $r['from_stock_id'] ?? '',
                $r['to_stock_id'] ?? ''
            ], ';');
        }
        fclose($out);
        exit;
    }
}








