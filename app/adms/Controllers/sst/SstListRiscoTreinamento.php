<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\SstRiscoNavigationHelper;

class SstListRiscoTreinamento
{
    public function index(string|int $page = 1): void
    {
        SstRiscoNavigationHelper::redirectListToHub();
    }
}
