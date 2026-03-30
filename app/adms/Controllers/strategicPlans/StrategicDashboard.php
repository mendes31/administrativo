<?php

declare(strict_types=1);

namespace App\adms\Controllers\strategicPlans;

use App\adms\Models\Repository\StrategicPlansRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;

class StrategicDashboard
{
    private $repository;
    private array $data = [];

    public function __construct()
    {
        $this->repository = new StrategicPlansRepository();
    }

    public function index(): void
    {
        $this->viewDashboard();
    }

    private function viewDashboard(): void
    {
        // Verificar se o usuário tem acesso total (super admin ou departamento Diretoria)
        $userDepartmentId = null;
        if (!$this->hasFullAccess()) {
            // Se não tiver acesso total, filtrar por departamento do usuário
            $userDepartmentId = $_SESSION['user_department_id'] ?? null;
        }

        // Obter estatísticas gerais
        $stats = $this->getDashboardStats($userDepartmentId);
        
        // Obter estatísticas por período (atual vs anterior)
        $periodStats = $this->getPeriodComparison($userDepartmentId);
        
        // Obter planos por status
        $plansByStatus = $this->getPlansByStatus($userDepartmentId);
        
        // Obter planos por departamento
        $plansByDepartment = $this->getPlansByDepartment($userDepartmentId);
        
        // Obter planos em andamento
        $activePlans = $this->getActivePlans($userDepartmentId);
        
        // Obter planos próximos do vencimento
        $upcomingDeadlines = $this->getUpcomingDeadlines($userDepartmentId);
        
        // Obter indicadores de performance
        $performanceIndicators = $this->getPerformanceIndicators($userDepartmentId);
        
        // Obter análise de custos
        $costAnalysis = $this->getCostAnalysis($userDepartmentId);

        // Elementos de página
        $pageElements = [
            'title_head' => 'Dashboard - Planejamento Estratégico',
            'menu' => 'strategic-dashboard',
            'buttonPermission' => ['StrategicDashboard'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Adicionar dados específicos
        $this->data['stats'] = $stats;
        $this->data['periodStats'] = $periodStats;
        $this->data['plansByStatus'] = $plansByStatus;
        $this->data['plansByDepartment'] = $plansByDepartment;
        $this->data['activePlans'] = $activePlans;
        $this->data['upcomingDeadlines'] = $upcomingDeadlines;
        $this->data['performanceIndicators'] = $performanceIndicators;
        $this->data['costAnalysis'] = $costAnalysis;

        // Carrega a view usando o padrão do projeto
        $loadView = new LoadViewService("adms/Views/strategicPlans/dashboard", $this->data);
        $loadView->loadView();
    }

    /**
     * Verifica se o usuário tem acesso total (super admin ou departamento Diretoria)
     */
    private function hasFullAccess(): bool
    {
        // Super administrador (nível 1) tem acesso total
        if (\App\adms\Helpers\UserAccessHelper::hasFullSystemAccess()) {
            return true;
        }

        // Usuários do departamento "Diretoria" também têm acesso total
        if (isset($_SESSION['user_department']) && $_SESSION['user_department'] === 'Diretoria') {
            return true;
        }

        return false;
    }

    private function getDashboardStats(?int $userDepartmentId = null): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_plans,
                    SUM(CASE WHEN status = 'Não iniciado' THEN 1 ELSE 0 END) as not_started,
                    SUM(CASE WHEN status = 'Em andamento' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'Concluído' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'Atrasado' THEN 1 ELSE 0 END) as `delayed`,
                    AVG(COALESCE(progress_percentage, 0)) as avg_progress
                FROM adms_strategic_plans";
        
        // Adicionar filtro por departamento se especificado
        if ($userDepartmentId !== null) {
            $sql .= " WHERE department_id = :department_id";
        }
        
        $stmt = $this->repository->getConnection()->prepare($sql);
        
        if ($userDepartmentId !== null) {
            $stmt->bindValue(':department_id', $userDepartmentId, \PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return [
            'total_plans' => (int)$result['total_plans'],
            'not_started' => (int)$result['not_started'],
            'in_progress' => (int)$result['in_progress'],
            'completed' => (int)$result['completed'],
            'delayed' => (int)$result['delayed'],
            'avg_progress' => round((float)$result['avg_progress'], 1)
        ];
    }

    private function getPlansByStatus(?int $userDepartmentId = null): array
    {
        $sql = "SELECT 
                    status,
                    COUNT(*) as count,
                    AVG(COALESCE(progress_percentage, 0)) as avg_progress
                FROM adms_strategic_plans";
        
        // Adicionar filtro por departamento se especificado
        if ($userDepartmentId !== null) {
            $sql .= " WHERE department_id = :department_id";
        }
        
        $sql .= " GROUP BY status 
                ORDER BY 
                    CASE status 
                        WHEN 'Não iniciado' THEN 1
                        WHEN 'Em andamento' THEN 2
                        WHEN 'Concluído' THEN 3
                        WHEN 'Atrasado' THEN 4
                    END";
        
        $stmt = $this->repository->getConnection()->prepare($sql);
        
        if ($userDepartmentId !== null) {
            $stmt->bindValue(':department_id', $userDepartmentId, \PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getPlansByDepartment(?int $userDepartmentId = null): array
    {
        $sql = "SELECT 
                    d.name as department_name,
                    COUNT(sp.id) as plan_count,
                    AVG(sp.progress_percentage) as avg_progress
                FROM adms_strategic_plans sp
                LEFT JOIN adms_departments d ON sp.department_id = d.id";
        
        // Adicionar filtro por departamento se especificado
        if ($userDepartmentId !== null) {
            $sql .= " WHERE sp.department_id = :department_id";
        }
        
        $sql .= " GROUP BY d.id, d.name
                ORDER BY plan_count DESC
                LIMIT 10";
        
        $stmt = $this->repository->getConnection()->prepare($sql);
        
        if ($userDepartmentId !== null) {
            $stmt->bindValue(':department_id', $userDepartmentId, \PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getActivePlans(?int $userDepartmentId = null): array
    {
        $sql = "SELECT 
                    sp.id,
                    sp.title,
                    sp.status,
                    COALESCE(sp.progress_percentage, 0) as progress_percentage,
                    sp.start_date,
                    sp.end_date,
                    d.name as department_name,
                    u.name as responsible_name
                FROM adms_strategic_plans sp
                LEFT JOIN adms_departments d ON sp.department_id = d.id
                LEFT JOIN adms_users u ON sp.responsible_id = u.id
                WHERE sp.status IN ('Em andamento', 'Não iniciado')";
        
        // Adicionar filtro por departamento se especificado
        if ($userDepartmentId !== null) {
            $sql .= " AND sp.department_id = :department_id";
        }
        
        $sql .= " ORDER BY sp.updated_at DESC
                LIMIT 10";
        
        $stmt = $this->repository->getConnection()->prepare($sql);
        
        if ($userDepartmentId !== null) {
            $stmt->bindValue(':department_id', $userDepartmentId, \PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getUpcomingDeadlines(?int $userDepartmentId = null): array
    {
        $sql = "SELECT 
                    sp.id,
                    sp.title,
                    sp.end_date,
                    sp.status,
                    COALESCE(sp.progress_percentage, 0) as progress_percentage,
                    d.name as department_name,
                    u.name as responsible_name,
                    DATEDIFF(sp.end_date, CURDATE()) as days_remaining
                FROM adms_strategic_plans sp
                LEFT JOIN adms_departments d ON sp.department_id = d.id
                LEFT JOIN adms_users u ON sp.responsible_id = u.id
                WHERE sp.status IN ('Em andamento', 'Não iniciado')
                AND sp.end_date IS NOT NULL
                AND sp.end_date >= CURDATE()";
        
        // Adicionar filtro por departamento se especificado
        if ($userDepartmentId !== null) {
            $sql .= " AND sp.department_id = :department_id";
        }
        
        $sql .= " ORDER BY sp.end_date ASC
                LIMIT 10";
        
        $stmt = $this->repository->getConnection()->prepare($sql);
        
        if ($userDepartmentId !== null) {
            $stmt->bindValue(':department_id', $userDepartmentId, \PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getPeriodComparison(?int $userDepartmentId = null): array
    {
        // Período atual (últimos 30 dias)
        $currentSql = "SELECT 
                    COUNT(*) as total_plans,
                    SUM(CASE WHEN status = 'Concluído' THEN 1 ELSE 0 END) as completed,
                    AVG(COALESCE(progress_percentage, 0)) as avg_progress,
                    SUM(CASE WHEN how_much IS NOT NULL THEN CAST(how_much AS DECIMAL(15,2)) ELSE 0 END) as total_cost
                FROM adms_strategic_plans 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        // Período anterior (30 dias antes dos últimos 30 dias)
        $previousSql = "SELECT 
                    COUNT(*) as total_plans,
                    SUM(CASE WHEN status = 'Concluído' THEN 1 ELSE 0 END) as completed,
                    AVG(COALESCE(progress_percentage, 0)) as avg_progress,
                    SUM(CASE WHEN how_much IS NOT NULL THEN CAST(how_much AS DECIMAL(15,2)) ELSE 0 END) as total_cost
                FROM adms_strategic_plans 
                WHERE created_at BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) AND DATE_SUB(NOW(), INTERVAL 30 DAY)";

        // Adicionar filtro por departamento se especificado
        if ($userDepartmentId !== null) {
            $currentSql .= " AND department_id = :department_id";
            $previousSql .= " AND department_id = :department_id";
        }

        $currentStmt = $this->repository->getConnection()->prepare($currentSql);
        if ($userDepartmentId !== null) {
            $currentStmt->bindValue(':department_id', $userDepartmentId, \PDO::PARAM_INT);
        }
        $currentStmt->execute();
        $current = $currentStmt->fetch(\PDO::FETCH_ASSOC);

        $previousStmt = $this->repository->getConnection()->prepare($previousSql);
        if ($userDepartmentId !== null) {
            $previousStmt->bindValue(':department_id', $userDepartmentId, \PDO::PARAM_INT);
        }
        $previousStmt->execute();
        $previous = $previousStmt->fetch(\PDO::FETCH_ASSOC);

        return [
            'current' => [
                'total_plans' => (int)$current['total_plans'],
                'completed' => (int)$current['completed'],
                'avg_progress' => round((float)$current['avg_progress'], 1),
                'total_cost' => (float)$current['total_cost']
            ],
            'previous' => [
                'total_plans' => (int)$previous['total_plans'],
                'completed' => (int)$previous['completed'],
                'avg_progress' => round((float)$previous['avg_progress'], 1),
                'total_cost' => (float)$previous['total_cost']
            ]
        ];
    }

    private function getPerformanceIndicators(?int $userDepartmentId = null): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_plans,
                    SUM(CASE WHEN status = 'Concluído' AND end_date >= CURDATE() THEN 1 ELSE 0 END) as on_time_completed,
                    SUM(CASE WHEN status = 'Concluído' THEN 1 ELSE 0 END) as total_completed,
                    SUM(CASE WHEN end_date < CURDATE() AND status != 'Concluído' THEN 1 ELSE 0 END) as overdue,
                    AVG(COALESCE(progress_percentage, 0)) as avg_progress,
                    SUM(CASE WHEN how_much IS NOT NULL THEN CAST(how_much AS DECIMAL(15,2)) ELSE 0 END) as total_budget,
                    SUM(CASE WHEN status = 'Concluído' AND how_much IS NOT NULL THEN CAST(how_much AS DECIMAL(15,2)) ELSE 0 END) as spent_budget
                FROM adms_strategic_plans";
        
        // Adicionar filtro por departamento se especificado
        if ($userDepartmentId !== null) {
            $sql .= " WHERE department_id = :department_id";
        }
        
        $stmt = $this->repository->getConnection()->prepare($sql);
        
        if ($userDepartmentId !== null) {
            $stmt->bindValue(':department_id', $userDepartmentId, \PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        $totalPlans = (int)$result['total_plans'];
        $onTimeCompleted = (int)$result['on_time_completed'];
        $totalCompleted = (int)$result['total_completed'];
        $overdue = (int)$result['overdue'];
        $totalBudget = (float)$result['total_budget'];
        $spentBudget = (float)$result['spent_budget'];

        // Calcular métricas de performance
        $efficiency = $totalCompleted > 0 ? round(($onTimeCompleted / $totalCompleted) * 100, 1) : 0;
        $completionRate = $totalPlans > 0 ? round(($totalCompleted / $totalPlans) * 100, 1) : 0;
        $budgetUtilization = $totalBudget > 0 ? round(($spentBudget / $totalBudget) * 100, 1) : 0;

        return [
            'efficiency' => $efficiency, // % de planos concluídos no prazo
            'completion_rate' => $completionRate, // % de planos concluídos
            'budget_utilization' => $budgetUtilization, // % do orçamento utilizado
            'overdue_count' => $overdue,
            'total_budget' => $totalBudget,
            'spent_budget' => $spentBudget,
            'avg_progress' => round((float)$result['avg_progress'], 1)
        ];
    }

    private function getCostAnalysis(?int $userDepartmentId = null): array
    {
        $sql = "SELECT 
                    d.name as department_name,
                    COUNT(sp.id) as plan_count,
                    SUM(CASE WHEN sp.how_much IS NOT NULL THEN CAST(sp.how_much AS DECIMAL(15,2)) ELSE 0 END) as total_cost,
                    SUM(CASE WHEN sp.status = 'Concluído' AND sp.how_much IS NOT NULL THEN CAST(sp.how_much AS DECIMAL(15,2)) ELSE 0 END) as completed_cost,
                    AVG(COALESCE(sp.progress_percentage, 0)) as avg_progress
                FROM adms_strategic_plans sp
                LEFT JOIN adms_departments d ON sp.department_id = d.id";
        
        // Adicionar filtro por departamento se especificado
        if ($userDepartmentId !== null) {
            $sql .= " WHERE sp.department_id = :department_id";
        }
        
        $sql .= " GROUP BY d.id, d.name
                HAVING plan_count > 0
                ORDER BY total_cost DESC
                LIMIT 10";
        
        $stmt = $this->repository->getConnection()->prepare($sql);
        
        if ($userDepartmentId !== null) {
            $stmt->bindValue(':department_id', $userDepartmentId, \PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
