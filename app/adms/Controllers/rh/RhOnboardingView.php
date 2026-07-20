<?php

declare(strict_types=1);

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RhOnboardingRepository;
use App\adms\Models\Services\RhOnboardingService;
use App\adms\Models\Services\RhPermissionService;
use App\adms\Views\Services\LoadViewService;
use Exception;

final class RhOnboardingView
{
    private array $data = [];

    public function index(int|string $id): void
    {
        $planoId = (int) $id;
        if ($planoId <= 0) {
            $_SESSION['msg'] = 'Plano de onboarding não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-candidatos');
            exit;
        }

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $this->handleAction($planoId);
            return;
        }

        $repo = new RhOnboardingRepository();
        $plano = $repo->getPlanoById($planoId);
        if ($plano === null) {
            $_SESSION['msg'] = 'Plano de onboarding não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-candidatos');
            exit;
        }

        $canManage = RhPermissionService::isSuperAdmin();
        $conversao = (new \App\adms\Models\Repository\RhConversoesAdmissaoRepository())
            ->getById((int) ($plano['rh_conversao_id'] ?? 0));
        if ($conversao !== null) {
            $canManage = $canManage
                || RhPermissionService::canManagePipelineByVagaId((int) $conversao['rh_vaga_id']);
        }

        $this->data = [
            'title_head' => 'Onboarding #' . $planoId,
            'menu' => 'rh-pessoas',
            'buttonPermission' => ['RhOnboardingView', 'RhOfertasView', 'RhVagas'],
            'csrf_token' => CSRFHelper::generateCSRFToken('form_rh_onboarding'),
            'plano' => $plano,
            'itens' => $repo->listItens($planoId),
            'can_manage' => $canManage,
        ];

        $pageLayout = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayout->configurePageElements($this->data));
        (new LoadViewService('adms/Views/rh/onboarding/view', $this->data))->loadView();
    }

    private function handleAction(int $planoId): void
    {
        if (!CSRFHelper::validateCSRFToken('form_rh_onboarding', (string) ($_POST['csrf_token'] ?? ''))) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-onboarding-view/' . $planoId);
            exit;
        }

        $repo = new RhOnboardingRepository();
        $plano = $repo->getPlanoById($planoId);
        if ($plano === null) {
            $_SESSION['msg'] = 'Plano de onboarding não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-candidatos');
            exit;
        }

        $canManage = RhPermissionService::isSuperAdmin();
        $conversao = (new \App\adms\Models\Repository\RhConversoesAdmissaoRepository())
            ->getById((int) ($plano['rh_conversao_id'] ?? 0));
        if ($conversao !== null) {
            $canManage = $canManage
                || RhPermissionService::canManagePipelineByVagaId((int) $conversao['rh_vaga_id']);
        }
        if (!$canManage) {
            $_SESSION['msg'] = 'Sem permissão para alterar este onboarding.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-onboarding-view/' . $planoId);
            exit;
        }

        $action = (string) ($_POST['action'] ?? '');
        $service = new RhOnboardingService();
        $actorId = (int) ($_SESSION['user_id'] ?? 0);

        try {
            if ($action === 'item') {
                $service->atualizarItem(
                    $planoId,
                    (int) ($_POST['item_id'] ?? 0),
                    (string) ($_POST['item_status'] ?? ''),
                    trim((string) ($_POST['observacoes'] ?? '')) ?: null,
                    $actorId
                );
            } elseif ($action === 'cancelar') {
                $service->cancelarPlano($planoId);
            } else {
                throw new Exception('Ação inválida.');
            }
            $_SESSION['msg'] = 'Onboarding atualizado.';
            $_SESSION['msg_type'] = 'success';
        } catch (Exception $e) {
            $_SESSION['msg'] = $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-onboarding-view/' . $planoId);
        exit;
    }
}
