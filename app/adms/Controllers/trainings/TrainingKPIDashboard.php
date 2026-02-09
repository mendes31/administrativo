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
        // Buscar dados e calcular status dinamicamente (como getTrainingStatusByUser faz)
        // Para status dinâmicos (exceto concluído): apenas usuários/treinamentos ativos
        // Para concluídos: todos os não-órfãos
        
        $sql = "SELECT 
                    d.id as department_id,
                    d.name as department_name,
                    tu.id as training_user_id,
                    tu.data_limite_primeiro_treinamento,
                    tu.data_agendada,
                    tu.tipo_vinculo,
                    t.prazo_treinamento,
                    ta_last.data_realizacao
                FROM adms_training_users tu
                INNER JOIN adms_users u 
                    ON u.id = tu.adms_user_id 
                   AND u.status = 'Ativo'
                INNER JOIN adms_trainings t 
                    ON t.id = tu.adms_training_id 
                   AND t.ativo = 1
                INNER JOIN adms_departments d ON u.user_department_id = d.id
                LEFT JOIN (
                    SELECT 
                        ta1.adms_user_id,
                        ta1.adms_training_id,
                        ta1.data_realizacao
                    FROM adms_training_applications ta1
                    INNER JOIN (
                        SELECT 
                            adms_user_id,
                            adms_training_id,
                            MAX(created_at) as max_created_at
                        FROM adms_training_applications
                        GROUP BY adms_user_id, adms_training_id
                    ) ta2 ON ta1.adms_user_id = ta2.adms_user_id 
                        AND ta1.adms_training_id = ta2.adms_training_id 
                        AND ta1.created_at = ta2.max_created_at
                ) ta_last ON ta_last.adms_user_id = tu.adms_user_id 
                    AND ta_last.adms_training_id = tu.adms_training_id
                    AND (ta_last.created_at >= tu.created_at OR ta_last.created_at IS NULL)";
        
        $pdo = $this->trainingUsersRepo->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Agrupar por departamento e calcular status dinamicamente
        $stats = [];
        $hoje = date('Y-m-d');
        
        foreach ($rows as $row) {
            $deptId = $row['department_id'];
            $deptName = $row['department_name'];
            
            if (!isset($stats[$deptId])) {
                $stats[$deptId] = [
                    'department_id' => $deptId,
                    'department_name' => $deptName,
                    'em_dia' => 0,
                    'pendentes' => 0,
                    'vencidos' => 0,
                    'agendados' => 0,
                ];
            }
            
            // Calcular status dinamicamente
            $dataAgendada = $row['data_agendada'] ?? null;
            $dataRealizacao = $row['data_realizacao'] ?? null;
            $dataLimite = $row['data_limite_primeiro_treinamento'] ?? null;
            $prazoTreinamento = $row['prazo_treinamento'] ?? null;
            $tipoVinculo = $row['tipo_vinculo'] ?? 'individual';
            
            // 1. Se tem agendamento futuro
            if ($dataAgendada && $dataAgendada > $hoje) {
                $stats[$deptId]['agendados']++;
                continue;
            }
            
            // 2. Se realizou o treinamento (não contar aqui, será contado separadamente)
            if ($dataRealizacao) {
                continue;
            }
            
            // 3. Se não realizou, analisar prazo
            if ($dataLimite) {
                $diasParaPrazo = (strtotime($dataLimite) - strtotime($hoje)) / (60 * 60 * 24);
                $primeiroCiclo = ($tipoVinculo !== 'reciclagem');
                
                $isProximoVencimento = false;
                if ($primeiroCiclo && $prazoTreinamento !== null) {
                    if ($prazoTreinamento <= 30 && $diasParaPrazo <= 10 && $diasParaPrazo >= 0) {
                        $isProximoVencimento = true;
                    } elseif ($prazoTreinamento <= 45 && $diasParaPrazo <= 15 && $diasParaPrazo >= 0) {
                        $isProximoVencimento = true;
                    } elseif ($prazoTreinamento > 45 && $diasParaPrazo <= 30 && $diasParaPrazo >= 0) {
                        $isProximoVencimento = true;
                    }
                } elseif (!$primeiroCiclo) {
                    if ($diasParaPrazo <= 30 && $diasParaPrazo >= 0) {
                        $isProximoVencimento = true;
                    }
                }
                
                if ($hoje > $dataLimite) {
                    $stats[$deptId]['vencidos']++;
                } elseif ($isProximoVencimento) {
                    $stats[$deptId]['pendentes']++;
                } else {
                    $stats[$deptId]['em_dia']++;
                }
            } else {
                // Sem data limite = dentro do prazo
                $stats[$deptId]['em_dia']++;
            }
        }
        
        // Query para concluídos (todos os não-órfãos com data_realizacao)
        $sqlConcluidos = "SELECT 
                    d.id as department_id,
                    COUNT(*) as concluidos
                FROM adms_training_users tu
                LEFT JOIN adms_users u ON u.id = tu.adms_user_id
                LEFT JOIN adms_trainings t ON t.id = tu.adms_training_id
                LEFT JOIN adms_departments d ON u.user_department_id = d.id
                LEFT JOIN (
                    SELECT 
                        ta1.adms_user_id,
                        ta1.adms_training_id,
                        ta1.data_realizacao
                    FROM adms_training_applications ta1
                    INNER JOIN (
                        SELECT 
                            adms_user_id,
                            adms_training_id,
                            MAX(created_at) as max_created_at
                        FROM adms_training_applications
                        GROUP BY adms_user_id, adms_training_id
                    ) ta2 ON ta1.adms_user_id = ta2.adms_user_id 
                        AND ta1.adms_training_id = ta2.adms_training_id 
                        AND ta1.created_at = ta2.max_created_at
                ) ta_last ON ta_last.adms_user_id = tu.adms_user_id 
                    AND ta_last.adms_training_id = tu.adms_training_id
                WHERE ta_last.data_realizacao IS NOT NULL
                  AND u.id IS NOT NULL
                  AND t.id IS NOT NULL
                  AND d.id IS NOT NULL
                GROUP BY d.id";
        
        $stmtConc = $pdo->prepare($sqlConcluidos);
        $stmtConc->execute();
        $concluidosStats = $stmtConc->fetchAll(\PDO::FETCH_ASSOC);
        
        // Criar mapa de concluídos por departamento
        $concluidosMap = [];
        foreach ($concluidosStats as $row) {
            $concluidosMap[$row['department_id']] = (int)($row['concluidos'] ?? 0);
        }
        
        // Combinar resultados
        $result = [];
        foreach ($stats as $deptId => $stat) {
            $concluidos = $concluidosMap[$deptId] ?? 0;
            $totalDinamicos = $stat['em_dia'] + $stat['pendentes'] + $stat['vencidos'] + $stat['agendados'];
            $result[] = [
                'department_id' => $deptId,
                'department_name' => $stat['department_name'],
                'total_vinculos' => $totalDinamicos + $concluidos,
                'concluidos' => $concluidos,
                'em_dia' => $stat['em_dia'],
                'pendentes' => $stat['pendentes'],
                'vencidos' => $stat['vencidos'],
                'agendados' => $stat['agendados'],
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
                        'department_id' => $deptId,
                        'department_name' => $deptName,
                        'total_vinculos' => $concluidos,
                        'concluidos' => $concluidos,
                        'em_dia' => 0,
                        'pendentes' => 0,
                        'vencidos' => 0,
                        'agendados' => 0,
                    ];
                }
            }
        }
        
        // Ordenar por total
        usort($result, function($a, $b) {
            return $b['total_vinculos'] - $a['total_vinculos'];
        });
        
        return $result;
    }

    private function getPositionStatistics(): array
    {
        // Alinhar com a lógica de getSummaryAll() e getTrainingStatusByUser()
        // Para status dinâmicos (exceto concluído): apenas usuários/treinamentos ativos
        // Para concluídos: todos os não-órfãos
        
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
        
        $pdo = $this->trainingUsersRepo->getConnection();
        
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