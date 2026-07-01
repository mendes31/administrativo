<?php
require __DIR__ . '/bootstrap_app.php';
$r = (new App\adms\Models\Services\InvCostPeriodSnapshotService())->recalculate(4);
echo ($r['message'] ?? '') . PHP_EOL;
$rows = (new App\adms\Models\Services\InvCostPeriodSnapshotService())->listForPeriod(4, '40500055');
$row = $rows[0] ?? [];
echo 'cvar_mp_unit=' . ($row['cvar_mp_unit'] ?? '—') . PHP_EOL;
echo 'cvar_sim_unit=' . ($row['cvar_sim_unit'] ?? '—') . PHP_EOL;
echo 'cvar_period_total=' . ($row['cvar_period_total'] ?? '—') . PHP_EOL;
echo 'cfix_unit=' . ($row['cfix_unit'] ?? '—') . PHP_EOL;
echo 'full_cost_unit=' . ($row['full_cost_unit'] ?? '—') . PHP_EOL;
echo 'costing_batch_size=' . ($row['costing_batch_size'] ?? '—') . PHP_EOL;
