<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

/**
 * Repository para gerenciar chamados/tickets do colaborador
 */
class EmployeeTicketsRepository extends DbConnection
{
    /**
     * Criar novo chamado
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO adms_employee_tickets 
                (employee_id, ticket_type, priority, title, description, status, 
                 assigned_to, department, attachments)
                VALUES 
                (:employee_id, :ticket_type, :priority, :title, :description, :status,
                 :assigned_to, :department, :attachments)";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':employee_id', $data['employee_id'], PDO::PARAM_INT);
        $stmt->bindValue(':ticket_type', $data['ticket_type']);
        $stmt->bindValue(':priority', $data['priority'] ?? 'medium');
        $stmt->bindValue(':title', $data['title']);
        $stmt->bindValue(':description', $data['description']);
        $stmt->bindValue(':status', $data['status'] ?? 'open');
        $stmt->bindValue(':assigned_to', $data['assigned_to'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':department', $data['department'] ?? null);
        $stmt->bindValue(':attachments', $data['attachments'] ? json_encode($data['attachments']) : null);
        
        $stmt->execute();
        
        $ticketId = (int)$this->getConnection()->lastInsertId();
        
        // Criar histórico inicial
        $this->addHistory($ticketId, $data['employee_id'], 'created', null, null, 'Chamado criado');

        $row = $this->getRawEmployeeTicketRow($ticketId);
        if (is_array($row)) {
            $actor = (int) ($_SESSION['user_id'] ?? 0) > 0
                ? (int) $_SESSION['user_id']
                : (int) ($data['employee_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao(
                'adms_employee_tickets',
                $ticketId,
                $actor,
                'INSERT',
                [],
                $row
            );
        }

        return $ticketId;
    }

    /**
     * Buscar por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT et.*, 
                       e.name as employee_name, e.email as employee_email,
                       a.name as assigned_name
                FROM adms_employee_tickets et
                INNER JOIN adms_users e ON et.employee_id = e.id
                LEFT JOIN adms_users a ON et.assigned_to = a.id
                WHERE et.id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['attachments']) {
            $result['attachments'] = json_decode($result['attachments'], true) ?? [];
        }
        
        return $result ?: null;
    }

    /**
     * Listar chamados com filtros
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);
        
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['employee_id'])) {
            $where[] = 'et.employee_id = :employee_id';
            $params[':employee_id'] = $filters['employee_id'];
        }
        
        if (!empty($filters['ticket_type'])) {
            $where[] = 'et.ticket_type = :ticket_type';
            $params[':ticket_type'] = $filters['ticket_type'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = 'et.status = :status';
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['priority'])) {
            $where[] = 'et.priority = :priority';
            $params[':priority'] = $filters['priority'];
        }
        
        if (!empty($filters['assigned_to'])) {
            $where[] = 'et.assigned_to = :assigned_to';
            $params[':assigned_to'] = $filters['assigned_to'];
        }
        
        // Permissões: colaborador vê apenas seus chamados, gestor vê da equipe
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        $userId = $_SESSION['user_id'] ?? 0;
        
        if (!$isSuperAdmin) {
            $where[] = 'et.employee_id = :user_id';
            $params[':user_id'] = $userId;
        }
        
        $sql = "SELECT et.*, e.name as employee_name, e.email as employee_email
                FROM adms_employee_tickets et
                INNER JOIN adms_users e ON et.employee_id = e.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY et.created_at DESC
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
     * Atualizar chamado
     */
    public function update(int $id, array $data): bool
    {
        $before = $this->getRawEmployeeTicketRow($id);
        $fields = [];
        $values = [];
        
        $allowedFields = ['ticket_type', 'priority', 'title', 'description', 'status',
                         'assigned_to', 'department', 'resolution', 'resolved_at', 
                         'resolved_by', 'attachments'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = :{$field}";
                if ($field === 'attachments' && is_array($data[$field])) {
                    $values[":{$field}"] = json_encode($data[$field]);
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
        
        $sql = "UPDATE adms_employee_tickets SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($values as $key => $value) {
            $type = PDO::PARAM_STR;
            if ($key === ':id' || $key === ':assigned_to' || $key === ':resolved_by') {
                $type = PDO::PARAM_INT;
            }
            $stmt->bindValue($key, $value, $type);
        }
        $ok = $stmt->execute();
        if ($ok && $stmt->rowCount() > 0 && is_array($before)) {
            $after = $this->getRawEmployeeTicketRow($id);
            if (is_array($after)) {
                $actor = (int) ($_SESSION['user_id'] ?? 0) > 0 ? (int) $_SESSION['user_id'] : (int) ($before['employee_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_employee_tickets',
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
    private function getRawEmployeeTicketRow(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare('SELECT * FROM adms_employee_tickets WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * Adicionar histórico ao chamado
     */
    public function addHistory(int $ticketId, int $userId, string $action, ?string $oldValue, ?string $newValue, ?string $comment): bool
    {
        $sql = "INSERT INTO adms_employee_ticket_history 
                (ticket_id, user_id, action, old_value, new_value, comment)
                VALUES 
                (:ticket_id, :user_id, :action, :old_value, :new_value, :comment)";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':ticket_id', $ticketId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':action', $action);
        $stmt->bindValue(':old_value', $oldValue);
        $stmt->bindValue(':new_value', $newValue);
        $stmt->bindValue(':comment', $comment);
        
        return $stmt->execute();
    }

    /**
     * Obter histórico do chamado
     */
    public function getHistory(int $ticketId): array
    {
        $sql = "SELECT eth.*, u.name as user_name
                FROM adms_employee_ticket_history eth
                INNER JOIN adms_users u ON eth.user_id = u.id
                WHERE eth.ticket_id = :ticket_id
                ORDER BY eth.created_at ASC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':ticket_id', $ticketId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar chamados por colaborador com filtros e paginação
     */
    public function getByEmployeeId(int $employeeId, array $filters = [], int $page = 1, int $limit = 20): array
    {
        return $this->getAll(array_merge($filters, ['employee_id' => $employeeId]), $page, $limit);
    }

    /**
     * Contar total de chamados por colaborador com filtros
     */
    public function getTotalByEmployeeId(int $employeeId, array $filters = []): int
    {
        $where = ['et.employee_id = :employee_id'];
        $params = [':employee_id' => $employeeId];
        
        if (!empty($filters['ticket_type'])) {
            $where[] = 'et.ticket_type = :ticket_type';
            $params[':ticket_type'] = $filters['ticket_type'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = 'et.status = :status';
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['priority'])) {
            $where[] = 'et.priority = :priority';
            $params[':priority'] = $filters['priority'];
        }
        
        $sql = "SELECT COUNT(*) as total
                FROM adms_employee_tickets et
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

