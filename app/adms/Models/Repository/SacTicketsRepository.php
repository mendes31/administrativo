<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\TextEncodingHelper;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class SacTicketsRepository extends DbConnection
{
    public function getAllTickets(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $whereConditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $whereConditions[] = '(t.code LIKE :search OR t.subject LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['status'])) {
            $whereConditions[] = 't.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['priority'])) {
            $whereConditions[] = 't.priority = :priority';
            $params[':priority'] = $filters['priority'];
        }

        if (!empty($filters['category_id'])) {
            $whereConditions[] = 't.category_id = :category_id';
            $params[':category_id'] = (int)$filters['category_id'];
        }

        if (!empty($filters['client_id'])) {
            $whereConditions[] = 't.client_id = :client_id';
            $params[':client_id'] = (int)$filters['client_id'];
        }

        if (!empty($filters['assigned_user_id'])) {
            $whereConditions[] = 't.assigned_user_id = :assigned_user_id';
            $params[':assigned_user_id'] = (int)$filters['assigned_user_id'];
        }

        if (!empty($filters['department_id'])) {
            $whereConditions[] = 't.department_id = :department_id';
            $params[':department_id'] = (int)$filters['department_id'];
        }

        if (!empty($filters['channel'])) {
            $whereConditions[] = 't.channel = :channel';
            $params[':channel'] = $filters['channel'];
        }

        if (isset($filters['sla_breached']) && $filters['sla_breached'] !== '') {
            $whereConditions[] = '(t.sla_response_breached = :sla_breached OR t.sla_resolution_breached = :sla_breached)';
            $params[':sla_breached'] = (int)$filters['sla_breached'];
        }

        if (!empty($filters['date_from'])) {
            $whereConditions[] = 'DATE(t.created_at) >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $whereConditions[] = 'DATE(t.created_at) <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }

        $sql = "SELECT t.*,
                    cl.razao_social as client_razao_social,
                    cl.nome_fantasia as client_nome_fantasia,
                    cat.name as category_name,
                    cat.color as category_color,
                    u.name as assigned_name
                FROM sac_tickets t
                LEFT JOIN sac_clients cl ON t.client_id = cl.id
                LEFT JOIN sac_categories cat ON t.category_id = cat.id
                LEFT JOIN adms_users u ON t.assigned_user_id = u.id
                {$whereClause}
                ORDER BY
                    CASE t.priority
                        WHEN 'Urgente' THEN 1
                        WHEN 'Alta' THEN 2
                        WHEN 'Média' THEN 3
                        WHEN 'Baixa' THEN 4
                        ELSE 5
                    END ASC,
                    t.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $this->normalizeRows($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    public function getTotalTickets(array $filters = []): int
    {
        $whereConditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $whereConditions[] = '(t.code LIKE :search OR t.subject LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['status'])) {
            $whereConditions[] = 't.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['priority'])) {
            $whereConditions[] = 't.priority = :priority';
            $params[':priority'] = $filters['priority'];
        }

        if (!empty($filters['category_id'])) {
            $whereConditions[] = 't.category_id = :category_id';
            $params[':category_id'] = (int)$filters['category_id'];
        }

        if (!empty($filters['client_id'])) {
            $whereConditions[] = 't.client_id = :client_id';
            $params[':client_id'] = (int)$filters['client_id'];
        }

        if (!empty($filters['assigned_user_id'])) {
            $whereConditions[] = 't.assigned_user_id = :assigned_user_id';
            $params[':assigned_user_id'] = (int)$filters['assigned_user_id'];
        }

        if (!empty($filters['department_id'])) {
            $whereConditions[] = 't.department_id = :department_id';
            $params[':department_id'] = (int)$filters['department_id'];
        }

        if (!empty($filters['channel'])) {
            $whereConditions[] = 't.channel = :channel';
            $params[':channel'] = $filters['channel'];
        }

        if (isset($filters['sla_breached']) && $filters['sla_breached'] !== '') {
            $whereConditions[] = '(t.sla_response_breached = :sla_breached OR t.sla_resolution_breached = :sla_breached)';
            $params[':sla_breached'] = (int)$filters['sla_breached'];
        }

        if (!empty($filters['date_from'])) {
            $whereConditions[] = 'DATE(t.created_at) >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $whereConditions[] = 'DATE(t.created_at) <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }

        $sql = "SELECT COUNT(*) as total FROM sac_tickets t {$whereClause}";
        $stmt = $this->getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)$result['total'];
    }

    public function getTicketById(int $id): ?array
    {
        $sql = "SELECT t.*,
                    cl.razao_social as client_razao_social,
                    cl.nome_fantasia as client_nome_fantasia,
                    cl.email as client_email,
                    cl.phone as client_phone,
                    cl.mobile as client_mobile,
                    cat.name as category_name,
                    cat.color as category_color,
                    u.name as assigned_name,
                    d.name as department_name
                FROM sac_tickets t
                LEFT JOIN sac_clients cl ON t.client_id = cl.id
                LEFT JOIN sac_categories cat ON t.category_id = cat.id
                LEFT JOIN adms_users u ON t.assigned_user_id = u.id
                LEFT JOIN adms_departments d ON t.department_id = d.id
                WHERE t.id = :id";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $this->normalizeRow($result) : null;
    }

    public function getNextCode(): string
    {
        $sql = "SELECT code FROM sac_tickets ORDER BY id DESC LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && !empty($result['code'])) {
            $number = (int)str_replace('TK-', '', $result['code']);
            return 'TK-' . str_pad((string)($number + 1), 5, '0', STR_PAD_LEFT);
        }

        return 'TK-00001';
    }

    public function createTicket(array $data): int|false
    {
        $sql = "INSERT INTO sac_tickets (code, subject, description, product, batch, status, priority, channel, category_id, client_id, assigned_user_id, department_id, sla_response_deadline, sla_resolution_deadline, created_by, created_at, updated_at)
                VALUES (:code, :subject, :description, :product, :batch, :status, :priority, :channel, :category_id, :client_id, :assigned_user_id, :department_id, :sla_response_deadline, :sla_resolution_deadline, :created_by, NOW(), NOW())";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':code', $data['code'], PDO::PARAM_STR);
        $stmt->bindValue(':subject', $data['subject'], PDO::PARAM_STR);
        $stmt->bindValue(':description', $data['description'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':product', $data['product'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':batch', $data['batch'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status'] ?? 'Aberto', PDO::PARAM_STR);
        $stmt->bindValue(':priority', $data['priority'] ?? 'Média', PDO::PARAM_STR);
        $stmt->bindValue(':channel', $data['channel'] ?? 'Portal', PDO::PARAM_STR);
        $stmt->bindValue(':category_id', $data['category_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':client_id', $data['client_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':assigned_user_id', $data['assigned_user_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':department_id', $data['department_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':sla_response_deadline', $data['sla_response_deadline'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':sla_resolution_deadline', $data['sla_resolution_deadline'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':created_by', $data['created_by'] ?? null, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            return false;
        }

        $newId = (int)$this->getConnection()->lastInsertId();

        if ($newId > 0) {
            $newData = $this->getTicketById($newId);
            if (is_array($newData)) {
                $usuarioId = (int)($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'sac_tickets',
                    $newId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $newData
                );
            }
        }

        return $newId;
    }

    public function updateTicket(int $id, array $data): bool
    {
        $oldData = $this->getTicketById($id);

        $sets = [];
        $binds = [];
        $allowedFields = [
            'subject', 'description', 'product', 'batch',
            'status', 'priority', 'channel',
            'category_id', 'client_id', 'assigned_user_id', 'department_id',
            'sla_response_deadline', 'sla_resolution_deadline',
            'sla_response_breached', 'sla_resolution_breached',
            'first_response_at', 'resolved_at', 'closed_at',
            'satisfaction_rating', 'satisfaction_comment',
            'updated_by',
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "{$field} = :{$field}";
                $binds[$field] = $data[$field];
            }
        }

        if (empty($sets)) {
            return false;
        }

        $sets[] = 'updated_at = NOW()';
        $sql = "UPDATE sac_tickets SET " . implode(', ', $sets) . " WHERE id = :id";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        foreach ($binds as $field => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : ($value === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':' . $field, $value, $type);
        }

        $ok = $stmt->execute();

        if ($ok && $stmt->rowCount() > 0 && is_array($oldData)) {
            $newData = $this->getTicketById($id);
            if (is_array($newData)) {
                $usuarioId = (int)($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'sac_tickets',
                    $id,
                    $usuarioId,
                    'UPDATE',
                    $oldData,
                    $newData
                );
            }
        }

        return $ok;
    }

    public function deleteTicket(int $id): bool
    {
        $oldData = $this->getTicketById($id);

        $sql = "DELETE FROM sac_tickets WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $stmt->execute();
        $deleted = $stmt->rowCount() > 0;

        if ($deleted && is_array($oldData)) {
            $usuarioId = (int)($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao(
                'sac_tickets',
                $id,
                $usuarioId,
                'DELETE',
                $oldData,
                []
            );
        }

        return $deleted;
    }

    public function updateStatus(int $id, string $newStatus, int $changedBy): bool
    {
        $oldData = $this->getTicketById($id);
        $oldStatus = $oldData['status'] ?? '';

        $sql = "UPDATE sac_tickets SET status = :status, updated_at = NOW()";

        if ($newStatus === 'Resolvido') {
            $sql .= ", resolved_at = COALESCE(resolved_at, NOW())";
        }
        if ($newStatus === 'Encerrado') {
            $sql .= ", closed_at = COALESCE(closed_at, NOW())";
        }

        $sql .= " WHERE id = :id";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':status', $newStatus, PDO::PARAM_STR);

        $ok = $stmt->execute();

        if ($ok && $stmt->rowCount() > 0) {
            $logSql = "INSERT INTO sac_ticket_status_log (ticket_id, from_status, to_status, changed_by, created_at)
                       VALUES (:ticket_id, :from_status, :to_status, :changed_by, NOW())";
            $logStmt = $this->getConnection()->prepare($logSql);
            $logStmt->bindValue(':ticket_id', $id, PDO::PARAM_INT);
            $logStmt->bindValue(':from_status', $oldStatus, PDO::PARAM_STR);
            $logStmt->bindValue(':to_status', $newStatus, PDO::PARAM_STR);
            $logStmt->bindValue(':changed_by', $changedBy, PDO::PARAM_INT);
            $logStmt->execute();

            if (is_array($oldData)) {
                $newData = $this->getTicketById($id);
                if (is_array($newData)) {
                    LogAlteracaoService::registrarAlteracao(
                        'sac_tickets',
                        $id,
                        $changedBy,
                        'UPDATE',
                        $oldData,
                        $newData
                    );
                }
            }
        }

        return $ok;
    }

    public function getTicketCountsByStatus(): array
    {
        $sql = "SELECT status, COUNT(*) as total FROM sac_tickets GROUP BY status";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getTicketCountsByCategory(): array
    {
        $sql = "SELECT cat.name as category_name, cat.color as category_color, COUNT(t.id) as total
                FROM sac_tickets t
                LEFT JOIN sac_categories cat ON t.category_id = cat.id
                GROUP BY t.category_id, cat.name, cat.color
                ORDER BY total DESC";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return $this->normalizeRows($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    public function getTicketCountsByPriority(): array
    {
        $sql = "SELECT priority, COUNT(*) as total FROM sac_tickets GROUP BY priority
                ORDER BY CASE priority
                    WHEN 'Urgente' THEN 1
                    WHEN 'Alta' THEN 2
                    WHEN 'Média' THEN 3
                    WHEN 'Baixa' THEN 4
                    ELSE 5
                END";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getTicketCountsByChannel(): array
    {
        $sql = "SELECT channel, COUNT(*) as total FROM sac_tickets GROUP BY channel ORDER BY total DESC";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getSlaBreachedCount(): array
    {
        $sql = "SELECT
                    SUM(CASE WHEN sla_response_breached = 1 OR sla_resolution_breached = 1 THEN 1 ELSE 0 END) as breached,
                    SUM(CASE WHEN (sla_response_breached = 0 OR sla_response_breached IS NULL)
                              AND (sla_resolution_breached = 0 OR sla_resolution_breached IS NULL) THEN 1 ELSE 0 END) as on_time
                FROM sac_tickets";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'breached' => (int)($result['breached'] ?? 0),
            'on_time' => (int)($result['on_time'] ?? 0),
        ];
    }

    public function getAverageResponseTime(): float
    {
        $sql = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, first_response_at)) as avg_hours
                FROM sac_tickets
                WHERE first_response_at IS NOT NULL";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($result['avg_hours'] ?? 0);
    }

    public function getAverageResolutionTime(): float
    {
        $sql = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours
                FROM sac_tickets
                WHERE resolved_at IS NOT NULL";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($result['avg_hours'] ?? 0);
    }

    public function getOpenTicketsCount(): int
    {
        $sql = "SELECT COUNT(*) as total FROM sac_tickets WHERE status IN ('Aberto', 'Em análise', 'Aguardando cliente')";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    public function getCriticalTicketsCount(): int
    {
        $sql = "SELECT COUNT(*) as total FROM sac_tickets
                WHERE priority = 'Urgente' AND status IN ('Aberto', 'Em análise')";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    private function normalizeRows(array $rows): array
    {
        foreach ($rows as &$row) {
            if (is_array($row)) {
                $row = $this->normalizeRow($row);
            }
        }
        unset($row);
        return $rows;
    }

    private function normalizeRow(array $row): array
    {
        foreach (['subject', 'description', 'client_razao_social', 'client_nome_fantasia', 'category_name', 'assigned_name', 'department_name'] as $field) {
            if (array_key_exists($field, $row) && is_string($row[$field])) {
                $row[$field] = TextEncodingHelper::decodeEntities($row[$field]);
            }
        }
        return $row;
    }
}
