<?php
require __DIR__ . '/bootstrap_app.php';
$s = new App\adms\Models\Services\InvCostRhDistributionService();
$p = $s->buildPreview(4, 0.0);
echo "pool={$p['personnel_pool_total']}\n";
echo "dist={$p['distribution_total']}\n";
foreach (array_slice($p['slices'], 0, 5) as $sl) {
    echo "{$sl['area_name']} | pct={$sl['share_pct']} | slice={$sl['slice_amount']} | valor={$sl['amount']}\n";
}
