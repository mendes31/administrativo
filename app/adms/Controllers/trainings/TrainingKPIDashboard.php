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

class TrainingKpiDashboard
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
        $data = [
            'title_head' => 'Dashboard de KPIs - Treinamentos',
            'menu' => 'training-kpi-dashboard',
            'buttonPermission' => ['TrainingKpiDashboard'],
        ];

        $pageLayout = new PageLayoutService();
        $data = array_merge($data, $pageLayout->configurePageElements($data));
        
        // Carregar dados para os KPIs e gráficos
        $data['dashboard'] = $this->getDashboardData();
        
        $loadView = new LoadViewService('adms/Views/trainings/kpiDashboard', $data);
        $loadView->loadView();
    }

    private function getDashboardData(): array
    {
        return [
            // Estatísticas gerais
            'summary' => $this->trainingUsersRepo->getSummaryAll(),
            
            // Dados para gráficos
            'statusCounts' => $this->trainingUsersRepo->getStatusCounts(),
            'monthlyRealizations' => $this->trainingUsersRepo->getMonthlyRealizations(),
            'topPendingUsers' => $this->trainingUsersRepo->getTopPendingUsers(),
            'topCriticalTrainings' => $this->trainingUsersRepo->getTopCriticalTrainings(),
            
            // Treinamentos próximos do vencimento
            'expiring' => $this->trainingUsersRepo->getExpiringTrainings(30),
            
            // Estatísticas por departamento
            'departmentStats' => $this->getDepartmentStatistics(),
            
            // Estatísticas por cargo
            'positionStats' => $this->getPositionStatistics(),
            
            // Últimas aplicações
            'recentApplications' => $this->applicationsRepo->getRecentApplications(10),
            
            // Treinamentos mais aplicados
            'mostAppliedTrainings' => $this->getMostAppliedTrainings(),
        ];
    }

    private function getDepartmentStatistics(): array
    {
        $sql = "SELECT 
                    d.id as department_id,
                    d.name as department_name,
                    COUNT(tu.id) as total_vinculos,
                    SUM(CASE WHEN tu.status = 'concluido' THEN 1 ELSE 0 END) as concluidos,
                    SUM(CASE WHEN tu.status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
                    SUM(CASE WHEN tu.status = 'vencido' THEN 1 ELSE 0 END) as vencidos
                FROM adms_training_users tu
                INNER JOIN adms_users u ON u.id = tu.adms_user_id
                INNER JOIN adms_departments d ON u.user_department_id = d.id
                GROUP BY d.id, d.name
                ORDER BY total_vinculos DESC";
        
        $stmt = $this->trainingUsersRepo->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getPositionStatistics(): array
    {
        $sql = "SELECT 
                    p.name as position_name,
                    COUNT(tu.id) as total_vinculos,
                    SUM(CASE WHEN tu.status = 'concluido' THEN 1 ELSE 0 END) as concluidos,
                    SUM(CASE WHEN tu.status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
                    SUM(CASE WHEN tu.status = 'vencido' THEN 1 ELSE 0 END) as vencidos
                FROM adms_training_users tu
                INNER JOIN adms_users u ON u.id = tu.adms_user_id
                INNER JOIN adms_positions p ON u.user_position_id = p.id
                GROUP BY p.id, p.name
                ORDER BY total_vinculos DESC
                LIMIT 10";
        
        $stmt = $this->trainingUsersRepo->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getMostAppliedTrainings(): array
    {
        $sql = "SELECT 
                    t.nome as training_name,
                    COUNT(ta.id) as total_aplicacoes,
                    COUNT(CASE WHEN ta.data_realizacao IS NOT NULL THEN 1 END) as realizados,
                    COUNT(CASE WHEN ta.data_agendada IS NOT NULL AND ta.data_realizacao IS NULL THEN 1 END) as agendados
                FROM adms_trainings t
                LEFT JOIN adms_training_applications ta ON t.id = ta.adms_training_id
                GROUP BY t.id, t.nome
                ORDER BY total_aplicacoes DESC
                LIMIT 10";
        
        $stmt = $this->trainingUsersRepo->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
} 