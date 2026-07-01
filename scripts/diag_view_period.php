<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Repository\inventory\InvCostDreImportsRepository;
use App\adms\Models\Repository\inventory\InvCostExpensePoolsRepository;
use App\adms\Models\Repository\inventory\InvCostPeriodsRepository;
use App\adms\Models\Services\InvCostEnergyDriversService;
use App\adms\Models\Services\InvCostEnergyRedistributionService;
use App\adms\Models\Services\InvCostFixedAllocationEngine;
use App\adms\Models\Services\InvCostPeriodSnapshotService;
use App\adms\Models\Services\InvCostRhDistributionService;

$periodId = (int)($argv[1] ?? 4);

try {
    $repo = new InvCostPeriodsRepository();
    $period = $repo->getOne($periodId);
    if ($period === false) {
        fwrite(STDERR, "Period not found\n");
        exit(1);
    }
    echo "Period OK: {$period['name']}\n";

    $poolsRepo = new InvCostExpensePoolsRepository();
    $total = $poolsRepo->sumAmountByPeriod($periodId);
    echo "Total expense: {$total}\n";

    $snapshotService = new InvCostPeriodSnapshotService();
    $cached = $snapshotService->getAllocationSummaryFromSnapshot($periodId, (float)$total);
    if ($cached !== null) {
        echo "Snapshot summary OK\n";
    } else {
        echo "Allocating via engine...\n";
        $alloc = (new InvCostFixedAllocationEngine())->allocateByPeriod($periodId, null);
        echo "CFIX: {$alloc['total_cfix_allocated']}\n";
    }

    $energyService = new InvCostEnergyRedistributionService();
    $energyTotal = $poolsRepo->sumEnergyAccountsForSplit($periodId);
    if ($energyTotal > 0) {
        $preview = $energyService->previewSplit($energyTotal, $period, $periodId);
        echo "Energy preview OK\n";
    }

    $kwh = (new InvCostEnergyDriversService())->sumDirectKwhByPeriod($periodId);
    echo "Direct kWh: {$kwh}\n";

    $rh = new InvCostRhDistributionService();
    $lines = $rh->getLinesForPeriod($periodId);
    echo "RH lines: " . count($lines) . "\n";
    $preview = $rh->buildPreview($periodId, 0.0);
    echo "RH preview pool: " . ($preview['personnel_pool_total'] ?? 0) . "\n";

    echo "ALL OK\n";
} catch (Throwable $e) {
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    fwrite(STDERR, $e->getFile() . ':' . $e->getLine() . "\n");
    fwrite(STDERR, $e->getTraceAsString() . "\n");
    exit(2);
}
