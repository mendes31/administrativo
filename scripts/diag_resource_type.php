<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Repository\inventory\InvProductionResourcesRepository;

$repo = new InvProductionResourcesRepository();
$row = $repo->getOne(1);
print_r($row);
