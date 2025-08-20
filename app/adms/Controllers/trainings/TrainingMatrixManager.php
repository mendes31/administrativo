<?php

namespace App\adms\Controllers\trainings;

use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\TrainingPositionsRepository;
use App\adms\Models\Repository\TrainingUsersRepository;
use App\adms\Models\Repository\TrainingsRepository;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;
use App\adms\Helpers\GenerateLog;

class TrainingMatrixManager
{
    private array|string|null $data = null;

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processMatrixUpdate();
        }

        $this->loadMatrixData();
        $this->loadView();
    }

    private function processMatrixUpdate(): void
    {
        $action = $_POST['action'] ?? '';
        
        try {
            $matrixService = new TrainingMatrixService();
            
            switch ($action) {
                case 'update_all':
                    $matrixService->updateMatrixForAllUsers();
                    $_SESSION['success'] = 'Matriz de treinamentos atualizada para todos os usuários!';
                    break;
                    
                case 'update_user':
                    $userId = (int)($_POST['user_id'] ?? 0);
                    if ($userId > 0) {
                        $matrixService->updateMatrixForUser($userId);
                        $_SESSION['success'] = 'Matriz de treinamentos atualizada para o usuário!';
                    }
                    break;
                    
                case 'update_position':
                    $positionId = (int)($_POST['position_id'] ?? 0);
                    if ($positionId > 0) {
                        $this->updateMatrixForPosition($positionId);
                        $_SESSION['success'] = 'Matriz de treinamentos atualizada para o cargo!';
                    }
                    break;
                    
                default:
                    $_SESSION['error'] = 'Ação inválida!';
            }
            
            GenerateLog::generateLog("info", "Matriz de treinamentos atualizada", ['action' => $action]);
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Erro ao atualizar matriz: ' . $e->getMessage();
            GenerateLog::generateLog("error", "Erro ao atualizar matriz de treinamentos", [
                'action' => $action,
                'error' => $e->getMessage()
            ]);
        }
        
        header('Location: ' . $_ENV['URL_ADM'] . 'training-matrix-manager');
        exit;
    }

    private function updateMatrixForPosition(int $positionId): void
    {
        $usersRepo = new UsersRepository();
        $matrixService = new TrainingMatrixService();
        
        // Buscar todos os usuários com este cargo
        $users = $usersRepo->getUsersByPosition($positionId);
        
        foreach ($users as $user) {
            $matrixService->updateMatrixForUser($user['id']);
        }
    }

    private function loadMatrixData(): void
    {
        $usersRepo = new UsersRepository();
        $positionsRepo = new PositionsRepository();
        $trainingsRepo = new TrainingsRepository();
        $trainingUsersRepo = new TrainingUsersRepository();

        // Estatísticas gerais
        $this->data['stats'] = [
            'total_users' => $usersRepo->getTotalUsers(),
            'total_positions' => $positionsRepo->getAmountPositions(),
            'total_trainings' => $trainingsRepo->getTotalTrainings(),
            'total_matrix_entries' => $trainingUsersRepo->getTotalMatrixEntries()
        ];

        // Lista de usuários para atualização individual
        $this->data['users'] = $usersRepo->getAllUsersSelect();
        
        // Lista de cargos para atualização por cargo
        $this->data['positions'] = $positionsRepo->getAllPositionsSelect();
        
        // Lista de treinamentos
        $this->data['trainings'] = $trainingsRepo->getAllTrainingsSelect();

        // Estatísticas por status
        $this->data['status_stats'] = $trainingUsersRepo->getStatusStatistics();
    }

    private function loadView(): void
    {
        $pageElements = [
            'title_head' => 'Gerenciar Matriz de Treinamentos',
            'menu' => 'list-trainings',
            'buttonPermission' => ['TrainingMatrixManager'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/trainings/matrixManager', $this->data);
        $loadView->loadView();
    }
} 