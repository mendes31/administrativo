<?php

declare(strict_types=1);

// Reenvio FTP experiencia/movimentacoes (controllers ausentes no servidor).

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RhConversoesAdmissaoRepository;
use App\adms\Models\Repository\RhPeriodosExperienciaRepository;
use App\adms\Models\Services\RhExperienciaService;
use App\adms\Models\Services\RhPermissionService;
use App\adms\Views\Services\LoadViewService;
use Exception;

final class RhExperienciaView
{
    private array $data = [];

    public function index(int|string $id): void
    {
        $experienciaId = (int) $id;
        if ($experienciaId <= 0) {
            $_SESSION['msg'] = 'Período de experiência não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-candidatos');
            exit;
        }

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $this->handleAction($experienciaId);
            return;
        }

        $repo = new RhPeriodosExperienciaRepository();
        $periodo = $repo->getById($experienciaId);
        if ($periodo === null) {
            $_SESSION['msg'] = 'Período de experiência não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-candidatos');
            exit;
        }

        $canManage = $this->canManage($periodo);

        $this->data = [
            'title_head' => 'Experiência #' . $experienciaId,
            'menu' => 'rh-experiencia-view',
            'buttonPermission' => ['RhExperienciaView', 'RhOnboardingView', 'RhVagas'],
            'csrf_token' => CSRFHelper::generateCSRFToken('form_rh_experiencia'),
            'periodo' => $periodo,
            'can_manage' => $canManage,
        ];

        $pageLayout = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayout->configurePageElements($this->data));
        (new LoadViewService('adms/Views/rh/experiencia/view', $this->data))->loadView();
    }

    private function handleAction(int $experienciaId): void
    {
        if (!CSRFHelper::validateCSRFToken('form_rh_experiencia', (string) ($_POST['csrf_token'] ?? ''))) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-experiencia-view/' . $experienciaId);
            exit;
        }

        $repo = new RhPeriodosExperienciaRepository();
        $periodo = $repo->getById($experienciaId);
        if ($periodo === null || !$this->canManage($periodo)) {
            $_SESSION['msg'] = 'Sem permissão ou período inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-experiencia-view/' . $experienciaId);
            exit;
        }

        $action = (string) ($_POST['action'] ?? '');
        $obs = trim((string) ($_POST['observacoes'] ?? ''));
        $service = new RhExperienciaService();
        $actorId = (int) ($_SESSION['user_id'] ?? 0);

        try {
            match ($action) {
                'aprovar' => $service->aprovar($experienciaId, $obs !== '' ? $obs : null, $actorId),
                'reprovar' => $service->reprovar($experienciaId, $obs, $actorId),
                'prorrogar' => $service->prorrogar(
                    $experienciaId,
                    $obs !== '' ? $obs : null,
                    $actorId,
                    !empty($_POST['dias_prorrogacao']) ? (int) $_POST['dias_prorrogacao'] : null
                ),
                'cancelar' => $service->cancelar($experienciaId),
                default => throw new Exception('Ação inválida.'),
            };
            $_SESSION['msg'] = 'Período de experiência atualizado.';
            $_SESSION['msg_type'] = 'success';
        } catch (Exception $e) {
            $_SESSION['msg'] = $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-experiencia-view/' . $experienciaId);
        exit;
    }

    /**
     * @param array<string, mixed> $periodo
     */
    private function canManage(array $periodo): bool
    {
        if (RhPermissionService::isSuperAdmin()) {
            return true;
        }
        $conversao = (new RhConversoesAdmissaoRepository())
            ->getById((int) ($periodo['rh_conversao_id'] ?? 0));
        if ($conversao === null) {
            return false;
        }

        return RhPermissionService::canManagePipelineByVagaId((int) $conversao['rh_vaga_id']);
    }
}
