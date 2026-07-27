<?php

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RhEntrevistasRepository;
use App\adms\Models\Repository\RhEntrevistaAvaliadoresRepository;
use App\adms\Models\Repository\RhEntrevistaComunicacoesRepository;
use App\adms\Models\Repository\RhEntrevistaReagendamentosRepository;
use App\adms\Models\Repository\RhEntrevistaScorecardRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Models\Services\RhPermissionService;
use App\adms\Views\Services\LoadViewService;

class RhEntrevistasView
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        if (!(int)$id) {
            GenerateLog::generateLog('error', 'Entrevista não encontrada', ['id' => (int)$id]);
            $_SESSION['error'] = "Entrevista não encontrada!";
            header("Location: {$_ENV['URL_ADM']}rh-entrevistas");
            return;
        }

        $repo = new RhEntrevistasRepository();
        $entrevista = $repo->getById((int)$id);

        if (!$entrevista) {
            $_SESSION['error'] = "Entrevista não encontrada!";
            header("Location: {$_ENV['URL_ADM']}rh-entrevistas");
            return;
        }

        if (!RhPermissionService::canViewEntrevista($entrevista)) {
            $_SESSION['error'] = 'Você não tem permissão para visualizar esta entrevista.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-entrevistas');
            return;
        }

        $this->data['entrevista'] = $entrevista;
        $this->data['can_manage_entrevista'] = RhPermissionService::canManageEntrevista($entrevista);
        $this->data['scorecards'] = [];
        $this->data['painel_avaliadores'] = [];
        $this->data['reagendamentos'] = [];
        $this->data['comunicacoes'] = [];
        try {
            $this->data['scorecards'] = (new RhEntrevistaScorecardRepository())->listByEntrevista((int) $id);
        } catch (\Throwable $e) {
            GenerateLog::generateLog('warning', 'Scorecards indisponíveis na visualização de entrevista.', [
                'entrevista_id' => (int) $id,
                'error' => $e->getMessage(),
            ]);
        }
        try {
            $this->data['painel_avaliadores'] = (new RhEntrevistaAvaliadoresRepository())->listPainelByEntrevista((int) $id);
        } catch (\Throwable $e) {
            GenerateLog::generateLog('warning', 'Painel de avaliadores indisponível na visualização.', [
                'entrevista_id' => (int) $id,
                'error' => $e->getMessage(),
            ]);
            // Fallback sem JOIN de scorecard (migration parcial).
            try {
                $this->data['painel_avaliadores'] = (new RhEntrevistaAvaliadoresRepository())->listByEntrevista((int) $id);
            } catch (\Throwable $ignored) {
                $this->data['painel_avaliadores'] = [];
            }
        }
        try {
            $this->data['reagendamentos'] = (new RhEntrevistaReagendamentosRepository())->listByEntrevista((int) $id);
        } catch (\Throwable $e) {
            GenerateLog::generateLog('warning', 'Histórico de reagendamentos indisponível.', [
                'entrevista_id' => (int) $id,
                'error' => $e->getMessage(),
            ]);
        }
        try {
            $this->data['comunicacoes'] = (new RhEntrevistaComunicacoesRepository())->listByEntrevista((int) $id);
        } catch (\Throwable $e) {
            GenerateLog::generateLog('warning', 'Histórico de comunicações indisponível.', [
                'entrevista_id' => (int) $id,
                'error' => $e->getMessage(),
            ]);
        }

        $idInt = (int) $id;
        $returnUrl = $_ENV['URL_ADM'] . 'rh-entrevistas-view/' . $idInt;
        $this->data['log_resumo'] = LogResumoService::getResumo('rh_entrevistas', $idInt, $returnUrl);
        $this->data['csrf_resend_comunicacao'] = CSRFHelper::generateCSRFToken(
            'form_resend_rh_entrevista_comunicacao'
        );
        $this->data['csrf_aceitar_avaliacao'] = CSRFHelper::generateCSRFToken(
            'form_aceitar_avaliacao_entrevista'
        );
        $this->data['csrf_recusar_avaliacao'] = CSRFHelper::generateCSRFToken(
            'form_recusar_avaliacao_entrevista'
        );
        $this->data['csrf_reenviar_convite_avaliador'] = CSRFHelper::generateCSRFToken(
            'form_reenviar_convite_avaliador'
        );
        $this->data['current_user_id'] = (int) ($_SESSION['user_id'] ?? 0);

        $pageElements = [
            'title_head' => 'Visualizar Entrevista',
            'menu'       => 'rh-entrevistas',
            'buttonPermission' => [
                'RhEntrevistas',
                'RhEntrevistasEdit',
                'RhEntrevistasDelete',
                'RhEntrevistasResendComunicacao',
                'RhEntrevistasAceitarAvaliacao',
                'RhEntrevistasRecusarAvaliacao',
                'RhEntrevistasReenviarConviteAvaliador',
                'RhEntrevistasViewAll',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rh/entrevistas/view', $this->data);
        $loadView->loadView();
    }
}
