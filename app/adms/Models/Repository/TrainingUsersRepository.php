<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Helpers\GenerateLog;
use PDO;
use Exception;

class TrainingUsersRepository extends DbConnection
{
    /**
     * Garante tabela de backup para vínculos removidos por deduplicação.
     */
    private function ensureTrainingUsersDedupeBackupTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS adms_training_users_dedup_backup LIKE adms_training_users";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
    }

    /**
     * Realiza backup dos registros de vínculo que serão removidos.
     */
    private function backupRowsForDedupeByUserTraining(int $userId, int $trainingId, int $keeperId): void
    {
        $this->ensureTrainingUsersDedupeBackupTable();
        $sql = "INSERT IGNORE INTO adms_training_users_dedup_backup
                SELECT *
                FROM adms_training_users
                WHERE adms_user_id = :user_id
                  AND adms_training_id = :training_id
                  AND id <> :keeper_id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
        $stmt->bindValue(':keeper_id', $keeperId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Backup em lote para deduplicação por usuário+treinamento.
     */
    private function backupRowsForGlobalDedupe(?int $trainingId = null): void
    {
        $this->ensureTrainingUsersDedupeBackupTable();
        $trainingFilter = '';
        if ($trainingId !== null) {
            $trainingFilter = ' AND d.adms_training_id = :training_id_filter';
        }

        $sql = "INSERT IGNORE INTO adms_training_users_dedup_backup
                SELECT tu.*
                FROM adms_training_users tu
                INNER JOIN (
                    SELECT
                        adms_user_id,
                        adms_training_id,
                        COALESCE(
                            MAX(CASE WHEN status <> 'concluido' THEN id END),
                            MAX(id)
                        ) AS keep_id
                    FROM adms_training_users
                    GROUP BY adms_user_id, adms_training_id
                    HAVING COUNT(*) > 1
                ) d
                        ON d.adms_user_id = tu.adms_user_id
                       AND d.adms_training_id = tu.adms_training_id
                WHERE tu.id <> d.keep_id{$trainingFilter}";
        $stmt = $this->getConnection()->prepare($sql);
        if ($trainingId !== null) {
            $stmt->bindValue(':training_id_filter', $trainingId, PDO::PARAM_INT);
        }
        $stmt->execute();
    }

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
        try {
            // Determinar prazo pelo tipo_treinamento no vínculo por cargo (Inicial=90, Continuo=365)
            $prazoDias = 90; // default
            try {
                // Buscar cargo do usuário
                $stmtUser = $this->getConnection()->prepare('SELECT user_position_id FROM adms_users WHERE id = :uid');
                $stmtUser->bindValue(':uid', $userId, PDO::PARAM_INT);
                $stmtUser->execute();
                $userPositionId = (int)($stmtUser->fetchColumn() ?? 0);
                if ($userPositionId) {
                    $stmtTipo = $this->getConnection()->prepare('SELECT tipo_treinamento FROM adms_training_positions WHERE adms_training_id = :tid AND adms_position_id = :pid LIMIT 1');
                    $stmtTipo->bindValue(':tid', $trainingId, PDO::PARAM_INT);
                    $stmtTipo->bindValue(':pid', $userPositionId, PDO::PARAM_INT);
                    $stmtTipo->execute();
                    $tipo = $stmtTipo->fetchColumn();
                    if ($tipo === 'Continuo') {
                        $prazoDias = 365;
                    } else {
                        $prazoDias = 90;
                    }
                }
            } catch (\Exception $e) {
                $prazoDias = 90;
            }

            // Calcular data limite
            if ($dataLimiteManual) {
                $dataLimite = $dataLimiteManual;
            } else {
                $dataLimite = (new \DateTime())->modify("+{$prazoDias} days")->format('Y-m-d');
            }

            // Buscar vínculos atuais para consolidar escrita e evitar duplicados
            $sqlCheck = "SELECT id, tipo_vinculo, status
                         FROM adms_training_users
                         WHERE adms_user_id = :user_id
                           AND adms_training_id = :training_id
                         ORDER BY id DESC";
            $stmtCheck = $this->getConnection()->prepare($sqlCheck);
            $stmtCheck->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmtCheck->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
            $stmtCheck->execute();
            $ativos = $stmtCheck->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $hasCargoAtivo = false;
            $latestSameTypeId = null;
            $latestAnyId = null;
            $concluidoId = null;

            foreach ($ativos as $ativo) {
                $currentId = (int)$ativo['id'];
                $latestAnyId = $latestAnyId === null ? $currentId : max($latestAnyId, $currentId);
                if (($ativo['tipo_vinculo'] ?? '') === 'cargo') {
                    $hasCargoAtivo = true;
                }
                if (($ativo['tipo_vinculo'] ?? '') === $tipoVinculo) {
                    $latestSameTypeId = $latestSameTypeId === null ? $currentId : max($latestSameTypeId, $currentId);
                }
                if (($ativo['status'] ?? '') === 'concluido') {
                    $concluidoId = $concluidoId === null ? $currentId : max($concluidoId, $currentId);
                }
            }

            // Regra: se já há vínculo por cargo, não permitir criar vínculo individual
            if ($tipoVinculo === 'individual' && $hasCargoAtivo) {
                return;
            }

            if (!empty($ativos)) {
                // Priorizar registro concluído para não perder histórico na sincronização por cargo.
                $keeperId = $concluidoId ?? $latestSameTypeId ?? $latestAnyId;
                $keeperIsConcluido = $concluidoId !== null;

                if ($keeperIsConcluido) {
                    // Apenas ajustar tipo de vínculo quando necessário; nunca reabrir treinamento concluído.
                    $sqlUpdate = "UPDATE adms_training_users
                                  SET tipo_vinculo = :tipo_vinculo,
                                      updated_at = NOW()
                                  WHERE id = :id
                                    AND tipo_vinculo <> :tipo_vinculo_2";
                    $stmtUpdate = $this->getConnection()->prepare($sqlUpdate);
                    $stmtUpdate->bindValue(':tipo_vinculo', $tipoVinculo, PDO::PARAM_STR);
                    $stmtUpdate->bindValue(':tipo_vinculo_2', $tipoVinculo, PDO::PARAM_STR);
                    $stmtUpdate->bindValue(':id', $keeperId, PDO::PARAM_INT);
                    $stmtUpdate->execute();
                } else {
                    $sqlUpdate = "UPDATE adms_training_users
                                  SET status = :status,
                                      tipo_vinculo = :tipo_vinculo,
                                      motivo = :motivo,
                                      updated_at = NOW(),
                                      data_limite_primeiro_treinamento = :data_limite
                                  WHERE id = :id";
                    $stmtUpdate = $this->getConnection()->prepare($sqlUpdate);
                    $stmtUpdate->bindValue(':status', $status, PDO::PARAM_STR);
                    $stmtUpdate->bindValue(':tipo_vinculo', $tipoVinculo, PDO::PARAM_STR);
                    $stmtUpdate->bindValue(':motivo', $motivo, PDO::PARAM_STR);
                    $stmtUpdate->bindValue(':data_limite', $dataLimite, PDO::PARAM_STR);
                    $stmtUpdate->bindValue(':id', $keeperId, PDO::PARAM_INT);
                    $stmtUpdate->execute();
                }

                $this->backupRowsForDedupeByUserTraining($userId, $trainingId, (int)$keeperId);
                $sqlDeleteExtras = "DELETE FROM adms_training_users
                                    WHERE adms_user_id = :user_id
                                      AND adms_training_id = :training_id
                                      AND id <> :id";
                $stmtDeleteExtras = $this->getConnection()->prepare($sqlDeleteExtras);
                $stmtDeleteExtras->bindValue(':user_id', $userId, PDO::PARAM_INT);
                $stmtDeleteExtras->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
                $stmtDeleteExtras->bindValue(':id', $keeperId, PDO::PARAM_INT);
                $stmtDeleteExtras->execute();
                return;
            }

            // Sem vínculo ativo: cria novo
            $sqlInsert = 'INSERT INTO adms_training_users
                        (adms_user_id, adms_training_id, status, tipo_vinculo, motivo, created_at, updated_at, data_limite_primeiro_treinamento)
                         VALUES (:user_id, :training_id, :status, :tipo_vinculo, :motivo, NOW(), NOW(), :data_limite)';
            $stmtInsert = $this->getConnection()->prepare($sqlInsert);
            $stmtInsert->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmtInsert->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
            $stmtInsert->bindValue(':status', $status, PDO::PARAM_STR);
            $stmtInsert->bindValue(':tipo_vinculo', $tipoVinculo, PDO::PARAM_STR);
            $stmtInsert->bindValue(':motivo', $motivo, PDO::PARAM_STR);
            $stmtInsert->bindValue(':data_limite', $dataLimite, PDO::PARAM_STR);
            $stmtInsert->execute();
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

    /**
     * Retorna vínculos de treinamentos com paginação e otimização N+1
     * 
     * @param array $filters Filtros de busca
     * @param int $page Número da página (começa em 1)
     * @param int $perPage Registros por página
     * @return array ['data' => [], 'total' => 0, 'total_pages' => 0]
     */
    public function getTrainingStatusByUser(array $filters = [], int $page = 1, int $perPage = 50): array
    {
        // Calcular offset
        $offset = max(0, ($page - 1) * $perPage);

        $applyStatusFilter = !empty($filters['status']) && $filters['status'] !== '';

        // Busca todos os vínculos de treinamentos dos usuários
        // IMPORTANTE: Filtra apenas usuários ATIVOS e treinamentos ATIVOS
        // OTIMIZAÇÃO: Usa LEFT JOIN para buscar última aplicação em uma única query (resolve N+1)
        $sql = 'SELECT 
                u.id as user_id, 
                u.name as user_name, 
                d.name as department, 
                p.name as position,
                t.id as training_id, 
                t.codigo, 
                t.versao as training_version,
                t.nome as training_name, 
                t.reciclagem, 
                t.reciclagem_periodo,
                t.prazo_treinamento,
                tu.id as training_user_id,
                tu.status,
                tu.tipo_vinculo,
                tu.created_at as vinculo_created_at,
                tu.data_limite_primeiro_treinamento,
                tu.data_agendada,
                tp.tipo_treinamento,
                -- Última aplicação (otimização N+1)
                ta_last.data_realizacao,
                ta_last.nota,
                ta_last.observacoes,
                ta_last.instrutor_nome,
                ta_last.instrutor_email,
                ta_last.aplicado_por,
                ta_last.id as application_id
            FROM adms_training_users tu
            INNER JOIN (
                SELECT
                    adms_user_id,
                    adms_training_id,
                    COALESCE(
                        MAX(CASE WHEN status <> "concluido" THEN id END),
                        MAX(id)
                    ) AS latest_id
                FROM adms_training_users
                GROUP BY adms_user_id, adms_training_id
            ) tu_latest ON tu_latest.adms_user_id = tu.adms_user_id
                        AND tu_latest.adms_training_id = tu.adms_training_id
                        AND tu_latest.latest_id = tu.id
            INNER JOIN adms_users u ON u.id = tu.adms_user_id AND u.status = "Ativo"
            INNER JOIN adms_departments d ON u.user_department_id = d.id
            INNER JOIN adms_positions p ON u.user_position_id = p.id
            LEFT JOIN adms_training_positions tp ON tp.adms_training_id = tu.adms_training_id AND tp.adms_position_id = u.user_position_id
            INNER JOIN adms_trainings t ON t.id = tu.adms_training_id AND t.ativo = 1
            -- LEFT JOIN para última aplicação (subquery otimizada)
            LEFT JOIN (
                SELECT 
                    ta1.adms_user_id,
                    ta1.adms_training_id,
                    ta1.data_realizacao,
                    ta1.nota,
                    ta1.observacoes,
                    ta1.instrutor_nome,
                    ta1.instrutor_email,
                    ta1.aplicado_por,
                    ta1.id,
                    ta1.created_at
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
            WHERE 1=1';
        
        $params = [];
        
        // Filtro de status:
        // - Quando há filtro de status, vamos filtrar DEPOIS de calcular o status dinâmico
        // - Quando não há filtro, por padrão excluímos concluídos da listagem
        //   e também quaisquer vínculos que já tenham aplicação registrada
        //   (para evitar mostrar como "Dentro do Prazo" algo que já foi realizado).
        if (!$applyStatusFilter) {
            $sql .= ' AND tu.status != "concluido"';
            $sql .= ' AND ta_last.data_realizacao IS NULL';
        }
        
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
        if (!empty($filters['codigo'])) {
            $sql .= ' AND t.codigo LIKE ?';
            $params[] = '%' . $filters['codigo'] . '%';
        }
        
        // Contar total antes de aplicar LIMIT (base sem filtro de status)
        $countSql = 'SELECT COUNT(*) as total FROM (' . $sql . ') as count_query';
        $countStmt = $this->getConnection()->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Aplicar ordenação
        $sql .= ' ORDER BY u.name ASC, t.codigo ASC';

        // Quando NÃO há filtro de status, aplicamos paginação diretamente no SQL
        // Quando HÁ filtro de status, buscamos tudo e paginamos depois em memória
        if (!$applyStatusFilter) {
            $sql .= ' LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset;
        }
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Filtrar por status se necessário
        if ($applyStatusFilter) {
            $results = array_filter($results, function($row) use ($filters) {
                $status = $row['status'] ?? '';
                return $status === $filters['status'];
            });
            // Recalcular total após filtro
            $total = count($results);

            // Paginar em memória após o filtro
            $results = array_slice(array_values($results), $offset, $perPage);
        }
        
        return [
            'data' => array_values($results), // Reindexar array após filter
            'total' => $total,
            'total_pages' => ceil($total / $perPage),
            'current_page' => $page,
            'per_page' => $perPage
        ];
    }
    
    /**
     * Método legado mantido para compatibilidade
     * @deprecated Use getTrainingStatusByUser com paginação
     */
    public function getTrainingStatusByUserLegacy(array $filters = []): array
    {
        $result = $this->getTrainingStatusByUser($filters, 1, 10000);
        return $result['data'];
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
        // Seleciona todos os vínculos com informações do treinamento,
        // incluindo prazo_treinamento para cálculo de "próximo do vencimento"
        // e data_realizacao da última aplicação para verificar se está concluído
        $sql = 'SELECT 
                    tu.*,
                    t.reciclagem,
                    t.reciclagem_periodo,
                    t.prazo_treinamento,
                    ta_last.data_realizacao as data_realizacao_ultima
                FROM adms_training_users tu
                INNER JOIN adms_trainings t ON t.id = tu.adms_training_id
                LEFT JOIN (
                    SELECT 
                        ta1.adms_user_id,
                        ta1.adms_training_id,
                        ta1.data_realizacao,
                        ta1.created_at
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
                    AND ta_last.adms_training_id = tu.adms_training_id';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $trainings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $updated = 0;
        foreach ($trainings as $training) {
            // Usar data_realizacao da última aplicação se disponível, senão usar da tu
            $training['data_realizacao'] = $training['data_realizacao_ultima'] ?? $training['data_realizacao'] ?? null;
            
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
        $sql = 'SELECT *
                FROM adms_training_users
                WHERE adms_user_id = ?
                  AND adms_training_id = ?
                ORDER BY (status != "concluido") DESC, id DESC
                LIMIT 1';
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
        // Preferir atualizar o vínculo ativo mais recente para não gerar duplicidade.
        $sqlActive = 'SELECT id
                      FROM adms_training_users
                      WHERE adms_user_id = :user_id
                        AND adms_training_id = :training_id
                        AND status != "concluido"
                      ORDER BY id DESC
                      LIMIT 1';
        $stmtActive = $this->getConnection()->prepare($sqlActive);
        $stmtActive->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmtActive->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
        $stmtActive->execute();
        $activeId = (int)($stmtActive->fetchColumn() ?? 0);

        if ($activeId > 0) {
            $sqlUpdate = 'UPDATE adms_training_users
                          SET data_realizacao = :data_realizacao,
                              data_agendada = :data_agendada,
                              nota = :nota,
                              observacoes = :observacoes,
                              status = :status,
                              updated_at = NOW()
                          WHERE id = :id';
            $stmtUpdate = $this->getConnection()->prepare($sqlUpdate);
            $stmtUpdate->bindValue(':data_realizacao', $data['data_realizacao'] ?? null, PDO::PARAM_STR);
            $stmtUpdate->bindValue(':data_agendada', $data['data_agendada'] ?? null, PDO::PARAM_STR);
            $stmtUpdate->bindValue(':nota', $data['nota'] ?? null, PDO::PARAM_STR);
            $stmtUpdate->bindValue(':observacoes', $data['observacoes'] ?? null, PDO::PARAM_STR);
            $stmtUpdate->bindValue(':status', $data['status'], PDO::PARAM_STR);
            $stmtUpdate->bindValue(':id', $activeId, PDO::PARAM_INT);
            return $stmtUpdate->execute();
        }

        // Sem vínculo ativo, cria um novo.
        $sqlInsert = 'INSERT INTO adms_training_users
                      (adms_user_id, adms_training_id, data_realizacao, data_agendada, nota, observacoes, status, created_at, updated_at)
                      VALUES (:user_id, :training_id, :data_realizacao, :data_agendada, :nota, :observacoes, :status, NOW(), NOW())';
        $stmtInsert = $this->getConnection()->prepare($sqlInsert);
        $stmtInsert->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmtInsert->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
        $stmtInsert->bindValue(':data_realizacao', $data['data_realizacao'] ?? null, PDO::PARAM_STR);
        $stmtInsert->bindValue(':data_agendada', $data['data_agendada'] ?? null, PDO::PARAM_STR);
        $stmtInsert->bindValue(':nota', $data['nota'] ?? null, PDO::PARAM_STR);
        $stmtInsert->bindValue(':observacoes', $data['observacoes'] ?? null, PDO::PARAM_STR);
        $stmtInsert->bindValue(':status', $data['status'], PDO::PARAM_STR);
        return $stmtInsert->execute();
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
                // Se não há treinamentos obrigatórios, remove apenas vínculos ativos de cargo
                // (não apagar históricos concluídos nem vínculos individuais).
                $this->deleteActiveCargoLinksByUserAndNotInTrainings($userId, []);
                return true;
            }
            
            // Criar vínculos para treinamentos obrigatórios que não existem
            foreach ($requiredTrainings as $trainingId) {
                $this->insertOrUpdate($userId, $trainingId, 'dentro_do_prazo', 'cargo');
            }
            
            // Remover apenas vínculos ativos de cargo que não são mais obrigatórios
            $this->deleteActiveCargoLinksByUserAndNotInTrainings($userId, $requiredTrainings);

            // Hardening: manter apenas 1 vínculo por usuário+treinamento para o usuário informado.
            $this->backupRowsForGlobalDedupe(null);
            $sqlDedupeUser = "DELETE tu
                              FROM adms_training_users tu
                              INNER JOIN (
                                  SELECT
                                      adms_training_id,
                                      COALESCE(
                                          MAX(CASE WHEN status <> 'concluido' THEN id END),
                                          MAX(id)
                                      ) AS keep_id
                                  FROM adms_training_users
                                  WHERE adms_user_id = :user_id
                                  GROUP BY adms_training_id
                                  HAVING COUNT(*) > 1
                              ) d
                                      ON d.adms_training_id = tu.adms_training_id
                              WHERE tu.adms_user_id = :user_id_2
                                AND tu.id <> d.keep_id";
            $stmtDedupeUser = $this->getConnection()->prepare($sqlDedupeUser);
            $stmtDedupeUser->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmtDedupeUser->bindValue(':user_id_2', $userId, PDO::PARAM_INT);
            $stmtDedupeUser->execute();
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Sincroniza vínculos obrigatórios por cargo para todos os usuários ativos.
     * - Converte vínculo ativo individual -> cargo quando o cargo é obrigatório.
     * - Insere vínculo ativo cargo quando não existe vínculo ativo para o par usuário+treinamento.
     */
    public function syncMandatoryCargoLinksForAllActiveUsers(?int $trainingId = null): bool
    {
        try {
            $trainingFilterUpdate = '';
            $trainingFilterInsert = '';
            $paramsUpdate = [];
            $paramsInsert = [];

            if ($trainingId !== null) {
                $trainingFilterUpdate = ' AND tp.adms_training_id = :training_id';
                $trainingFilterInsert = ' AND tp.adms_training_id = :training_id';
                $paramsUpdate[':training_id'] = $trainingId;
                $paramsInsert[':training_id'] = $trainingId;
            }

            // 1) Se o cargo passou a ser obrigatório, vínculo ativo deve ser por cargo.
            $sqlConvert = "UPDATE adms_training_users tu
                           INNER JOIN adms_users u
                                   ON u.id = tu.adms_user_id
                                  AND u.status = 'Ativo'
                           INNER JOIN adms_training_positions tp
                                   ON tp.adms_training_id = tu.adms_training_id
                                  AND tp.adms_position_id = u.user_position_id
                                  AND tp.obrigatorio = 1
                           INNER JOIN adms_trainings t
                                   ON t.id = tu.adms_training_id
                                  AND t.ativo = 1
                           SET tu.tipo_vinculo = 'cargo',
                               tu.motivo = 'sincronizacao_cargo',
                               tu.updated_at = NOW()
                           WHERE tu.status != 'concluido'
                             AND tu.tipo_vinculo = 'individual'{$trainingFilterUpdate}";
            $stmtConvert = $this->getConnection()->prepare($sqlConvert);
            foreach ($paramsUpdate as $key => $value) {
                $stmtConvert->bindValue($key, $value, PDO::PARAM_INT);
            }
            $stmtConvert->execute();

            // 2) Inserir vínculos por cargo quando não existe nenhum vínculo para o par usuário+treinamento.
            $sqlInsertMissing = "INSERT INTO adms_training_users
                                    (adms_user_id, adms_training_id, status, tipo_vinculo, motivo, created_at, updated_at, data_limite_primeiro_treinamento)
                                 SELECT
                                    u.id,
                                    tp.adms_training_id,
                                    'dentro_do_prazo',
                                    'cargo',
                                    'sincronizacao_cargo',
                                    NOW(),
                                    NOW(),
                                    DATE_ADD(CURDATE(), INTERVAL CASE WHEN tp.tipo_treinamento = 'Continuo' THEN 365 ELSE 90 END DAY)
                                 FROM adms_training_positions tp
                                 INNER JOIN adms_users u
                                         ON u.user_position_id = tp.adms_position_id
                                        AND u.status = 'Ativo'
                                 INNER JOIN adms_trainings t
                                         ON t.id = tp.adms_training_id
                                        AND t.ativo = 1
                                 LEFT JOIN adms_training_users tu_any
                                       ON tu_any.adms_user_id = u.id
                                      AND tu_any.adms_training_id = tp.adms_training_id
                                 WHERE tp.obrigatorio = 1
                                   AND tu_any.id IS NULL{$trainingFilterInsert}";
            $stmtInsert = $this->getConnection()->prepare($sqlInsertMissing);
            foreach ($paramsInsert as $key => $value) {
                $stmtInsert->bindValue($key, $value, PDO::PARAM_INT);
            }
            $stmtInsert->execute();

            // 3) Fallback determinístico:
            // caso algum par usuário+treinamento obrigatório ainda fique sem vínculo,
            // materializa linha a linha via regra central.
            $sqlMissingAfterBulk = "SELECT
                                        u.id AS user_id,
                                        tp.adms_training_id AS training_id
                                    FROM adms_training_positions tp
                                    INNER JOIN adms_users u
                                            ON u.user_position_id = tp.adms_position_id
                                           AND u.status = 'Ativo'
                                    INNER JOIN adms_trainings t
                                            ON t.id = tp.adms_training_id
                                           AND t.ativo = 1
                                    LEFT JOIN adms_training_users tu_any
                                           ON tu_any.adms_user_id = u.id
                                          AND tu_any.adms_training_id = tp.adms_training_id
                                    WHERE tp.obrigatorio = 1
                                      AND tu_any.id IS NULL";
            if ($trainingId !== null) {
                $sqlMissingAfterBulk .= " AND tp.adms_training_id = :training_id";
            }
            $stmtMissing = $this->getConnection()->prepare($sqlMissingAfterBulk);
            if ($trainingId !== null) {
                $stmtMissing->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
            }
            $stmtMissing->execute();
            $missingPairs = $stmtMissing->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($missingPairs as $pair) {
                $this->insertOrUpdate(
                    (int)$pair['user_id'],
                    (int)$pair['training_id'],
                    'dentro_do_prazo',
                    'cargo',
                    null,
                    'sincronizacao'
                );
            }

            // 4) Hardening: manter apenas 1 vínculo por usuário+treinamento.
            $dedupeTrainingFilter = '';
            if ($trainingId !== null) {
                $dedupeTrainingFilter = ' AND d.adms_training_id = :training_id_filter';
            }
            $this->backupRowsForGlobalDedupe($trainingId);
            $sqlDedupe = "DELETE tu
                          FROM adms_training_users tu
                          INNER JOIN (
                              SELECT
                                  adms_user_id,
                                  adms_training_id,
                                  COALESCE(
                                      MAX(CASE WHEN status <> 'concluido' THEN id END),
                                      MAX(id)
                                  ) AS keep_id
                              FROM adms_training_users
                              GROUP BY adms_user_id, adms_training_id
                              HAVING COUNT(*) > 1
                          ) d
                                  ON d.adms_user_id = tu.adms_user_id
                                 AND d.adms_training_id = tu.adms_training_id
                          WHERE tu.id <> d.keep_id{$dedupeTrainingFilter}";
            $stmtDedupe = $this->getConnection()->prepare($sqlDedupe);
            if ($trainingId !== null) {
                $stmtDedupe->bindValue(':training_id_filter', $trainingId, PDO::PARAM_INT);
            }
            $stmtDedupe->execute();

            return true;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Falha na sincronização global de vínculos por cargo.", [
                'training_id' => $trainingId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Remove apenas vínculos ATIVOS por CARGO que não estejam na lista informada.
     * Preserva históricos concluídos e vínculos individuais do colaborador.
     */
    public function deleteActiveCargoLinksByUserAndNotInTrainings(int $userId, array $trainingIds): void
    {
        try {
            if (empty($trainingIds)) {
                $sql = "DELETE FROM adms_training_users
                        WHERE adms_user_id = ?
                          AND tipo_vinculo = 'cargo'
                          AND status != 'concluido'";
                $stmt = $this->getConnection()->prepare($sql);
                $stmt->bindValue(1, $userId, PDO::PARAM_INT);
                $stmt->execute();
                return;
            }

            $in = implode(',', array_fill(0, count($trainingIds), '?'));
            $sql = "DELETE FROM adms_training_users
                    WHERE adms_user_id = ?
                      AND tipo_vinculo = 'cargo'
                      AND status != 'concluido'
                      AND adms_training_id NOT IN ($in)";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(1, $userId, PDO::PARAM_INT);
            foreach ($trainingIds as $k => $tid) {
                $stmt->bindValue($k + 2, (int)$tid, PDO::PARAM_INT);
            }
            $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Falha ao remover vínculos ativos de cargo fora da lista.", [
                'user_id' => $userId,
                'training_ids' => $trainingIds,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Retorna estatísticas resumidas
     */
    public function getSummaryAll(): array
    {
        // Regras:
        // - Todos: contar TODOS os registros cadastrados em adms_training_users (independente do status)
        // - Dentro do Prazo: status em_dia ou dentro_do_prazo
        // - Próximo do Vencimento: status proximo_vencimento
        // - Vencido: status vencido
        // - Agendado: status agendado
        // - Concluído: status concluido

        // $sql = 'SELECT 
        //             COUNT(DISTINCT tu.adms_user_id) as total_users,
        //             COUNT(*) as total_entries,
        //             SUM(CASE WHEN tu.status = "pendente" THEN 1 ELSE 0 END) as pendente_count,
        //             SUM(CASE WHEN tu.status = "concluido" THEN 1 ELSE 0 END) as concluido_count,
        //             SUM(CASE WHEN tu.status = "vencido" THEN 1 ELSE 0 END) as vencido_count,
        //             SUM(CASE WHEN tu.status = "agendado" THEN 1 ELSE 0 END) as agendado_count,
        //             SUM(CASE WHEN tu.status = "proximo_vencimento" THEN 1 ELSE 0 END) as proximo_vencimento_count,
        //             SUM(CASE WHEN tu.status IN ("em_dia","dentro_do_prazo") THEN 1 ELSE 0 END) as em_dia_count
        //         FROM adms_training_users tu';

        // $stmt = $this->getConnection()->prepare($sql);
        // $stmt->execute();
        // $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];

        // $totalUsers            = (int)($row['total_users'] ?? 0);
        // $totalEntries          = (int)($row['total_entries'] ?? 0);
        // $concluidos            = (int)($row['concluido_count'] ?? 0);
        // $pendentes             = (int)($row['pendente_count'] ?? 0);
        // $vencidos              = (int)($row['vencido_count'] ?? 0);
        // $agendados             = (int)($row['agendado_count'] ?? 0);
        // $proximoVencimento     = (int)($row['proximo_vencimento_count'] ?? 0);
        // $emDia                 = (int)($row['em_dia_count'] ?? 0);

        // // Estrutura completa, mantendo chaves antigas e adicionando aliases
        // return [
        //     // Estrutura original usada em outros pontos
        //     'total'               => $totalEntries,
        //     'concluidos'          => $concluidos,
        //     'pendentes'           => $pendentes,
        //     'vencidos'            => $vencidos,
        //     'agendados'           => $agendados,
        //     'proximo_vencimento'  => $proximoVencimento,
        //     'em_dia'              => $emDia,

        //     // Aliases para compatibilidade com os cards da tela list-training-status
        //     // 'Todos' deve considerar TODOS os vínculos cadastrados, independente do status
        //     'todos'               => $totalEntries,
        //     'concluido'           => $concluidos,
        //     'pendente'            => $pendentes,
        //     'vencido'             => $vencidos,
        //     'agendado'            => $agendados,
        //     'dentro_do_prazo'     => $emDia,

        //     // Extras para depuração/uso futuro
        //     'total_users'         => $totalUsers,
        //     'total_entries'       => $totalEntries,
        // ];

        $pdo = $this->getConnection();

        // 1) Contagens para status dinâmicos (exceto concluído) seguindo a regra da listagem
        $sqlActive = '
            SELECT 
                COUNT(DISTINCT tu.adms_user_id) as total_users,
                COUNT(*) as total_entries,
                SUM(CASE WHEN tu.status = "pendente" THEN 1 ELSE 0 END) as pendente_count,
                SUM(CASE WHEN tu.status = "vencido" THEN 1 ELSE 0 END) as vencido_count,
                SUM(CASE WHEN tu.status = "agendado" THEN 1 ELSE 0 END) as agendado_count,
                SUM(CASE WHEN tu.status = "proximo_vencimento" THEN 1 ELSE 0 END) as proximo_vencimento_count,
                SUM(CASE WHEN tu.status IN ("em_dia","dentro_do_prazo") THEN 1 ELSE 0 END) as em_dia_count
            FROM adms_training_users tu
            INNER JOIN adms_users u 
                ON u.id = tu.adms_user_id 
               AND u.status = "Ativo"
            INNER JOIN adms_trainings t 
                ON t.id = tu.adms_training_id 
               AND t.ativo = 1
            WHERE tu.status != "concluido" OR tu.status IS NULL
        ';

        $stmtActive = $pdo->prepare($sqlActive);
        $stmtActive->execute();
        $rowActive = $stmtActive->fetch(\PDO::FETCH_ASSOC) ?: [];

        $totalUsers        = (int)($rowActive['total_users'] ?? 0);
        $totalEntriesBase  = (int)($rowActive['total_entries'] ?? 0);
        $pendentes         = (int)($rowActive['pendente_count'] ?? 0);
        $vencidos          = (int)($rowActive['vencido_count'] ?? 0);
        $agendados         = (int)($rowActive['agendado_count'] ?? 0);
        $proximoVencimento = (int)($rowActive['proximo_vencimento_count'] ?? 0);
        $emDia             = (int)($rowActive['em_dia_count'] ?? 0);

        // 2) Contagem de concluídos (todos os usuários/treinamentos existentes, mesmo inativos),
        //    ainda exigindo que NÃO sejam órfãos.
        $sqlConcluidos = '
            SELECT 
                COUNT(*) as concluido_count
            FROM adms_training_users tu
            LEFT JOIN adms_users u ON u.id = tu.adms_user_id
            LEFT JOIN adms_trainings t ON t.id = tu.adms_training_id
            WHERE tu.status = "concluido"
              AND u.id IS NOT NULL
              AND t.id IS NOT NULL
        ';

        $stmtConc = $pdo->prepare($sqlConcluidos);
        $stmtConc->execute();
        $rowConc = $stmtConc->fetch(\PDO::FETCH_ASSOC) ?: [];

        $concluidos = (int)($rowConc['concluido_count'] ?? 0);

        // 3) Total dos cards (Todos) = soma dos demais cards
        $todos = $emDia + $proximoVencimento + $vencidos + $agendados + $concluidos;

        return [
            // Estrutura geral
            'total'               => $todos,
            'concluidos'          => $concluidos,
            'pendentes'           => $pendentes,
            'vencidos'            => $vencidos,
            'agendados'           => $agendados,
            'proximo_vencimento'  => $proximoVencimento,
            'em_dia'              => $emDia,

            // Aliases para os cards
            'todos'               => $todos,
            'concluido'           => $concluidos,
            'pendente'            => $pendentes,
            'vencido'             => $vencidos,
            'agendado'            => $agendados,
            'dentro_do_prazo'     => $emDia,

            // Extras para referência/depuração
            'total_users'         => $totalUsers,
            'total_entries'       => $todos,
            'total_entries_base'  => $totalEntriesBase,
        ];
    }

    /**
     * Estatísticas da matriz agrupadas por departamento.
     * Regra: status em aberto = usuário/treinamento ativos; concluídos = não órfãos.
     */
    public function getMatrixStatisticsByDepartment(): array
    {
        $pdo = $this->getConnection();

        $sqlActive = "SELECT
                    d.id   AS group_id,
                    d.name AS group_name,
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
                WHERE tu.status != 'concluido' OR tu.status IS NULL
                GROUP BY d.id, d.name";

        $sqlConcluidos = "SELECT
                    d.id AS group_id,
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

        $byId = $this->mergeGroupedMatrixStatistics($pdo, $sqlActive, $sqlConcluidos, 'department');

        $stmtAllDepts = $pdo->query('SELECT id, name FROM adms_departments ORDER BY name');
        $allDepts = $stmtAllDepts->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($allDepts as $dept) {
            $deptId = (int)$dept['id'];
            if (!isset($byId[$deptId])) {
                $byId[$deptId] = $this->emptyMatrixGroupRow('department', $deptId, $dept['name']);
            }
        }

        usort($byId, static fn(array $a, array $b) => $b['total_vinculos'] <=> $a['total_vinculos']);

        return array_values($byId);
    }

    /**
     * Estatísticas da matriz agrupadas por cargo (top N por volume).
     */
    public function getMatrixStatisticsByPosition(int $limit = 10): array
    {
        $pdo = $this->getConnection();

        $sqlActive = "SELECT
                    p.id   AS group_id,
                    p.name AS group_name,
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
                INNER JOIN adms_positions p
                    ON u.user_position_id = p.id
                WHERE tu.status != 'concluido' OR tu.status IS NULL
                GROUP BY p.id, p.name";

        $sqlConcluidos = "SELECT
                    p.id AS group_id,
                    COUNT(*) AS concluidos
                FROM adms_training_users tu
                LEFT JOIN adms_users u ON u.id = tu.adms_user_id
                LEFT JOIN adms_trainings t ON t.id = tu.adms_training_id
                LEFT JOIN adms_positions p ON u.user_position_id = p.id
                WHERE tu.status = 'concluido'
                  AND u.id IS NOT NULL
                  AND t.id IS NOT NULL
                  AND p.id IS NOT NULL
                GROUP BY p.id";

        $byId = $this->mergeGroupedMatrixStatistics($pdo, $sqlActive, $sqlConcluidos, 'position');

        usort($byId, static fn(array $a, array $b) => $b['total_vinculos'] <=> $a['total_vinculos']);

        return array_slice(array_values($byId), 0, $limit);
    }

    /**
     * Combina contagens de status em aberto e concluídos por grupo.
     */
    private function mergeGroupedMatrixStatistics(
        \PDO $pdo,
        string $sqlActive,
        string $sqlConcluidos,
        string $entityType
    ): array {
        $stmtActive = $pdo->prepare($sqlActive);
        $stmtActive->execute();
        $activeStats = $stmtActive->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $stmtConc = $pdo->prepare($sqlConcluidos);
        $stmtConc->execute();
        $concluidosStats = $stmtConc->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $concluidosMap = [];
        foreach ($concluidosStats as $row) {
            $concluidosMap[(int)$row['group_id']] = (int)($row['concluidos'] ?? 0);
        }

        $byId = [];
        foreach ($activeStats as $row) {
            $groupId = (int)$row['group_id'];
            $concluidos = $concluidosMap[$groupId] ?? 0;
            $totalDinamicos = (int)($row['total_entries'] ?? 0);

            $byId[$groupId] = $this->buildMatrixGroupRow(
                $entityType,
                $groupId,
                $row['group_name'],
                $totalDinamicos,
                $concluidos,
                $row
            );
            unset($concluidosMap[$groupId]);
        }

        foreach ($concluidosMap as $groupId => $concluidos) {
            $name = $this->resolveMatrixGroupName($pdo, $entityType, (int)$groupId);
            if ($name === null) {
                continue;
            }
            $byId[(int)$groupId] = $this->buildMatrixGroupRow(
                $entityType,
                (int)$groupId,
                $name,
                0,
                $concluidos,
                []
            );
        }

        return $byId;
    }

    private function buildMatrixGroupRow(
        string $entityType,
        int $groupId,
        string $groupName,
        int $totalDinamicos,
        int $concluidos,
        array $statusRow
    ): array {
        $base = [
            'total_vinculos' => $totalDinamicos + $concluidos,
            'concluidos'      => $concluidos,
            'em_dia'          => (int)($statusRow['em_dia'] ?? 0),
            'pendentes'       => (int)($statusRow['pendentes'] ?? 0),
            'vencidos'        => (int)($statusRow['vencidos'] ?? 0),
            'agendados'       => (int)($statusRow['agendados'] ?? 0),
        ];

        if ($entityType === 'department') {
            return array_merge([
                'department_id'   => $groupId,
                'department_name' => $groupName,
            ], $base);
        }

        return array_merge([
            'position_id'   => $groupId,
            'position_name' => $groupName,
        ], $base);
    }

    private function emptyMatrixGroupRow(string $entityType, int $groupId, string $groupName): array
    {
        return $this->buildMatrixGroupRow($entityType, $groupId, $groupName, 0, 0, []);
    }

    private function resolveMatrixGroupName(\PDO $pdo, string $entityType, int $groupId): ?string
    {
        $table = $entityType === 'department' ? 'adms_departments' : 'adms_positions';
        $stmt = $pdo->prepare("SELECT name FROM {$table} WHERE id = ?");
        $stmt->execute([$groupId]);
        $name = $stmt->fetchColumn();

        return $name !== false ? (string)$name : null;
    }

    public function getSummaryMandatory(): array
    {
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
                INNER JOIN adms_users u ON u.id = tu.adms_user_id AND u.status = "Ativo"
                INNER JOIN adms_departments d ON u.user_department_id = d.id
                INNER JOIN adms_positions p ON u.user_position_id = p.id
                INNER JOIN adms_trainings t ON t.id = tu.adms_training_id AND t.ativo = 1
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
                // Reabre prazo a partir de hoje conforme tipo_treinamento (Inicial=90, Continuo=365)
                $prazoDias = 90;
                try {
                    $stmtUser = $this->getConnection()->prepare('SELECT user_position_id FROM adms_users WHERE id = :uid');
                    $stmtUser->bindValue(':uid', $userId, PDO::PARAM_INT);
                    $stmtUser->execute();
                    $userPositionId = (int)($stmtUser->fetchColumn() ?? 0);
                    if ($userPositionId) {
                        $stmtTipo = $this->getConnection()->prepare('SELECT tipo_treinamento FROM adms_training_positions WHERE adms_training_id = :tid AND adms_position_id = :pid LIMIT 1');
                        $stmtTipo->bindValue(':tid', $trainingId, PDO::PARAM_INT);
                        $stmtTipo->bindValue(':pid', $userPositionId, PDO::PARAM_INT);
                        $stmtTipo->execute();
                        $tipo = $stmtTipo->fetchColumn();
                        $prazoDias = ($tipo === 'Continuo') ? 365 : 90;
                    }
                } catch (\Exception $e) {
                    $prazoDias = 90;
                }
                $dataLimite = (new \DateTime())->modify("+{$prazoDias} days")->format('Y-m-d');
                $sql = 'UPDATE adms_training_users SET status = "dentro_do_prazo", motivo = "retreinamento", data_limite_primeiro_treinamento = :dataLimite, updated_at = NOW() WHERE adms_user_id = :userId AND adms_training_id = :trainingId';
                $stmt = $this->getConnection()->prepare($sql);
                $stmt->bindValue(':dataLimite', $dataLimite, PDO::PARAM_STR);
                $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
                $stmt->bindValue(':trainingId', $trainingId, PDO::PARAM_INT);
                $stmt->execute();
                // NÃO marcar como concluído e não criar novo ciclo!
            } else {
                // Marcar apenas o vínculo ativo mais recente como concluído.
                $sqlActive = 'SELECT id
                              FROM adms_training_users
                              WHERE adms_user_id = :user_id
                                AND adms_training_id = :training_id
                                AND status != "concluido"
                              ORDER BY id DESC
                              LIMIT 1';
                $stmtActive = $this->getConnection()->prepare($sqlActive);
                $stmtActive->bindValue(':user_id', $userId, PDO::PARAM_INT);
                $stmtActive->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
                $stmtActive->execute();
                $activeId = (int)($stmtActive->fetchColumn() ?? 0);

                if ($activeId > 0) {
                    $sql = 'UPDATE adms_training_users
                            SET status = "concluido", updated_at = NOW()
                            WHERE id = ?';
                    $stmt = $this->getConnection()->prepare($sql);
                    $stmt->bindValue(1, $activeId, PDO::PARAM_INT);
                    $stmt->execute();
                }

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
     * OTIMIZADO: Resolve N+1 e adiciona contagem eficiente
     * 
     * @param array $filters Filtros de busca
     * @param int $limit Limite de registros
     * @param int $offset Offset para paginação
     * @param bool $returnTotal Se true, retorna array com 'data' e 'total'
     * @param bool $forLntExport Inclui CPF/e-mail/admissão/gestor e última aplicação com status concluído (PDF LNT)
     * @return array Dados ou array com 'data' e 'total' se $returnTotal = true
     */
    public function getMandatoryMatrixByUser(array $filters = [], int $limit = 10, int $offset = 0, bool $returnTotal = false, bool $forLntExport = false): array
    {
        $lntCols = $forLntExport
            ? ',
                u.cpf,
                u.email AS user_email_lnt,
                u.data_admissao,
                gestor.name AS gestor_nome'
            : '';

        $lntGestorJoin = $forLntExport
            ? 'LEFT JOIN adms_users gestor ON gestor.id = u.immediate_supervisor_id'
            : '';

        if ($forLntExport) {
            // Apenas aplicações do MESMO registro de treinamento da linha (codigo+versao do catálogo = tu.adms_training_id).
            // Não misturar versões: outro par (codigo, versao) é outro id em adms_trainings.
            // Priorizar aplicação com data válida e a mais recente (alinhado a getLastCompletedTraining).
            $taLastJoin = 'LEFT JOIN adms_training_applications ta_last ON ta_last.id = (
                SELECT ta3.id
                FROM adms_training_applications ta3
                WHERE ta3.adms_user_id = tu.adms_user_id
                  AND ta3.adms_training_id = tu.adms_training_id
                  AND ta3.status = \'concluido\'
                ORDER BY
                    CASE WHEN ta3.data_realizacao IS NOT NULL AND ta3.data_realizacao > \'0000-00-00\' THEN 0 ELSE 1 END ASC,
                    ta3.data_realizacao DESC,
                    ta3.created_at DESC,
                    ta3.id DESC
                LIMIT 1
            )';
        } else {
            $taLastJoin = 'LEFT JOIN (
                SELECT 
                    ta1.adms_user_id,
                    ta1.adms_training_id,
                    ta1.data_realizacao,
                    ta1.nota,
                    ta1.observacoes,
                    ta1.instrutor_nome,
                    ta1.instrutor_email,
                    ta1.aplicado_por,
                    ta1.id,
                    ta1.created_at
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
                AND ta_last.adms_training_id = tu.adms_training_id';
        }

        // Construir query base
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
                tp.tipo_treinamento,
                tu.status,
                tu.tipo_vinculo,
                tu.created_at as vinculo_created_at,
                tu.data_limite_primeiro_treinamento
                ' . $lntCols . ',
                -- Última aplicação (otimização N+1)
                ta_last.data_realizacao,
                ta_last.nota,
                ta_last.observacoes,
                ta_last.instrutor_nome,
                ta_last.instrutor_email,
                ta_last.aplicado_por,
                ta_last.id as application_id
            FROM adms_training_users tu
            INNER JOIN (
                -- Consolidar matriz por colaborador+treinamento:
                -- prioriza vínculo ativo e, na ausência dele, usa o mais recente.
                SELECT
                    adms_user_id,
                    adms_training_id,
                    COALESCE(
                        MAX(CASE WHEN status <> "concluido" THEN id END),
                        MAX(id)
                    ) AS latest_id
                FROM adms_training_users
                GROUP BY adms_user_id, adms_training_id
            ) tu_latest
                ON tu_latest.adms_user_id = tu.adms_user_id
               AND tu_latest.adms_training_id = tu.adms_training_id
               AND tu_latest.latest_id = tu.id
            INNER JOIN adms_users u ON u.id = tu.adms_user_id
            INNER JOIN adms_departments d ON u.user_department_id = d.id
            INNER JOIN adms_positions p ON u.user_position_id = p.id
            ' . $lntGestorJoin . '
            INNER JOIN adms_trainings t ON t.id = tu.adms_training_id
            LEFT JOIN adms_training_positions tp ON tp.adms_training_id = tu.adms_training_id AND tp.adms_position_id = u.user_position_id
            -- LEFT JOIN para última aplicação (subquery otimizada - resolve N+1)
            ' . $taLastJoin . '
            WHERE t.ativo = 1';
        $params = [];
        
        // Aplicar filtros
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
        if (!empty($filters['codigo'])) {
            $sql .= ' AND t.codigo LIKE ?';
            $params[] = '%' . $filters['codigo'] . '%';
        }
        
        // Contar total antes de aplicar LIMIT (se necessário)
        $total = 0;
        if ($returnTotal) {
            $countSql = 'SELECT COUNT(*) as total FROM (' . $sql . ') as count_query';
            $countStmt = $this->getConnection()->prepare($countSql);
            $countStmt->execute($params);
            $total = (int)$countStmt->fetch(\PDO::FETCH_ASSOC)['total'];
        }
        
        // Aplicar ordenação e paginação
        $sql .= ' ORDER BY u.name ASC, t.codigo ASC';
        $sql .= ' LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // Calcular status dinâmico para cada resultado
        foreach ($results as &$result) {
            $result['status_dinamico'] = $this->calculateStatus($result);
        }
        unset($result);
        
        // Retornar com total se solicitado
        if ($returnTotal) {
            return [
                'data' => $results,
                'total' => $total
            ];
        }
        
        return $results;
    }

    /**
     * Para exportação LNT com filtro por treinamento: preenche data_realizacao e nota
     * da melhor aplicação concluída para o mesmo adms_training_id da linha (codigo+versao da matriz).
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    public function mergeUltimaRealizacaoConcluidaForUser(array $rows, int $userId): array
    {
        if ($rows === [] || $userId <= 0) {
            return $rows;
        }

        $sql = 'SELECT * FROM adms_training_applications
                WHERE adms_user_id = ? AND status = \'concluido\'';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([$userId]);
        $applications = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $bestByTrainingId = [];
        foreach ($applications as $app) {
            $tid = (int)($app['adms_training_id'] ?? 0);
            if ($tid <= 0) {
                continue;
            }
            if (!isset($bestByTrainingId[$tid])) {
                $bestByTrainingId[$tid] = $app;
            } elseif ($this->lntConcluidoApplicationCompare($app, $bestByTrainingId[$tid]) > 0) {
                $bestByTrainingId[$tid] = $app;
            }
        }

        foreach ($rows as &$row) {
            $tid = (int)($row['training_id'] ?? 0);
            if ($tid > 0 && isset($bestByTrainingId[$tid])) {
                $pick = $bestByTrainingId[$tid];
                $row['data_realizacao'] = $pick['data_realizacao'] ?? null;
                $row['nota'] = $pick['nota'] ?? null;
            } else {
                $row['data_realizacao'] = null;
                $row['nota'] = null;
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * Compara duas aplicações concluídas do mesmo usuário+t treinamento: retorno > 0 se $a deve prevalecer.
     */
    private function lntConcluidoApplicationCompare(array $a, array $b): int
    {
        $aOk = $this->lntHasValidDataRealizacao($a);
        $bOk = $this->lntHasValidDataRealizacao($b);
        if ($aOk !== $bOk) {
            return $aOk <=> $bOk;
        }
        if ($aOk) {
            $cmp = strcmp((string)($a['data_realizacao'] ?? ''), (string)($b['data_realizacao'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }
        }
        $ta = strtotime((string)($a['created_at'] ?? '')) ?: 0;
        $tb = strtotime((string)($b['created_at'] ?? '')) ?: 0;
        if ($ta !== $tb) {
            return $ta <=> $tb;
        }

        return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
    }

    private function lntHasValidDataRealizacao(array $row): bool
    {
        $dr = $row['data_realizacao'] ?? null;
        if ($dr === null || $dr === '') {
            return false;
        }
        if (strpos((string)$dr, '0000-') === 0) {
            return false;
        }

        return true;
    }

    /**
     * Retorna contagem de treinamentos por status (para gráfico de pizza)
     */
    public function getStatusCounts(): array
    {
        $summary = $this->getSummaryAll();

        return [
            'pendente'            => (int)($summary['pendentes'] ?? 0),
            'em_dia'              => (int)($summary['em_dia'] ?? 0),
            'proximo_vencimento'  => (int)($summary['proximo_vencimento'] ?? 0),
            'vencido'             => (int)($summary['vencidos'] ?? 0),
            'agendado'            => (int)($summary['agendados'] ?? 0),
            'concluido'           => (int)($summary['concluidos'] ?? 0),
        ];
    }

    /**
     * Retorna quantidade de treinamentos realizados por mês (últimos 12 meses)
     */
    public function getMonthlyRealizations(): array
    {
        // Primeiro, tentar buscar de adms_training_applications
        $sql = "SELECT DATE_FORMAT(data_realizacao, '%Y-%m') as mes, COUNT(*) as total 
                FROM adms_training_applications 
                WHERE data_realizacao IS NOT NULL 
                  AND data_realizacao >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) 
                GROUP BY mes 
                ORDER BY mes ASC";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data = [];
        foreach ($result as $row) {
            $data[$row['mes']] = (int)$row['total'];
        }
        
        // Se não houver dados em applications, usar adms_training_users com status concluido
        // usando updated_at como data de conclusão aproximada
        if (empty($data)) {
            $sql = "SELECT DATE_FORMAT(updated_at, '%Y-%m') as mes, COUNT(*) as total 
                    FROM adms_training_users 
                    WHERE status = 'concluido' 
                      AND updated_at IS NOT NULL
                      AND updated_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) 
                    GROUP BY mes 
                    ORDER BY mes ASC";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($result as $row) {
                $data[$row['mes']] = (int)$row['total'];
            }
        }
        
        return $data;
    }

    /**
     * Retorna top 5 usuários com mais treinamentos pendentes
     */
    public function getTopPendingUsers(): array
    {
        // Pendências: treinamentos dentro do prazo, próximos do vencimento ou vencidos (exclui concluídos e agendados)
        // Nota: Repetir a expressão SUM no HAVING e ORDER BY para compatibilidade com MySQL
        $sql = "SELECT
                    u.id as user_id,
                    u.name,
                    SUM(CASE WHEN tu.status IN ('em_dia','dentro_do_prazo','proximo_vencimento','vencido') THEN 1 ELSE 0 END) as pendentes
                FROM adms_training_users tu
                INNER JOIN adms_users u ON u.id = tu.adms_user_id AND u.status = 'Ativo'
                INNER JOIN adms_trainings t ON t.id = tu.adms_training_id AND t.ativo = 1
                GROUP BY u.id, u.name
                HAVING SUM(CASE WHEN tu.status IN ('em_dia','dentro_do_prazo','proximo_vencimento','vencido') THEN 1 ELSE 0 END) > 0
                ORDER BY SUM(CASE WHEN tu.status IN ('em_dia','dentro_do_prazo','proximo_vencimento','vencido') THEN 1 ELSE 0 END) DESC, u.name ASC 
                LIMIT 5";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna top 5 treinamentos com mais pendências/vencidos
     */
    public function getTopCriticalTrainings(): array
    {
        // Nota: MySQL não permite referenciar aliases de agregação dentro de expressões no HAVING/ORDER BY.
        // Repetimos as expressões de agregação completas para compatibilidade.
        $sql = "SELECT
                    t.id as training_id,
                    t.nome as training_name,
                    -- Pendentes: dentro do prazo + próximo do vencimento
                    SUM(CASE WHEN tu.status IN ('em_dia','dentro_do_prazo','proximo_vencimento') THEN 1 ELSE 0 END) as pendentes,
                    -- Vencidos
                    SUM(CASE WHEN tu.status = 'vencido' THEN 1 ELSE 0 END) as vencidos
                FROM adms_training_users tu
                INNER JOIN adms_trainings t ON t.id = tu.adms_training_id AND t.ativo = 1
                INNER JOIN adms_users u ON u.id = tu.adms_user_id AND u.status = 'Ativo'
                GROUP BY t.id, t.nome
                HAVING (SUM(CASE WHEN tu.status IN ('em_dia','dentro_do_prazo','proximo_vencimento') THEN 1 ELSE 0 END) + 
                        SUM(CASE WHEN tu.status = 'vencido' THEN 1 ELSE 0 END)) > 0
                ORDER BY 
                    (SUM(CASE WHEN tu.status IN ('em_dia','dentro_do_prazo','proximo_vencimento') THEN 1 ELSE 0 END) + 
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

        // Auto-sincronização defensiva:
        // garante que todos os usuários ativos dos cargos obrigatórios tenham vínculo por cargo
        // antes de processar vínculos individuais selecionados na tela.
        foreach ($cargosObrigatorios as $cargoId) {
            $usersFromCargo = $usersRepo->getUsersByPosition((int)$cargoId);
            foreach ($usersFromCargo as $cargoUser) {
                if (($cargoUser['status'] ?? '') === 'Ativo') {
                    $this->insertOrUpdate((int)$cargoUser['id'], $trainingId, 'dentro_do_prazo', 'cargo');
                }
            }
        }
        
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
            
            // Verifica se já existe vínculo para log/diagnóstico (qualquer status)
            $sqlCheck = "SELECT tipo_vinculo FROM adms_training_users WHERE adms_user_id = :user_id AND adms_training_id = :training_id";
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

            $targetTipo = in_array($user['user_position_id'], $cargosObrigatorios, true) ? 'cargo' : 'individual';
            \App\adms\Helpers\GenerateLog::generateLog(
                "debug",
                "vincularUsuariosTreinamento - Consolidando vínculo",
                [
                    'userId' => $userId,
                    'trainingId' => $trainingId,
                    'tipo_destino' => $targetTipo,
                    'vinculo_existente' => $vinculoExistente
                ]
            );
            $this->insertOrUpdate((int)$userId, (int)$trainingId, 'dentro_do_prazo', $targetTipo);
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
    public function getUsuariosVinculados(int $trainingId): array
    {
        // Vínculos diretos (apenas usuários ATIVOS)
        $sqlDireto = "SELECT u.id, u.name, u.email, 'direto' as tipo
            FROM adms_training_users tu
            INNER JOIN adms_users u ON u.id = tu.adms_user_id AND u.status = 'Ativo'
            WHERE tu.adms_training_id = :training_id";

        // Vínculos por cargo (sem vínculo direto) - apenas usuários ATIVOS
        $sqlCargo = "SELECT u.id, u.name, u.email, 'cargo' as tipo
            FROM adms_users u
            INNER JOIN adms_training_positions tp ON tp.adms_position_id = u.user_position_id
            WHERE tp.adms_training_id = :training_id
            AND u.status = 'Ativo'
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
    public function getAllVinculadosPorTreinamento(int $trainingId): array
    {
        // Buscar todos os vínculos individuais (apenas usuários ATIVOS)
        $sqlIndividuais = "SELECT tu.adms_user_id as id, u.name, u.email, 'individual' as tipo, p.name as cargo_nome, d.name as department_nome, tp.tipo_treinamento
            FROM adms_training_users tu
            INNER JOIN adms_users u ON u.id = tu.adms_user_id AND u.status = 'Ativo'
            INNER JOIN adms_positions p ON p.id = u.user_position_id
            INNER JOIN adms_departments d ON d.id = u.user_department_id
            LEFT JOIN adms_training_positions tp ON tp.adms_training_id = tu.adms_training_id AND tp.adms_position_id = u.user_position_id
            WHERE tu.adms_training_id = :training_id AND tu.tipo_vinculo = 'individual'";
        $stmtIndividuais = $this->getConnection()->prepare($sqlIndividuais);
        $stmtIndividuais->bindValue(':training_id', $trainingId, \PDO::PARAM_INT);
        $stmtIndividuais->execute();
        $individuais = $stmtIndividuais->fetchAll(\PDO::FETCH_ASSOC);
        $idsIndividuais = array_column($individuais, 'id');

        // Buscar vínculos por cargo, excluindo quem já tem vínculo individual (apenas usuários ATIVOS)
        $sqlCargo = "SELECT u.id, u.name, u.email, 'cargo' as tipo, p.name as cargo_nome, d.name as department_nome, tp.tipo_treinamento
            FROM adms_users u
            INNER JOIN adms_positions p ON p.id = u.user_position_id
            INNER JOIN adms_departments d ON d.id = u.user_department_id
            INNER JOIN adms_training_positions tp ON tp.adms_position_id = u.user_position_id
            WHERE tp.adms_training_id = :training_id
            AND tp.obrigatorio = 1
            AND u.status = 'Ativo'
            " . (count($idsIndividuais) ? ("AND u.id NOT IN (" . implode(',', $idsIndividuais) . ")") : "") .
            " ORDER BY u.name ASC";
        $stmtCargo = $this->getConnection()->prepare($sqlCargo);
        $stmtCargo->bindValue(':training_id', $trainingId, \PDO::PARAM_INT);
        $stmtCargo->execute();
        $cargos = $stmtCargo->fetchAll(\PDO::FETCH_ASSOC);

        // Buscar dados do treinamento
        $sqlTreinamento = "SELECT id, nome, codigo, versao, reciclagem, reciclagem_periodo FROM adms_trainings WHERE id = :training_id";
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
                'training_version' => $treinamento['versao'] ?? '',
                'tipo_vinculo' => ($item['tipo'] ?? '') === 'individual' ? 'individual' : 'cargo',
                'tipo_treinamento' => $item['tipo_treinamento'] ?? '',
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
            $byUser = strcasecmp((string)$a['user_name'], (string)$b['user_name']);
            if ($byUser !== 0) {
                return $byUser;
            }
            return strcasecmp((string)($a['codigo'] ?? ''), (string)($b['codigo'] ?? ''));
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
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Vínculo individual não excluído.", [
                'user_id' => $userId,
                'training_id' => $trainingId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Evita linhas repetidas na matriz quando há mais de uma aplicação concluída
     * com o mesmo colaborador, treinamento e data de realização (ex.: duplo envio do formulário).
     */
    private function completedTrainingsDedupJoinSql(): string
    {
        return 'INNER JOIN (
                    SELECT MAX(ta_dedup.id) AS keep_id
                    FROM adms_training_applications ta_dedup
                    WHERE ta_dedup.status = \'concluido\'
                    GROUP BY ta_dedup.adms_user_id, ta_dedup.adms_training_id, ta_dedup.data_realizacao
                ) ta_dedup ON ta_dedup.keep_id = ta.id';
    }

    /**
     * Retorna matriz de treinamentos concluídos por colaborador, paginada.
     * Passe $perPage = null para retornar todos os registros que atendem aos filtros (exportação).
     */
    public function getCompletedTrainingsMatrixPaginated(array $filters = [], int $page = 1, ?int $perPage = 20): array
    {
        $sql = 'SELECT 
                    ta.id as application_id,
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
                    ta.observacoes,
                    (
                        SELECT tp_inner.tipo_treinamento
                        FROM adms_training_positions tp_inner
                        WHERE tp_inner.adms_training_id = ta.adms_training_id
                          AND tp_inner.adms_position_id = u.user_position_id
                        LIMIT 1
                    ) AS tipo_treinamento
                FROM adms_training_applications ta
                ' . $this->completedTrainingsDedupJoinSql() . '
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
        if (!empty($filters['codigo'])) {
            $sql .= ' AND t.codigo LIKE ?';
            $params[] = '%' . $filters['codigo'] . '%';
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
            $sql .= ' ORDER BY ' . $allowedSort[$sort] . ' ' . $order . ', u.name ASC, t.codigo ASC, ta.data_realizacao DESC';
        } else {
            $sql .= ' ORDER BY u.name ASC, t.codigo ASC, ta.data_realizacao DESC';
        }
        if ($perPage !== null) {
            $offset = max(0, ($page - 1) * $perPage);
            $perPage = max(1, (int)$perPage);
            $sql .= ' LIMIT ' . $perPage . ' OFFSET ' . $offset;
        }
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        // Total de registros (sem paginação, com mesma deduplicação da listagem)
        $sqlCount = 'SELECT COUNT(*) as total
            FROM adms_training_applications ta
            ' . $this->completedTrainingsDedupJoinSql() . '
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
        if (!empty($filters['codigo'])) {
            $sqlCount .= ' AND t.codigo LIKE ?';
            $paramsCount[] = '%' . $filters['codigo'] . '%';
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
                ' . $this->completedTrainingsDedupJoinSql() . '
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
                    ' . $this->completedTrainingsDedupJoinSql() . '
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
        // Reutiliza a regra central para evitar criação de ativos duplicados.
        $this->insertOrUpdate($userId, $trainingId, $status, $tipoVinculo, $dataLimite, $motivo);
    }

    public function removeActiveLinksByUser(int $userId): void
    {
        $sql = "DELETE FROM adms_training_users WHERE adms_user_id = :user_id AND status != 'concluido'";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function removeActiveLinksByCargoAndTraining(int $cargoId, int $trainingId): void
    {
        $sql = "DELETE tu FROM adms_training_users tu
                INNER JOIN adms_users u ON u.id = tu.adms_user_id
                WHERE u.user_position_id = :cargo_id AND tu.adms_training_id = :training_id AND tu.tipo_vinculo = 'cargo' AND tu.status != 'concluido'";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':cargo_id', $cargoId, PDO::PARAM_INT);
        $stmt->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function removeActiveLinksByTraining(int $trainingId): void
    {
        $sql = "DELETE FROM adms_training_users WHERE adms_training_id = :training_id AND status != 'concluido'";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function getLastCompletedTraining(int $userId, int $trainingId): ?array
    {
        // Fonte oficial de conclusão: aplicações registradas.
        $sqlApp = "SELECT id, adms_user_id, adms_training_id, data_realizacao, status, created_at, updated_at
                   FROM adms_training_applications
                   WHERE adms_user_id = :user_id
                     AND adms_training_id = :training_id
                     AND status = 'concluido'
                     AND data_realizacao IS NOT NULL
                   ORDER BY data_realizacao DESC, created_at DESC, id DESC
                   LIMIT 1";
        $stmtApp = $this->getConnection()->prepare($sqlApp);
        $stmtApp->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmtApp->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
        $stmtApp->execute();
        $application = $stmtApp->fetch(PDO::FETCH_ASSOC);
        if ($application) {
            return $application;
        }
        return null;
    }

    public function isReciclagemVencida(?string $dataRealizacao, int|string|null $reciclagemPeriodo): bool
    {
        if (!$dataRealizacao || !$reciclagemPeriodo) {
            return true;
        }
        $dataVencimento = (new \DateTime($dataRealizacao))->modify('+' . (int)$reciclagemPeriodo . ' months');
        return new \DateTime() > $dataVencimento;
    }

    public function recreateLinksForUser(int $userId, int $userPositionId): void
    {
        // Verificar se o usuário está ativo antes de recriar vínculos
        $usersRepo = new \App\adms\Models\Repository\UsersRepository();
        $user = $usersRepo->getUser($userId);
        if (!$user || $user['status'] !== 'Ativo') {
            // Usuário inativo - não recriar vínculos
            return;
        }
        
        $trainingPositionsRepo = new \App\adms\Models\Repository\TrainingPositionsRepository();
        $trainingsRepo = new \App\adms\Models\Repository\TrainingsRepository();
        $mandatoryTrainings = $trainingPositionsRepo->getTrainingsByPosition($userPositionId);
        foreach ($mandatoryTrainings as $trainingId) {
            // Verificar se o treinamento está ativo antes de criar vínculo
            $training = $trainingsRepo->getTraining($trainingId);
            if (!$training || $training['ativo'] != 1) {
                // Treinamento inativo - pular
                continue;
            }
            
            $lastCompleted = $this->getLastCompletedTraining($userId, $trainingId);
            if ($lastCompleted && $training['reciclagem']) {
                if ($this->isReciclagemVencida($lastCompleted['data_realizacao'], $training['reciclagem_periodo'])) {
                    $this->insertOrUpdate($userId, $trainingId, 'dentro_do_prazo', 'cargo', null, 'reciclagem');
                }
            } elseif (!$lastCompleted) {
                $this->insertOrUpdate($userId, $trainingId, 'dentro_do_prazo', 'cargo');
            }
        }
    }

    public function recreateLinksForTraining(int $trainingId): void
    {
        // Verificar se o treinamento está ativo antes de recriar vínculos
        $trainingsRepo = new \App\adms\Models\Repository\TrainingsRepository();
        $training = $trainingsRepo->getTraining($trainingId);
        if (!$training || $training['ativo'] != 1) {
            // Treinamento inativo - não recriar vínculos
            return;
        }
        
        $trainingPositionsRepo = new \App\adms\Models\Repository\TrainingPositionsRepository();
        $positions = $trainingPositionsRepo->getPositionsByTraining($trainingId);
        $usersRepo = new \App\adms\Models\Repository\UsersRepository();
        foreach ($positions as $pos) {
            $users = $usersRepo->getUsersByPosition($pos['adms_position_id']);
            foreach ($users as $user) {
                // Verificar se o usuário está ativo antes de recriar vínculo
                if ($user['status'] === 'Ativo') {
                    $this->recreateLinksForUser($user['id'], $pos['adms_position_id']);
                }
            }
        }
    }

    /**
     * Remove vínculos órfãos (usuários inativos ou treinamentos inativos)
     * Este método deve ser executado periodicamente para manter a integridade dos dados
     */
    public function cleanupOrphanLinks(): array
    {
        $results = [
            'removed_inactive_users' => 0,
            'removed_inactive_trainings' => 0,
            'total_removed' => 0
        ];
        
        try {
            // Remover vínculos de usuários inativos (exceto concluídos)
            $sqlUsers = "DELETE tu FROM adms_training_users tu
                        INNER JOIN adms_users u ON u.id = tu.adms_user_id
                        WHERE u.status != 'Ativo' AND tu.status != 'concluido'";
            $stmtUsers = $this->getConnection()->prepare($sqlUsers);
            $stmtUsers->execute();
            $results['removed_inactive_users'] = $stmtUsers->rowCount();
            
            // Remover vínculos de treinamentos inativos (exceto concluídos)
            $sqlTrainings = "DELETE tu FROM adms_training_users tu
                            INNER JOIN adms_trainings t ON t.id = tu.adms_training_id
                            WHERE t.ativo != 1 AND tu.status != 'concluido'";
            $stmtTrainings = $this->getConnection()->prepare($sqlTrainings);
            $stmtTrainings->execute();
            $results['removed_inactive_trainings'] = $stmtTrainings->rowCount();
            
            $results['total_removed'] = $results['removed_inactive_users'] + $results['removed_inactive_trainings'];
            
            // Log da ação
            if ($results['total_removed'] > 0) {
                GenerateLog::generateLog("info", "Vínculos órfãos removidos", [
                    'removed_inactive_users' => $results['removed_inactive_users'],
                    'removed_inactive_trainings' => $results['removed_inactive_trainings'],
                    'total_removed' => $results['total_removed']
                ]);
            }
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao limpar vínculos órfãos", [
                'error' => $e->getMessage()
            ]);
        }
        
        return $results;
    }
} 