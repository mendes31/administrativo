<?php

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Services\InvCostFixedAllocationEngine;
use App\adms\Models\Services\InvCostPeriodSnapshotService;
use App\adms\Models\Services\InvCostPeriodProductionItemsService;

$periodId = 4;
$erp = $argv[1] ?? '40500055';

$item = (new InvItemsRepository())->findByErpCode($erp);
$itemId = (int)($item['id'] ?? 0);

$live = (new InvCostPeriodProductionItemsService())->listForPeriod($periodId, null, $erp, bypassSnapshot: true)[0] ?? [];
$snap = (new InvCostPeriodSnapshotService())->listForPeriod($periodId, $erp)[0] ?? [];
$alloc = (new InvCostFixedAllocationEngine())->allocateForItem($periodId, $itemId);

echo "=== $erp (item $itemId) ===\n";
echo "Cadastro lote padrão: " . ($item['standard_batch_size'] ?? '—') . "\n\n";

echo "--- Live (sem snapshot) ---\n";
echo "qty={$live['total_qty']} planned={$live['qty_planned']} adopted={$live['batch_size_adopted']} batches_f27={$live['batches_produced']} eff={$live['efficiency_pct']}%\n";
echo "hh_period=" . ($live['hh_period'] ?? 'n/a') . " share_crit2=" . ($live['share_criterion_2'] ?? 'n/a') . "%\n\n";

echo "--- Snapshot Resultados ---\n";
echo "adopted=" . ($snap['batch_size_adopted'] ?? 'NULL') . " cfix_total=" . ($snap['cfix_total'] ?? '—') . " cfix_unit=" . ($snap['cfix_unit'] ?? '—') . "\n\n";

echo "--- CFIX alocado (motor) ---\n";
echo "cfix_total=" . ($alloc['cfix_total'] ?? 0) . "\n";
$qty = (float)($live['total_qty'] ?? 1);
echo "÷ qty $qty = " . round(($alloc['cfix_total'] ?? 0) / max($qty, 1), 4) . "/un\n";
echo "Planilha ref: 13.07/un → total implícito " . round(13.07 * $qty, 2) . "\n\n";

echo "Top fatias:\n";
$details = $alloc['details'] ?? [];
usort($details, fn($a, $b) => ($b['allocated'] ?? 0) <=> ($a['allocated'] ?? 0));
foreach (array_slice($details, 0, 8) as $d) {
    echo sprintf(
        "  crit %d pool %s → R$ %.2f\n",
        (int)($d['criterion'] ?? 0),
        (string)($d['pool_code'] ?? $d['pool_id'] ?? '?'),
        (float)($d['allocated'] ?? 0)
    );
}
