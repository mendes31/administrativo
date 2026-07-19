<?php

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\PerformanceCyclesRepository;
use App\adms\Models\Repository\PerformanceGoalsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\PerformanceCycleService;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para atualizar meta de desempenho
 */
class UpdatePerformanceGoal
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        if (!(int)$id) {
            $_SESSION['error'] = 'Meta não encontrada.';
            header("Location: {$_ENV['URL_ADM']}list-performance-goals");
            return;
        }

        $repository = new PerformanceGoalsRepository();
        $this->data['goal'] = $repository->getById((int)$id);

        if (!$this->data['goal']) {
            $_SESSION['error'] = 'Meta não encontrada.';
            header("Location: {$_ENV['URL_ADM']}list-performance-goals");
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update((int)$id);
        }

        // Buscar usuários para o select
        $usersRepo = new UsersRepository();
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        
        if ($isSuperAdmin) {
            $this->data['employees'] = $usersRepo->getAllUsers(1, 1000);
        } else {
            $userId = $_SESSION['user_id'] ?? 0;
            $allUsers = $usersRepo->getAllUsers(1, 1000, []);
            $this->data['employees'] = array_filter($allUsers, function($user) use ($userId) {
                return isset($user['immediate_supervisor']) && $user['immediate_supervisor'] == $userId;
            });
        }

        $cyclesRepo = new PerformanceCyclesRepository();
        $this->data['cycles'] = $cyclesRepo->listLinkable();
        $currentCycleId = (int) ($this->data['goal']['performance_cycle_id'] ?? 0);
        if ($currentCycleId > 0) {
            $currentCycle = $cyclesRepo->getById($currentCycleId);
            if ($currentCycle) {
                $alreadyListed = false;
                foreach ($this->data['cycles'] as $c) {
                    if ((int) $c['id'] === $currentCycleId) {
                        $alreadyListed = true;
                        break;
                    }
                }
                if (!$alreadyListed) {
                    $this->data['cycles'][] = $currentCycle;
                }
            }
        }

        $pageElements = [
            'title_head' => 'Editar Meta de Desempenho',
            'menu' => 'update-performance-goal',
            'buttonPermission' => [
                'ListPerformanceGoals',
                'ViewPerformanceGoal',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/performance/update_goal', $this->data);
        $loadView->loadView();
    }

    private function update(int $id): void
    {
        // Validar CSRF
        if (!CSRFHelper::validateCSRFToken('form_update_performance_goal', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            return;
        }

        $data = [
            'employee_id' => (int)($_POST['employee_id'] ?? 0),
            'performance_cycle_id' => !empty($_POST['performance_cycle_id']) ? (int) $_POST['performance_cycle_id'] : null,
            'goal_title' => trim($_POST['goal_title'] ?? ''),
            'goal_description' => trim($_POST['goal_description'] ?? ''),
            'goal_type' => $_POST['goal_type'] ?? 'individual',
            'target_value' => !empty($_POST['target_value']) ? $_POST['target_value'] : null,
            'current_value' => !empty($_POST['current_value']) ? $_POST['current_value'] : 0,
            'unit' => trim($_POST['unit'] ?? ''),
            'deadline' => !empty($_POST['deadline']) ? $_POST['deadline'] : null,
            'weight' => !empty($_POST['weight']) ? $_POST['weight'] : 1.0,
            'status' => $_POST['status'] ?? 'pending',
            'progress_percentage' => (int)($_POST['progress_percentage'] ?? 0),
        ];

        // Validações
        if (empty($data['goal_title'])) {
            $_SESSION['error'] = 'Título da meta é obrigatório.';
            return;
        }

        $currentCycleId = !empty($this->data['goal']['performance_cycle_id'])
            ? (int) $this->data['goal']['performance_cycle_id']
            : null;
        $requestedCycleId = $data['performance_cycle_id'];
        if ($requestedCycleId !== $currentCycleId) {
            $cycleCheck = (new PerformanceCycleService())->assertGoalMayLink($requestedCycleId);
            if (!$cycleCheck['ok']) {
                $_SESSION['error'] = $cycleCheck['error'] ?? 'Ciclo inválido.';
                return;
            }
            $data['performance_cycle_id'] = $cycleCheck['cycle_id'];
        }

        // Calcular progresso se tiver valores
        if (!empty($data['target_value']) && !empty($data['current_value'])) {
            $data['progress_percentage'] = min(100, max(0, (int)(($data['current_value'] / $data['target_value']) * 100)));
        }

        // Se progresso = 100%, marcar como alcançada
        if ($data['progress_percentage'] >= 100 && $data['status'] !== 'achieved') {
            $data['status'] = 'achieved';
            $data['achieved_at'] = date('Y-m-d H:i:s');
        }

        $repository = new PerformanceGoalsRepository();
        
        if ($repository->update($id, $data)) {
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Meta atualizada com sucesso!</div>';
            GenerateLog::generateLog("info", "Meta de desempenho atualizada.", ['id' => $id, 'data' => $data]);
            header('Location: ' . $_ENV['URL_ADM'] . 'view-performance-goal/' . $id);
            exit;
        } else {
            $_SESSION['error'] = 'Erro ao atualizar meta. Tente novamente.';
        }
    }
}

