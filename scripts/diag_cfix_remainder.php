<?php
require __DIR__ . '/bootstrap_app.php';
$a = (new App\adms\Models\Services\InvCostFixedAllocationEngine())->allocateByPeriod(4);
echo 'allocated=' . $a['total_cfix_allocated'] . PHP_EOL;
echo 'unallocated=' . $a['unallocated_without_recipient'] . PHP_EOL;
echo 'expense=' . $a['total_expense'] . PHP_EOL;
