<?php

declare(strict_types=1);

namespace App\adms\Controllers\pdi;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\PerformanceCyclesRepository;
use App\adms\Models\Repository\PdiPlansRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\PdiPlanService;
use App\adms\Views\Services\LoadViewService;

class UpdatePdiPlan
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

        $this->data['plan'] = $plan;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update($planId);
            $this->data['plan'] = $repository->getById($planId) ?? $plan;
        }

        $this->loadFormData();

        $pageElements = [
            'title_head' => 'Editar PDI',
            'menu' => 'list-pdi-plans',
            'buttonPermission' => ['ListPdiPlans', 'ViewPdiPlan'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/pdi/update', $this->data);
        $loadView->loadView();
    }

    private function loadFormData(): void
    {
        $usersRepo = new UsersRepository();
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();

        if ($isSuperAdmin) {
            $this->data['employees'] = $usersRepo->getAllUsers(1, 1000);
        } else {
            $userId = $_SESSION['user_id'] ?? 0;
            $allUsers = $usersRepo->getAllUsers(1, 1000, []);
            $this->data['employees'] = array_filter($allUsers, static function ($user) use ($userId) {
                return isset($user['immediate_supervisor']) && (int) $user['immediate_supervisor'] === (int) $userId;
            });
        }

        $this->data['cycles'] = (new PerformanceCyclesRepository())->listLinkable();
        $currentCycleId = (int) ($this->data['plan']['performance_cycle_id'] ?? 0);
        if ($currentCycleId > 0) {
            $current = (new PerformanceCyclesRepository())->getById($currentCycleId);
            if ($current) {
                $ids = array_column($this->data['cycles'], 'id');
                if (!in_array($currentCycleId, array_map('intval', $ids), true)) {
                    $this->data['cycles'][] = $current;
                }
            }
        }
    }

    private function update(int $id): void
    {
        if (!CSRFHelper::validateCSRFToken('form_update_pdi_plan', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';

            return;
        }

        $result = (new PdiPlanService())->update($id, $_POST);
        if (!$result['ok']) {
            $_SESSION['error'] = $result['error'] ?? 'Erro ao atualizar PDI.';

            return;
        }

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">PDI atualizado!</div>';
        GenerateLog::generateLog('info', 'PDI atualizado.', ['id' => $id]);
        header('Location: ' . $_ENV['URL_ADM'] . 'view-pdi-plan/' . $id);
        exit;
    }
}
