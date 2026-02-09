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
        try {
            $data = [
                'title_head' => 'Dashboard de KPIs - Treinamentos',
                'menu' => 'training-kpi-dashboard',
                'buttonPermission' => ['TrainingKpiDashboard'],
            ];

            $pageLayout = new PageLayoutService();
            $data = array_merge($data, $pageLayout->configurePageElements($data));
            
            // Carregar dados para os KPIs e gráficos.
            // Se alguma parte nova (Top 5, estatísticas por depto/cargo, etc.) falhar,
            // voltamos para um estado "seguro" com os dados principais (summary, status, mensal).
            try {
                $data['dashboard'] = $this->getDashboardData();
            } catch (\Exception $e) {
                error_log("Erro ao carregar dados completos do dashboard: " . $e->getMessage());
                error_log("Trace: " . $e->getTraceAsString());

                // Fallback: manter o comportamento anterior, só com as informações principais
                $data['dashboard'] = [
                    'summary'             => $this->trainingUsersRepo->getSummaryAll(),
                    'statusCounts'        => $this->trainingUsersRepo->getStatusCounts(),
                    'monthlyRealizations' => $this->trainingUsersRepo->getMonthlyRealizations(),
                    'topPendingUsers'     => [],
                    'topCriticalTrainings'=> [],
                    'expiring'            => [],
                    'departmentStats'     => [],
                    'positionStats'       => [],
                    'recentApplications'  => [],
                    'mostAppliedTrainings'=> [],
                ];

                if (ini_get('display_errors')) {
                    $data['error_message'] = "Erro ao carregar dados completos do dashboard: " . $e->getMessage();
                }
            }
            
            $loadView = new LoadViewService('adms/Views/trainings/kpiDashboard', $data);
            $loadView->loadView();
        } catch (\Exception $e) {
            // Log do erro crítico
            error_log("Erro crítico no TrainingKpiDashboard: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            
            // Exibir erro se display_errors estiver ativo
            if (ini_get('display_errors')) {
                echo "<h1>Erro ao carregar Dashboard de KPIs</h1>";
                echo "<p><strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            } else {
                // Redirecionar para dashboard em caso de erro em produção
                header("Location: " . $_ENV['URL_ADM'] . "dashboard");
                exit;
            }
        }
    }

    private function getDashboardData(): array
    {
        // Sempre garantir que os dados básicos sejam carregados primeiro
        $data = [
            // Estatísticas gerais (sempre carregar)
            'summary' => $this->trainingUsersRepo->getSummaryAll(),
            
            // Dados para gráficos (sempre carregar)
            'statusCounts' => $this->trainingUsersRepo->getStatusCounts(),
            'monthlyRealizations' => $this->trainingUsersRepo->getMonthlyRealizations(),
        ];
        
        // Carregar dados secundários com tratamento de erro individual
        try {
            $data['topPendingUsers'] = $this->trainingUsersRepo->getTopPendingUsers();
        } catch (\Exception $e) {
            error_log("Erro ao carregar topPendingUsers: " . $e->getMessage());
            $data['topPendingUsers'] = [];
        }
        
        try {
            $data['topCriticalTrainings'] = $this->trainingUsersRepo->getTopCriticalTrainings();
        } catch (\Exception $e) {
            error_log("Erro ao carregar topCriticalTrainings: " . $e->getMessage());
            $data['topCriticalTrainings'] = [];
        }
        
        try {
            $data['expiring'] = $this->trainingUsersRepo->getExpiringTrainings(30);
        } catch (\Exception $e) {
            error_log("Erro ao carregar expiring: " . $e->getMessage());
            $data['expiring'] = [];
        }
        
        try {
            $data['departmentStats'] = $this->getDepartmentStatistics();
        } catch (\Exception $e) {
            error_log("Erro ao carregar departmentStats: " . $e->getMessage());
            $data['departmentStats'] = [];
        }
        
        try {
            $data['positionStats'] = $this->getPositionStatistics();
        } catch (\Exception $e) {
            error_log("Erro ao carregar positionStats: " . $e->getMessage());
            $data['positionStats'] = [];
        }
        
        try {
            $data['recentApplications'] = $this->applicationsRepo->getRecentApplications(10);
        } catch (\Exception $e) {
            error_log("Erro ao carregar recentApplications: " . $e->getMessage());
            $data['recentApplications'] = [];
        }
        
        try {
            $data['mostAppliedTrainings'] = $this->getMostAppliedTrainings();
        } catch (\Exception $e) {
            error_log("Erro ao carregar mostAppliedTrainings: " . $e->getMessage());
            $data['mostAppliedTrainings'] = [];
        }
        
        return $data;
    }

    private function getDepartmentStatistics(): array
    {
        $sql = "SELECT 
                    d.id as department_id,
                    d.name as department_name,
                    COUNT(tu.id) as total_vinculos,
                    SUM(CASE WHEN tu.status = 'concluido' THEN 1 ELSE 0 END) as concluidos,
                    SUM(CASE WHEN tu.status IN ('em_dia','dentro_do_prazo') THEN 1 ELSE 0 END) as em_dia,
                    SUM(CASE WHEN tu.status = 'proximo_vencimento' THEN 1 ELSE 0 END) as pendentes,
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
                    SUM(CASE WHEN tu.status IN ('em_dia','dentro_do_prazo') THEN 1 ELSE 0 END) as em_dia,
                    SUM(CASE WHEN tu.status = 'proximo_vencimento' THEN 1 ELSE 0 END) as pendentes,
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