<?php

namespace App\adms\Controllers\inventory;

use App\adms\Models\Services\InvCostDreImportService;

class DownloadInvCostDreTemplate
{
    public function index(): void
    {
        $csv = InvCostDreImportService::buildTemplateCsv();
        $filename = 'template_importacao_dre_custeio.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        // BOM para Excel abrir UTF-8 corretamente
        echo "\xEF\xBB\xBF";
        echo $csv;
        exit;
    }
}
