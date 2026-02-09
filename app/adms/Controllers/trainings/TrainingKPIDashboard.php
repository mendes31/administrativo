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
use App\adms\Models\Services\TrainingStatusUpdaterService;

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
            // Atualizar status dinâmicos automaticamente (se necessário)
            // Executa apenas se passou mais de 15 minutos desde a última atualização
            // Isso garante que os dados estejam sempre atualizados sem sobrecarregar o servidor
            TrainingStatusUpdaterService::ensureUpdated();
            
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
            // Debug: log do primeiro departamento para verificar se os dados estão corretos
            if (!empty($data['departmentStats'])) {
                $firstDept = $data['departmentStats'][0];
                error_log("TrainingKpiDashboard: Primeiro departamento passado para view - " . json_encode([
                    'department' => $firstDept['department_name'] ?? 'N/A',
                    'em_dia' => $firstDept['em_dia'] ?? 0,
                    'pendentes' => $firstDept['pendentes'] ?? 0,
                    'vencidos' => $firstDept['vencidos'] ?? 0,
                    'agendados' => $firstDept['agendados'] ?? 0,
                ]));
            }
        } catch (\Exception $e) {
            error_log("Erro ao carregar departmentStats: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
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
        // Versão simplificada e alinhada exatamente com a regra de negócio:
        // - Tudo vem DIRETAMENTE de tu.status
        // - Um único SELECT já traz: total, concluídos, em_dia, pendentes, vencidos, agendados
        // - Sem cálculos dinâmicos em PHP

        $pdo = $this->trainingUsersRepo->getConnection();

        // Query para status dinâmicos (apenas ativos) - mesma lógica de getSummaryAll()
        // IMPORTANTE: Não usar WHERE para filtrar status, pois queremos contar todos os status dinâmicos
        // O INNER JOIN já garante que apenas usuários/treinamentos ativos sejam contados
        $sqlActive = "SELECT 
                    d.id   AS department_id,
                    d.name AS department_name,
                    COUNT(*) AS total_entries,
                    SUM(CASE WHEN tu.status IN ('em_dia','dentro_do_prazo') THEN 1 ELSE 0 END) AS em_dia,
                    SUM(CASE WHEN tu.status = 'proximo_vencimento' THEN 1 ELSE 0 END) AS pendentes,
                    SUM(CASE WHEN tu.status = 'vencido' THEN 1 ELSE 0 END) AS vencidos,
                    SUM(CASE WHEN tu.status = 'agendado' THEN 1 ELSE 0 END) AS agendados
                FROM adms_training_users tu
                INNER JOIN adms_users u 
                    ON u.id = tu.adms_user_id 
                   AND u.status = 'Ativo'
                INNER JOIN adms_trainings t 
                    ON t.id = tu.adms_training_id 
                   AND t.ativo = 1
                INNER JOIN adms_departments d 
                    ON u.user_department_id = d.id
                WHERE (tu.status != 'concluido' OR tu.status IS NULL)
                GROUP BY d.id, d.name";
        
        // Query para concluídos (todos os não-órfãos) - mesma lógica de getSummaryAll()
        $sqlConcluidos = "SELECT 
                    d.id   AS department_id,
                    COUNT(*) AS concluidos
                FROM adms_training_users tu
                LEFT JOIN adms_users u ON u.id = tu.adms_user_id
                LEFT JOIN adms_trainings t ON t.id = tu.adms_training_id
                LEFT JOIN adms_departments d ON u.user_department_id = d.id
                WHERE tu.status = 'concluido'
                  AND u.id IS NOT NULL
                  AND t.id IS NOT NULL
                  AND d.id IS NOT NULL
                GROUP BY d.id";

        // Executar query de status dinâmicos
        $stmtActive = $pdo->prepare($sqlActive);
        $stmtActive->execute();
        $activeStats = $stmtActive->fetchAll(\PDO::FETCH_ASSOC);
        error_log("getDepartmentStatistics: activeStats (primeiros 2) - " . json_encode(array_slice($activeStats, 0, 2)));
        
        // Executar query de concluídos
        $stmtConc = $pdo->prepare($sqlConcluidos);
        $stmtConc->execute();
        $concluidosStats = $stmtConc->fetchAll(\PDO::FETCH_ASSOC);
        error_log("getDepartmentStatistics: concluidosStats (primeiros 2) - " . json_encode(array_slice($concluidosStats, 0, 2)));
        
        // Criar mapa de concluídos por departamento
        $concluidosMap = [];
        foreach ($concluidosStats as $row) {
            $concluidosMap[$row['department_id']] = (int)($row['concluidos'] ?? 0);
        }
        
        // Combinar resultados
        $result = [];
        foreach ($activeStats as $row) {
            $deptId = $row['department_id'];
            $totalDinamicos = (int)($row['total_entries'] ?? 0);
            $concluidos = $concluidosMap[$deptId] ?? 0;
            $emDia = (int)($row['em_dia'] ?? 0);
            $pendentes = (int)($row['pendentes'] ?? 0);
            $vencidos = (int)($row['vencidos'] ?? 0);
            $agendados = (int)($row['agendados'] ?? 0);
            
            // Debug: log do primeiro departamento após combinação
            if (count($result) === 0) {
                error_log("getDepartmentStatistics: Primeiro departamento após combinação - " . json_encode([
                    'department_id' => $deptId,
                    'department_name' => $row['department_name'],
                    'total_entries' => $totalDinamicos,
                    'concluidos' => $concluidos,
                    'em_dia' => $emDia,
                    'pendentes' => $pendentes,
                    'vencidos' => $vencidos,
                    'agendados' => $agendados,
                    'total_vinculos' => $totalDinamicos + $concluidos,
                ]));
            }
            
            $result[] = [
                'department_id'   => (int) $deptId,
                'department_name' => $row['department_name'],
                'total_vinculos'  => $totalDinamicos + $concluidos,
                'concluidos'      => $concluidos,
                'em_dia'          => $emDia,
                'pendentes'       => $pendentes,
                'vencidos'        => $vencidos,
                'agendados'       => $agendados,
            ];
        }
        
        // Adicionar departamentos que só têm concluídos
        foreach ($concluidosMap as $deptId => $concluidos) {
            $found = false;
            foreach ($result as $row) {
                if ($row['department_id'] == $deptId) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                // Buscar nome do departamento
                $stmtDept = $pdo->prepare("SELECT name FROM adms_departments WHERE id = ?");
                $stmtDept->execute([$deptId]);
                $deptName = $stmtDept->fetchColumn();
                if ($deptName) {
                    $result[] = [
                        'department_id'   => (int) $deptId,
                        'department_name' => $deptName,
                        'total_vinculos'  => $concluidos,
                        'concluidos'      => $concluidos,
                        'em_dia'          => 0,
                        'pendentes'       => 0,
                        'vencidos'        => 0,
                        'agendados'       => 0,
                    ];
                }
            }
        }

        // Buscar todos os departamentos para garantir que apareçam na lista,
        // mesmo que algum não tenha nenhum vínculo ativo.
        $sqlAllDepts = "SELECT id, name FROM adms_departments ORDER BY name";
        $stmtAllDepts = $pdo->prepare($sqlAllDepts);
        $stmtAllDepts->execute();
        $allDepts = $stmtAllDepts->fetchAll(\PDO::FETCH_ASSOC);

        // Mapear os já existentes
        $byId = [];
        foreach ($result as $row) {
            $byId[$row['department_id']] = $row;
        }

        // Garantir presença de todos os departamentos
        foreach ($allDepts as $dept) {
            $deptId = (int) $dept['id'];
            if (!isset($byId[$deptId])) {
                $byId[$deptId] = [
                    'department_id'   => $deptId,
                    'department_name' => $dept['name'],
                    'total_vinculos'  => 0,
                    'concluidos'      => 0,
                    'em_dia'          => 0,
                    'pendentes'       => 0,
                    'vencidos'        => 0,
                    'agendados'       => 0,
                ];
            }
        }

        // Ordenar por total_vinculos (decrescente)
        usort($byId, function(array $a, array $b) {
            return $b['total_vinculos'] <=> $a['total_vinculos'];
        });

        return array_values($byId);
    }

    private function getPositionStatistics(): array
    {
        // Usar mesma lógica de getSummaryAll(): buscar diretamente de tu.status
        // Para status dinâmicos (exceto concluído): apenas usuários/treinamentos ativos
        // Para concluídos: todos os não-órfãos
        
        $pdo = $this->trainingUsersRepo->getConnection();
        
        // Query para status dinâmicos (apenas ativos) - mesma lógica de getSummaryAll()
        $sqlActive = "SELECT 
                    p.id as position_id,
                    p.name as position_name,
                    COUNT(*) as total_entries,
                    SUM(CASE WHEN tu.status IN ('em_dia','dentro_do_prazo') THEN 1 ELSE 0 END) as em_dia,
                    SUM(CASE WHEN tu.status = 'proximo_vencimento' THEN 1 ELSE 0 END) as pendentes,
                    SUM(CASE WHEN tu.status = 'vencido' THEN 1 ELSE 0 END) as vencidos,
                    SUM(CASE WHEN tu.status = 'agendado' THEN 1 ELSE 0 END) as agendados
                FROM adms_training_users tu
                INNER JOIN adms_users u 
                    ON u.id = tu.adms_user_id 
                   AND u.status = 'Ativo'
                INNER JOIN adms_trainings t 
                    ON t.id = tu.adms_training_id 
                   AND t.ativo = 1
                INNER JOIN adms_positions p ON u.user_position_id = p.id
                GROUP BY p.id, p.name";
        
        // Query para concluídos (todos os não-órfãos) - mesma lógica de getSummaryAll()
        $sqlConcluidos = "SELECT 
                    p.id as position_id,
                    COUNT(*) as concluidos
                FROM adms_training_users tu
                LEFT JOIN adms_users u ON u.id = tu.adms_user_id
                LEFT JOIN adms_trainings t ON t.id = tu.adms_training_id
                LEFT JOIN adms_positions p ON u.user_position_id = p.id
                WHERE tu.status = 'concluido'
                  AND u.id IS NOT NULL
                  AND t.id IS NOT NULL
                  AND p.id IS NOT NULL
                GROUP BY p.id";
        
        // Executar query de status dinâmicos
        $stmtActive = $pdo->prepare($sqlActive);
        $stmtActive->execute();
        $activeStats = $stmtActive->fetchAll(\PDO::FETCH_ASSOC);
        
        // Executar query de concluídos
        $stmtConc = $pdo->prepare($sqlConcluidos);
        $stmtConc->execute();
        $concluidosStats = $stmtConc->fetchAll(\PDO::FETCH_ASSOC);
        
        // Criar mapa de concluídos por cargo
        $concluidosMap = [];
        foreach ($concluidosStats as $row) {
            $concluidosMap[$row['position_id']] = (int)($row['concluidos'] ?? 0);
        }
        
        // Combinar resultados
        $result = [];
        foreach ($activeStats as $row) {
            $posId = $row['position_id'];
            $totalDinamicos = (int)($row['total_entries'] ?? 0);
            $concluidos = $concluidosMap[$posId] ?? 0;
            $result[] = [
                'position_id' => $posId,
                'position_name' => $row['position_name'],
                'total_vinculos' => $totalDinamicos + $concluidos,
                'concluidos' => $concluidos,
                'em_dia' => (int)($row['em_dia'] ?? 0),
                'pendentes' => (int)($row['pendentes'] ?? 0),
                'vencidos' => (int)($row['vencidos'] ?? 0),
                'agendados' => (int)($row['agendados'] ?? 0),
            ];
        }
        
        // Ordenar por total e limitar
        usort($result, function($a, $b) {
            return $b['total_vinculos'] - $a['total_vinculos'];
        });
        
        return array_slice($result, 0, 10);
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