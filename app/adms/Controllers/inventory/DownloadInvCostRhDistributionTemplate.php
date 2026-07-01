<?php

declare(strict_types=1);

namespace App\adms\Controllers\inventory;

class DownloadInvCostRhDistributionTemplate
{
    public function index(): void
    {
        $csv = \App\adms\Models\Services\InvCostRhDistributionService::buildTemplateCsv();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="distribuicao_rh_template.csv"');
        echo "\xEF\xBB\xBF";
        echo $csv;
        exit;
    }
}
