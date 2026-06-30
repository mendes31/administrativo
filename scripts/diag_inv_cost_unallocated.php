<?php

declare(strict_types=1);

/**
 * Lista contas DRE com critério mas sem rateio efetivo aos SKUs (driver/% zerado).
 *
 * Uso: php scripts/diag_inv_cost_unallocated.php [period_id|nome]
 */

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Repository\inventory\InvCostExpensePoolsRepository;
use App\adms\Models\Repository\inventory\InvCostPeriodsRepository;
use App\adms\Models\Services\InvCostFixedAllocationEngine;

$arg = $argv[1] ?? '';
$periodRepo = new InvCostPeriodsRepository();

if ($arg === '' || $arg === '--help') {
    echo "Uso: php scripts/diag_inv_cost_unallocated.php <period_id|nome>\n";
    exit(0);
}

$periodId = ctype_digit($arg) ? (int)$arg : 0;
if ($periodId > 0) {
    $probe = $periodRepo->getOne($periodId);
    if ($probe === false) {
        $periodId = 0;
    }
}
if ($periodId <= 0) {
    foreach ($periodRepo->getForSelect() as $p) {
        $name = (string)($p['name'] ?? '');
        if (stripos($name, $arg) !== false) {
            $periodId = (int)($p['id'] ?? 0);
            break;
        }
    }
}

if ($periodId <= 0) {
    fwrite(STDERR, "Período não encontrado: {$arg}\n");
    exit(1);
}

$period = $periodRepo->getOne($periodId);
if ($period === false) {
    fwrite(STDERR, "Período #{$periodId} inexistente.\n");
    exit(1);
}

$poolsRepo = new InvCostExpensePoolsRepository();
$pools = $poolsRepo->getByPeriodWithRules($periodId);
$engine = new InvCostFixedAllocationEngine();
$allocation = $engine->allocateByPeriod($periodId);
$totalExpense = (float)($allocation['total_expense'] ?? 0);
$totalAllocated = (float)($allocation['total_cfix_allocated'] ?? 0);
$poolsWithoutCrit = (float)($allocation['pools_without_criterion'] ?? 0);
$unallocatedNoRecipient = (float)($allocation['unallocated_without_recipient'] ?? 0);

echo "Período: {$period['name']} (#{$periodId})\n";
echo sprintf("Despesas: R$ %s | CFIX rateado: R$ %s\n", number_format($totalExpense, 2, ',', '.'), number_format($totalAllocated, 2, ',', '.'));
echo sprintf("Sem critério definido: R$ %s\n", number_format($poolsWithoutCrit, 2, ',', '.'));
echo sprintf("Com critério, sem destino: R$ %s\n\n", number_format($unallocatedNoRecipient, 2, ',', '.'));

$poolAllocated = [];
foreach ($allocation['by_item'] ?? [] as $itemId => $row) {
    foreach ($row['details'] ?? [] as $detail) {
        $poolId = (int)($detail['pool_id'] ?? 0);
        $poolAllocated[$poolId] = ($poolAllocated[$poolId] ?? 0) + (float)($detail['allocated'] ?? 0);
    }
}

$rows = [];
foreach ($pools as $pool) {
    $poolId = (int)($pool['id'] ?? 0);
    $amount = (float)($pool['amount'] ?? 0);
    if ($amount <= 0) {
        continue;
    }
    $rules = $pool['rules'] ?? [];
    if ($rules === []) {
        continue;
    }
    $allocated = round($poolAllocated[$poolId] ?? 0, 4);
    $gap = round($amount - $allocated, 4);
    if ($gap <= 0.005) {
        continue;
    }
    $criteria = array_map(
        static fn(array $r): string => (string)($r['criterion'] ?? ''),
        $rules
    );
    $rows[] = [
        'code' => (string)($pool['account_code'] ?? ''),
        'description' => (string)($pool['description'] ?? ''),
        'amount' => $amount,
        'allocated' => $allocated,
        'gap' => $gap,
        'criteria' => implode(',', $criteria),
    ];
}

usort($rows, static fn(array $a, array $b): int => $b['gap'] <=> $a['gap']);

if ($rows === []) {
    echo "Nenhuma conta com critério e gap de rateio.\n";
    exit(0);
}

echo str_pad('Conta', 8) . str_pad('Crit.', 8) . str_pad('Valor', 18) . str_pad('Rateado', 18) . str_pad('Sem destino', 18) . "Descrição\n";
echo str_repeat('-', 100) . "\n";
foreach ($rows as $row) {
    echo str_pad($row['code'], 8)
        . str_pad($row['criteria'], 8)
        . str_pad(number_format($row['amount'], 2, ',', '.'), 18)
        . str_pad(number_format($row['allocated'], 2, ',', '.'), 18)
        . str_pad(number_format($row['gap'], 2, ',', '.'), 18)
        . mb_substr($row['description'], 0, 60)
        . "\n";
}
