<?php

namespace App\adms\Controllers\analytics;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\EmploymentHistoryRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para People Analytics
 */
class PeopleAnalytics
{
    private array|string|null $data = null;

    public function index(): void
    {
        $usersRepo = new UsersRepository();
        
        // Estatísticas básicas
        $allUsers = $usersRepo->getAllUsers(1, 10000);
        $this->data['total_employees'] = count($allUsers);
        $this->data['active_employees'] = count(array_filter($allUsers, function($u) {
            return ($u['status'] ?? '') === 'Ativo' && empty($u['data_desligamento']);
        }));
        
        // Calcular Taxa de Rotatividade (últimos 12 meses)
        $currentDate = date('Y-m-d');
        $oneYearAgo = date('Y-m-d', strtotime('-12 months'));
        
        // Colaboradores desligados nos últimos 12 meses
        $terminatedLastYear = array_filter($allUsers, function($u) use ($oneYearAgo, $currentDate) {
            return !empty($u['data_desligamento']) && 
                   $u['data_desligamento'] >= $oneYearAgo && 
                   $u['data_desligamento'] <= $currentDate;
        });
        
        // Média de colaboradores no período (simplificado: média entre início e fim)
        $activeAtStart = count(array_filter($allUsers, function($u) use ($oneYearAgo) {
            return ($u['status'] ?? '') === 'Ativo' && 
                   (empty($u['data_admissao']) || $u['data_admissao'] <= $oneYearAgo) &&
                   (empty($u['data_desligamento']) || $u['data_desligamento'] > $oneYearAgo);
        }));
        
        $activeAtEnd = $this->data['active_employees'];
        $averageEmployees = ($activeAtStart + $activeAtEnd) / 2;
        
        // Calcular taxa de rotatividade
        if ($averageEmployees > 0) {
            $turnoverRate = (count($terminatedLastYear) / $averageEmployees) * 100;
            $this->data['turnover_rate'] = number_format($turnoverRate, 2);
        } else {
            $this->data['turnover_rate'] = '0.00';
        }
        
        $this->data['terminated_last_year'] = count($terminatedLastYear);
        
        // Calcular tempo médio de permanência usando histórico completo
        $historyRepo = new EmploymentHistoryRepository();
        $allHistory = [];
        foreach ($allUsers as $user) {
            $userHistory = $historyRepo->getByUserId($user['id']);
            if (!empty($userHistory)) {
                $allHistory = array_merge($allHistory, $userHistory);
            }
        }
        
        // Filtrar apenas períodos com desligamento para calcular média
        $terminatedPeriods = array_filter($allHistory, function($period) {
            return !empty($period['data_desligamento']);
        });
        
        $totalTenureDays = 0;
        $countWithTenure = count($terminatedPeriods);
        
        foreach ($terminatedPeriods as $period) {
            $admission = new \DateTime($period['data_admissao']);
            $termination = new \DateTime($period['data_desligamento']);
            $diff = $admission->diff($termination);
            $totalTenureDays += $diff->days;
        }
        
        if ($countWithTenure > 0) {
            $avgTenureDays = $totalTenureDays / $countWithTenure;
            $avgTenureYears = floor($avgTenureDays / 365);
            $avgTenureMonths = floor(($avgTenureDays % 365) / 30);
            $this->data['avg_tenure'] = $avgTenureYears > 0 
                ? "{$avgTenureYears} ano(s) e {$avgTenureMonths} mês(es)"
                : "{$avgTenureMonths} mês(es)";
        } else {
            $this->data['avg_tenure'] = 'N/A';
        }
        
        // Estatísticas de recontratações
        $rehires = array_filter($allHistory, function($period) {
            return $period['tipo_periodo'] === 'Recontratação';
        });
        $this->data['total_rehires'] = count($rehires);
        
        // Headcount mensal (últimos 12 meses)
        $monthlyHeadcount = [];
        $currentDate = new \DateTime();
        for ($i = 11; $i >= 0; $i--) {
            $monthDate = clone $currentDate;
            $monthDate->modify("-$i months");
            $monthKey = $monthDate->format('Y-m');
            $monthLabel = $monthDate->format('M/Y');
            
            // Contar colaboradores ativos no início do mês
            $monthStart = $monthDate->format('Y-m-01');
            $monthEnd = $monthDate->format('Y-m-t');
            
            $activeInMonth = 0;
            foreach ($allUsers as $user) {
                $userAdmission = !empty($user['data_admissao']) ? new \DateTime($user['data_admissao']) : null;
                $userTermination = !empty($user['data_desligamento']) ? new \DateTime($user['data_desligamento']) : null;
                
                // Verificar se estava ativo neste mês
                if ($userAdmission && $userAdmission->format('Y-m-d') <= $monthEnd) {
                    if (!$userTermination || $userTermination->format('Y-m-d') >= $monthStart) {
                        $activeInMonth++;
                    }
                }
            }
            
            $monthlyHeadcount[$monthLabel] = $activeInMonth;
        }
        $this->data['monthly_headcount'] = $monthlyHeadcount;
        
        // Turnover por departamento
        $turnoverByDept = [];
        foreach ($allUsers as $user) {
            $deptName = $user['name_dep'] ?? 'Sem Departamento';
            if (!isset($turnoverByDept[$deptName])) {
                $turnoverByDept[$deptName] = [
                    'total' => 0,
                    'terminated' => 0,
                    'active' => 0
                ];
            }
            $turnoverByDept[$deptName]['total']++;
            if (!empty($user['data_desligamento'])) {
                $terminationDate = new \DateTime($user['data_desligamento']);
                $oneYearAgo = new \DateTime('-12 months');
                if ($terminationDate >= $oneYearAgo) {
                    $turnoverByDept[$deptName]['terminated']++;
                }
            } else if (($user['status'] ?? '') === 'Ativo') {
                $turnoverByDept[$deptName]['active']++;
            }
        }
        
        // Calcular taxa de turnover por departamento
        foreach ($turnoverByDept as $dept => &$data) {
            $avgEmployees = ($data['active'] + ($data['total'] - $data['active'])) / 2;
            $data['turnover_rate'] = $avgEmployees > 0 
                ? round(($data['terminated'] / $avgEmployees) * 100, 2)
                : 0;
        }
        $this->data['turnover_by_department'] = $turnoverByDept;
        
        // Distribuição por cargo
        $positionDistribution = [];
        foreach ($allUsers as $user) {
            if (($user['status'] ?? '') === 'Ativo' && empty($user['data_desligamento'])) {
                $posName = $user['name_pos'] ?? 'Sem Cargo';
                $positionDistribution[$posName] = ($positionDistribution[$posName] ?? 0) + 1;
            }
        }
        $this->data['position_distribution'] = $positionDistribution;
        
        // Distribuição por departamento
        $departmentDistribution = [];
        foreach ($allUsers as $user) {
            if (($user['status'] ?? '') === 'Ativo' && empty($user['data_desligamento'])) {
                $deptName = $user['name_dep'] ?? 'Sem Departamento';
                $departmentDistribution[$deptName] = ($departmentDistribution[$deptName] ?? 0) + 1;
            }
        }
        $this->data['department_distribution'] = $departmentDistribution;
        
        $pageElements = [
            'title_head' => 'People Analytics',
            'menu' => 'people-analytics',
            'buttonPermission' => [
                'PeopleReports',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/analytics/people_analytics', $this->data);
        $loadView->loadView();
    }
}

