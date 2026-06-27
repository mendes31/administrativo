<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Services\InvCostCriterionDriversService;
use App\adms\Models\Services\InvCostPeriodItemDefaultsService;

$periodId = (int)($argv[1] ?? 4);
$erp = $argv[2] ?? '43000043';

$item = (new InvItemsRepository())->findByErpCode($erp);
if ($item === null) {
    fwrite(STDERR, "SKU {$erp} não encontrado\n");
    exit(1);
}

$itemId = (int)$item['id'];
$periodItem = (new InvCostPeriodItemDefaultsService())->mergeWithDefaults($itemId, null);
$line = (string)($periodItem['production_line'] ?? '—');

$agg = (new InvCostCriterionDriversService())->aggregateAllCriteria($periodId);
$row = null;
foreach ($agg['items'] ?? [] as $r) {
    if ((int)($r['inv_item_id'] ?? 0) === $itemId) {
        $row = $r;
        break;
    }
}

echo "SKU {$erp} | linha cadastro: {$line}\n";
if ($row === null) {
    echo "Sem produção no período {$periodId}\n";
    exit(0);
}

echo 'driver_7: ' . ($row['driver_7'] ?? 0) . "\n";
echo 'share_criterion_7: ' . ($row['share_criterion_7'] ?? 0) . "%\n";
