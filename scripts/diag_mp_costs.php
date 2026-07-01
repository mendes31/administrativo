<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Repository\inventory\InvItemsRepository;

$codes = ['10500015', '11000003', '10500013', '10500014', '11000010', '10500146'];
foreach ($codes as $c) {
    $i = (new InvItemsRepository())->findByErpCode($c);
    echo sprintf(
        "%s avg=%s last=%s active=%s\n",
        $c,
        $i['average_cost'] ?? 'n/a',
        $i['last_cost'] ?? 'n/a',
        $i['active'] ?? '?'
    );
}
