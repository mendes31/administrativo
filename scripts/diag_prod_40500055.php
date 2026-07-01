<?php
require __DIR__ . '/bootstrap_app.php';
$s = (new App\adms\Models\Repository\inventory\InvCostPeriodsRepository())->getConnection()->prepare(
    'SELECT COUNT(*) AS c, SUM(quantity) AS q, MIN(production_date) AS dmin, MAX(production_date) AS dmax
     FROM inv_cost_production_batches WHERE erp_code = ? AND production_date BETWEEN ? AND ?'
);
$s->execute(['40500055', '2025-01-01', '2025-12-31']);
print_r($s->fetch(PDO::FETCH_ASSOC));
