<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Helpers\InvItemBomExplosionHelper;
use App\adms\Models\Repository\inventory\InvItemBomRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;

foreach (['61000095', '60500005'] as $erp) {
    $item = (new InvItemsRepository())->findByErpCode($erp);
    if ($item === null) {
        echo "{$erp}: não encontrado\n\n";
        continue;
    }
    $id = (int)$item['id'];
    echo "=== BOM DB — {$erp} (id {$id}) ===\n";
    foreach ((new InvItemBomRepository())->getCostingRowsByItem($id) as $row) {
        $cat = (string)($row['component_category'] ?? '');
        $isPi = InvItemBomExplosionHelper::isIntermediateCategory($cat) ? ' [PI]' : '';
        echo sprintf(
            "  %s %-40s qty=%.6f cat=%s%s\n",
            $row['component_code'] ?? '?',
            mb_substr((string)($row['component_description'] ?? ''), 0, 40),
            (float)($row['quantity_per_batch'] ?? 0),
            $cat,
            $isPi
        );
    }
    echo "\n";
}
