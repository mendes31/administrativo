<?php

namespace App\adms\Controllers\trainings;

use App\adms\Models\Repository\TrainingUsersRepository;
use App\adms\Models\Repository\TrainingApplicationsRepository;
use App\adms\Models\Repository\TrainingsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;
use App\adms\Helpers\ScreenResolutionHelper;

class TrainingDashboard
{
    private TrainingUsersRepository $trainingUsersRepo;
    private TrainingApplicationsRepository $applicationsRepo;
    private TrainingsRepository $trainingsRepo;
    private UsersRepository $usersRepo;
    private DepartmentsRepository $departmentsRepo;
    private PositionsRepository $positionsRepo;

    public function __construct()
    {
        $this->trainingUsersRepo = new TrainingUsersRepository();
        $this->applicationsRepo = new TrainingApplicationsRepository();
        $this->trainingsRepo = new TrainingsRepository();
        $this->usersRepo = new UsersRepository();
        $this->departmentsRepo = new DepartmentsRepository();
        $this->positionsRepo = new PositionsRepository();
    }

    public function index(): void
    {
        // Obter configurações responsivas
        $resolution = ScreenResolutionHelper::getScreenResolution();
        $responsiveClasses = ScreenResolutionHelper::getResponsiveClasses($resolution['category']);
        $paginationSettings = ScreenResolutionHelper::getPaginationSettings($resolution['category']);
        
        $data = [
            'title_head' => 'Dashboard de Treinamentos',
            'menu' => 'training-dashboard',
            'buttonPermission' => ['TrainingDashboard'],
        ];
        $pageLayout = new PageLayoutService();
        $data = array_merge($data, $pageLayout->configurePageElements($data));
        $data['dashboard'] = $this->getDashboardData();
        
        // Adicionar configurações responsivas
        $data['responsiveClasses'] = $responsiveClasses;
        $data['paginationSettings'] = $paginationSettings;
        $loadView = new LoadViewService('adms/Views/trainings/trainingDashboard', $data);
        $loadView->loadView();
    }

    private function getDashboardData(): array
    {
        $repo = new TrainingUsersRepository();
        // Dados agregados para gráficos
        return [
            'status_counts' => $repo->getStatusCounts(),
            'monthly_realizations' => $repo->getMonthlyRealizations(),
            'top_pending_users' => $repo->getTopPendingUsers(),
            'top_critical_trainings' => $repo->getTopCriticalTrainings(),
        ];
    }

    /**
     * Sincroniza vínculos de treinamentos para um usuário
     */
    public function syncUserLinks(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'training-dashboard');
            exit;
        }

        $userId = (int) ($_POST['user_id'] ?? 0);
        $positionId = (int) ($_POST['position_id'] ?? 0);

        if (!$userId || !$positionId) {
            $_SESSION['msg'] = 'Dados inválidos!';
            header('Location: ' . $_ENV['URL_ADM'] . 'training-dashboard');
            exit;
        }

        $success = $this->trainingUsersRepo->syncUserTrainingLinks($userId, $positionId);

        if ($success) {
            $_SESSION['msg'] = 'Vínculos sincronizados com sucesso!';
        } else {
            $_SESSION['msg'] = 'Erro ao sincronizar vínculos!';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'training-dashboard');
        exit;
    }

    /**
     * Marca um treinamento como concluído
     */
    public function markCompleted(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'training-dashboard');
            exit;
        }

        $userId = (int) ($_POST['user_id'] ?? 0);
        $trainingId = (int) ($_POST['training_id'] ?? 0);
        $createNewCycle = (bool) ($_POST['create_new_cycle'] ?? true);

        if (!$userId || !$trainingId) {
            $_SESSION['msg'] = 'Dados inválidos!';
            header('Location: ' . $_ENV['URL_ADM'] . 'training-dashboard');
            exit;
        }

        $success = $this->trainingUsersRepo->markAsCompleted($userId, $trainingId, $createNewCycle);

        if ($success) {
            $_SESSION['msg'] = 'Treinamento marcado como concluído!';
        } else {
            $_SESSION['msg'] = 'Erro ao marcar como concluído!';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'training-dashboard');
        exit;
    }
} 