<?php

declare(strict_types=1);

namespace App\adms\Controllers\pdi;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\CompetenciesRepository;
use App\adms\Models\Repository\PdiActionsRepository;
use App\adms\Models\Repository\PdiCompetenciesRepository;
use App\adms\Models\Repository\PdiPlansRepository;
use App\adms\Models\Repository\TrainingsRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Models\Services\PdiPlanService;
use App\adms\Views\Services\LoadViewService;

class ViewPdiPlan
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $planId = (int) $id;
        if ($planId <= 0) {
            $_SESSION['error'] = 'PDI não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-pdi-plans');
            exit;
        }

        $repository = new PdiPlansRepository();
        $plan = $repository->getById($planId);
        if (!$plan) {
            $_SESSION['error'] = 'PDI não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-pdi-plans');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost($planId);
            $plan = $repository->getById($planId) ?? $plan;
        }

        $this->data['plan'] = $plan;
        $this->data['actions'] = (new PdiActionsRepository())->getByPlanId($planId);
        $this->data['competencies'] = (new PdiCompetenciesRepository())->getByPlanId($planId);
        $this->data['trainings'] = (new TrainingsRepository())->getAllTrainingsSelect();
        $this->data['catalog_competencies'] = (new CompetenciesRepository())->getAll();

        $returnUrl = $_ENV['URL_ADM'] . 'view-pdi-plan/' . $planId;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_pdi_plans', $planId, $returnUrl);

        $pageElements = [
            'title_head' => 'Visualizar PDI',
            'menu' => 'list-pdi-plans',
            'buttonPermission' => [
                'ListPdiPlans',
                'UpdatePdiPlan',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/pdi/view', $this->data);
        $loadView->loadView();
    }

    private function handlePost(int $planId): void
    {
        $action = (string) ($_POST['form_action'] ?? '');
        $csrfMap = [
            'add_action' => 'form_pdi_add_action',
            'update_action' => 'form_pdi_update_action',
            'add_competency' => 'form_pdi_add_competency',
            'remove_competency' => 'form_pdi_remove_competency',
        ];

        if (!isset($csrfMap[$action])) {
            $_SESSION['error'] = 'Ação inválida.';

            return;
        }

        if (!CSRFHelper::validateCSRFToken($csrfMap[$action], $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';

            return;
        }

        $service = new PdiPlanService();
        $result = match ($action) {
            'add_action' => $service->addAction($planId, $_POST),
            'update_action' => $service->updateAction((int) ($_POST['action_id'] ?? 0), $planId, $_POST),
            'add_competency' => $service->addCompetency($planId, $_POST),
            'remove_competency' => $service->removeCompetency((int) ($_POST['competency_row_id'] ?? 0), $planId),
            default => ['ok' => false, 'error' => 'Ação inválida.'],
        };

        if (!$result['ok']) {
            $_SESSION['error'] = $result['error'] ?? 'Erro ao processar.';

            return;
        }

        $messages = [
            'add_action' => 'Ação adicionada.',
            'update_action' => 'Ação atualizada.',
            'add_competency' => 'Competência vinculada.',
            'remove_competency' => 'Competência removida.',
        ];
        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">'
            . ($messages[$action] ?? 'OK') . '</div>';
        GenerateLog::generateLog('info', 'PDI view action: ' . $action, ['plan_id' => $planId]);
        header('Location: ' . $_ENV['URL_ADM'] . 'view-pdi-plan/' . $planId);
        exit;
    }
}
