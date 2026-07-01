<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Services\InvCostPeriodSnapshotService;

$periodId = (int)($argv[1] ?? 0);
if ($periodId <= 0) {
    fwrite(STDERR, "Uso: php scripts/recalc_period_snapshot.php <period_id>\n");
    exit(1);
}

$result = (new InvCostPeriodSnapshotService())->recalculate($periodId);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
