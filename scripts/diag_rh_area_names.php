<?php
require __DIR__ . '/bootstrap_app.php';
$repo = new App\adms\Models\Repository\inventory\InvCostRhDistributionLinesRepository();
foreach ($repo->getByPeriod(4) as $row) {
    echo ($row['area_name'] ?? '') . " | len=" . mb_strlen((string)($row['area_name'] ?? ''), 'UTF-8') . "\n";
}
