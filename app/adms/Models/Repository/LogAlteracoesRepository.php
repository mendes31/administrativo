<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;
use Exception;

class LogAlteracoesRepository extends DbConnection
{
    public function getAll($pagina = 1, $perPage = 10, $filtros = [], $orderBy = 'id', $orderDirection = 'DESC')
    {
        $offset = ($pagina - 1) * $perPage;
        $where = [];
        $params = [];
        
        // Se há filtro por identificador, buscar IDs correspondentes primeiro
        if (!empty($filtros['identificador'])) {
            $idsFiltrados = $this->buscarIdsPorIdentificador($filtros['identificador']);
            if (empty($idsFiltrados)) {
                return []; // Nenhum resultado encontrado
            }
            $where[] = 'log.id IN (' . implode(',', $idsFiltrados) . ')';
        }
        
        if (!empty($filtros['tabela'])) {
            $where[] = 'log.tabela LIKE :tabela';
            $params[':tabela'] = '%' . $filtros['tabela'] . '%';
        }
        if (!empty($filtros['objeto_id'])) {
            // Igualdade exata: LIKE com % incluía substrings (ex.: filtro "1" retornava objeto_id 18).
            $where[] = 'log.objeto_id = :objeto_id';
            $params[':objeto_id'] = (string) $filtros['objeto_id'];
        }
        if (!empty($filtros['usuario_id'])) {
            $where[] = 'log.usuario_id = :usuario_id';
            $params[':usuario_id'] = $filtros['usuario_id'];
        }
        if (!empty($filtros['usuario_nome'])) {
            $where[] = 'usr.name LIKE :usuario_nome';
            $params[':usuario_nome'] = '%' . $filtros['usuario_nome'] . '%';
        }
        if (!empty($filtros['data_inicio'])) {
            $where[] = 'log.data_alteracao >= :data_inicio';
            $params[':data_inicio'] = $filtros['data_inicio'] . ' 00:00:00';
        }
        if (!empty($filtros['data_fim'])) {
            $where[] = 'log.data_alteracao <= :data_fim';
            $params[':data_fim'] = $filtros['data_fim'] . ' 23:59:59';
        }
        if (!empty($filtros['tipo'])) {
            $where[] = 'log.tipo_operacao = :tipo';
            $params[':tipo'] = $filtros['tipo'];
        }
        if (!empty($filtros['strategic_plan_context'])) {
            $spId = (int) $filtros['strategic_plan_context'];
            if ($spId > 0) {
                $where[] = '((log.tabela = \'adms_strategic_plans\' AND log.objeto_id = :sp_ctx_plan) OR (log.tabela = \'adms_strategic_plan_observations\' AND log.objeto_id IN (SELECT id FROM adms_strategic_plan_observations WHERE strategic_plan_id = :sp_ctx_obs_plan)))';
                $params[':sp_ctx_plan'] = $spId;
                $params[':sp_ctx_obs_plan'] = $spId;
            }
        }
        if (!empty($filtros['inventory_item_context'])) {
            $iid = (int) $filtros['inventory_item_context'];
            if ($iid > 0) {
                $where[] = '((log.tabela = \'inv_items\' AND log.objeto_id = :inv_ctx_item) OR (log.tabela = \'inv_item_bom\' AND log.objeto_id = :inv_ctx_bom) OR (log.tabela = \'inv_item_operations\' AND log.objeto_id = :inv_ctx_ops) OR (log.tabela = \'inv_balances\' AND log.objeto_id IN (SELECT id FROM inv_balances WHERE inv_item_id = :inv_ctx_bal)) OR (log.tabela = \'inv_movements\' AND log.objeto_id IN (SELECT DISTINCT inv_movement_id FROM inv_movement_items WHERE inv_item_id = :inv_ctx_mov)))';
                $params[':inv_ctx_item'] = $iid;
                $params[':inv_ctx_bom'] = $iid;
                $params[':inv_ctx_ops'] = $iid;
                $params[':inv_ctx_bal'] = $iid;
                $params[':inv_ctx_mov'] = $iid;
            }
        }

        $sql = 'SELECT log.*, usr.name as usuario_nome FROM adms_log_alteracoes log LEFT JOIN adms_users usr ON log.usuario_id = usr.id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        
        // Mapear campos de ordenação para nomes corretos da tabela
        $orderByMap = [
            'id' => 'log.id',
            'tabela' => 'log.tabela',
            'objeto_id' => 'log.objeto_id',
            'usuario_nome' => 'usr.name',
            'data_alteracao' => 'log.data_alteracao',
            'tipo_operacao' => 'log.tipo_operacao',
            'ip' => 'log.ip',
            'hostname' => 'log.hostname'
        ];
        
        $orderField = $orderByMap[$orderBy] ?? 'log.id';
        $sql .= ' ORDER BY ' . $orderField . ' ' . strtoupper($orderDirection) . ' LIMIT :limit OFFSET :offset';
        
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Buscar identificador principal de cada registro
        foreach ($logs as &$log) {
            $identificador = '-';
            try {
                switch ($log['tabela']) {
                    case 'adms_users':
                        $repo = new \App\adms\Models\Repository\UsersRepository();
                        $user = $repo->getUser((int)$log['objeto_id']);
                        $identificador = $user && !empty($user['name']) ? $user['name'] : $log['objeto_id'];
                        break;
                    case 'adms_customer':
                        $repo = new \App\adms\Models\Repository\CustomerRepository();
                        $customer = $repo->getCustomer((int)$log['objeto_id']);
                        $identificador = $customer && !empty($customer['card_name']) ? $customer['card_name'] : $log['objeto_id'];
                        break;
                    case 'adms_departments':
                        $repo = new \App\adms\Models\Repository\DepartmentsRepository();
                        $dep = $repo->getDepartment((int)$log['objeto_id']);
                        $identificador = $dep && !empty($dep['name']) ? $dep['name'] : $log['objeto_id'];
                        break;
                    case 'adms_pay':
                        $repo = new \App\adms\Models\Repository\PayRepository();
                        $pay = $repo->getPay((int)$log['objeto_id']);
                        if ($pay) {
                            // Nota-Parcela-Fornecedor
                            $identificador = ($pay['num_doc'] ?? '') . '-' . ($pay['installment_number'] ?? '') . '-' . ($pay['description'] ?? '');
                            $identificador = trim($identificador, '-');
                        } else {
                            $identificador = $log['objeto_id'];
                        }
                        break;
                    case 'adms_receive':
                        $repo = new \App\adms\Models\Repository\ReceiptsRepository();
                        $rec = $repo->getReceive((int)$log['objeto_id']);
                        if ($rec) {
                            $identificador = ($rec['num_doc'] ?? '') . '-' . ($rec['installment_number'] ?? '') . '-' . ($rec['description'] ?? '');
                            $identificador = trim($identificador, '-');
                        } else {
                            $identificador = $log['objeto_id'];
                        }
                        break;
                    case 'adms_training_users':
                        $repo = new \App\adms\Models\Repository\TrainingUsersRepository();
                        $rel = $repo->getById((int)$log['objeto_id']);
                        if ($rel && isset($rel['adms_user_id'], $rel['adms_training_id'])) {
                            $userRepo = new \App\adms\Models\Repository\UsersRepository();
                            $trainingRepo = new \App\adms\Models\Repository\TrainingsRepository();
                            $user = $userRepo->getUser((int)$rel['adms_user_id']);
                            $training = $trainingRepo->getTraining((int)$rel['adms_training_id']);
                            $identificador = ($user['name'] ?? $rel['adms_user_id']) . ' - ' . ($training['title'] ?? $rel['adms_training_id']);
                        } else {
                            $identificador = $log['objeto_id'];
                        }
                        break;
                    case 'adms_trainings':
                        $repo = new \App\adms\Models\Repository\TrainingsRepository();
                        $training = $repo->getTraining((int)$log['objeto_id']);
                        $identificador = $training && !empty($training['title']) ? $training['title'] : $log['objeto_id'];
                        break;
                    case 'adms_movements':
                        // Buscar o registro de movimentação
                        $movRepo = new \App\adms\Models\Repository\FinancialMovementsRepository();
                        $mov = $movRepo->getMovementById((int)$log['objeto_id']);
                        if ($mov && isset($mov['movement'], $mov['movement_id'])) {
                            if ($mov['movement'] === 'Conta à Pagar') {
                                $payRepo = new \App\adms\Models\Repository\PayRepository();
                                $pay = $payRepo->getPay((int)$mov['movement_id']);
                                if ($pay) {
                                    $identificador = ($pay['num_doc'] ?? '') . '-' . ($pay['installment_number'] ?? '') . '-' . ($pay['description'] ?? '');
                                    $identificador = trim($identificador, '-');
                                } else {
                                    $identificador = $mov['movement_id'];
                                }
                            } elseif ($mov['movement'] === 'Conta à Receber') {
                                $recRepo = new \App\adms\Models\Repository\ReceiptsRepository();
                                $rec = $recRepo->getReceive((int)$mov['movement_id']);
                                if ($rec) {
                                    $identificador = ($rec['num_doc'] ?? '') . '-' . ($rec['installment_number'] ?? '') . '-' . ($rec['description'] ?? '');
                                    $identificador = trim($identificador, '-');
                                } else {
                                    $identificador = $mov['movement_id'];
                                }
                            } else {
                                $identificador = $mov['movement_id'];
                            }
                        } else {
                            $identificador = $log['objeto_id'];
                        }
                        break;
                    case 'inv_item_bom':
                    case 'inv_item_operations':
                        $invItRepo = new \App\adms\Models\Repository\inventory\InvItemsRepository();
                        $invIt = $invItRepo->getOne((int) $log['objeto_id']);
                        $identificador = $invIt && !empty($invIt['description'])
                            ? ('Item #' . $log['objeto_id'] . ': ' . $invIt['description'])
                            : (string) $log['objeto_id'];
                        break;
                    case 'inv_balances':
                        $balRepo = new \App\adms\Models\Repository\inventory\InvBalancesRepository();
                        $bRow = $balRepo->getBalanceRowById((int) $log['objeto_id']);
                        $identificador = is_array($bRow)
                            ? ('Saldo item ' . ($bRow['inv_item_id'] ?? '') . ' / balanço #' . $log['objeto_id'])
                            : (string) $log['objeto_id'];
                        break;
                    case 'inv_movements':
                        $identificador = 'Movimento estoque #' . $log['objeto_id'];
                        break;
                    case 'adms_dashboard_reports':
                    case 'adms_dashboard_relationships':
                        $dashRepo = new DashboardsRepository();
                        $dash = $dashRepo->getById((int) $log['objeto_id']);
                        $suffix = $log['tabela'] === 'adms_dashboard_reports' ? 'relatórios no dashboard' : 'relações entre relatórios';
                        $identificador = $dash && !empty($dash['name'])
                            ? ($dash['name'] . ' · ' . $suffix)
                            : ('Dashboard #' . $log['objeto_id'] . ' · ' . $suffix);
                        break;
                    case 'adms_dynamic_report_shared_users':
                        $drRepo = new DynamicReportsRepository();
                        $dr = $drRepo->getById((int) $log['objeto_id']);
                        $identificador = $dr && !empty($dr['name'])
                            ? ($dr['name'] . ' · partilhas')
                            : ('Relatório #' . $log['objeto_id'] . ' · partilhas');
                        break;
                    case 'adms_kpi_widgets':
                        $kpiRepo = new KpiDashboardRepository();
                        $did = $kpiRepo->findWidgetDashboardId((int) $log['objeto_id']);
                        $kdash = $did !== null ? $kpiRepo->findById($did) : null;
                        $identificador = $kdash && !empty($kdash['name'])
                            ? ($kdash['name'] . ' · widget #' . $log['objeto_id'])
                            : ('Widget KPI #' . $log['objeto_id']);
                        break;
                    // Adicione outros cases específicos conforme necessário
                    default:
                        // Tenta buscar dinamicamente um campo textual comum
                        $identificador = $this->getIdentificadorGenerico($log['tabela'], (int)$log['objeto_id']);
                        if ($identificador === '-' || empty($identificador)) {
                            $identificador = $log['objeto_id'];
                        }
                }
            } catch (\Throwable $e) {
                $identificador = $log['objeto_id'];
            }
            $log['identificador'] = $identificador;
        }
        unset($log);
        return $logs;
    }

    /**
     * Busca IDs de logs que correspondem ao filtro de identificador
     */
    private function buscarIdsPorIdentificador(string $termo): array
    {
        $ids = [];
        $sql = 'SELECT id, tabela, objeto_id FROM adms_log_alteracoes ORDER BY data_alteracao DESC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($logs as $log) {
            $identificador = $this->getIdentificadorParaLog($log);
            if (stripos($identificador, $termo) !== false) {
                $ids[] = $log['id'];
            }
        }

        return $ids;
    }

    /**
     * Obtém o identificador para um log específico
     */
    private function getIdentificadorParaLog(array $log): string
    {
        $identificador = '-';
        try {
            switch ($log['tabela']) {
                case 'adms_users':
                    $repo = new \App\adms\Models\Repository\UsersRepository();
                    $user = $repo->getUser((int)$log['objeto_id']);
                    $identificador = $user && !empty($user['name']) ? $user['name'] : $log['objeto_id'];
                    break;
                case 'adms_customer':
                    $repo = new \App\adms\Models\Repository\CustomerRepository();
                    $customer = $repo->getCustomer((int)$log['objeto_id']);
                    $identificador = $customer && !empty($customer['card_name']) ? $customer['card_name'] : $log['objeto_id'];
                    break;
                case 'adms_departments':
                    $repo = new \App\adms\Models\Repository\DepartmentsRepository();
                    $dep = $repo->getDepartment((int)$log['objeto_id']);
                    $identificador = $dep && !empty($dep['name']) ? $dep['name'] : $log['objeto_id'];
                    break;
                case 'adms_pay':
                    $repo = new \App\adms\Models\Repository\PayRepository();
                    $pay = $repo->getPay((int)$log['objeto_id']);
                    if ($pay) {
                        $identificador = ($pay['num_doc'] ?? '') . '-' . ($pay['installment_number'] ?? '') . '-' . ($pay['description'] ?? '');
                        $identificador = trim($identificador, '-');
                    } else {
                        $identificador = $log['objeto_id'];
                    }
                    break;
                case 'adms_receive':
                    $repo = new \App\adms\Models\Repository\ReceiptsRepository();
                    $rec = $repo->getReceive((int)$log['objeto_id']);
                    if ($rec) {
                        $identificador = ($rec['num_doc'] ?? '') . '-' . ($rec['installment_number'] ?? '') . '-' . ($rec['description'] ?? '');
                        $identificador = trim($identificador, '-');
                    } else {
                        $identificador = $log['objeto_id'];
                    }
                    break;
                case 'adms_training_users':
                    $repo = new \App\adms\Models\Repository\TrainingUsersRepository();
                    $rel = $repo->getById((int)$log['objeto_id']);
                    if ($rel && isset($rel['adms_user_id'], $rel['adms_training_id'])) {
                        $userRepo = new \App\adms\Models\Repository\UsersRepository();
                        $trainingRepo = new \App\adms\Models\Repository\TrainingsRepository();
                        $user = $userRepo->getUser((int)$rel['adms_user_id']);
                        $training = $trainingRepo->getTraining((int)$rel['adms_training_id']);
                        $identificador = ($user['name'] ?? $rel['adms_user_id']) . ' - ' . ($training['title'] ?? $rel['adms_training_id']);
                    } else {
                        $identificador = $log['objeto_id'];
                    }
                    break;
                case 'adms_trainings':
                    $repo = new \App\adms\Models\Repository\TrainingsRepository();
                    $training = $repo->getTraining((int)$log['objeto_id']);
                    $identificador = $training && !empty($training['title']) ? $training['title'] : $log['objeto_id'];
                    break;
                case 'adms_movements':
                    $movRepo = new \App\adms\Models\Repository\FinancialMovementsRepository();
                    $mov = $movRepo->getMovementById((int)$log['objeto_id']);
                    if ($mov && isset($mov['movement'], $mov['movement_id'])) {
                        if ($mov['movement'] === 'Conta à Pagar') {
                            $payRepo = new \App\adms\Models\Repository\PayRepository();
                            $pay = $payRepo->getPay((int)$mov['movement_id']);
                            if ($pay) {
                                $identificador = ($pay['num_doc'] ?? '') . '-' . ($pay['installment_number'] ?? '') . '-' . ($pay['description'] ?? '');
                                $identificador = trim($identificador, '-');
                            } else {
                                $identificador = $mov['movement_id'];
                            }
                        } elseif ($mov['movement'] === 'Conta à Receber') {
                            $recRepo = new \App\adms\Models\Repository\ReceiptsRepository();
                            $rec = $recRepo->getReceive((int)$mov['movement_id']);
                            if ($rec) {
                                $identificador = ($rec['num_doc'] ?? '') . '-' . ($rec['installment_number'] ?? '') . '-' . ($rec['description'] ?? '');
                                $identificador = trim($identificador, '-');
                            } else {
                                $identificador = $mov['movement_id'];
                            }
                        } else {
                            $identificador = $mov['movement_id'];
                        }
                    } else {
                        $identificador = $log['objeto_id'];
                    }
                    break;
                case 'inv_item_bom':
                case 'inv_item_operations':
                    $invItRepo2 = new \App\adms\Models\Repository\inventory\InvItemsRepository();
                    $invIt2 = $invItRepo2->getOne((int) $log['objeto_id']);
                    $identificador = $invIt2 && !empty($invIt2['description'])
                        ? ('Item #' . $log['objeto_id'] . ': ' . $invIt2['description'])
                        : (string) $log['objeto_id'];
                    break;
                case 'inv_balances':
                    $balRepo2 = new \App\adms\Models\Repository\inventory\InvBalancesRepository();
                    $bRow2 = $balRepo2->getBalanceRowById((int) $log['objeto_id']);
                    $identificador = is_array($bRow2)
                        ? ('Saldo item ' . ($bRow2['inv_item_id'] ?? '') . ' / balanço #' . $log['objeto_id'])
                        : (string) $log['objeto_id'];
                    break;
                case 'inv_movements':
                    $identificador = 'Movimento estoque #' . $log['objeto_id'];
                    break;
                case 'adms_dashboard_reports':
                case 'adms_dashboard_relationships':
                    $dashRepo2 = new DashboardsRepository();
                    $dash2 = $dashRepo2->getById((int) $log['objeto_id']);
                    $suffix2 = $log['tabela'] === 'adms_dashboard_reports' ? 'relatórios no dashboard' : 'relações entre relatórios';
                    $identificador = $dash2 && !empty($dash2['name'])
                        ? ($dash2['name'] . ' · ' . $suffix2)
                        : ('Dashboard #' . $log['objeto_id'] . ' · ' . $suffix2);
                    break;
                case 'adms_dynamic_report_shared_users':
                    $drRepo2 = new DynamicReportsRepository();
                    $dr2 = $drRepo2->getById((int) $log['objeto_id']);
                    $identificador = $dr2 && !empty($dr2['name'])
                        ? ($dr2['name'] . ' · partilhas')
                        : ('Relatório #' . $log['objeto_id'] . ' · partilhas');
                    break;
                case 'adms_kpi_widgets':
                    $kpiRepo2 = new KpiDashboardRepository();
                    $did2 = $kpiRepo2->findWidgetDashboardId((int) $log['objeto_id']);
                    $kdash2 = $did2 !== null ? $kpiRepo2->findById($did2) : null;
                    $identificador = $kdash2 && !empty($kdash2['name'])
                        ? ($kdash2['name'] . ' · widget #' . $log['objeto_id'])
                        : ('Widget KPI #' . $log['objeto_id']);
                    break;
                default:
                    $identificador = $this->getIdentificadorGenerico($log['tabela'], (int)$log['objeto_id']);
                    if ($identificador === '-' || empty($identificador)) {
                        $identificador = $log['objeto_id'];
                    }
            }
        } catch (\Throwable $e) {
            $identificador = $log['objeto_id'];
        }
        return $identificador;
    }

    public function getById(int $id): array|bool
    {
        $sql = 'SELECT log.*, usr.name as usuario_nome FROM adms_log_alteracoes log LEFT JOIN adms_users usr ON log.usuario_id = usr.id WHERE log.id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insert(array $data): int|bool
    {
        try {
            $sql = 'INSERT INTO adms_log_alteracoes (tabela, objeto_id, usuario_id, data_alteracao, tipo_operacao, ip, hostname, user_agent, criado_por) VALUES (:tabela, :objeto_id, :usuario_id, :data_alteracao, :tipo_operacao, :ip, :hostname, :user_agent, :criado_por)';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':tabela', $data['tabela']);
            $stmt->bindValue(':objeto_id', $data['objeto_id']);
            $stmt->bindValue(':usuario_id', $data['usuario_id']);
            $stmt->bindValue(':data_alteracao', $data['data_alteracao']);
            $stmt->bindValue(':tipo_operacao', $data['tipo_operacao']);
            $stmt->bindValue(':ip', $data['ip']);
            $stmt->bindValue(':hostname', $data['hostname'] ?? null);
            $stmt->bindValue(':user_agent', $data['user_agent']);
            $stmt->bindValue(':criado_por', $data['criado_por']);
            $stmt->execute();
            return $this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            return false;
        }
    }

    public function countAll($filtros = [])
    {
        $where = [];
        $params = [];
        
        // Se há filtro por identificador, buscar IDs correspondentes primeiro
        if (!empty($filtros['identificador'])) {
            $idsFiltrados = $this->buscarIdsPorIdentificador($filtros['identificador']);
            if (empty($idsFiltrados)) {
                return 0; // Nenhum resultado encontrado
            }
            $where[] = 'id IN (' . implode(',', $idsFiltrados) . ')';
        }
        
        if (!empty($filtros['tabela'])) {
            $where[] = 'tabela LIKE :tabela';
            $params[':tabela'] = '%' . $filtros['tabela'] . '%';
        }
        if (!empty($filtros['objeto_id'])) {
            $where[] = 'objeto_id = :objeto_id';
            $params[':objeto_id'] = (string) $filtros['objeto_id'];
        }
        if (!empty($filtros['usuario_nome'])) {
            $where[] = 'usuario_id IN (SELECT id FROM adms_users WHERE name LIKE :usuario_nome)';
            $params[':usuario_nome'] = '%' . $filtros['usuario_nome'] . '%';
        }
        if (!empty($filtros['data_inicio'])) {
            $where[] = 'data_alteracao >= :data_inicio';
            $params[':data_inicio'] = $filtros['data_inicio'] . ' 00:00:00';
        }
        if (!empty($filtros['data_fim'])) {
            $where[] = 'data_alteracao <= :data_fim';
            $params[':data_fim'] = $filtros['data_fim'] . ' 23:59:59';
        }
        if (!empty($filtros['tipo'])) {
            $where[] = 'tipo_operacao = :tipo';
            $params[':tipo'] = $filtros['tipo'];
        }
        if (!empty($filtros['strategic_plan_context'])) {
            $spId = (int) $filtros['strategic_plan_context'];
            if ($spId > 0) {
                $where[] = '((tabela = \'adms_strategic_plans\' AND objeto_id = :sp_ctx_plan_c) OR (tabela = \'adms_strategic_plan_observations\' AND objeto_id IN (SELECT id FROM adms_strategic_plan_observations WHERE strategic_plan_id = :sp_ctx_obs_plan_c)))';
                $params[':sp_ctx_plan_c'] = $spId;
                $params[':sp_ctx_obs_plan_c'] = $spId;
            }
        }
        if (!empty($filtros['inventory_item_context'])) {
            $iid = (int) $filtros['inventory_item_context'];
            if ($iid > 0) {
                $where[] = '((tabela = \'inv_items\' AND objeto_id = :inv_ctx_item_c) OR (tabela = \'inv_item_bom\' AND objeto_id = :inv_ctx_bom_c) OR (tabela = \'inv_item_operations\' AND objeto_id = :inv_ctx_ops_c) OR (tabela = \'inv_balances\' AND objeto_id IN (SELECT id FROM inv_balances WHERE inv_item_id = :inv_ctx_bal_c)) OR (tabela = \'inv_movements\' AND objeto_id IN (SELECT DISTINCT inv_movement_id FROM inv_movement_items WHERE inv_item_id = :inv_ctx_mov_c)))';
                $params[':inv_ctx_item_c'] = $iid;
                $params[':inv_ctx_bom_c'] = $iid;
                $params[':inv_ctx_ops_c'] = $iid;
                $params[':inv_ctx_bal_c'] = $iid;
                $params[':inv_ctx_mov_c'] = $iid;
            }
        }
        $sql = 'SELECT COUNT(*) as total FROM adms_log_alteracoes';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Busca um campo identificador genérico para tabelas não mapeadas
     */
    private function getIdentificadorGenerico(string $tabela, int $id): string
    {
        $camposPossiveis = ['name', 'description', 'title', 'card_name', 'num_doc', 'email'];
        try {
            $sql = 'SELECT * FROM ' . $tabela . ' WHERE id = :id LIMIT 1';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                foreach ($camposPossiveis as $campo) {
                    if (!empty($row[$campo])) {
                        return $row[$campo];
                    }
                }
                // Se não encontrar campo textual, retorna o ID
                return $row['id'] ?? '-';
            }
        } catch (\Throwable $e) {
            // ignora
        }
        return '-';
    }
} 