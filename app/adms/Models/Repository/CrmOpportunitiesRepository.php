<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

/**
 * Repository responsável pela gestão de oportunidades do CRM
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class CrmOpportunitiesRepository extends DbConnection
{
    /**
     * Buscar oportunidades por etapa do pipeline (para Kanban)
     *
     * @param int $stageId ID da etapa
     * @param array $filters Filtros adicionais
     * @return array Lista de oportunidades
     */
    public function getOpportunitiesByStage(int $stageId, array $filters = []): array
    {
        $sql = 'SELECT 
                    o.id,
                    o.code,
                    o.title,
                    o.value,
                    o.probability,
                    o.expected_close_date,
                    o.next_action,
                    o.stage_entered_at,
                    p.name as partner_name,
                    p.segment,
                    u.name as responsible_name,
                    DATEDIFF(NOW(), o.stage_entered_at) as days_in_stage
                FROM crm_opportunities o
                INNER JOIN crm_partners p ON o.partner_id = p.id
                INNER JOIN adms_users u ON o.responsible_user_id = u.id
                WHERE o.stage_id = :stage_id
                AND o.status = :status';

        $params = [
            ':stage_id' => $stageId,
            ':status' => 'Aberta'
        ];

        // Filtro por responsável
        if (!empty($filters['responsible_user_id'])) {
            $sql .= ' AND o.responsible_user_id = :responsible_user_id';
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
        }

        // Filtro por busca
        if (!empty($filters['search'])) {
            $sql .= ' AND (o.title LIKE :search OR p.name LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql .= ' ORDER BY o.value DESC';

        $stmt = $this->getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar valor total de oportunidades por etapa
     *
     * @param int $stageId ID da etapa
     * @param array $filters Filtros adicionais
     * @return float Valor total
     */
    public function getTotalValueByStage(int $stageId, array $filters = []): float
    {
        $sql = 'SELECT SUM(o.value) as total
                FROM crm_opportunities o
                WHERE o.stage_id = :stage_id
                AND o.status = :status';

        $params = [
            ':stage_id' => $stageId,
            ':status' => 'Aberta'
        ];

        // Filtro por responsável
        if (!empty($filters['responsible_user_id'])) {
            $sql .= ' AND o.responsible_user_id = :responsible_user_id';
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
        }

        $stmt = $this->getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (float)($result['total'] ?? 0);
    }

    /**
     * Buscar valor total do pipeline
     *
     * @param array $filters Filtros adicionais
     * @return float Valor total
     */
    public function getTotalPipelineValue(array $filters = []): float
    {
        $sql = 'SELECT SUM(o.value) as total
                FROM crm_opportunities o
                LEFT JOIN crm_partners p ON o.partner_id = p.id
                LEFT JOIN crm_pipeline_stages s ON o.stage_id = s.id
                WHERE o.status = :status';

        $params = [':status' => 'Aberta'];

        // Filtro por responsável
        if (!empty($filters['responsible_user_id'])) {
            $sql .= ' AND o.responsible_user_id = :responsible_user_id';
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
        }

        // Filtro por etapa (do gráfico)
        if (!empty($filters['filter_stage'])) {
            $sql .= ' AND s.name = :filter_stage';
            $params[':filter_stage'] = $filters['filter_stage'];
        }

        // Filtro por segmento (do gráfico)
        if (!empty($filters['filter_segment'])) {
            $sql .= ' AND p.segment = :filter_segment';
            $params[':filter_segment'] = $filters['filter_segment'];
        }

        // Filtro por período
        if (!empty($filters['periodo_inicio'])) {
            $sql .= ' AND o.created_at >= :periodo_inicio';
            $params[':periodo_inicio'] = $filters['periodo_inicio'];
        }

        if (!empty($filters['periodo_fim'])) {
            $sql .= ' AND o.created_at <= :periodo_fim';
            $params[':periodo_fim'] = $filters['periodo_fim'] . ' 23:59:59';
        }

        $stmt = $this->getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (float)($result['total'] ?? 0);
    }

    /**
     * Mover oportunidade para outra etapa
     *
     * @param int $opportunityId ID da oportunidade
     * @param int $newStageId ID da nova etapa
     * @param int $userId ID do usuário que moveu
     * @return bool Sucesso ou falha
     */
    public function moveOpportunity(int $opportunityId, int $newStageId, int $userId): bool
    {
        try {
            $this->getConnection()->beginTransaction();

            // Buscar oportunidade atual
            $opportunity = $this->getOpportunity($opportunityId);

            if (!$opportunity) {
                throw new Exception("Oportunidade não encontrada");
            }

            $oldStageId = $opportunity['stage_id'];

            // Calcular dias na etapa anterior
            $daysInStage = 0;
            if ($opportunity['stage_entered_at']) {
                $daysInStage = (new \DateTime())->diff(new \DateTime($opportunity['stage_entered_at']))->days;
            }

            // Atualizar a oportunidade
            $sql = 'UPDATE crm_opportunities 
                    SET stage_id = :new_stage_id,
                        previous_stage_id = :old_stage_id,
                        stage_entered_at = NOW(),
                        updated_by = :user_id,
                        updated_at = NOW()
                    WHERE id = :id';

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':new_stage_id', $newStageId, PDO::PARAM_INT);
            $stmt->bindValue(':old_stage_id', $oldStageId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':id', $opportunityId, PDO::PARAM_INT);
            $stmt->execute();

            // Registrar no histórico
            $sqlHistory = 'INSERT INTO crm_stage_history 
                          (opportunity_id, from_stage_id, to_stage_id, days_in_stage, moved_by, moved_at)
                          VALUES (:opportunity_id, :from_stage_id, :to_stage_id, :days_in_stage, :moved_by, NOW())';

            $stmtHistory = $this->getConnection()->prepare($sqlHistory);
            $stmtHistory->bindValue(':opportunity_id', $opportunityId, PDO::PARAM_INT);
            $stmtHistory->bindValue(':from_stage_id', $oldStageId, PDO::PARAM_INT);
            $stmtHistory->bindValue(':to_stage_id', $newStageId, PDO::PARAM_INT);
            $stmtHistory->bindValue(':days_in_stage', $daysInStage, PDO::PARAM_INT);
            $stmtHistory->bindValue(':moved_by', $userId, PDO::PARAM_INT);
            $stmtHistory->execute();

            // Registrar log de alteração
            if ($oldStageId != $newStageId) {
                LogAlteracaoService::registrarAlteracao(
                    'crm_opportunities',
                    $opportunityId,
                    $userId,
                    'UPDATE',
                    ['stage_id' => $oldStageId],
                    ['stage_id' => $newStageId]
                );
            }

            $this->getConnection()->commit();

            return true;
        } catch (Exception $e) {
            $this->getConnection()->rollBack();
            GenerateLog::generateLog("error", "Erro ao mover oportunidade", [
                'opportunity_id' => $opportunityId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Obter todas as oportunidades para select (id, title)
     * 
     * @return array
     */
    public function getAllOpportunitiesSelect(): array
    {
        $sql = 'SELECT 
                    id, 
                    title,
                    partner_id
                FROM crm_opportunities
                WHERE status IN ("Aberta", "Negociação", "Proposta")
                ORDER BY title ASC';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar uma oportunidade específica
     *
     * @param int $id ID da oportunidade
     * @return array|bool Dados da oportunidade ou false
     */
    public function getOpportunity(int $id): array|bool
    {
        $sql = 'SELECT 
                    o.*,
                    p.name as partner_name,
                    p.email as partner_email,
                    p.phone as partner_phone,
                    p.mobile as partner_mobile,
                    u.name as responsible_name,
                    s.name as stage_name,
                    s.color as stage_color
                FROM crm_opportunities o
                INNER JOIN crm_partners p ON o.partner_id = p.id
                INNER JOIN adms_users u ON o.responsible_user_id = u.id
                INNER JOIN crm_pipeline_stages s ON o.stage_id = s.id
                WHERE o.id = :id';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Criar nova oportunidade
     *
     * @param array $data Dados da oportunidade
     * @return bool|int ID da oportunidade criada ou false
     */
    public function createOpportunity(array $data): bool|int
    {
        try {
            $sql = 'INSERT INTO crm_opportunities (
                        code, title, description, partner_id, responsible_user_id,
                        stage_id, value, probability, expected_close_date,
                        next_action, next_action_date, source, products_services,
                        notes, tags, created_by, stage_entered_at, created_at
                    ) VALUES (
                        :code, :title, :description, :partner_id, :responsible_user_id,
                        :stage_id, :value, :probability, :expected_close_date,
                        :next_action, :next_action_date, :source, :products_services,
                        :notes, :tags, :created_by, NOW(), NOW()
                    )';

            $stmt = $this->getConnection()->prepare($sql);

            $stmt->bindValue(':code', $data['code']);
            $stmt->bindValue(':title', $data['title']);
            $stmt->bindValue(':description', $data['description'] ?? null);
            $stmt->bindValue(':partner_id', $data['partner_id'], PDO::PARAM_INT);
            $stmt->bindValue(':responsible_user_id', $data['responsible_user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':stage_id', $data['stage_id'], PDO::PARAM_INT);
            $stmt->bindValue(':value', $data['value']);
            $stmt->bindValue(':probability', $data['probability'] ?? 50, PDO::PARAM_INT);
            $stmt->bindValue(':expected_close_date', $data['expected_close_date'] ?? null);
            $stmt->bindValue(':next_action', $data['next_action'] ?? null);
            $stmt->bindValue(':next_action_date', $data['next_action_date'] ?? null);
            $stmt->bindValue(':source', $data['source'] ?? null);
            $stmt->bindValue(':products_services', $data['products_services'] ?? null);
            $stmt->bindValue(':notes', $data['notes'] ?? null);
            $stmt->bindValue(':tags', $data['tags'] ?? null);
            $stmt->bindValue(':created_by', $_SESSION['user_id'] ?? 1, PDO::PARAM_INT);

            $stmt->execute();

            $opportunityId = $this->getConnection()->lastInsertId();

            // Registrar log
            if ($opportunityId) {
                LogAlteracaoService::registrarAlteracao(
                    'crm_opportunities',
                    $opportunityId,
                    $_SESSION['user_id'] ?? 1,
                    'INSERT',
                    [],
                    $data
                );
            }

            return $opportunityId;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao criar oportunidade", [
                'data' => $data,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Gerar próximo código de oportunidade
     *
     * @return string Próximo código
     */
    public function getNextOpportunityCode(): string
    {
        try {
            $sql = "SELECT code FROM crm_opportunities ORDER BY code DESC LIMIT 1";

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute();

            $lastCode = $stmt->fetchColumn();

            if ($lastCode) {
                $numericPart = (int)substr($lastCode, 3);
                $nextNumber = $numericPart + 1;
            } else {
                $nextNumber = 1;
            }

            return 'OPP' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao gerar código de oportunidade", [
                'error' => $e->getMessage()
            ]);
            return 'OPP00001';
        }
    }

    /**
     * Buscar dados do funil para dashboard
     * Sempre retorna TODAS as etapas, com ou sem dados
     *
     * @return array Dados do funil
     */
    public function getFunnelData(array $filters = []): array
    {
        // Construir condições para o LEFT JOIN (aplicar filtros no ON)
        $joinConditions = ['o.stage_id = s.id', 'o.status = "Aberta"'];
        $params = [];
        
        if (!empty($filters['responsible_user_id'])) {
            $joinConditions[] = 'o.responsible_user_id = :responsible_user_id';
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
        }
        
        if (!empty($filters['periodo_inicio'])) {
            $joinConditions[] = 'o.created_at >= :periodo_inicio';
            $params[':periodo_inicio'] = $filters['periodo_inicio'];
        }
        
        if (!empty($filters['periodo_fim'])) {
            $joinConditions[] = 'o.created_at <= :periodo_fim';
            $params[':periodo_fim'] = $filters['periodo_fim'] . ' 23:59:59';
        }

        $sql = 'SELECT 
                    s.name,
                    s.color,
                    s.display_order,
                    COUNT(o.id) as count,
                    COALESCE(SUM(o.value), 0) as total_value
                FROM crm_pipeline_stages s
                LEFT JOIN crm_opportunities o ON ' . implode(' AND ', $joinConditions);

        // Filtro de segmento via JOIN adicional
        if (!empty($filters['filter_segment'])) {
            $sql .= ' AND o.partner_id IN (SELECT id FROM crm_partners WHERE segment = :filter_segment)';
            $params[':filter_segment'] = $filters['filter_segment'];
        }
        
        $sql .= ' WHERE s.is_active = 1 AND s.stage_type = "active"
                  GROUP BY s.id, s.name, s.color, s.display_order
                  ORDER BY s.display_order';

        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar taxa de conversão geral
     *
     * @return float Taxa de conversão em %
     */
    public function getConversionRate(array $filters = []): float
    {
        $sql = 'SELECT 
                    (COUNT(CASE WHEN o.status = "Ganha" THEN 1 END) * 100.0 / 
                    NULLIF(COUNT(*), 0)) as conversion_rate
                FROM crm_opportunities o
                LEFT JOIN crm_partners p ON o.partner_id = p.id
                LEFT JOIN crm_pipeline_stages s ON o.stage_id = s.id
                WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)';

        $params = [];
        
        if (!empty($filters['responsible_user_id'])) {
            $sql .= " AND o.responsible_user_id = :responsible_user_id";
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
        }

        if (!empty($filters['filter_segment'])) {
            $sql .= " AND p.segment = :filter_segment";
            $params[':filter_segment'] = $filters['filter_segment'];
        }

        if (!empty($filters['filter_stage'])) {
            $sql .= " AND s.name = :filter_stage";
            $params[':filter_stage'] = $filters['filter_stage'];
        }

        if (!empty($filters['periodo_inicio'])) {
            $sql .= " AND o.created_at >= :periodo_inicio";
            $params[':periodo_inicio'] = $filters['periodo_inicio'];
        }

        if (!empty($filters['periodo_fim'])) {
            $sql .= " AND o.created_at <= :periodo_fim";
            $params[':periodo_fim'] = $filters['periodo_fim'] . ' 23:59:59';
        }

        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return round((float)($result['conversion_rate'] ?? 0), 1);
    }

    /**
     * Buscar total de oportunidades abertas
     */
    public function getTotalOpenOpportunities(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as total 
                FROM crm_opportunities o
                LEFT JOIN crm_partners p ON o.partner_id = p.id
                LEFT JOIN crm_pipeline_stages s ON o.stage_id = s.id
                WHERE o.status = 'Aberta'";
        
        $params = [];
        
        if (!empty($filters['responsible_user_id'])) {
            $sql .= " AND o.responsible_user_id = :responsible_user_id";
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
        }
        
        if (!empty($filters['partner_id'])) {
            $sql .= " AND o.partner_id = :partner_id";
            $params[':partner_id'] = $filters['partner_id'];
        }
        
        if (!empty($filters['filter_stage'])) {
            $sql .= " AND s.name = :filter_stage";
            $params[':filter_stage'] = $filters['filter_stage'];
        }
        
        if (!empty($filters['filter_segment'])) {
            $sql .= " AND p.segment = :filter_segment";
            $params[':filter_segment'] = $filters['filter_segment'];
        }
        
        if (!empty($filters['periodo_inicio'])) {
            $sql .= " AND o.created_at >= :periodo_inicio";
            $params[':periodo_inicio'] = $filters['periodo_inicio'];
        }
        
        if (!empty($filters['periodo_fim'])) {
            $sql .= " AND o.created_at <= :periodo_fim";
            $params[':periodo_fim'] = $filters['periodo_fim'] . ' 23:59:59';
        }
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Buscar oportunidades por mês (últimos 6 meses)
     */
    public function getOpportunitiesByMonth(array $filters = []): array
    {
        $sql = "SELECT 
                    DATE_FORMAT(o.created_at, '%Y-%m') as month,
                    COUNT(*) as total,
                    SUM(o.value) as total_value
                FROM crm_opportunities o
                LEFT JOIN crm_partners p ON o.partner_id = p.id
                LEFT JOIN crm_pipeline_stages s ON o.stage_id = s.id
                WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";

        $params = [];
        
        if (!empty($filters['responsible_user_id'])) {
            $sql .= " AND o.responsible_user_id = :responsible_user_id";
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
        }
        
        if (!empty($filters['filter_stage'])) {
            $sql .= " AND s.name = :filter_stage";
            $params[':filter_stage'] = $filters['filter_stage'];
        }
        
        if (!empty($filters['filter_segment'])) {
            $sql .= " AND p.segment = :filter_segment";
            $params[':filter_segment'] = $filters['filter_segment'];
        }
        
        $sql .= " GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
                  ORDER BY month ASC";

        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar receita por segmento
     */
    public function getRevenueBySegment(array $filters = []): array
    {
        $sql = "SELECT 
                    p.segment,
                    SUM(o.value) as total_revenue,
                    COUNT(o.id) as total_opportunities
                FROM crm_opportunities o
                INNER JOIN crm_partners p ON o.partner_id = p.id
                LEFT JOIN crm_pipeline_stages s ON o.stage_id = s.id
                WHERE o.status = 'Aberta'";

        $params = [];
        
        if (!empty($filters['responsible_user_id'])) {
            $sql .= " AND o.responsible_user_id = :responsible_user_id";
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
        }
        
        if (!empty($filters['filter_stage'])) {
            $sql .= " AND s.name = :filter_stage";
            $params[':filter_stage'] = $filters['filter_stage'];
        }
        
        if (!empty($filters['periodo_inicio'])) {
            $sql .= " AND o.created_at >= :periodo_inicio";
            $params[':periodo_inicio'] = $filters['periodo_inicio'];
        }
        
        if (!empty($filters['periodo_fim'])) {
            $sql .= " AND o.created_at <= :periodo_fim";
            $params[':periodo_fim'] = $filters['periodo_fim'] . ' 23:59:59';
        }
        
        $sql .= " GROUP BY p.segment
                  ORDER BY total_revenue DESC";

        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar oportunidades de um parceiro
     */
    public function getOpportunitiesByPartner(int $partnerId): array
    {
        $sql = "SELECT 
                    o.*,
                    s.name as stage_name,
                    s.color as stage_color,
                    u.name as responsible_name
                FROM crm_opportunities o
                INNER JOIN crm_pipeline_stages s ON o.stage_id = s.id
                LEFT JOIN adms_users u ON o.responsible_user_id = u.id
                WHERE o.partner_id = :partner_id
                ORDER BY o.created_at DESC";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':partner_id', $partnerId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar histórico de movimentações de etapas da oportunidade
     *
     * @param int $opportunityId ID da oportunidade
     * @return array Histórico de movimentações
     */
    public function getStageHistory(int $opportunityId): array
    {
        $sql = "SELECT 
                    h.*,
                    s1.name as from_stage_name,
                    s1.color as from_stage_color,
                    s2.name as to_stage_name,
                    s2.color as to_stage_color,
                    u.name as moved_by_name
                FROM crm_stage_history h
                LEFT JOIN crm_pipeline_stages s1 ON h.from_stage_id = s1.id
                INNER JOIN crm_pipeline_stages s2 ON h.to_stage_id = s2.id
                INNER JOIN adms_users u ON h.moved_by = u.id
                WHERE h.opportunity_id = :opportunity_id
                ORDER BY h.moved_at DESC";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':opportunity_id', $opportunityId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Atualizar oportunidade
     *
     * @param array $data Dados da oportunidade
     * @return bool Sucesso ou falha
     */
    public function updateOpportunity(array $data): bool
    {
        try {
            // Buscar dados antigos para log
            $oldData = $this->getOpportunity($data['id']);

            $sql = 'UPDATE crm_opportunities SET
                        title = :title,
                        description = :description,
                        partner_id = :partner_id,
                        responsible_user_id = :responsible_user_id,
                        stage_id = :stage_id,
                        value = :value,
                        probability = :probability,
                        expected_close_date = :expected_close_date,
                        next_action = :next_action,
                        next_action_date = :next_action_date,
                        source = :source,
                        status = :status,
                        notes = :notes,
                        updated_by = :updated_by,
                        updated_at = NOW()
                    WHERE id = :id';

            $stmt = $this->getConnection()->prepare($sql);

            $stmt->bindValue(':title', $data['title']);
            $stmt->bindValue(':description', $data['description'] ?? null);
            $stmt->bindValue(':partner_id', $data['partner_id'], PDO::PARAM_INT);
            $stmt->bindValue(':responsible_user_id', $data['responsible_user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':stage_id', $data['stage_id'], PDO::PARAM_INT);
            $stmt->bindValue(':value', $data['value']);
            $stmt->bindValue(':probability', $data['probability'] ?? 50, PDO::PARAM_INT);
            $stmt->bindValue(':expected_close_date', $data['expected_close_date'] ?? null);
            $stmt->bindValue(':next_action', $data['next_action'] ?? null);
            $stmt->bindValue(':next_action_date', $data['next_action_date'] ?? null);
            $stmt->bindValue(':source', $data['source'] ?? null);
            $stmt->bindValue(':status', $data['status'] ?? 'Aberta');
            $stmt->bindValue(':notes', $data['notes'] ?? null);
            $stmt->bindValue(':updated_by', $_SESSION['user_id'] ?? 1, PDO::PARAM_INT);
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);

            $result = $stmt->execute();

            // Registrar log de alteração
            if ($result) {
                LogAlteracaoService::registrarAlteracao(
                    'crm_opportunities',
                    $data['id'],
                    $_SESSION['user_id'] ?? 1,
                    'UPDATE',
                    $oldData,
                    $data
                );
            }

            return $result;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao atualizar oportunidade", [
                'data' => $data,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Deletar oportunidade
     *
     * @param int $id ID da oportunidade
     * @return bool Sucesso ou falha
     */
    public function deleteOpportunity(int $id): bool
    {
        try {
            // Buscar dados para log
            $opportunity = $this->getOpportunity($id);

            $sql = 'DELETE FROM crm_opportunities WHERE id = :id';

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            $result = $stmt->execute();

            // Registrar log de alteração
            if ($result) {
                LogAlteracaoService::registrarAlteracao(
                    'crm_opportunities',
                    $id,
                    $_SESSION['user_id'] ?? 1,
                    'DELETE',
                    $opportunity,
                    []
                );
            }

            return $result;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao deletar oportunidade", [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Listar todas as oportunidades com filtros e paginação
     *
     * @param array $filters Filtros de busca
     * @param int $page Página atual
     * @param int $perPage Itens por página
     * @return array Dados paginados
     */
    public function getAllOpportunities(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        // Query base
        $sql = 'SELECT 
                    o.id,
                    o.code,
                    o.title,
                    o.value,
                    o.probability,
                    o.status,
                    o.created_at,
                    p.name as partner_name,
                    u.name as responsible_name,
                    s.name as stage_name,
                    s.color as stage_color
                FROM crm_opportunities o
                INNER JOIN crm_partners p ON o.partner_id = p.id
                INNER JOIN adms_users u ON o.responsible_user_id = u.id
                INNER JOIN crm_pipeline_stages s ON o.stage_id = s.id
                WHERE 1=1';

        $params = [];

        // Filtros
        if (!empty($filters['search'])) {
            $sql .= ' AND (o.title LIKE :search OR o.code LIKE :search OR p.name LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['stage_id'])) {
            $sql .= ' AND o.stage_id = :stage_id';
            $params[':stage_id'] = $filters['stage_id'];
        }

        if (!empty($filters['responsible_user_id'])) {
            $sql .= ' AND o.responsible_user_id = :responsible_user_id';
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND o.status = :status';
            $params[':status'] = $filters['status'];
        }

        // Contar total de registros
        $sqlCount = str_replace('SELECT o.id, o.code, o.title, o.value, o.probability, o.status, o.created_at, p.name as partner_name, u.name as responsible_name, s.name as stage_name, s.color as stage_color', 'SELECT COUNT(*) as total', $sql);
        $stmtCount = $this->getConnection()->prepare($sqlCount);
        foreach ($params as $key => $value) {
            $stmtCount->bindValue($key, $value);
        }
        $stmtCount->execute();
        $total = (int)$stmtCount->fetchColumn();

        // Buscar dados com paginação
        $sql .= ' ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcular paginação
        $totalPages = ceil($total / $perPage);
        $firstItem = $offset + 1;
        $lastItem = min($offset + $perPage, $total);

        return [
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'first_item' => $firstItem,
                'last_item' => $lastItem,
                'html' => $this->generatePaginationHtml($page, $totalPages, $filters)
            ]
        ];
    }

    /**
     * Gerar HTML de paginação
     */
    private function generatePaginationHtml(int $currentPage, int $totalPages, array $filters = []): string
    {
        if ($totalPages <= 1) {
            return '';
        }

        $html = '<nav><ul class="pagination">';

        // Query string com filtros
        $queryString = http_build_query(array_filter($filters));
        $separator = $queryString ? '&' : '';

        // Botão Anterior
        if ($currentPage > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="?' . $queryString . $separator . 'page=' . ($currentPage - 1) . '">Anterior</a></li>';
        }

        // Páginas
        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $currentPage + 2);

        for ($i = $start; $i <= $end; $i++) {
            $active = $i === $currentPage ? 'active' : '';
            $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="?' . $queryString . $separator . 'page=' . $i . '">' . $i . '</a></li>';
        }

        // Botão Próximo
        if ($currentPage < $totalPages) {
            $html .= '<li class="page-item"><a class="page-link" href="?' . $queryString . $separator . 'page=' . ($currentPage + 1) . '">Próximo</a></li>';
        }

        $html .= '</ul></nav>';

        return $html;
    }

    /**
     * Buscar total de oportunidades ganhas com filtros
     */
    public function getWonOpportunities(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as total 
                FROM crm_opportunities o
                LEFT JOIN crm_partners p ON o.partner_id = p.id
                LEFT JOIN crm_pipeline_stages s ON o.stage_id = s.id
                WHERE o.status = 'Ganha'";
        
        $params = [];
        
        if (!empty($filters['responsible_user_id'])) {
            $sql .= " AND o.responsible_user_id = :responsible_user_id";
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
        }
        
        if (!empty($filters['periodo_inicio'])) {
            $sql .= " AND o.created_at >= :periodo_inicio";
            $params[':periodo_inicio'] = $filters['periodo_inicio'];
        }
        
        if (!empty($filters['periodo_fim'])) {
            $sql .= " AND o.created_at <= :periodo_fim";
            $params[':periodo_fim'] = $filters['periodo_fim'] . ' 23:59:59';
        }
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
}


