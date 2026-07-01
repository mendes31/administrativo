<?php

require __DIR__ . '/bootstrap_app.php';

use App\adms\Helpers\InvCostBatchAdoptedHelper;

$cases = [
    ['40500055', 700, 666, 1],
    ['40500052', 2, 2270, 1],
    ['40500052', 1, 2270, 1],
    ['test', 1, 500, 2],
];

foreach ($cases as [$erp, $cat, $q, $b]) {
    $ctx = InvCostBatchAdoptedHelper::resolveProductionContext(null, $q, $b, $cat);
    echo sprintf(
        "%s cat=%s q=%s lots=%d => F24=%s adopted=%s batches=%s eff=%s%%\n",
        $erp,
        $cat,
        $q,
        $b,
        $ctx['batch_size_theoretical_fixed'] ?? 'null',
        $ctx['batch_size_adopted'],
        $ctx['batches_produced'],
        $ctx['efficiency_pct']
    );
}

$r = (new App\adms\Models\Services\InvCostPeriodSnapshotService())->recalculate(4);
echo "\n" . ($r['message'] ?? '') . "\n";
foreach (['40500055', '40500052'] as $erp) {
    $rows = (new App\adms\Models\Services\InvCostPeriodProductionItemsService())->listForPeriod(4, null, $erp, bypassSnapshot: true);
    $row = $rows[0] ?? [];
    echo "$erp SKUs-tab: qty={$row['total_qty']} planned={$row['qty_planned']} adopted={$row['batch_size_adopted']} eff={$row['efficiency_pct']}%\n";
    $snap = (new App\adms\Models\Services\InvCostPeriodSnapshotService())->listForPeriod(4, $erp)[0] ?? [];
    echo "$erp Resultados: adopted={$snap['batch_size_adopted']} cfix_unit={$snap['cfix_unit']}\n";
}
