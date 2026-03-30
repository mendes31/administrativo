<?php

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\PerformanceGoalsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criar meta de desempenho
 */
class CreatePerformanceGoal
{
    private array|string|null $data = null;

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
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

        $pageElements = [
            'title_head' => 'Criar Meta de Desempenho',
            'menu' => 'create-performance-goal',
            'buttonPermission' => [
                'ListPerformanceGoals',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/performance/create_goal', $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        // Validar CSRF
        if (!CSRFHelper::validateCSRFToken('form_create_performance_goal', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-performance-goal');
            exit;
        }

        $data = [
            'employee_id' => (int)($_POST['employee_id'] ?? 0),
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
        if (empty($data['employee_id'])) {
            $_SESSION['error'] = 'Colaborador é obrigatório.';
            return;
        }

        if (empty($data['goal_title'])) {
            $_SESSION['error'] = 'Título da meta é obrigatório.';
            return;
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
        
        if ($repository->create($data)) {
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Meta criada com sucesso!</div>';
            GenerateLog::generateLog("info", "Meta de desempenho criada.", $data);
            header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-goals');
            exit;
        } else {
            $_SESSION['error'] = 'Erro ao criar meta. Tente novamente.';
        }
    }
}

