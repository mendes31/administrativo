<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

/**
 * Repository responsável pela gestão de parceiros do CRM
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class CrmPartnersRepository extends DbConnection
{
    /**
     * Buscar todos os parceiros com filtros e paginação
     */
    public function getAllPartners(int $page = 1, int $limitResult = 10, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * $limitResult);

        $sql = 'SELECT 
                    p.*,
                    u.name as responsible_name,
                    d.name as department_name
                FROM crm_partners p
                LEFT JOIN adms_users u ON p.responsible_user_id = u.id
                LEFT JOIN adms_departments d ON p.department_id = d.id
                WHERE 1=1';

        $params = [];

        // Filtro por busca (nome, email, documento)
        if (!empty($filters['search'])) {
            $sql .= ' AND (p.name LIKE :search OR p.email LIKE :search OR p.document LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        // Filtro por segmento
        if (!empty($filters['segment'])) {
            $sql .= ' AND p.segment = :segment';
            $params[':segment'] = $filters['segment'];
        }

        // Filtro por tipo de parceiro
        if (!empty($filters['partner_type'])) {
            $sql .= ' AND p.partner_type = :partner_type';
            $params[':partner_type'] = $filters['partner_type'];
        }

        // Filtro por status
        if (!empty($filters['status'])) {
            $sql .= ' AND p.status = :status';
            $params[':status'] = $filters['status'];
        }

        // Filtro por responsável
        if (!empty($filters['responsible_user_id'])) {
            $sql .= ' AND p.responsible_user_id = :responsible_user_id';
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
        }

        $sql .= ' ORDER BY p.id DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $limitResult, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Contar total de parceiros com filtros
     */
    public function getAmountPartners(array $filters = []): int
    {
        $sql = 'SELECT COUNT(id) as amount_records FROM crm_partners WHERE 1=1';

        $params = [];

        if (!empty($filters['search'])) {
            $sql .= ' AND (name LIKE :search OR email LIKE :search OR document LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['segment'])) {
            $sql .= ' AND segment = :segment';
            $params[':segment'] = $filters['segment'];
        }

        if (!empty($filters['partner_type'])) {
            $sql .= ' AND partner_type = :partner_type';
            $params[':partner_type'] = $filters['partner_type'];
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['responsible_user_id'])) {
            $sql .= ' AND responsible_user_id = :responsible_user_id';
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
        }

        $stmt = $this->getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    }

    /**
     * Buscar parceiro específico
     */
    public function getPartner(int $id): array|bool
    {
        $sql = 'SELECT 
                    p.*,
                    u.name as responsible_name,
                    d.name as department_name
                FROM crm_partners p
                LEFT JOIN adms_users u ON p.responsible_user_id = u.id
                LEFT JOIN adms_departments d ON p.department_id = d.id
                WHERE p.id = :id';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Criar novo parceiro
     */
    public function createPartner(array $data): bool|int
    {
        try {
            $sql = 'INSERT INTO crm_partners (
                        code, name, trading_name, type_person, document,
                        email, phone, mobile, website,
                        zip_code, address, number, complement, neighborhood, city, state,
                        segment, partner_type, source,
                        lead_score, priority, status,
                        responsible_user_id, department_id,
                        first_contact_date, last_contact_date, next_contact_date,
                        estimated_revenue, notes, tags,
                        created_by, created_at
                    ) VALUES (
                        :code, :name, :trading_name, :type_person, :document,
                        :email, :phone, :mobile, :website,
                        :zip_code, :address, :number, :complement, :neighborhood, :city, :state,
                        :segment, :partner_type, :source,
                        :lead_score, :priority, :status,
                        :responsible_user_id, :department_id,
                        :first_contact_date, :last_contact_date, :next_contact_date,
                        :estimated_revenue, :notes, :tags,
                        :created_by, NOW()
                    )';

            $stmt = $this->getConnection()->prepare($sql);

            $stmt->bindValue(':code', $data['code']);
            $stmt->bindValue(':name', $data['name']);
            $stmt->bindValue(':trading_name', $data['trading_name'] ?? null);
            $stmt->bindValue(':type_person', $data['type_person']);
            $stmt->bindValue(':document', $data['document'] ?? null);
            
            $stmt->bindValue(':email', $data['email'] ?? null);
            $stmt->bindValue(':phone', $data['phone'] ?? null);
            $stmt->bindValue(':mobile', $data['mobile'] ?? null);
            $stmt->bindValue(':website', $data['website'] ?? null);
            
            $stmt->bindValue(':zip_code', $data['zip_code'] ?? null);
            $stmt->bindValue(':address', $data['address'] ?? null);
            $stmt->bindValue(':number', $data['number'] ?? null);
            $stmt->bindValue(':complement', $data['complement'] ?? null);
            $stmt->bindValue(':neighborhood', $data['neighborhood'] ?? null);
            $stmt->bindValue(':city', $data['city'] ?? null);
            $stmt->bindValue(':state', $data['state'] ?? null);
            
            $stmt->bindValue(':segment', $data['segment']);
            $stmt->bindValue(':partner_type', $data['partner_type'] ?? 'Lead');
            $stmt->bindValue(':source', $data['source'] ?? null);
            
            $stmt->bindValue(':lead_score', $data['lead_score'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':priority', $data['priority'] ?? 'Média');
            $stmt->bindValue(':status', $data['status'] ?? 'Ativo');
            
            $stmt->bindValue(':responsible_user_id', $data['responsible_user_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':department_id', $data['department_id'] ?? null, PDO::PARAM_INT);
            
            $stmt->bindValue(':first_contact_date', $data['first_contact_date'] ?? date('Y-m-d H:i:s'));
            $stmt->bindValue(':last_contact_date', $data['last_contact_date'] ?? null);
            $stmt->bindValue(':next_contact_date', $data['next_contact_date'] ?? null);
            
            $stmt->bindValue(':estimated_revenue', $data['estimated_revenue'] ?? 0);
            $stmt->bindValue(':notes', $data['notes'] ?? null);
            $stmt->bindValue(':tags', $data['tags'] ?? null);
            
            $stmt->bindValue(':created_by', $_SESSION['user_id'] ?? 1, PDO::PARAM_INT);

            $stmt->execute();

            $partnerId = $this->getConnection()->lastInsertId();

            if ($partnerId) {
                LogAlteracaoService::registrarAlteracao(
                    'crm_partners',
                    $partnerId,
                    $_SESSION['user_id'] ?? 1,
                    'INSERT',
                    [],
                    $data
                );
            }

            return $partnerId;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Parceiro não cadastrado.", [
                'name' => $data['name'],
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Atualizar parceiro
     */
    public function updatePartner(array $data): bool
    {
        try {
            $oldData = $this->getPartner($data['id']);

            $sql = 'UPDATE crm_partners SET
                        name = :name, trading_name = :trading_name, type_person = :type_person, document = :document,
                        email = :email, phone = :phone, mobile = :mobile, website = :website,
                        zip_code = :zip_code, address = :address, number = :number, complement = :complement,
                        neighborhood = :neighborhood, city = :city, state = :state,
                        segment = :segment, partner_type = :partner_type, source = :source,
                        lead_score = :lead_score, priority = :priority, status = :status,
                        responsible_user_id = :responsible_user_id, department_id = :department_id,
                        last_contact_date = :last_contact_date, next_contact_date = :next_contact_date,
                        estimated_revenue = :estimated_revenue, notes = :notes, tags = :tags,
                        updated_by = :updated_by, updated_at = NOW()
                    WHERE id = :id';

            $stmt = $this->getConnection()->prepare($sql);

            $stmt->bindValue(':name', $data['name']);
            $stmt->bindValue(':trading_name', $data['trading_name'] ?? null);
            $stmt->bindValue(':type_person', $data['type_person']);
            $stmt->bindValue(':document', $data['document'] ?? null);
            
            $stmt->bindValue(':email', $data['email'] ?? null);
            $stmt->bindValue(':phone', $data['phone'] ?? null);
            $stmt->bindValue(':mobile', $data['mobile'] ?? null);
            $stmt->bindValue(':website', $data['website'] ?? null);
            
            $stmt->bindValue(':zip_code', $data['zip_code'] ?? null);
            $stmt->bindValue(':address', $data['address'] ?? null);
            $stmt->bindValue(':number', $data['number'] ?? null);
            $stmt->bindValue(':complement', $data['complement'] ?? null);
            $stmt->bindValue(':neighborhood', $data['neighborhood'] ?? null);
            $stmt->bindValue(':city', $data['city'] ?? null);
            $stmt->bindValue(':state', $data['state'] ?? null);
            
            $stmt->bindValue(':segment', $data['segment']);
            $stmt->bindValue(':partner_type', $data['partner_type']);
            $stmt->bindValue(':source', $data['source'] ?? null);
            
            $stmt->bindValue(':lead_score', $data['lead_score'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':priority', $data['priority']);
            $stmt->bindValue(':status', $data['status']);
            
            $stmt->bindValue(':responsible_user_id', $data['responsible_user_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':department_id', $data['department_id'] ?? null, PDO::PARAM_INT);
            
            $stmt->bindValue(':last_contact_date', date('Y-m-d H:i:s'));
            $stmt->bindValue(':next_contact_date', $data['next_contact_date'] ?? null);
            
            $stmt->bindValue(':estimated_revenue', $data['estimated_revenue'] ?? 0);
            $stmt->bindValue(':notes', $data['notes'] ?? null);
            $stmt->bindValue(':tags', $data['tags'] ?? null);
            
            $stmt->bindValue(':updated_by', $_SESSION['user_id'] ?? 1, PDO::PARAM_INT);
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);

            $result = $stmt->execute();

            if ($result && $oldData) {
                LogAlteracaoService::registrarAlteracao(
                    'crm_partners',
                    $data['id'],
                    $_SESSION['user_id'] ?? 1,
                    'UPDATE',
                    $oldData,
                    $data
                );
            }

            return $result;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Parceiro não atualizado.", [
                'id' => $data['id'],
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Deletar parceiro
     */
    public function deletePartner(int $id): bool
    {
        try {
            $oldData = $this->getPartner($id);

            $sql = 'DELETE FROM crm_partners WHERE id = :id LIMIT 1';

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $affectedRows = $stmt->rowCount();

            if ($affectedRows > 0) {
                if ($oldData) {
                    LogAlteracaoService::registrarAlteracao(
                        'crm_partners',
                        $id,
                        $_SESSION['user_id'] ?? 1,
                        'DELETE',
                        $oldData,
                        []
                    );
                }
                return true;
            } else {
                GenerateLog::generateLog("error", "Parceiro não apagado.", ['id' => $id]);
                return false;
            }
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Parceiro não apagado.", [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Buscar parceiros para select (dropdown)
     */
    public function getAllPartnersSelect(): array
    {
        $sql = 'SELECT id, code, name, segment
                FROM crm_partners
                WHERE status = "Ativo"
                ORDER BY name ASC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gerar próximo código de parceiro
     */
    public function getNextPartnerCode(): string
    {
        try {
            $sql = "SELECT code FROM crm_partners ORDER BY code DESC LIMIT 1";

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute();

            $lastCode = $stmt->fetchColumn();

            if ($lastCode) {
                $numericPart = (int) substr($lastCode, 1);
                $nextNumber = $numericPart + 1;
            } else {
                $nextNumber = 1;
            }

            return 'P' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao gerar código de parceiro.", [
                'error' => $e->getMessage()
            ]);
            return 'P00001';
        }
    }

    /**
     * Buscar total de leads
     */
    public function getTotalLeads(array $filters = []): int
    {
        $sql = "SELECT COUNT(DISTINCT p.id) as total 
                FROM crm_partners p
                LEFT JOIN crm_opportunities o ON p.id = o.partner_id AND o.status = 'Aberta'
                LEFT JOIN crm_pipeline_stages s ON o.stage_id = s.id
                WHERE p.partner_type = 'Lead'";
        
        $params = [];
        
        if (!empty($filters['responsible_user_id'])) {
            $sql .= " AND (p.responsible_user_id = :responsible_user_id OR o.responsible_user_id = :responsible_user_id2 OR o.id IS NULL)";
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
            $params[':responsible_user_id2'] = $filters['responsible_user_id'];
        }

        if (!empty($filters['filter_segment'])) {
            $sql .= " AND p.segment = :filter_segment";
            $params[':filter_segment'] = $filters['filter_segment'];
        }

        if (!empty($filters['filter_stage'])) {
            $sql .= " AND (s.name = :filter_stage OR o.id IS NOT NULL)";
            $params[':filter_stage'] = $filters['filter_stage'];
        }
        
        if (!empty($filters['periodo_inicio'])) {
            $sql .= " AND p.created_at >= :periodo_inicio";
            $params[':periodo_inicio'] = $filters['periodo_inicio'];
        }
        
        if (!empty($filters['periodo_fim'])) {
            $sql .= " AND p.created_at <= :periodo_fim";
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
     * Buscar variação percentual de leads vs mês anterior
     */
    public function getLeadsChangePercent(): float
    {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM crm_partners WHERE partner_type = 'Lead' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)) as current_month,
                    (SELECT COUNT(*) FROM crm_partners WHERE partner_type = 'Lead' AND created_at >= DATE_SUB(NOW(), INTERVAL 2 MONTH) AND created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)) as previous_month";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $current = (int) $result['current_month'];
        $previous = (int) $result['previous_month'];

        if ($previous == 0) return 0;

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Buscar total de parceiros
     */
    public function getTotalPartners(array $filters = []): int
    {
        $sql = "SELECT COUNT(DISTINCT p.id) as total 
                FROM crm_partners p
                LEFT JOIN crm_opportunities o ON p.id = o.partner_id AND o.status = 'Aberta'
                LEFT JOIN crm_pipeline_stages s ON o.stage_id = s.id
                WHERE p.status = 'Ativo'";
        
        $params = [];
        
        if (!empty($filters['responsible_user_id'])) {
            $sql .= " AND (p.responsible_user_id = :responsible_user_id OR o.responsible_user_id = :responsible_user_id2 OR o.id IS NULL)";
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
            $params[':responsible_user_id2'] = $filters['responsible_user_id'];
        }

        if (!empty($filters['filter_segment'])) {
            $sql .= " AND p.segment = :filter_segment";
            $params[':filter_segment'] = $filters['filter_segment'];
        }

        if (!empty($filters['filter_stage'])) {
            $sql .= " AND (s.name = :filter_stage OR o.id IS NOT NULL)";
            $params[':filter_stage'] = $filters['filter_stage'];
        }
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Buscar top parceiros por receita
     */
    public function getTopPartnersByRevenue(int $limit = 5, array $filters = []): array
    {
        $sql = "SELECT 
                    p.name,
                    p.segment,
                    SUM(o.value) as total_revenue,
                    COUNT(o.id) as total_opportunities
                FROM crm_partners p
                LEFT JOIN crm_opportunities o ON p.id = o.partner_id AND o.status = 'Aberta'
                LEFT JOIN crm_pipeline_stages s ON o.stage_id = s.id
                WHERE p.status = 'Ativo'";

        $params = [];
        
        if (!empty($filters['responsible_user_id'])) {
            $sql .= " AND (p.responsible_user_id = :responsible_user_id OR o.responsible_user_id = :responsible_user_id2)";
            $params[':responsible_user_id'] = $filters['responsible_user_id'];
            $params[':responsible_user_id2'] = $filters['responsible_user_id'];
        }

        // Filtro de segmento (do gráfico)
        if (!empty($filters['filter_segment'])) {
            $sql .= " AND p.segment = :filter_segment";
            $params[':filter_segment'] = $filters['filter_segment'];
        }

        // Filtro de etapa (do gráfico)
        if (!empty($filters['filter_stage'])) {
            $sql .= " AND s.name = :filter_stage";
            $params[':filter_stage'] = $filters['filter_stage'];
        }

        // Filtro de período
        if (!empty($filters['periodo_inicio'])) {
            $sql .= " AND o.created_at >= :periodo_inicio";
            $params[':periodo_inicio'] = $filters['periodo_inicio'];
        }

        if (!empty($filters['periodo_fim'])) {
            $sql .= " AND o.created_at <= :periodo_fim";
            $params[':periodo_fim'] = $filters['periodo_fim'] . ' 23:59:59';
        }

        $sql .= " GROUP BY p.id, p.name, p.segment
                  HAVING total_revenue > 0
                  ORDER BY total_revenue DESC
                  LIMIT :limit";

        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}


