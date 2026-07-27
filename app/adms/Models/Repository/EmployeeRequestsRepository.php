<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
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
        $hasWorkflow = $this->hasWorkflowColumns();

        if ($hasWorkflow) {
            $hasEscLimit = $this->hasEscalationLimitColumns();
            if ($hasEscLimit) {
                $sql = "INSERT INTO adms_employee_requests 
                        (employee_id, request_type, title, description, start_date, end_date,
                         days_requested, amount, requires_manager_approval, status, attachments,
                         current_stage_code, current_approver_user_id, original_approver_user_id,
                         stage_started_at, escalate_after_hours, escalation_count, max_escalation_levels)
                        VALUES 
                        (:employee_id, :request_type, :title, :description, :start_date, :end_date,
                         :days_requested, :amount, :requires_manager_approval, :status, :attachments,
                         :current_stage_code, :current_approver_user_id, :original_approver_user_id,
                         :stage_started_at, :escalate_after_hours, :escalation_count, :max_escalation_levels)";
            } else {
                $sql = "INSERT INTO adms_employee_requests 
                        (employee_id, request_type, title, description, start_date, end_date,
                         days_requested, amount, requires_manager_approval, status, attachments,
                         current_stage_code, current_approver_user_id, original_approver_user_id,
                         stage_started_at, escalate_after_hours)
                        VALUES 
                        (:employee_id, :request_type, :title, :description, :start_date, :end_date,
                         :days_requested, :amount, :requires_manager_approval, :status, :attachments,
                         :current_stage_code, :current_approver_user_id, :original_approver_user_id,
                         :stage_started_at, :escalate_after_hours)";
            }
        } else {
            $sql = "INSERT INTO adms_employee_requests 
                    (employee_id, request_type, title, description, start_date, end_date,
                     days_requested, amount, requires_manager_approval, status, attachments)
                    VALUES 
                    (:employee_id, :request_type, :title, :description, :start_date, :end_date,
                     :days_requested, :amount, :requires_manager_approval, :status, :attachments)";
        }
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':employee_id', $data['employee_id'], PDO::PARAM_INT);
        $stmt->bindValue(':request_type', $data['request_type']);
        $stmt->bindValue(':title', $data['title']);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':start_date', $data['start_date'] ?? null);
        $stmt->bindValue(':end_date', $data['end_date'] ?? null);
        $stmt->bindValue(':days_requested', $data['days_requested'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':amount', $data['amount'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':requires_manager_approval', isset($data['requires_manager_approval']) ? ($data['requires_manager_approval'] ? 1 : 0) : 0, PDO::PARAM_INT);
        $stmt->bindValue(':status', $data['status'] ?? 'pending_hr_approval');
        $stmt->bindValue(':attachments', !empty($data['attachments']) ? json_encode($data['attachments']) : null);

        if ($hasWorkflow) {
            $stmt->bindValue(':current_stage_code', $data['current_stage_code'] ?? null);
            $stmt->bindValue(
                ':current_approver_user_id',
                $data['current_approver_user_id'] ?? null,
                isset($data['current_approver_user_id']) && $data['current_approver_user_id'] !== null
                    ? PDO::PARAM_INT
                    : PDO::PARAM_NULL
            );
            $stmt->bindValue(
                ':original_approver_user_id',
                $data['original_approver_user_id'] ?? null,
                isset($data['original_approver_user_id']) && $data['original_approver_user_id'] !== null
                    ? PDO::PARAM_INT
                    : PDO::PARAM_NULL
            );
            $stmt->bindValue(':stage_started_at', $data['stage_started_at'] ?? date('Y-m-d H:i:s'));
            $stmt->bindValue(
                ':escalate_after_hours',
                $data['escalate_after_hours'] ?? 72,
                PDO::PARAM_INT
            );
            if ($this->hasEscalationLimitColumns()) {
                $stmt->bindValue(':escalation_count', (int) ($data['escalation_count'] ?? 0), PDO::PARAM_INT);
                $stmt->bindValue(
                    ':max_escalation_levels',
                    (int) ($data['max_escalation_levels'] ?? 1),
                    PDO::PARAM_INT
                );
            }
        }
        
        $stmt->execute();

        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $row = $this->getRawEmployeeRequestRow($newId);
            if (is_array($row)) {
                $actor = (int) ($_SESSION['user_id'] ?? 0) > 0
                    ? (int) $_SESSION['user_id']
                    : (int) ($data['employee_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_employee_requests',
                    $newId,
                    $actor,
                    'INSERT',
                    [],
                    $row
                );
            }

            if (!empty($data['current_stage_code'])) {
                $this->addApprovalEvent(
                    $newId,
                    (string) $data['current_stage_code'],
                    'assigned',
                    (int) ($data['current_approver_user_id'] ?? 0) ?: null,
                    (int) ($data['original_approver_user_id'] ?? 0) ?: null,
                    !empty($data['via_delegation']) ? 'Atribuído via delegação' : 'Atribuído na abertura'
                );
            }
        }

        return $newId;
    }

    /**
     * Buscar por ID
     */
    public function getById(int $id): ?array
    {
        $workflowSelect = '';
        $workflowJoin = '';
        if ($this->hasWorkflowColumns()) {
            $workflowSelect = ',
                       ca.name as current_approver_name,
                       oa.name as original_approver_name';
            $workflowJoin = '
                LEFT JOIN adms_users ca ON er.current_approver_user_id = ca.id
                LEFT JOIN adms_users oa ON er.original_approver_user_id = oa.id';
        }

        $sql = "SELECT er.*, 
                       e.name as employee_name, e.email as employee_email,
                       e.immediate_supervisor_id,
                       a.name as approver_name,
                       m.name as manager_name,
                       h.name as hr_name
                       {$workflowSelect},
                       rt.requires_manager_approval
                FROM adms_employee_requests er
                INNER JOIN adms_users e ON er.employee_id = e.id
                LEFT JOIN adms_users a ON er.approved_by = a.id
                LEFT JOIN adms_users m ON er.manager_approved_by = m.id
                LEFT JOIN adms_users h ON er.hr_approved_by = h.id
                {$workflowJoin}
                LEFT JOIN adms_request_types rt ON er.request_type = rt.code
                WHERE er.id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['attachments']) {
            $result['attachments'] = json_decode($result['attachments'], true) ?? [];
        }
        
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
        [$where, $params] = $this->buildFilterClauses($filters);

        $approverJoin = '';
        $approverSelect = '';
        if ($this->hasWorkflowColumns()) {
            $approverSelect = ', ca.name as current_approver_name';
            $approverJoin = 'LEFT JOIN adms_users ca ON er.current_approver_user_id = ca.id';
        }
        
        $sql = "SELECT er.*, e.name as employee_name, e.email as employee_email
                       {$approverSelect}
                FROM adms_employee_requests er
                INNER JOIN adms_users e ON er.employee_id = e.id
                {$approverJoin}
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
     * Avança a solicitação para o próximo estado de etapa (ou finaliza).
     *
     * @param array{
     *   status: string,
     *   current_stage_code: ?string,
     *   current_approver_user_id: ?int,
     *   original_approver_user_id: ?int,
     *   escalate_after_hours?: int,
     *   max_escalation_levels?: int,
     *   escalation_count?: int
     * } $next
     */
    public function applyStageTransition(int $id, int $actorId, array $next, bool $recordManagerApproval = true): bool
    {
        $before = $this->getRawEmployeeRequestRow($id);
        if (!$before) {
            return false;
        }

        $status = (string) ($next['status'] ?? '');
        if ($status === '') {
            return false;
        }

        $sets = [
            'status = :status',
            'updated_at = NOW()',
        ];
        $params = [
            ':id' => $id,
            ':status' => $status,
        ];

        if ($recordManagerApproval && in_array($before['status'] ?? '', ['pending_manager_approval'], true)) {
            $sets[] = 'manager_approved_by = :actor';
            $sets[] = 'manager_approved_at = NOW()';
            $params[':actor'] = $actorId;
        }

        if ($status === 'approved') {
            $sets[] = 'approved_by = :approved_by';
            $sets[] = 'approved_at = NOW()';
            $sets[] = 'hr_approved_by = COALESCE(hr_approved_by, :hr_by)';
            $sets[] = 'hr_approved_at = COALESCE(hr_approved_at, NOW())';
            $params[':approved_by'] = $actorId;
            $params[':hr_by'] = $actorId;
        }

        if ($this->hasWorkflowColumns()) {
            $sets[] = 'current_stage_code = :stage_code';
            $sets[] = 'current_approver_user_id = :approver';
            $sets[] = 'original_approver_user_id = :original';
            $sets[] = 'stage_started_at = :started';
            $sets[] = 'escalate_after_hours = :sla';
            $params[':stage_code'] = $next['current_stage_code'] ?? null;
            $params[':approver'] = $next['current_approver_user_id'] ?? null;
            $params[':original'] = $next['original_approver_user_id'] ?? null;
            $params[':started'] = $status === 'approved' ? null : ($next['stage_started_at'] ?? date('Y-m-d H:i:s'));
            $params[':sla'] = (int) ($next['escalate_after_hours'] ?? 0);
            if ($this->hasEscalationLimitColumns()) {
                $sets[] = 'escalation_count = :esc_count';
                $sets[] = 'max_escalation_levels = :max_esc';
                $params[':esc_count'] = (int) ($next['escalation_count'] ?? 0);
                $params[':max_esc'] = (int) ($next['max_escalation_levels'] ?? 0);
            }
        }

        $sql = 'UPDATE adms_employee_requests SET ' . implode(', ', $sets)
            . " WHERE id = :id AND status IN ('pending_manager_approval', 'pending_hr_approval')";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            if ($value === null) {
                $stmt->bindValue($key, null, PDO::PARAM_NULL);
            } elseif (is_int($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        $ok = $stmt->execute();
        if ($ok && $stmt->rowCount() > 0 && is_array($before)) {
            $after = $this->getRawEmployeeRequestRow($id);
            if (is_array($after)) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_employee_requests',
                    $id,
                    $actorId,
                    'UPDATE',
                    $before,
                    $after
                );
            }
        }

        return $ok && $stmt->rowCount() > 0;
    }

    /**
     * Aprovar solicitação pelo gestor
     */
    public function approveByManager(int $id, int $approvedBy): bool
    {
        $before = $this->getRawEmployeeRequestRow($id);
        $sql = "UPDATE adms_employee_requests 
                SET status = 'pending_hr_approval', 
                    manager_approved_by = :approved_by, 
                    manager_approved_at = NOW(), 
                    updated_at = NOW()
                WHERE id = :id AND status = 'pending_manager_approval'";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':approved_by', $approvedBy, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok && $stmt->rowCount() > 0 && is_array($before)) {
            $after = $this->getRawEmployeeRequestRow($id);
            if (is_array($after)) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_employee_requests',
                    $id,
                    $approvedBy,
                    'UPDATE',
                    $before,
                    $after
                );
            }
        }

        return $ok;
    }

    /**
     * Rejeitar solicitação pelo gestor
     */
    public function rejectByManager(int $id, int $approvedBy, string $reason): bool
    {
        $before = $this->getRawEmployeeRequestRow($id);
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
        $ok = $stmt->execute();
        if ($ok && $stmt->rowCount() > 0 && is_array($before)) {
            $after = $this->getRawEmployeeRequestRow($id);
            if (is_array($after)) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_employee_requests',
                    $id,
                    $approvedBy,
                    'UPDATE',
                    $before,
                    $after
                );
            }
        }

        return $ok;
    }

    /**
     * Aprovar solicitação pelo RH
     */
    public function approveByHR(int $id, int $approvedBy): bool
    {
        $before = $this->getRawEmployeeRequestRow($id);
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
        $ok = $stmt->execute();
        if ($ok && $stmt->rowCount() > 0 && is_array($before)) {
            $after = $this->getRawEmployeeRequestRow($id);
            if (is_array($after)) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_employee_requests',
                    $id,
                    $approvedBy,
                    'UPDATE',
                    $before,
                    $after
                );
            }
        }

        return $ok;
    }

    /**
     * Rejeitar solicitação pelo RH
     */
    public function rejectByHR(int $id, int $approvedBy, string $reason): bool
    {
        $before = $this->getRawEmployeeRequestRow($id);
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
        $ok = $stmt->execute();
        if ($ok && $stmt->rowCount() > 0 && is_array($before)) {
            $after = $this->getRawEmployeeRequestRow($id);
            if (is_array($after)) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_employee_requests',
                    $id,
                    $approvedBy,
                    'UPDATE',
                    $before,
                    $after
                );
            }
        }

        return $ok;
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

        $before = $this->getRawEmployeeRequestRow($id);
        
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
        $ok = $stmt->execute();
        if ($ok && $stmt->rowCount() > 0 && is_array($before)) {
            $after = $this->getRawEmployeeRequestRow($id);
            if (is_array($after)) {
                $actor = (int) ($_SESSION['user_id'] ?? 0) > 0 ? (int) $_SESSION['user_id'] : (int) ($before['employee_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_employee_requests',
                    $id,
                    $actor,
                    'UPDATE',
                    $before,
                    $after
                );
            }
        }

        return $ok;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getRawEmployeeRequestRow(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare('SELECT * FROM adms_employee_requests WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * Contar total de solicitações com filtros
     */
    public function count(array $filters = []): int
    {
        [$where, $params] = $this->buildFilterClauses($filters);

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
        return (int) ($result['total'] ?? 0);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listDueForEscalation(string $now): array
    {
        if (!$this->hasWorkflowColumns()) {
            return [];
        }

        $sql = "SELECT er.*
                FROM adms_employee_requests er
                WHERE er.status = 'pending_manager_approval'
                  AND er.stage_started_at IS NOT NULL
                  AND COALESCE(er.escalate_after_hours, 0) > 0
                  AND TIMESTAMPADD(HOUR, er.escalate_after_hours, er.stage_started_at) <= :now
                ORDER BY er.id ASC
                LIMIT 200";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':now', $now);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function updateWorkflowState(int $id, array $data): bool
    {
        if (!$this->hasWorkflowColumns() || $id <= 0) {
            return false;
        }

        $fields = [];
        $params = [':id' => $id];
        $map = [
            'current_stage_code' => PDO::PARAM_STR,
            'current_approver_user_id' => PDO::PARAM_INT,
            'original_approver_user_id' => PDO::PARAM_INT,
            'stage_started_at' => PDO::PARAM_STR,
            'escalate_after_hours' => PDO::PARAM_INT,
            'escalation_count' => PDO::PARAM_INT,
            'max_escalation_levels' => PDO::PARAM_INT,
        ];

        foreach ($map as $field => $type) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $fields[] = "{$field} = :{$field}";
            $params[":{$field}"] = $data[$field];
        }

        if ($fields === []) {
            return false;
        }

        $fields[] = 'updated_at = NOW()';
        $sql = 'UPDATE adms_employee_requests SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            if ($value === null) {
                $stmt->bindValue($key, null, PDO::PARAM_NULL);
            } elseif ($key !== ':id' && isset($map[substr($key, 1)])) {
                $stmt->bindValue($key, $value, $map[substr($key, 1)]);
            } else {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            }
        }

        return $stmt->execute();
    }

    public function addApprovalEvent(
        int $requestId,
        ?string $stageCode,
        string $action,
        ?int $actorUserId,
        ?int $onBehalfOf,
        ?string $notes
    ): void {
        if ($requestId <= 0 || !$this->approvalEventsTableExists()) {
            return;
        }

        try {
            $sql = 'INSERT INTO adms_employee_request_approval_events
                        (request_id, stage_code, action, actor_user_id, on_behalf_of_user_id, notes, created_at)
                    VALUES
                        (:request_id, :stage_code, :action, :actor, :behalf, :notes, NOW())';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':request_id', $requestId, PDO::PARAM_INT);
            $stmt->bindValue(':stage_code', $stageCode);
            $stmt->bindValue(':action', $action);
            $stmt->bindValue(':actor', $actorUserId, $actorUserId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':behalf', $onBehalfOf, $onBehalfOf !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':notes', $notes);
            $stmt->execute();
        } catch (\Throwable) {
            // Não bloqueia o fluxo principal.
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listApprovalEvents(int $requestId): array
    {
        if ($requestId <= 0 || !$this->approvalEventsTableExists()) {
            return [];
        }

        $sql = 'SELECT ev.*,
                       a.name AS actor_name,
                       b.name AS on_behalf_name
                FROM adms_employee_request_approval_events ev
                LEFT JOIN adms_users a ON a.id = ev.actor_user_id
                LEFT JOIN adms_users b ON b.id = ev.on_behalf_of_user_id
                WHERE ev.request_id = :id
                ORDER BY ev.id ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $requestId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array{0: list<string>, 1: array<string, mixed>}
     */
    private function buildFilterClauses(array $filters): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['employee_id'])) {
            $where[] = 'er.employee_id = :employee_id';
            $params[':employee_id'] = $filters['employee_id'];
        }

        if (!empty($filters['employee_ids']) && is_array($filters['employee_ids'])) {
            $placeholders = [];
            foreach (array_values($filters['employee_ids']) as $index => $empId) {
                $key = ':employee_id_' . $index;
                $placeholders[] = $key;
                $params[$key] = (int) $empId;
            }
            if ($placeholders !== []) {
                $where[] = 'er.employee_id IN (' . implode(', ', $placeholders) . ')';
            }
        }

        if (!empty($filters['current_approver_ids']) && is_array($filters['current_approver_ids'])) {
            $placeholders = [];
            foreach (array_values($filters['current_approver_ids']) as $index => $approverId) {
                $key = ':approver_id_' . $index;
                $placeholders[] = $key;
                $params[$key] = (int) $approverId;
            }
            if ($placeholders !== []) {
                $where[] = 'er.current_approver_user_id IN (' . implode(', ', $placeholders) . ')';
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

        if (
            empty($filters['employee_ids'])
            && empty($filters['current_approver_ids'])
            && empty($filters['skip_owner_scope'])
        ) {
            $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
            $userId = $_SESSION['user_id'] ?? 0;

            if (!$isSuperAdmin && empty($filters['employee_id'])) {
                $where[] = 'er.employee_id = :user_id';
                $params[':user_id'] = $userId;
            }
        }

        return [$where, $params];
    }

    private function hasWorkflowColumns(): bool
    {
        static $has = null;
        if ($has !== null) {
            return $has;
        }

        try {
            $stmt = $this->getConnection()->query("SHOW COLUMNS FROM adms_employee_requests LIKE 'current_stage_code'");
            $has = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            $has = false;
        }

        return $has;
    }

    private function hasEscalationLimitColumns(): bool
    {
        static $has = null;
        if ($has !== null) {
            return $has;
        }

        try {
            $stmt = $this->getConnection()->query("SHOW COLUMNS FROM adms_employee_requests LIKE 'max_escalation_levels'");
            $has = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            $has = false;
        }

        return $has;
    }

    private function approvalEventsTableExists(): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }

        try {
            $this->getConnection()->query('SELECT 1 FROM adms_employee_request_approval_events LIMIT 1');
            $exists = true;
        } catch (\Throwable) {
            $exists = false;
        }

        return $exists;
    }
}

