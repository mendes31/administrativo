<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Helpers\GenerateLog;
use PDO;
use Exception;

class TrainingUsersRepository extends DbConnection
{
    public function getByUser(int $userId): array
    {
        $sql = 'SELECT * FROM adms_training_users WHERE adms_user_id = :user_id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByTraining(int $trainingId): array
    {
        $sql = 'SELECT * FROM adms_training_users WHERE adms_training_id = :training_id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insertOrUpdate(
        int $userId,
        int $trainingId,
        string $status = 'dentro_do_prazo',
        string $tipoVinculo = 'individual',
        ?string $dataLimiteManual = null,
        string $motivo = 'primeiro'
    ): void
    {
        // Verificar se já existe vínculo ativo (status != 'concluido')
        $sqlCheck = "SELECT id, tipo_vinculo FROM adms_training_users WHERE adms_user_id = :user_id AND adms_training_id = :training_id AND status != 'concluido'";
        $stmtCheck = $this->getConnection()->prepare($sqlCheck);
        $stmtCheck->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmtCheck->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
        $stmtCheck->execute();
        $existeAtivo = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existeAtivo) {
            // Se já existe vínculo ativo do mesmo tipo, não criar outro
            if ($existeAtivo['tipo_vinculo'] === $tipoVinculo) {
                return;
            }
            // Se for individual e já existe cargo, não criar individual
            if ($tipoVinculo === 'individual' && $existeAtivo['tipo_vinculo'] === 'cargo') {
                return;
            }
            // Se for cargo e já existe individual, permitir atualização para 'cargo'
        }

        try {
            // Buscar prazo_treinamento do treinamento
            $sqlPrazo = "SELECT prazo_treinamento FROM adms_trainings WHERE id = :training_id";
            $stmtPrazo = $this->getConnection()->prepare($sqlPrazo);
            $stmtPrazo->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
            $stmtPrazo->execute();
            $prazo = (int)($stmtPrazo->fetchColumn() ?? 0);

            // Calcular data limite
            if ($dataLimiteManual) {
                $dataLimite = $dataLimiteManual;
            } else {
                $dataLimite = (new \DateTime())->modify("+{$prazo} days")->format('Y-m-d');
            }

            $sql = 'INSERT INTO adms_training_users (adms_user_id, adms_training_id, status, tipo_vinculo, motivo, created_at, updated_at, data_limite_primeiro_treinamento)
                    VALUES (:user_id, :training_id, :status, :tipo_vinculo, :motivo, NOW(), NOW(), :data_limite)
                    ON DUPLICATE KEY UPDATE status = :status, tipo_vinculo = :tipo_vinculo, motivo = :motivo, updated_at = NOW(), data_limite_primeiro_treinamento = :data_limite';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(':tipo_vinculo', $tipoVinculo, PDO::PARAM_STR);
            $stmt->bindValue(':motivo', $motivo, PDO::PARAM_STR);
            $stmt->bindValue(':data_limite', $dataLimite, PDO::PARAM_STR);
            $stmt->execute();
            
            // Log de alteração (insert ou update)
            $operacao = $existeAtivo ? 'update' : 'insert';
            $dadosDepois = [
                'adms_user_id' => $userId,
                'adms_training_id' => $trainingId,
                'status' => $status,
                'tipo_vinculo' => $tipoVinculo,
                'motivo' => $motivo,
            ];
            
            \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                'adms_training_users',
                $userId, // Usando user_id como identificador principal
                $_SESSION['user_id'] ?? 0,
                $operacao,
                $existeAtivo ?: [],
                $dadosDepois
            );
            // Atualizar matriz do usuário após alteração
            $user = (new \App\adms\Models\Repository\UsersRepository())->getUser($userId);
            if ($user) {
                $this->recreateLinksForUser($userId, $user['user_position_id']);
            }
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Vínculo de treinamento não salvo.", [
                'user_id' => $userId,
                'training_id' => $trainingId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function deleteByUserAndNotInTrainings(int $userId, array $trainingIds): void
    {
        try {
            // Captura os dados antigos antes da exclusão
            $dadosAntes = $this->getByUser($userId);
            
            if (empty($trainingIds)) {
                $sql = "DELETE FROM adms_training_users WHERE adms_user_id = ?";
                $stmt = $this->getConnection()->prepare($sql);
                $stmt->bindValue(1, $userId, PDO::PARAM_INT);
                $stmt->execute();
            } else {
                $in = implode(',', array_fill(0, count($trainingIds), '?'));
                $sql = "DELETE FROM adms_training_users WHERE adms_user_id = ? AND adms_training_id NOT IN ($in)";
                $stmt = $this->getConnection()->prepare($sql);
                $stmt->bindValue(1, $userId, PDO::PARAM_INT);
                foreach ($trainingIds as $k => $tid) {
                    $stmt->bindValue($k+2, $tid, PDO::PARAM_INT);
                }
                $stmt->execute();
            }
            
            // Log de exclusão em lote
            if (!empty($dadosAntes)) {
                foreach ($dadosAntes as $vinculo) {
                    \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                        'adms_training_users',
                        $userId,
                        $_SESSION['user_id'] ?? 0,
                        'delete',
                        $vinculo,
                        []
                    );
                }
            }
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Vínculos de treinamento não excluídos.", [
                'user_id' => $userId,
                'training_ids' => $trainingIds,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getTrainingStatusByUser(array $filters = []): array
    {
        // Busca todos os vínculos de treinamentos dos usuários
        $sql = 'SELECT 
                u.id as user_id, 
                u.name as user_name, 
                d.name as department, 
                p.name as position,
                t.id as training_id, 
                t.codigo, 
                t.nome as training_name, 
                t.reciclagem, 
                t.reciclagem_periodo,
                t.prazo_treinamento,
                tu.id as training_user_id,
                tu.status,
                tu.tipo_vinculo,
                tu.created_at as vinculo_created_at,
                tu.data_limite_primeiro_treinamento,
                tu.data_agendada
            FROM adms_training_users tu
            INNER JOIN adms_users u ON u.id = tu.adms_user_id
            INNER JOIN adms_departments d ON u.user_department_id = d.id
            INNER JOIN adms_positions p ON u.user_position_id = p.id
            INNER JOIN adms_trainings t ON t.id = tu.adms_training_id
            WHERE 1=1 and tu.status != "concluido"';
        
        $params = [];
        if (!empty($filters['colaborador'])) {
            $sql .= ' AND u.id = ?';
            $params[] = $filters['colaborador'];
        }
        if (!empty($filters['departamento'])) {
            $sql .= ' AND d.id = ?';
            $params[] = $filters['departamento'];
        }
        if (!empty($filters['cargo'])) {
            $sql .= ' AND p.id = ?';
            $params[] = $filters['cargo'];
        }
        if (!empty($filters['treinamento'])) {
            $sql .= ' AND t.id = ?';
            $params[] = $filters['treinamento'];
        }
        if (!empty($filters['area_responsavel_id'])) {
            $sql .= ' AND t.area_responsavel_id = ?';
            $params[] = $filters['area_responsavel_id'];
        }
        if (!empty($filters['area_elaborador_id'])) {
            $sql .= ' AND t.area_elaborador_id = ?';
            $params[] = $filters['area_elaborador_id'];
        }
        
        $sql .= ' ORDER BY u.name ASC, t.nome ASC';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar dados da última aplicação para cada vínculo
        $appRepo = new \App\adms\Models\Repository\TrainingApplicationsRepository();
        foreach ($results as &$result) {
            // Buscar última aplicação após o vínculo
            $history = $appRepo->getHistoryAfter(
                $result['user_id'], 
                $result['training_id'], 
                $result['vinculo_created_at']
            );
            $lastApplication = $history[0] ?? null;
            // Adicionar dados da aplicação apenas para histórico, não sobrescrever data_agendada do vínculo
            $result['data_realizacao'] = $lastApplication['data_realizacao'] ?? null;
            $result['nota'] = $lastApplication['nota'] ?? null;
            $result['observacoes'] = $lastApplication['observacoes'] ?? null;
            $result['instrutor_nome'] = $lastApplication['instrutor_nome'] ?? null;
            $result['instrutor_email'] = $lastApplication['instrutor_email'] ?? null;
            $result['aplicado_por'] = $lastApplication['aplicado_por'] ?? null;
            $result['application_id'] = $lastApplication['id'] ?? null;
            // NÃO sobrescrever data_agendada do vínculo:
            // $result['data_agendada'] = $lastApplication['data_agendada'] ?? null;
            // Calcular status dinâmico com base apenas nos campos do vínculo
            $result['status_dinamico'] = $this->calculateStatus([
                'data_limite_primeiro_treinamento' => $result['data_limite_primeiro_treinamento'],
                'data_realizacao' => null, // não considerar realização na matriz de obrigatoriedade
                'data_agendada' => $result['data_agendada'] ?? null,
                'prazo_treinamento' => $result['prazo_treinamento'] ?? null,
                'tipo_vinculo' => $result['tipo_vinculo'] ?? 'individual',
            ]);
        }
        
        return $results;
    }

    /**
     * Retorna estatísticas de usuários para um treinamento específico
     */
    public function getTrainingUserStats(int $trainingId): array
    {
        $sql = 'SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN status = "pendente" THEN 1 ELSE 0 END) as pendente_count,
                    SUM(CASE WHEN status = "concluido" THEN 1 ELSE 0 END) as concluido_count,
                    SUM(CASE WHEN status = "vencido" THEN 1 ELSE 0 END) as vencido_count,
                    AVG(CASE WHEN nota IS NOT NULL THEN nota ELSE NULL END) as media_nota,
                    COUNT(CASE WHEN nota IS NOT NULL THEN 1 ELSE NULL END) as total_avaliacoes
                FROM adms_training_users 
                WHERE adms_training_id = :training_id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Retorna lista de usuários com status para um treinamento específico
     */
    public function getUsersByTraining(int $trainingId): array
    {
        $sql = 'SELECT tu.*, u.name as user_name, u.email, d.name as department_name, p.name as position_name, tp.reciclagem_periodo, t.prazo_treinamento, tu.tipo_vinculo
                FROM adms_training_users tu
                INNER JOIN adms_users u ON u.id = tu.adms_user_id
                INNER JOIN adms_departments d ON u.user_department_id = d.id
                INNER JOIN adms_positions p ON u.user_position_id = p.id
                INNER JOIN adms_training_positions tp ON tp.adms_training_id = tu.adms_training_id AND tp.adms_position_id = u.user_position_id
                INNER JOIN adms_trainings t ON t.id = tu.adms_training_id
                WHERE tu.adms_training_id = :training_id
                ORDER BY u.name ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Retorna o total de entradas na matriz de treinamentos
     */
    public function getTotalMatrixEntries(): int
    {
        $sql = 'SELECT COUNT(*) as total FROM adms_training_users';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }

    /**
     * Retorna estatísticas por status
     */
    public function getStatusStatistics(): array
    {
        $sql = 'SELECT 
                    status,
                    COUNT(*) as count
                FROM adms_training_users 
                GROUP BY status 
                ORDER BY count DESC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Busca treinamentos próximos do vencimento para notificação
     */
    public function getExpiringTrainingsForNotification(int $daysAhead = 30): array
    {
        $sql = 'SELECT 
                    tu.adms_user_id as user_id,
                    tu.adms_training_id as training_id,
                    u.name as user_name,
                    u.email as user_email,
                    t.nome as training_name,
                    t.codigo,
                    t.reciclagem,
                    t.reciclagem_periodo,
                    tu.data_realizacao,
                    tu.status,
                    DATE_ADD(tu.data_realizacao, INTERVAL t.reciclagem_periodo MONTH) as expiry_date
                FROM adms_training_users tu
                INNER JOIN adms_users u ON u.id = tu.adms_user_id
                INNER JOIN adms_trainings t ON t.id = tu.adms_training_id
                WHERE tu.status = "concluido" 
                AND t.reciclagem = 1 
                AND t.reciclagem_periodo > 0
                AND tu.data_realizacao IS NOT NULL
                AND DATE_ADD(tu.data_realizacao, INTERVAL t.reciclagem_periodo MONTH) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                AND (tu.last_notification_expiring IS NULL OR tu.last_notification_expiring < DATE_SUB(CURDATE(), INTERVAL 7 DAY))';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(1, $daysAhead, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Busca treinamentos vencidos para notificação
     */
    public function getExpiredTrainingsForNotification(): array
    {
        $sql = 'SELECT 
                    tu.adms_user_id as user_id,
                    tu.adms_training_id as training_id,
                    u.name as user_name,
                    u.email as user_email,
                    t.nome as training_name,
                    t.codigo,
                    t.reciclagem,
                    t.reciclagem_periodo,
                    tu.data_realizacao,
                    tu.status,
                    DATE_ADD(tu.data_realizacao, INTERVAL t.reciclagem_periodo MONTH) as expiry_date
                FROM adms_training_users tu
                INNER JOIN adms_users u ON u.id = tu.adms_user_id
                INNER JOIN adms_trainings t ON t.id = tu.adms_training_id
                WHERE tu.status = "concluido" 
                AND t.reciclagem = 1 
                AND t.reciclagem_periodo > 0
                AND tu.data_realizacao IS NOT NULL
                AND DATE_ADD(tu.data_realizacao, INTERVAL t.reciclagem_periodo MONTH) < CURDATE()
                AND (tu.last_notification_expired IS NULL OR tu.last_notification_expired < DATE_SUB(CURDATE(), INTERVAL 7 DAY))';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Marca como notificado
     */
    public function markAsNotified(int $userId, int $trainingId, string $type): bool
    {
        $column = $type === 'expired' ? 'last_notification_expired' : 'last_notification_expiring';
        $sql = "UPDATE adms_training_users SET {$column} = NOW() WHERE adms_user_id = ? AND adms_training_id = ?";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $trainingId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Calcula o status dinâmico baseado na última aplicação
     */
    private function calculateStatus(array $trainingUser): string
    {
        $dataLimite = $trainingUser['data_limite_primeiro_treinamento'] ?? null;
        $dataRealizacao = $trainingUser['data_realizacao'] ?? null;
        $dataAgendada = $trainingUser['data_agendada'] ?? null;
        $prazoTreinamento = $trainingUser['prazo_treinamento'] ?? null;
        $tipoVinculo = $trainingUser['tipo_vinculo'] ?? 'individual';
        $hoje = date('Y-m-d');

        // 1. Se tem agendamento futuro
        if ($dataAgendada && $dataAgendada > $hoje) {
            return 'agendado';
        }

        // 2. Se realizou o treinamento
        if ($dataRealizacao) {
            return 'concluido';
        }

        // 3. Se não realizou, analisar prazo
        if ($dataLimite) {
            $diasParaPrazo = (strtotime($dataLimite) - strtotime($hoje)) / (60 * 60 * 24);
            $primeiroCiclo = ($tipoVinculo !== 'reciclagem');

            if ($primeiroCiclo && $prazoTreinamento !== null) {
                if ($prazoTreinamento <= 30 && $diasParaPrazo <= 10 && $diasParaPrazo >= 0) {
                    return 'proximo_vencimento';
                } elseif ($prazoTreinamento <= 45 && $diasParaPrazo <= 15 && $diasParaPrazo >= 0) {
                    return 'proximo_vencimento';
                } elseif ($prazoTreinamento > 45 && $diasParaPrazo <= 30 && $diasParaPrazo >= 0) {
                    return 'proximo_vencimento';
                }
            } elseif (!$primeiroCiclo) {
                if ($diasParaPrazo <= 30 && $diasParaPrazo >= 0) {
                    return 'proximo_vencimento';
                }
            }

            if ($hoje > $dataLimite) {
                return 'vencido';
            } else {
                return 'dentro_do_prazo';
            }
        }

        // Caso não tenha data limite definida
        return 'dentro_do_prazo';
    }

    /**
     * Atualiza o status dinâmico de todos os treinamentos
     */
    public function updateDynamicStatuses(): int
    {
        $sql = 'SELECT 
                    tu.*,
                    t.reciclagem,
                    t.reciclagem_periodo
                FROM adms_training_users tu
                INNER JOIN adms_trainings t ON t.id = tu.adms_training_id
                WHERE t.reciclagem = 1 AND t.reciclagem_periodo > 0';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $trainings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $updated = 0;
        foreach ($trainings as $training) {
            $newStatus = $this->calculateStatus($training);
            if ($newStatus !== ($training['status'] ?? 'pendente')) {
                $this->updateStatus($training['adms_user_id'], $training['adms_training_id'], $newStatus);
                $updated++;
            }
        }
        
        return $updated;
    }

    /**
     * Atualiza o status de um treinamento específico
     */
    public function updateStatus(int $userId, int $trainingId, string $status): bool
    {
        try {
            // Captura os dados antigos antes da alteração
            $dadosAntes = $this->getByUserAndTraining($userId, $trainingId);
            
            $sql = 'UPDATE adms_training_users SET status = :status, updated_at = NOW() WHERE adms_user_id = :user_id AND adms_training_id = :training_id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
            $result = $stmt->execute();
            
            // Se atualização bem-sucedida, registra o log de alteração
            if ($result && $dadosAntes) {
                $dadosDepois = array_merge($dadosAntes, ['status' => $status]);
                \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                    'adms_training_users',
                    $userId,
                    $_SESSION['user_id'] ?? 0,
                    'update',
                    $dadosAntes,
                    $dadosDepois
                );
            }
            return $result;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Status do vínculo não atualizado.", [
                'user_id' => $userId,
                'training_id' => $trainingId,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Busca treinamento específico de um usuário
     */
    public function getByUserAndTraining(int $userId, int $trainingId): ?array
    {
        $sql = 'SELECT * FROM adms_training_users WHERE adms_user_id = ? AND adms_training_id = ?';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $trainingId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Aplica um treinamento para um usuário
     */
    public function applyTraining(int $userId, int $trainingId, array $data): bool
    {
        // Garante que data_realizacao tenha data e hora
        if (empty($data['data_realizacao'])) {
            $data['data_realizacao'] = date('Y-m-d H:i:s');
        }
        // Atualiza o vínculo na tabela de usuários
        $sql = 'INSERT INTO adms_training_users (adms_user_id, adms_training_id, data_realizacao, data_agendada, nota, observacoes, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                data_realizacao = VALUES(data_realizacao),
                data_agendada = VALUES(data_agendada),
                nota = VALUES(nota),
                observacoes = VALUES(observacoes),
                status = VALUES(status),
                updated_at = NOW()';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $trainingId, PDO::PARAM_INT);
        $stmt->bindValue(3, $data['data_realizacao'], PDO::PARAM_STR);
        $stmt->bindValue(4, $data['data_agendada'], PDO::PARAM_STR);
        $stmt->bindValue(5, $data['nota'], PDO::PARAM_STR);
        $stmt->bindValue(6, $data['observacoes'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(7, $data['status'], PDO::PARAM_STR);
        $ok = $stmt->execute();
        return $ok;
    }

    /**
     * Retorna todas as aplicações/agendamentos de um usuário para um treinamento
     */
    public function getAllApplications(int $userId, int $trainingId): array
    {
        $sql = 'SELECT * FROM adms_training_users WHERE adms_user_id = ? AND adms_training_id = ? ORDER BY data_realizacao DESC, data_agendada DESC, id DESC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $trainingId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Busca uma aplicação específica pelo id
     */
    public function getById(int $id): ?array
    {
        $sql = 'SELECT * FROM adms_training_users WHERE id = ?';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Sincroniza vínculos obrigatórios para um usuário baseado no cargo
     */
    public function syncUserTrainingLinks(int $userId, int $positionId): bool
    {
        try {
            // Buscar treinamentos obrigatórios para o cargo
            $sql = 'SELECT adms_training_id FROM adms_training_positions 
                    WHERE adms_position_id = ? AND obrigatorio = 1';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(1, $positionId, PDO::PARAM_INT);
            $stmt->execute();
            $requiredTrainings = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            
            if (empty($requiredTrainings)) {
                // Se não há treinamentos obrigatórios, remove todos os vínculos
                $this->deleteByUserAndNotInTrainings($userId, []);
                return true;
            }
            
            // Criar vínculos para treinamentos obrigatórios que não existem
            foreach ($requiredTrainings as $trainingId) {
                $this->insertOrUpdate($userId, $trainingId, 'dentro_do_prazo', 'cargo');
            }
            
            // Remover vínculos de treinamentos que não são mais obrigatórios
            $this->deleteByUserAndNotInTrainings($userId, $requiredTrainings);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Retorna estatísticas resumidas
     */
    public function getSummaryAll(): array
    {
        $this->updateDynamicStatuses();
        $sql = 'SELECT 
                    COUNT(DISTINCT tu.adms_user_id) as total_users,
                    COUNT(*) as total_entries,
                    SUM(CASE WHEN tu.status = "pendente" THEN 1 ELSE 0 END) as pendente_count,
                    SUM(CASE WHEN tu.status = "concluido" THEN 1 ELSE 0 END) as concluido_count,
                    SUM(CASE WHEN tu.status = "vencido" THEN 1 ELSE 0 END) as vencido_count,
                    SUM(CASE WHEN tu.status = "agendado" THEN 1 ELSE 0 END) as agendado_count,
                    SUM(CASE WHEN tu.status = "proximo_vencimento" THEN 1 ELSE 0 END) as proximo_vencimento_count,
                    SUM(CASE WHEN tu.status = "em_dia" THEN 1 ELSE 0 END) as em_dia_count
                FROM adms_training_users tu';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        return [
            'total' => $row['total_entries'] ?? 0,
            'concluidos' => $row['concluido_count'] ?? 0,
            'pendentes' => $row['pendente_count'] ?? 0,
            'vencidos' => $row['vencido_count'] ?? 0,
            'agendados' => $row['agendado_count'] ?? 0,
            'proximo_vencimento' => $row['proximo_vencimento_count'] ?? 0,
            'em_dia' => $row['em_dia_count'] ?? 0,
        ];
    }

    public function getSummaryMandatory(): array
    {
        $this->updateDynamicStatuses();
        $sql = 'SELECT 
                    COUNT(DISTINCT tu.adms_user_id) as total_users,
                    COUNT(*) as total_entries,
                    SUM(CASE WHEN tu.status = "pendente" THEN 1 ELSE 0 END) as pendente_count,
                    SUM(CASE WHEN tu.status = "concluido" THEN 1 ELSE 0 END) as concluido_count,
                    SUM(CASE WHEN tu.status = "vencido" THEN 1 ELSE 0 END) as vencido_count,
                    SUM(CASE WHEN tu.status = "agendado" THEN 1 ELSE 0 END) as agendado_count,
                    SUM(CASE WHEN tu.status = "proximo_vencimento" THEN 1 ELSE 0 END) as proximo_vencimento_count,
                    SUM(CASE WHEN tu.status = "em_dia" THEN 1 ELSE 0 END) as em_dia_count
                FROM adms_training_users tu
                INNER JOIN adms_users u ON u.id = tu.adms_user_id
                INNER JOIN adms_positions p ON u.user_position_id = p.id
                INNER JOIN adms_trainings t ON t.id = tu.adms_training_id
                INNER JOIN adms_training_positions tp ON tp.adms_training_id = t.id 
                    AND tp.adms_position_id = u.user_position_id 
                    AND tp.obrigatorio = 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        return [
            'total' => $row['total_entries'] ?? 0,
            'concluidos' => $row['concluido_count'] ?? 0,
            'pendentes' => $row['pendente_count'] ?? 0,
            'vencidos' => $row['vencido_count'] ?? 0,
            'agendados' => $row['agendado_count'] ?? 0,
            'proximo_vencimento' => $row['proximo_vencimento_count'] ?? 0,
            'em_dia' => $row['em_dia_count'] ?? 0,
        ];
    }

    /**
     * Retorna treinamentos vencidos ou próximos do vencimento
     */
    public function getExpiringTrainings(int $daysAhead = 30): array
    {
        $sql = 'SELECT 
                    u.id as user_id,
                    u.name as user_name,
                    u.email as user_email,
                    d.name as department,
                    p.name as position,
                    t.id as training_id,
                    t.nome as training_name,
                    t.codigo as training_code,
                    tu.status,
                    tu.created_at as vinculo_created_at,
                    ta.data_realizacao,
                    ta.data_agendada
                FROM adms_training_users tu
                INNER JOIN adms_users u ON u.id = tu.adms_user_id
                INNER JOIN adms_departments d ON u.user_department_id = d.id
                INNER JOIN adms_positions p ON u.user_position_id = p.id
                INNER JOIN adms_trainings t ON t.id = tu.adms_training_id
                INNER JOIN adms_training_positions tp ON tp.adms_training_id = t.id 
                    AND tp.adms_position_id = u.user_position_id 
                    AND tp.obrigatorio = 1
                LEFT JOIN adms_training_applications ta ON ta.adms_user_id = tu.adms_user_id 
                    AND ta.adms_training_id = tu.adms_training_id
                    AND ta.created_at >= tu.created_at
                WHERE tu.status IN ("vencido", "proximo_vencimento")
                ORDER BY tu.status DESC, u.name ASC, t.nome ASC';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Marca um vínculo como concluído e cria novo ciclo se necessário
     */
    public function markAsCompleted(
        int $userId,
        int $trainingId,
        bool $createNewCycle = true,
        bool $reprovado = false // novo parâmetro
    ): bool
    {
        try {
            $this->getConnection()->beginTransaction();

            if ($reprovado) {
                // NÃO marcar como concluído!
                // Atualizar motivo e prazo para retreinamento, manter status ativo
                $sqlPrazo = "SELECT prazo_treinamento FROM adms_trainings WHERE id = ?";
                $stmtPrazo = $this->getConnection()->prepare($sqlPrazo);
                $stmtPrazo->bindValue(1, $trainingId, PDO::PARAM_INT);
                $stmtPrazo->execute();
                $prazo = (int)($stmtPrazo->fetchColumn() ?? 0);
                $dataLimite = (new \DateTime())->modify("+{$prazo} days")->format('Y-m-d');
                $sql = 'UPDATE adms_training_users SET status = "dentro_do_prazo", motivo = "retreinamento", data_limite_primeiro_treinamento = :dataLimite, updated_at = NOW() WHERE adms_user_id = :userId AND adms_training_id = :trainingId';
                $stmt = $this->getConnection()->prepare($sql);
                $stmt->bindValue(':dataLimite', $dataLimite, PDO::PARAM_STR);
                $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
                $stmt->bindValue(':trainingId', $trainingId, PDO::PARAM_INT);
                $stmt->execute();
                // NÃO marcar como concluído e não criar novo ciclo!
            } else {
                // Marcar vínculo atual como concluído
                $sql = 'UPDATE adms_training_users SET status = "concluido", updated_at = NOW() 
                        WHERE adms_user_id = ? AND adms_training_id = ?';
                $stmt = $this->getConnection()->prepare($sql);
                $stmt->bindValue(1, $userId, PDO::PARAM_INT);
                $stmt->bindValue(2, $trainingId, PDO::PARAM_INT);
                $stmt->execute();

                // Se deve criar novo ciclo e o treinamento tem reciclagem
                if ($createNewCycle) {
                    $sql = 'SELECT reciclagem, reciclagem_periodo, prazo_treinamento FROM adms_trainings WHERE id = ?';
                    $stmt = $this->getConnection()->prepare($sql);
                    $stmt->bindValue(1, $trainingId, PDO::PARAM_INT);
                    $stmt->execute();
                    $training = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($training && $training['reciclagem'] && $training['reciclagem_periodo']) {
                        // Novo ciclo por aprovação: data_realizacao + reciclagem_periodo
                        $sqlUltima = 'SELECT data_realizacao FROM adms_training_users WHERE adms_user_id = ? AND adms_training_id = ? AND status = "concluido" ORDER BY data_realizacao DESC LIMIT 1';
                        $stmtUltima = $this->getConnection()->prepare($sqlUltima);
                        $stmtUltima->bindValue(1, $userId, PDO::PARAM_INT);
                        $stmtUltima->bindValue(2, $trainingId, PDO::PARAM_INT);
                        $stmtUltima->execute();
                        $dataRealizacao = $stmtUltima->fetchColumn();

                        if ($dataRealizacao) {
                            $sqlTipoVinculo = 'SELECT tipo_vinculo FROM adms_training_users WHERE adms_user_id = ? AND adms_training_id = ? ORDER BY id DESC LIMIT 1';
                            $stmtTipoVinculo = $this->getConnection()->prepare($sqlTipoVinculo);
                            $stmtTipoVinculo->bindValue(1, $userId, PDO::PARAM_INT);
                            $stmtTipoVinculo->bindValue(2, $trainingId, PDO::PARAM_INT);
                            $stmtTipoVinculo->execute();
                            $tipoVinculo = $stmtTipoVinculo->fetchColumn() ?: 'reciclagem';
                            $dataLimite = (new \DateTime($dataRealizacao))->modify('+' . $training['reciclagem_periodo'] . ' months')->format('Y-m-d');
                            $this->createNewCycle($userId, $trainingId, 'dentro_do_prazo', $tipoVinculo, 'reciclagem', $dataLimite);
                        }
                    }
                }
            }
            
            $this->getConnection()->commit();
            return true;
        } catch (\Exception $e) {
            $this->getConnection()->rollBack();
            return false;
        }
    }

    /**
     * Retorna todos os vínculos obrigatórios de treinamentos por colaborador, ordenados por nome
     */
    public function getMandatoryMatrixByUser(array $filters = [], int $limit = 10, int $offset = 0): array
    {
        $sql = 'SELECT 
                u.id as user_id,
                u.name as user_name,
                d.name as department,
                p.name as position,
                t.id as training_id,
                t.nome as training_name,
                t.codigo,
                t.versao as training_version,
                t.reciclagem,
                t.reciclagem_periodo,
                t.prazo_treinamento,
                tu.status,
                tu.tipo_vinculo,
                tu.created_at as vinculo_created_at,
                tu.data_limite_primeiro_treinamento
            FROM adms_training_users tu
            INNER JOIN adms_users u ON u.id = tu.adms_user_id
            INNER JOIN adms_departments d ON u.user_department_id = d.id
            INNER JOIN adms_positions p ON u.user_position_id = p.id
            INNER JOIN adms_trainings t ON t.id = tu.adms_training_id
            WHERE t.ativo = 1';
        $params = [];
        // Apenas aplica filtros se eles forem explicitamente passados
        if (!empty($filters['colaborador'])) {
            $sql .= ' AND u.id = ?';
            $params[] = $filters['colaborador'];
        }
        if (!empty($filters['departamento'])) {
            $sql .= ' AND d.id = ?';
            $params[] = $filters['departamento'];
        }
        if (!empty($filters['cargo'])) {
            $sql .= ' AND p.id = ?';
            $params[] = $filters['cargo'];
        }
        if (!empty($filters['treinamento'])) {
            $sql .= ' AND t.id = ?';
            $params[] = $filters['treinamento'];
        }
        if (!empty($filters['tipo_vinculo'])) {
            $sql .= ' AND tu.tipo_vinculo = ?';
            $params[] = $filters['tipo_vinculo'];
        }
        $sql .= ' ORDER BY u.name ASC, t.nome ASC';
        $sql .= ' LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // Buscar dados da última aplicação para cada vínculo
        $appRepo = new \App\adms\Models\Repository\TrainingApplicationsRepository();
        foreach ($results as &$result) {
            $history = $appRepo->getHistoryAfter(
                $result['user_id'],
                $result['training_id'],
                $result['vinculo_created_at']
            );
            $lastApplication = $history[0] ?? null;
            $result['data_realizacao'] = $lastApplication['data_realizacao'] ?? null;
            $result['data_agendada'] = $lastApplication['data_agendada'] ?? null;
            $result['nota'] = $lastApplication['nota'] ?? null;
            $result['observacoes'] = $lastApplication['observacoes'] ?? null;
            $result['instrutor_nome'] = $lastApplication['instrutor_nome'] ?? null;
            $result['instrutor_email'] = $lastApplication['instrutor_email'] ?? null;
            $result['aplicado_por'] = $lastApplication['aplicado_por'] ?? null;
            $result['application_id'] = $lastApplication['id'] ?? null;
            $result['status_dinamico'] = $this->calculateStatus($result);
        }
        unset($result);
        return $results;
    }

    /**
     * Retorna contagem de treinamentos por status (para gráfico de pizza)
     */
    public function getStatusCounts(): array
    {
        $sql = 'SELECT status, COUNT(*) as count FROM adms_training_users GROUP BY status';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $counts = [
            'pendente' => 0,
            'em_dia' => 0,
            'proximo_vencimento' => 0,
            'vencido' => 0,
            'concluido' => 0
        ];
        foreach ($result as $row) {
            $counts[$row['status']] = (int)$row['count'];
        }
        return $counts;
    }

    /**
     * Retorna quantidade de treinamentos realizados por mês (últimos 12 meses)
     */
    public function getMonthlyRealizations(): array
    {
        $sql = "SELECT DATE_FORMAT(data_realizacao, '%Y-%m') as mes, COUNT(*) as total FROM adms_training_applications WHERE data_realizacao IS NOT NULL AND data_realizacao >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY mes ORDER BY mes ASC";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data = [];
        foreach ($result as $row) {
            $data[$row['mes']] = (int)$row['total'];
        }
        return $data;
    }

    /**
     * Retorna top 5 usuários com mais treinamentos pendentes
     */
    public function getTopPendingUsers(): array
    {
        $sql = "SELECT u.id as user_id, u.name, COUNT(*) as pendentes FROM adms_training_users tu INNER JOIN adms_users u ON u.id = tu.adms_user_id WHERE tu.status = 'pendente' GROUP BY u.id ORDER BY pendentes DESC, u.name ASC LIMIT 5";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna top 5 treinamentos com mais pendências/vencidos
     */
    public function getTopCriticalTrainings(): array
    {
        // Nota: MySQL não permite referenciar aliases de agregação dentro de expressões no ORDER BY.
        // Repetimos as expressões de agregação para ordenar pela soma de pendentes + vencidos.
        $sql = "SELECT 
                    t.id as training_id, 
                    t.nome as training_name, 
                    SUM(CASE WHEN tu.status = 'pendente' THEN 1 ELSE 0 END) as pendentes, 
                    SUM(CASE WHEN tu.status = 'vencido' THEN 1 ELSE 0 END) as vencidos 
                FROM adms_training_users tu 
                INNER JOIN adms_trainings t ON t.id = tu.adms_training_id 
                GROUP BY t.id 
                ORDER BY 
                    (SUM(CASE WHEN tu.status = 'pendente' THEN 1 ELSE 0 END) + 
                     SUM(CASE WHEN tu.status = 'vencido' THEN 1 ELSE 0 END)) DESC, 
                    t.nome ASC 
                LIMIT 5";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vincula múltiplos usuários a um treinamento, evitando duplicidade
     */
    public function vincularUsuariosTreinamento(int $trainingId, array $userIds): void
    {
        // Log de debug
        \App\adms\Helpers\GenerateLog::generateLog(
            "debug", 
            "vincularUsuariosTreinamento - Iniciando", 
            [
                'trainingId' => $trainingId,
                'userIds' => $userIds,
                'count_userIds' => count($userIds)
            ]
        );
        
        $positionsRepo = new \App\adms\Models\Repository\TrainingPositionsRepository();
        $cargosObrigatorios = $positionsRepo->getPositionIdsByTraining($trainingId);
        $usersRepo = new \App\adms\Models\Repository\UsersRepository();
        
        // Log dos cargos obrigatórios
        \App\adms\Helpers\GenerateLog::generateLog(
            "debug", 
            "vincularUsuariosTreinamento - Cargos obrigatórios", 
            [
                'trainingId' => $trainingId,
                'cargosObrigatorios' => $cargosObrigatorios
            ]
        );
        
        foreach ($userIds as $userId) {
            // Log para cada usuário
            \App\adms\Helpers\GenerateLog::generateLog(
                "debug", 
                "vincularUsuariosTreinamento - Processando usuário", 
                [
                    'trainingId' => $trainingId,
                    'userId' => $userId
                ]
            );
            
            $user = $usersRepo->getUser($userId);
            
            // Log dos dados do usuário
            \App\adms\Helpers\GenerateLog::generateLog(
                "debug", 
                "vincularUsuariosTreinamento - Dados do usuário", 
                [
                    'userId' => $userId,
                    'user' => $user ? [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'user_position_id' => $user['user_position_id'] ?? null
                    ] : null
                ]
            );
            
            // Verifica se já existe vínculo ativo
            $sqlCheck = "SELECT tipo_vinculo FROM adms_training_users WHERE adms_user_id = :user_id AND adms_training_id = :training_id AND status != 'concluido'";
            $stmtCheck = $this->getConnection()->prepare($sqlCheck);
            $stmtCheck->bindValue(':user_id', $userId, \PDO::PARAM_INT);
            $stmtCheck->bindValue(':training_id', $trainingId, \PDO::PARAM_INT);
            $stmtCheck->execute();
            $vinculoExistente = $stmtCheck->fetch(\PDO::FETCH_ASSOC);
            
            // Log da verificação de vínculo existente
            \App\adms\Helpers\GenerateLog::generateLog(
                "debug", 
                "vincularUsuariosTreinamento - Verificação de vínculo", 
                [
                    'userId' => $userId,
                    'trainingId' => $trainingId,
                    'vinculoExistente' => $vinculoExistente,
                    'user_position_id' => $user['user_position_id'] ?? null,
                    'is_cargo_obrigatorio' => in_array($user['user_position_id'], $cargosObrigatorios)
                ]
            );

            if (in_array($user['user_position_id'], $cargosObrigatorios)) {
                // Usuário tem cargo obrigatório - sempre criar/atualizar para 'cargo'
                \App\adms\Helpers\GenerateLog::generateLog(
                    "debug", 
                    "vincularUsuariosTreinamento - Criando/atualizando vínculo por cargo", 
                    [
                        'userId' => $userId,
                        'trainingId' => $trainingId,
                        'tipo' => 'cargo',
                        'vinculo_existente' => $vinculoExistente
                    ]
                );
                $this->insertOrUpdate((int)$userId, (int)$trainingId, 'dentro_do_prazo', 'cargo');
            } else {
                // Usuário não tem cargo obrigatório - só criar individual se não existir vínculo
                if (!$vinculoExistente) {
                    \App\adms\Helpers\GenerateLog::generateLog(
                        "debug", 
                        "vincularUsuariosTreinamento - Criando vínculo individual", 
                        [
                            'userId' => $userId,
                            'trainingId' => $trainingId,
                            'tipo' => 'individual'
                        ]
                    );
                    $this->insertOrUpdate((int)$userId, (int)$trainingId, 'dentro_do_prazo', 'individual');
                } else {
                    \App\adms\Helpers\GenerateLog::generateLog(
                        "debug", 
                        "vincularUsuariosTreinamento - Vínculo já existe, ignorando", 
                        [
                            'userId' => $userId,
                            'trainingId' => $trainingId,
                            'vinculo_existente' => $vinculoExistente
                        ]
                    );
                }
            }
            // Atualizar matriz do usuário após alteração
            if ($user) {
                $this->recreateLinksForUser($userId, $user['user_position_id']);
            }
        }
        
        // Log final
        \App\adms\Helpers\GenerateLog::generateLog(
            "info", 
            "vincularUsuariosTreinamento - Concluído", 
            [
                'trainingId' => $trainingId,
                'userIds' => $userIds,
                'count_userIds' => count($userIds)
            ]
        );
    }

    /**
     * Retorna os usuários já vinculados a um treinamento
     */
    public function getUsuariosVinculados($trainingId)
    {
        // Vínculos diretos
        $sqlDireto = "SELECT u.id, u.name, u.email, 'direto' as tipo
            FROM adms_training_users tu
            INNER JOIN adms_users u ON u.id = tu.adms_user_id
            WHERE tu.adms_training_id = :training_id";

        // Vínculos por cargo (sem vínculo direto)
        $sqlCargo = "SELECT u.id, u.name, u.email, 'cargo' as tipo
            FROM adms_users u
            INNER JOIN adms_training_positions tp ON tp.adms_position_id = u.user_position_id
            WHERE tp.adms_training_id = :training_id
            AND u.id NOT IN (
                SELECT adms_user_id FROM adms_training_users WHERE adms_training_id = :training_id
            )";

        $stmtDireto = $this->getConnection()->prepare($sqlDireto);
        $stmtDireto->bindValue(':training_id', $trainingId, \PDO::PARAM_INT);
        $stmtDireto->execute();
        $diretos = $stmtDireto->fetchAll(\PDO::FETCH_ASSOC);

        $stmtCargo = $this->getConnection()->prepare($sqlCargo);
        $stmtCargo->bindValue(':training_id', $trainingId, \PDO::PARAM_INT);
        $stmtCargo->execute();
        $cargos = $stmtCargo->fetchAll(\PDO::FETCH_ASSOC);

        return array_merge($diretos, $cargos);
    }

    /**
     * Retorna todos os colaboradores vinculados ao treinamento:
     * - Por cargo obrigatório: todos os usuários com tipo_vinculo = 'cargo'
     * - Direto: todos os usuários com tipo_vinculo = 'individual'
     * Adiciona o nome do cargo ao lado do nome
     */
    public function getAllVinculadosPorTreinamento($trainingId)
    {
        // Buscar todos os vínculos individuais
        $sqlIndividuais = "SELECT tu.adms_user_id as id, u.name, u.email, 'individual' as tipo, p.name as cargo_nome, d.name as department_nome
            FROM adms_training_users tu
            INNER JOIN adms_users u ON u.id = tu.adms_user_id
            INNER JOIN adms_positions p ON p.id = u.user_position_id
            INNER JOIN adms_departments d ON d.id = u.user_department_id
            WHERE tu.adms_training_id = :training_id AND tu.tipo_vinculo = 'individual'";
        $stmtIndividuais = $this->getConnection()->prepare($sqlIndividuais);
        $stmtIndividuais->bindValue(':training_id', $trainingId, \PDO::PARAM_INT);
        $stmtIndividuais->execute();
        $individuais = $stmtIndividuais->fetchAll(\PDO::FETCH_ASSOC);
        $idsIndividuais = array_column($individuais, 'id');

        // Buscar vínculos por cargo, excluindo quem já tem vínculo individual
        $sqlCargo = "SELECT u.id, u.name, u.email, 'cargo' as tipo, p.name as cargo_nome, d.name as department_nome
            FROM adms_users u
            INNER JOIN adms_positions p ON p.id = u.user_position_id
            INNER JOIN adms_departments d ON d.id = u.user_department_id
            INNER JOIN adms_training_positions tp ON tp.adms_position_id = u.user_position_id
            WHERE tp.adms_training_id = :training_id
            AND tp.obrigatorio = 1
            " . (count($idsIndividuais) ? ("AND u.id NOT IN (" . implode(',', $idsIndividuais) . ")") : "") .
            " ORDER BY u.name ASC";
        $stmtCargo = $this->getConnection()->prepare($sqlCargo);
        $stmtCargo->bindValue(':training_id', $trainingId, \PDO::PARAM_INT);
        $stmtCargo->execute();
        $cargos = $stmtCargo->fetchAll(\PDO::FETCH_ASSOC);

        // Buscar dados do treinamento
        $sqlTreinamento = "SELECT id, nome, codigo, reciclagem, reciclagem_periodo FROM adms_trainings WHERE id = :training_id";
        $stmtTreinamento = $this->getConnection()->prepare($sqlTreinamento);
        $stmtTreinamento->bindValue(':training_id', $trainingId, \PDO::PARAM_INT);
        $stmtTreinamento->execute();
        $treinamento = $stmtTreinamento->fetch(\PDO::FETCH_ASSOC);

        // Unir e padronizar os campos para a view
        $todos = array_merge($individuais, $cargos);
        $padronizados = [];
        foreach ($todos as $item) {
            $padronizados[] = [
                'user_id' => $item['id'],
                'user_name' => $item['name'],
                'department' => $item['department_nome'] ?? '',
                'position' => $item['cargo_nome'] ?? '',
                'training_id' => $treinamento['id'],
                'training_name' => $treinamento['nome'],
                'codigo' => $treinamento['codigo'],
                'reciclagem' => $treinamento['reciclagem'],
                'reciclagem_periodo' => $treinamento['reciclagem_periodo'] ?? '',
                // Compatibilidade com a view:
                'id' => $item['id'],
                'name' => $item['name'],
                'cargo_nome' => $item['cargo_nome'] ?? '',
                'tipo' => $item['tipo'] ?? '',
                'email' => $item['email'] ?? '',
            ];
        }
        usort($padronizados, function($a, $b) {
            return strcasecmp($a['user_name'], $b['user_name']);
        });
        return $padronizados;
    }

    /**
     * Remove o vínculo individual de um usuário em um treinamento
     */
    public function deleteIndividualVinculo(int $trainingId, int $userId): void
    {
        try {
            // Captura os dados antigos antes da exclusão
            $dadosAntes = $this->getByUserAndTraining($userId, $trainingId);
            
            $sql = 'DELETE FROM adms_training_users WHERE adms_training_id = :training_id AND adms_user_id = :user_id AND tipo_vinculo = \'individual\' AND status != \'concluido\'';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $result = $stmt->execute();
            
            // Se exclusão bem-sucedida, registra o log de alteração
            if ($result && $dadosAntes) {
                \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                    'adms_training_users',
                    $userId,
                    $_SESSION['user_id'] ?? 0,
                    'delete',
                    $dadosAntes,
                    []
                );
            }
            // Atualizar matriz do usuário após alteração
            $user = (new \App\adms\Models\Repository\UsersRepository())->getUser($userId);
            if ($user) {
                $this->recreateLinksForUser($userId, $user['user_position_id']);
            }
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Vínculo individual não excluído.", [
                'user_id' => $userId,
                'training_id' => $trainingId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Retorna matriz de treinamentos concluídos por colaborador, paginada
     */
    public function getCompletedTrainingsMatrixPaginated(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $sql = 'SELECT 
                    u.id as user_id,
                    u.name as user_name,
                    t.id as training_id,
                    t.nome as training_name,
                    t.codigo as training_code,
                    t.versao as training_version,
                    ta.data_realizacao,
                    ta.data_avaliacao,
                    t.carga_horaria,
                    ta.instrutor_nome,
                    ta.instructor_user_id,
                    u2.name as instructor_user_name,
                    ta.nota,
                    ta.observacoes
                FROM adms_training_applications ta
                INNER JOIN adms_users u ON u.id = ta.adms_user_id
                INNER JOIN adms_trainings t ON t.id = ta.adms_training_id
                LEFT JOIN adms_users u2 ON u2.id = ta.instructor_user_id
                WHERE ta.status = "concluido"';
        $params = [];
        if (!empty($filters['colaborador'])) {
            $sql .= ' AND u.id = ?';
            $params[] = $filters['colaborador'];
        }
        if (!empty($filters['treinamento'])) {
            $sql .= ' AND t.id = ?';
            $params[] = $filters['treinamento'];
        }
        if (!empty($filters['mes'])) {
            $sql .= ' AND MONTH(ta.data_realizacao) = ?';
            $params[] = $filters['mes'];
        }
        if (!empty($filters['ano'])) {
            $sql .= ' AND YEAR(ta.data_realizacao) = ?';
            $params[] = $filters['ano'];
        }
        $allowedSort = [
            'user_name' => 'u.name',
            'training_name' => 't.nome',
            'training_code' => 't.codigo',
            'data_realizacao' => 'ta.data_realizacao',
            'data_avaliacao' => 'ta.data_avaliacao',
            'carga_horaria' => 't.carga_horaria',
            'instrutor_nome' => 'ta.instrutor_nome',
            'nota' => 'ta.nota',
            'observacoes' => 'ta.observacoes',
        ];
        $sort = $filters['sort'] ?? null;
        $order = strtolower($filters['order'] ?? 'asc');
        $order = ($order === 'desc') ? 'DESC' : 'ASC';
        if ($sort && isset($allowedSort[$sort])) {
            $sql .= ' ORDER BY ' . $allowedSort[$sort] . ' ' . $order . ', u.name ASC, ta.data_realizacao DESC';
        } else {
            $sql .= ' ORDER BY u.name ASC, ta.data_realizacao DESC';
        }
        $offset = max(0, ($page - 1) * $perPage);
        $perPage = max(1, (int)$perPage);
        $sql .= ' LIMIT ' . $perPage . ' OFFSET ' . $offset;
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        // Total de registros (sem paginação)
        $sqlCount = 'SELECT COUNT(*) as total
            FROM adms_training_applications ta
            INNER JOIN adms_users u ON u.id = ta.adms_user_id
            INNER JOIN adms_trainings t ON t.id = ta.adms_training_id
            WHERE ta.status = "concluido"';
        $paramsCount = [];
        if (!empty($filters['colaborador'])) {
            $sqlCount .= ' AND u.id = ?';
            $paramsCount[] = $filters['colaborador'];
        }
        if (!empty($filters['treinamento'])) {
            $sqlCount .= ' AND t.id = ?';
            $paramsCount[] = $filters['treinamento'];
        }
        if (!empty($filters['mes'])) {
            $sqlCount .= ' AND MONTH(ta.data_realizacao) = ?';
            $paramsCount[] = $filters['mes'];
        }
        if (!empty($filters['ano'])) {
            $sqlCount .= ' AND YEAR(ta.data_realizacao) = ?';
            $paramsCount[] = $filters['ano'];
        }
        $stmtCount = $this->getConnection()->prepare($sqlCount);
        $stmtCount->execute($paramsCount);
        $total = (int)($stmtCount->fetch(\PDO::FETCH_ASSOC)['total'] ?? 0);
        return [
            'data' => $data,
            'total' => $total,
        ];
    }

    /**
     * Retorna resumo de estatísticas para a Matriz de Treinamentos Realizados
     */
    public function getCompletedTrainingsSummary(array $filters = []): array
    {
        // Query para estatísticas dos treinamentos realizados
        $sql = 'SELECT 
                    COUNT(DISTINCT ta.adms_user_id) as total_colaboradores,
                    COUNT(*) as total_treinamentos,
                    SUM(CASE WHEN ta.nota >= 7 THEN 1 ELSE 0 END) as total_aprovados,
                    SUM(CASE WHEN ta.nota < 7 AND ta.nota IS NOT NULL THEN 1 ELSE 0 END) as total_reprovados,
                    AVG(ta.nota) as media_nota
                FROM adms_training_applications ta
                INNER JOIN adms_users u ON u.id = ta.adms_user_id
                INNER JOIN adms_trainings t ON t.id = ta.adms_training_id
                WHERE ta.status = "concluido"';
        
        $params = [];
        if (!empty($filters['colaborador'])) {
            $sql .= ' AND u.id = ?';
            $params[] = $filters['colaborador'];
        }
        if (!empty($filters['treinamento'])) {
            $sql .= ' AND t.id = ?';
            $params[] = $filters['treinamento'];
        }
        if (!empty($filters['mes'])) {
            $sql .= ' AND MONTH(ta.data_realizacao) = ?';
            $params[] = $filters['mes'];
        }
        if (!empty($filters['ano'])) {
            $sql .= ' AND YEAR(ta.data_realizacao) = ?';
            $params[] = $filters['ano'];
        }
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // Calcular total de horas separadamente para campos TIME
        $sqlHoras = 'SELECT t.carga_horaria
                    FROM adms_training_applications ta
                    INNER JOIN adms_users u ON u.id = ta.adms_user_id
                    INNER JOIN adms_trainings t ON t.id = ta.adms_training_id
                    WHERE ta.status = "concluido"';
        
        if (!empty($filters['colaborador'])) {
            $sqlHoras .= ' AND u.id = ?';
        }
        if (!empty($filters['treinamento'])) {
            $sqlHoras .= ' AND t.id = ?';
        }
        if (!empty($filters['mes'])) {
            $sqlHoras .= ' AND MONTH(ta.data_realizacao) = ?';
        }
        if (!empty($filters['ano'])) {
            $sqlHoras .= ' AND YEAR(ta.data_realizacao) = ?';
        }
        
        $stmtHoras = $this->getConnection()->prepare($sqlHoras);
        $stmtHoras->execute($params);
        $horas = $stmtHoras->fetchAll(\PDO::FETCH_COLUMN);
        
        // Calcular total de horas em formato HH:MM
        $totalMinutos = 0;
        foreach ($horas as $hora) {
            if (!empty($hora)) {
                $partes = explode(':', $hora);
                if (count($partes) >= 2) {
                    $totalMinutos += (int)$partes[0] * 60 + (int)$partes[1];
                }
            }
        }
        
        $horasTotal = floor($totalMinutos / 60);
        $minutosTotal = $totalMinutos % 60;
        $totalHorasFormatado = sprintf('%02d:%02d', $horasTotal, $minutosTotal);
        
        return [
            'total_colaboradores' => (int)($result['total_colaboradores'] ?? 0),
            'total_treinamentos' => (int)($result['total_treinamentos'] ?? 0),
            'total_aprovados' => (int)($result['total_aprovados'] ?? 0),
            'total_reprovados' => (int)($result['total_reprovados'] ?? 0),
            'media_nota' => round((float)($result['media_nota'] ?? 0), 1),
            'total_horas' => $totalHorasFormatado
        ];
    }

    public function createNewCycle(
        int $userId,
        int $trainingId,
        string $status,
        string $tipoVinculo,
        string $motivo,
        string $dataLimite
    ): void
    {
        $sql = 'INSERT INTO adms_training_users (adms_user_id, adms_training_id, status, tipo_vinculo, motivo, created_at, updated_at, data_limite_primeiro_treinamento)
                VALUES (:user_id, :training_id, :status, :tipo_vinculo, :motivo, NOW(), NOW(), :data_limite)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':tipo_vinculo', $tipoVinculo, PDO::PARAM_STR);
        $stmt->bindValue(':motivo', $motivo, PDO::PARAM_STR);
        $stmt->bindValue(':data_limite', $dataLimite, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function removeActiveLinksByUser($userId) {
        $sql = "DELETE FROM adms_training_users WHERE adms_user_id = :user_id AND status != 'concluido'";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function removeActiveLinksByCargoAndTraining($cargoId, $trainingId) {
        $sql = "DELETE tu FROM adms_training_users tu
                INNER JOIN adms_users u ON u.id = tu.adms_user_id
                WHERE u.user_position_id = :cargo_id AND tu.adms_training_id = :training_id AND tu.tipo_vinculo = 'cargo' AND tu.status != 'concluido'";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':cargo_id', $cargoId, PDO::PARAM_INT);
        $stmt->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function removeActiveLinksByTraining($trainingId) {
        $sql = "DELETE FROM adms_training_users WHERE adms_training_id = :training_id AND status != 'concluido'";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function getLastCompletedTraining($userId, $trainingId) {
        $sql = "SELECT * FROM adms_training_users WHERE adms_user_id = :user_id AND adms_training_id = :training_id AND status = 'concluido' ORDER BY data_realizacao DESC, updated_at DESC LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function isReciclagemVencida($dataRealizacao, $reciclagemPeriodo) {
        if (!$dataRealizacao || !$reciclagemPeriodo) return true;
        $dataVencimento = (new \DateTime($dataRealizacao))->modify('+' . $reciclagemPeriodo . ' months');
        return (new \DateTime() > $dataVencimento);
    }

    public function recreateLinksForUser($userId, $userPositionId) {
        $trainingPositionsRepo = new \App\adms\Models\Repository\TrainingPositionsRepository();
        $trainingsRepo = new \App\adms\Models\Repository\TrainingsRepository();
        $mandatoryTrainings = $trainingPositionsRepo->getTrainingsByPosition($userPositionId);
        foreach ($mandatoryTrainings as $trainingId) {
            $lastCompleted = $this->getLastCompletedTraining($userId, $trainingId);
            $training = $trainingsRepo->getTraining($trainingId);
            if ($lastCompleted && $training['reciclagem']) {
                if ($this->isReciclagemVencida($lastCompleted['data_realizacao'], $training['reciclagem_periodo'])) {
                    $this->insertOrUpdate($userId, $trainingId, 'dentro_do_prazo', 'cargo', null, 'reciclagem');
                }
            } elseif (!$lastCompleted) {
                $this->insertOrUpdate($userId, $trainingId, 'dentro_do_prazo', 'cargo');
            }
        }
    }

    public function recreateLinksForTraining($trainingId) {
        $trainingPositionsRepo = new \App\adms\Models\Repository\TrainingPositionsRepository();
        $positions = $trainingPositionsRepo->getPositionsByTraining($trainingId);
        $usersRepo = new \App\adms\Models\Repository\UsersRepository();
        foreach ($positions as $pos) {
            $users = $usersRepo->getUsersByPosition($pos['adms_position_id']);
            foreach ($users as $user) {
                $this->recreateLinksForUser($user['id'], $pos['adms_position_id']);
            }
        }
    }
} 