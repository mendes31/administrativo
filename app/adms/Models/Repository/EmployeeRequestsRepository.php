<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Repository para gerenciar solicitações do colaborador
 */
class EmployeeRequestsRepository extends DbConnection
{
    /**
     * Criar nova solicitação
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO adms_employee_requests 
                (employee_id, request_type, title, description, start_date, end_date,
                 days_requested, amount, requires_manager_approval, status, attachments)
                VALUES 
                (:employee_id, :request_type, :title, :description, :start_date, :end_date,
                 :days_requested, :amount, :requires_manager_approval, :status, :attachments)";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':employee_id', $data['employee_id'], PDO::PARAM_INT);
        $stmt->bindValue(':request_type', $data['request_type']);
        $stmt->bindValue(':title', $data['title']);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':start_date', $data['start_date'] ?? null);
        $stmt->bindValue(':end_date', $data['end_date'] ?? null);
        $stmt->bindValue(':days_requested', $data['days_requested'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':amount', $data['amount'] ?? null, PDO::PARAM_STR);
        // requires_manager_approval agora vem do tipo de solicitação, mas mantemos no registro para histórico
        $stmt->bindValue(':requires_manager_approval', isset($data['requires_manager_approval']) ? ($data['requires_manager_approval'] ? 1 : 0) : 0, PDO::PARAM_INT);
        $stmt->bindValue(':status', $data['status'] ?? 'pending_hr_approval');
        $stmt->bindValue(':attachments', $data['attachments'] ? json_encode($data['attachments']) : null);
        
        $stmt->execute();
        
        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * Buscar por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT er.*, 
                       e.name as employee_name, e.email as employee_email,
                       e.immediate_supervisor_id,
                       a.name as approver_name,
                       m.name as manager_name,
                       h.name as hr_name,
                       rt.requires_manager_approval
                FROM adms_employee_requests er
                INNER JOIN adms_users e ON er.employee_id = e.id
                LEFT JOIN adms_users a ON er.approved_by = a.id
                LEFT JOIN adms_users m ON er.manager_approved_by = m.id
                LEFT JOIN adms_users h ON er.hr_approved_by = h.id
                LEFT JOIN adms_request_types rt ON er.request_type = rt.code
                WHERE er.id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['attachments']) {
            $result['attachments'] = json_decode($result['attachments'], true) ?? [];
        }
        
        // Garantir que requires_manager_approval seja boolean
        if ($result) {
            $result['requires_manager_approval'] = !empty($result['requires_manager_approval']);
        }
        
        return $result ?: null;
    }

    /**
     * Listar solicitações com filtros
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);
        
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['employee_id'])) {
            $where[] = 'er.employee_id = :employee_id';
            $params[':employee_id'] = $filters['employee_id'];
        }
        
        if (!empty($filters['request_type'])) {
            $where[] = 'er.request_type = :request_type';
            $params[':request_type'] = $filters['request_type'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = 'er.status = :status';
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = '(e.name LIKE :search OR er.title LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        // Permissões: colaborador vê apenas suas solicitações, gestor vê da equipe
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        $userId = $_SESSION['user_id'] ?? 0;
        
        if (!$isSuperAdmin) {
            $where[] = 'er.employee_id = :user_id';
            $params[':user_id'] = $userId;
        }
        
        $sql = "SELECT er.*, e.name as employee_name, e.email as employee_email
                FROM adms_employee_requests er
                INNER JOIN adms_users e ON er.employee_id = e.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY er.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($results as &$result) {
            if ($result['attachments']) {
                $result['attachments'] = json_decode($result['attachments'], true) ?? [];
            }
        }
        
        return $results;
    }

    /**
     * Aprovar solicitação pelo gestor
     */
    public function approveByManager(int $id, int $approvedBy): bool
    {
        $sql = "UPDATE adms_employee_requests 
                SET status = 'pending_hr_approval', 
                    manager_approved_by = :approved_by, 
                    manager_approved_at = NOW(), 
                    updated_at = NOW()
                WHERE id = :id AND status = 'pending_manager_approval'";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':approved_by', $approvedBy, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Rejeitar solicitação pelo gestor
     */
    public function rejectByManager(int $id, int $approvedBy, string $reason): bool
    {
        $sql = "UPDATE adms_employee_requests 
                SET status = 'rejected', 
                    manager_approved_by = :approved_by, 
                    manager_approved_at = NOW(), 
                    manager_rejection_reason = :reason, 
                    updated_at = NOW()
                WHERE id = :id AND status = 'pending_manager_approval'";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':approved_by', $approvedBy, PDO::PARAM_INT);
        $stmt->bindValue(':reason', $reason);
        
        return $stmt->execute();
    }

    /**
     * Aprovar solicitação pelo RH
     */
    public function approveByHR(int $id, int $approvedBy): bool
    {
        $sql = "UPDATE adms_employee_requests 
                SET status = 'approved', 
                    hr_approved_by = :approved_by, 
                    hr_approved_at = NOW(), 
                    approved_by = :approved_by,
                    approved_at = NOW(),
                    updated_at = NOW()
                WHERE id = :id AND status = 'pending_hr_approval'";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':approved_by', $approvedBy, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Rejeitar solicitação pelo RH
     */
    public function rejectByHR(int $id, int $approvedBy, string $reason): bool
    {
        $sql = "UPDATE adms_employee_requests 
                SET status = 'rejected', 
                    hr_approved_by = :approved_by, 
                    hr_approved_at = NOW(), 
                    hr_rejection_reason = :reason, 
                    updated_at = NOW()
                WHERE id = :id AND status = 'pending_hr_approval'";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':approved_by', $approvedBy, PDO::PARAM_INT);
        $stmt->bindValue(':reason', $reason);
        
        return $stmt->execute();
    }

    /**
     * Aprovar solicitação (método genérico - mantido para compatibilidade)
     */
    public function approve(int $id, int $approvedBy): bool
    {
        // Verificar status atual
        $request = $this->getById($id);
        if (!$request) {
            return false;
        }

        if ($request['status'] === 'pending_manager_approval') {
            return $this->approveByManager($id, $approvedBy);
        } elseif ($request['status'] === 'pending_hr_approval') {
            return $this->approveByHR($id, $approvedBy);
        }

        return false;
    }

    /**
     * Rejeitar solicitação (método genérico - mantido para compatibilidade)
     */
    public function reject(int $id, int $approvedBy, string $reason): bool
    {
        // Verificar status atual
        $request = $this->getById($id);
        if (!$request) {
            return false;
        }

        if ($request['status'] === 'pending_manager_approval') {
            return $this->rejectByManager($id, $approvedBy, $reason);
        } elseif ($request['status'] === 'pending_hr_approval') {
            return $this->rejectByHR($id, $approvedBy, $reason);
        }

        return false;
    }

    /**
     * Atualizar solicitação
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        
        $allowedFields = ['request_type', 'title', 'description', 'start_date', 'end_date', 
                         'days_requested', 'amount', 'requires_manager_approval', 'status', 'attachments',
                         'manager_approved_by', 'manager_approved_at', 'manager_rejection_reason',
                         'hr_approved_by', 'hr_approved_at', 'hr_rejection_reason'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = :{$field}";
                if ($field === 'attachments' && is_array($data[$field])) {
                    $values[":{$field}"] = json_encode($data[$field]);
                } elseif (in_array($field, ['requires_manager_approval'])) {
                    $values[":{$field}"] = $data[$field] ? 1 : 0;
                } elseif (in_array($field, ['manager_approved_by', 'hr_approved_by'])) {
                    $values[":{$field}"] = $data[$field] ?: null;
                } else {
                    $values[":{$field}"] = $data[$field];
                }
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $values[':id'] = $id;
        $fields[] = "updated_at = NOW()";
        
        $sql = "UPDATE adms_employee_requests SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($values as $key => $value) {
            $type = PDO::PARAM_STR;
            if ($key === ':id' || $key === ':days_requested' || 
                in_array($key, [':requires_manager_approval', ':manager_approved_by', ':hr_approved_by'])) {
                $type = PDO::PARAM_INT;
            }
            $stmt->bindValue($key, $value, $type);
        }
        
        return $stmt->execute();
    }

    /**
     * Contar total de solicitações com filtros
     */
    public function count(array $filters = []): int
    {
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['employee_id'])) {
            $where[] = 'er.employee_id = :employee_id';
            $params[':employee_id'] = $filters['employee_id'];
        }
        
        // Suporte para múltiplos employee_ids (para gestores verem subordinados)
        if (!empty($filters['employee_ids']) && is_array($filters['employee_ids'])) {
            $placeholders = [];
            foreach ($filters['employee_ids'] as $index => $empId) {
                $key = ':employee_id_' . $index;
                $placeholders[] = $key;
                $params[$key] = $empId;
            }
            if (!empty($placeholders)) {
                $where[] = 'er.employee_id IN (' . implode(', ', $placeholders) . ')';
            }
        }
        
        if (!empty($filters['request_type'])) {
            $where[] = 'er.request_type = :request_type';
            $params[':request_type'] = $filters['request_type'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = 'er.status = :status';
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = '(e.name LIKE :search OR er.title LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        // Permissões: colaborador vê apenas suas solicitações, gestor vê da equipe
        // NOTA: Se employee_ids foi passado, não aplicar filtro de user_id
        if (empty($filters['employee_ids'])) {
            $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
            $userId = $_SESSION['user_id'] ?? 0;
            
            if (!$isSuperAdmin && empty($filters['employee_id'])) {
                $where[] = 'er.employee_id = :user_id';
                $params[':user_id'] = $userId;
            }
        }
        
        $sql = "SELECT COUNT(*) as total
                FROM adms_employee_requests er
                INNER JOIN adms_users e ON er.employee_id = e.id
                WHERE " . implode(' AND ', $where);
        
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }
}

