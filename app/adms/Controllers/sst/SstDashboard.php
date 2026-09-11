<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstPlanosAcaoRepository;
use App\adms\Models\Services\SstDashboardService;
use App\adms\Models\Services\SstGoLiveReadinessService;
use App\adms\Models\Services\SstPendenciasService;
use App\adms\Views\Services\LoadViewService;

class SstDashboard
{
    private array $data = [];

    public function index(): void
    {
        (new \App\adms\Models\Services\SstAsoSolicitacaoService())->sincronizarSolicitacoesPendentes();

        $service = new SstDashboardService();
        $pendenciasService = new SstPendenciasService();
        $this->data['pending_exams_count'] = $service->getPendingExamsCount();
        $this->data['expired_epis_count'] = $service->getExpiredEpisCount();
        $this->data['open_accidents_count'] = $service->getOpenAccidentsCount();
        $this->data['afastamentos_ativos_count'] = $service->getAfastamentosAtivosCount();
        $this->data['low_stock_epis_count'] = $service->getLowStockEpisCount();
        $pendenciasResumo = $pendenciasService->getDashboardResumo(5);
        $this->data['pendencias_vinculo_count'] = $pendenciasResumo['criticas_count'];
        $this->data['pendencias_epi_amostra'] = $pendenciasResumo['epis_amostra'];
        $this->data['pendencias_exame_amostra'] = $pendenciasResumo['exames_amostra'];
        $this->data['pendencias_treinamento_amostra'] = $pendenciasResumo['treinamentos_amostra'] ?? [];
        $this->data['treinamentos_pendentes_count'] = (int) ($pendenciasResumo['treinamentos_pendentes_count'] ?? 0);
        $this->data['treinamentos_vencidos_count'] = (int) ($pendenciasResumo['treinamentos_vencidos_count'] ?? 0);
        $this->data['pending_exams'] = $service->getPendingExams(5);
        $this->data['expired_epis'] = $service->getExpiredEpis(5);
        $this->data['open_accidents'] = $service->getOpenAccidents(5);
        $this->data['low_stock_epis'] = $service->getLowStockEpis(5);
        $this->data['afastamentos_ativos'] = $service->listAfastamentosAtivos(5);
        $this->data['planos_acao_vencidos_count'] = (new SstPlanosAcaoRepository())->countVencidos();
        $this->data['asos_aguardando_count'] = (new \App\adms\Models\Repository\SstAsosRepository())->countAguardando();
        $this->data['golive'] = (new SstGoLiveReadinessService())->getChecklist();

        $pageElements = [
            'title_head' => 'Dashboard - SST',
            'menu' => 'sst-dashboard',
            'buttonPermission' => [
                'SstDashboard',
                'SstReportPendencias', 'SstReportExames', 'SstReportEpis', 'SstReportAfastamentos', 'SstReportConformidade',
                'SstReportTreinamentos',
                'SstListExames', 'SstListEpis', 'SstListRiscos', 'SstListCids', 'SstListMedicos',
                'SstListEpiNecessidade', 'SstListExameNecessidade', 'SstListRiscoCargo',
                'SstListTreinamentos', 'SstListTreinamentoNecessidade', 'SstMatrizTreinamentoCargo', 'SstListTreinamentoVinculos', 'SstListGhe',
                'SstListAsos', 'SstListAfastamentos', 'SstListEpiEntregas', 'SstListAcidentes',
                'SstListEquipamentos', 'SstListEquipamentoTipos', 'SstEquipamentoSettings',
                'SstListEpiFichas', 'SstEncaminhamentoAso',
                'ListUsers', 'NotificationSettings',
            ],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/dashboard', $this->data))->loadView();
    }
}