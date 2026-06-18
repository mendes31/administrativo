<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstPlanosAcaoRepository;
use App\adms\Models\Services\SstDashboardService;
use App\adms\Models\Services\SstPendenciasService;
use App\adms\Views\Services\LoadViewService;

class SstDashboard
{
    private array $data = [];

    public function index(): void
    {
        $service = new SstDashboardService();
        $pendenciasService = new SstPendenciasService();
        $this->data['pending_exams_count'] = $service->getPendingExamsCount();
        $this->data['expired_epis_count'] = $service->getExpiredEpisCount();
        $this->data['open_accidents_count'] = $service->getOpenAccidentsCount();
        $this->data['afastamentos_ativos_count'] = $service->getAfastamentosAtivosCount();
        $this->data['low_stock_epis_count'] = $service->getLowStockEpisCount();
        $this->data['planos_acao_vencidos_count'] = (new SstPlanosAcaoRepository())->countVencidos();
        $pendenciasResumo = $pendenciasService->getDashboardResumo(5);
        $this->data['pendencias_vinculo_count'] = $pendenciasResumo['criticas_count'];
        $this->data['pendencias_epi_amostra'] = $pendenciasResumo['epis_amostra'];
        $this->data['pendencias_exame_amostra'] = $pendenciasResumo['exames_amostra'];
        $this->data['pending_exams'] = $service->getPendingExams(5);
        $this->data['expired_epis'] = $service->getExpiredEpis(5);
        $this->data['open_accidents'] = $service->getOpenAccidents(5);
        $this->data['low_stock_epis'] = $service->getLowStockEpis(5);
        $this->data['afastamentos_ativos'] = $service->listAfastamentosAtivos(5);

        $pageElements = [
            'title_head' => 'Dashboard - SST',
            'menu' => 'sst-dashboard',
            'buttonPermission' => [
                'SstDashboard',
                'SstReportPendencias', 'SstReportExames', 'SstReportEpis', 'SstReportAfastamentos', 'SstReportConformidade',
                'SstListExames', 'SstListEpis', 'SstListRiscos', 'SstListCids', 'SstListMedicos',
                'SstListEpiNecessidade', 'SstListExameNecessidade', 'SstListRiscoCargo',
                'SstListAsos', 'SstListAfastamentos', 'SstListEpiEntregas', 'SstListAcidentes',
            ],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/dashboard', $this->data))->loadView();
    }
}