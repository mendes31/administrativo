<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Services\SstConformidadeService;
use App\adms\Views\Services\LoadViewService;

class SstReportConformidade
{
    private array $data = [];

    public function index(): void
    {
        $this->data['resumo'] = (new SstConformidadeService())->getResumo();
        $this->data['csrf_esocial'] = CSRFHelper::generateCSRFToken('sst_esocial_actions');

        $pageElements = [
            'title_head' => 'Conformidade SST - PGR/PCMSO/eSocial',
            'menu' => 'sst-report-conformidade',
            'buttonPermission' => [
                'SstReportConformidade', 'SstListProgramas', 'SstListEsocialEventos',
                'SstSyncEsocialPendentes', 'SstCreatePrograma',
            ],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/report_conformidade', $this->data))->loadView();
    }
}
