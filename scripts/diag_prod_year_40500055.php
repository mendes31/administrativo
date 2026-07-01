<?php
require __DIR__ . '/bootstrap_app.php';
$s = (new App\adms\Models\Repository\inventory\InvCostPeriodsRepository())->getConnection();
$q = $s->prepare('SELECT SUM(quantity) AS q, COUNT(*) AS c FROM inv_cost_production_batches WHERE erp_code = ? AND production_date BETWEEN ? AND ?');
$q->execute(['40500055', '2025-01-01', '2025-12-31']);
print_r($q->fetch(PDO::FETCH_ASSOC));
$cfix = 16197.51;
echo "cfix/13.07 qty_implied=" . round($cfix / 13.07, 1) . PHP_EOL;
echo "cfix/666=" . round($cfix / 666, 2) . PHP_EOL;
