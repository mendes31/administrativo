<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Repository\inventory\InvItemsRepository;

$erps = ['40500069', '43000123', '43000122', '40500058', '40500052'];
$repo = new InvItemsRepository();

echo "erp;id;standard_batch_size;average_cost;description\n";
foreach ($erps as $erp) {
    $item = $repo->findByErpCode($erp);
    if ($item === null) {
        continue;
    }
    echo implode(';', [
        $erp,
        (string)($item['id'] ?? ''),
        (string)($item['standard_batch_size'] ?? ''),
        (string)($item['average_cost'] ?? ''),
        '"' . str_replace('"', "'", (string)($item['description'] ?? '')) . '"',
    ]) . "\n";
}
