<?php

namespace App\adms\Controllers\inventory;

use App\adms\Models\Repository\inventory\InvBalancesRepository;

class ExportInventoryBalanceCsv
{
    public function index(): void
    {
        $filters = [
            'inv_item_id' => $_GET['inv_item_id'] ?? '',
            'inv_stock_id' => $_GET['inv_stock_id'] ?? '',
            'inv_position_id' => $_GET['inv_position_id'] ?? '',
            'batch_code' => $_GET['batch_code'] ?? '',
            'expiration_date' => $_GET['expiration_date'] ?? ''
        ];

        $repo = new InvBalancesRepository();
        // Obter um lote grande (ajuste se necessário)
        $rows = $repo->reportBalances(1, 1000000, $filters);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="relatorio_saldos_estoque.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
        fputcsv($out, ['Item', 'Estoque', 'Posição', 'Lote', 'Validade', 'Quantidade', 'Custo Médio', 'Valor Total'], ';');
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['item_description'] ?? '',
                $r['stock_name'] ?? '',
                trim(($r['position_code'] ?? '') . ' ' . ($r['position_description'] ?? '')),
                $r['batch_code'] ?? '',
                $r['expiration_date'] ?? '',
                number_format((float)($r['qty'] ?? 0), 4, ',', '.'),
                number_format((float)($r['average_cost'] ?? 0), 4, ',', '.'),
                number_format((float)(($r['qty'] ?? 0) * ($r['average_cost'] ?? 0)), 2, ',', '.')
            ], ';');
        }
        fclose($out);
        exit;
    }
}








