<?php

namespace App\adms\Controllers\inventory;

use App\adms\Models\Repository\inventory\InvBalancesRepository;
use App\adms\Models\Repository\inventory\InvPositionsRepository;

class InventoryAjaxPositions
{
    // Retorna dados conforme 'action'. Padrão: posições por estoque
    public function index(): void
    {
        header('Content-Type: application/json');
        $action = $_GET['action'] ?? '';
        if ($action === 'getAvailable') { $this->getAvailable(); return; }
        if ($action === 'getBalanceOptions') { $this->getBalanceOptions(); return; }
        if ($action === 'stocksAvailable') { $this->getStocksAvailable(); return; }
        if ($action === 'positionsAvailable') { $this->getPositionsAvailable(); return; }

        $stockId = isset($_GET['stock_id']) ? (int)$_GET['stock_id'] : 0;
        if (!$stockId) { echo json_encode([]); return; }
        $repo = new InvPositionsRepository();
        echo json_encode($repo->getAllForSelectByStock($stockId));
    }

    public function getAvailable(): void
    {
        header('Content-Type: application/json');
        $itemId = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
        $stockId = isset($_GET['stock_id']) ? (int)$_GET['stock_id'] : 0;
        $positionId = isset($_GET['position_id']) && $_GET['position_id'] !== '' ? (int)$_GET['position_id'] : null;
        if (!$itemId || !$stockId) { echo json_encode(['available' => 0]); return; }
        $repo = new InvBalancesRepository();
        $filters = ['inv_item_id' => $itemId, 'inv_stock_id' => $stockId];
        if ($positionId) { $filters['inv_position_id'] = $positionId; }
        $rows = $repo->reportBalances(1, 1000, $filters);
        $sum = 0.0; foreach ($rows as $r) { $sum += (float)($r['qty'] ?? 0); }
        echo json_encode(['available' => $sum]);
    }

    public function getBalanceOptions(): void
    {
        header('Content-Type: application/json');
        $itemId = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
        $stockId = isset($_GET['stock_id']) ? (int)$_GET['stock_id'] : 0;
        $positionId = isset($_GET['position_id']) && $_GET['position_id'] !== '' ? (int)$_GET['position_id'] : null;
        if (!$itemId || !$stockId) { echo json_encode([]); return; }
        $repo = new InvBalancesRepository();
        $filters = ['inv_item_id' => $itemId, 'inv_stock_id' => $stockId];
        if ($positionId) { $filters['inv_position_id'] = $positionId; }
        $rows = $repo->reportBalances(1, 1000, $filters);
        $out = array_map(function($r){ return [
            'batch_code' => $r['batch_code'] ?? null,
            'expiration_date' => $r['expiration_date'] ?? null,
            'qty' => (float)($r['qty'] ?? 0)
        ]; }, $rows);
        echo json_encode($out);
    }

    // Estoques com disponibilidade para um item
    public function getStocksAvailable(): void
    {
        header('Content-Type: application/json');
        $itemId = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
        if (!$itemId) { echo json_encode([]); return; }
        $repo = new InvBalancesRepository();
        $rows = $repo->reportBalances(1, 5000, ['inv_item_id' => $itemId]);
        $byStock = [];
        foreach ($rows as $r) {
            $sid = (int)($r['inv_stock_id'] ?? 0);
            $qty = (float)($r['qty'] ?? 0);
            if (!isset($byStock[$sid])) { $byStock[$sid] = ['id' => $sid, 'name' => $r['stock_name'] ?? '', 'qty' => 0]; }
            $byStock[$sid]['qty'] += $qty;
        }
        $out = array_values(array_filter($byStock, function($s){ return $s['id'] && $s['qty'] > 0; }));
        echo json_encode($out);
    }

    // Posições com disponibilidade dentro de um estoque para o item
    public function getPositionsAvailable(): void
    {
        header('Content-Type: application/json');
        $itemId = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
        $stockId = isset($_GET['stock_id']) ? (int)$_GET['stock_id'] : 0;
        if (!$itemId || !$stockId) { echo json_encode([]); return; }
        $repo = new InvBalancesRepository();
        $rows = $repo->reportBalances(1, 5000, ['inv_item_id' => $itemId, 'inv_stock_id' => $stockId]);
        $byPos = [];
        foreach ($rows as $r) {
            $pid = $r['inv_position_id'] ? (int)$r['inv_position_id'] : 0;
            $qty = (float)($r['qty'] ?? 0);
            if (!$pid) { continue; }
            if (!isset($byPos[$pid])) { $byPos[$pid] = ['id' => $pid, 'code' => $r['position_code'] ?? '', 'description' => $r['position_description'] ?? '', 'qty' => 0]; }
            $byPos[$pid]['qty'] += $qty;
        }
        $out = array_values(array_filter($byPos, function($p){ return $p['qty'] > 0; }));
        echo json_encode($out);
    }
}


