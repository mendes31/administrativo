<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\WhistleblowingEncryptionService;
use App\adms\Models\Services\WhistleblowingProtocolService;
use PDO;

class WhistleblowingReportsRepository extends DbConnection
{
    private WhistleblowingEncryptionService $encryption;

    public function __construct()
    {
        $this->encryption = new WhistleblowingEncryptionService();
    }

    /**
     * @param array{category: string, risk_level: string, description: string, involved: string} $content
     * @param array{name?: string, email?: string, phone?: string}|null $reporterContact
     * @return array{report_id: int, uuid: string, protocol: string, password: string, committee_id: int|null}|null
     */
    public function createAnonymousReport(
        array $content,
        string $category,
        string $riskLevel,
        bool $isReporterIdentified = false,
        ?array $reporterContact = null
    ): ?array {
        $uuid = WhistleblowingProtocolService::generateUuid();
        $protocol = $this->generateUniqueProtocol();
        $plainPassword = WhistleblowingProtocolService::generateAccessPassword();
        $passwordHash = WhistleblowingProtocolService::hashAccessPassword($plainPassword);

        $encrypted = $this->encryption->encryptJson([
            'description' => $content['description'] ?? '',
            'involved' => $content['involved'] ?? '',
        ]);

        $reporterEncrypted = null;
        if ($isReporterIdentified && $reporterContact !== null) {
            $reporterEncrypted = $this->encryption->encryptJson([
                'name' => trim((string) ($reporterContact['name'] ?? '')),
                'email' => trim((string) ($reporterContact['email'] ?? '')),
                'phone' => trim((string) ($reporterContact['phone'] ?? '')),
            ]);
        }

        $now = date('Y-m-d H:i:s');

        $committeesRepo = new WhistleblowingCommitteesRepository();
        $committeeId = $committeesRepo->findCommitteeIdByCategory($category);
        $assignedUserId = $committeeId !== null ? $committeesRepo->getFirstMemberUserId($committeeId) : null;

        $sql = 'INSERT INTO adms_whistleblowing_reports
                (uuid, protocol, password_hash, category, risk_level, content_encrypted,
                 is_reporter_identified, reporter_contact_encrypted, status,
                 committee_id, assigned_user_id,
                 retention_archive_at, retention_delete_at, created_at, updated_at)
                VALUES
                (:uuid, :protocol, :password_hash, :category, :risk_level, :content_encrypted,
                 :is_reporter_identified, :reporter_contact_encrypted, :status,
                 :committee_id, :assigned_user_id,
                 :retention_archive_at, :retention_delete_at, :created_at, :updated_at)';

        $stmt = $this->getConnection()->prepare($sql);
        $ok = $stmt->execute([
            ':uuid' => $uuid,
            ':protocol' => $protocol,
            ':password_hash' => $passwordHash,
            ':category' => $category,
            ':risk_level' => $riskLevel,
            ':content_encrypted' => $encrypted,
            ':is_reporter_identified' => $isReporterIdentified ? 1 : 0,
            ':reporter_contact_encrypted' => $reporterEncrypted,
            ':status' => 'Recebida',
            ':committee_id' => $committeeId,
            ':assigned_user_id' => $assignedUserId,
            ':retention_archive_at' => null,
            ':retention_delete_at' => null,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        if (!$ok) {
            return null;
        }

        $reportId = (int) $this->getConnection()->lastInsertId();

        $this->logStatusChange($reportId, null, 'Recebida', null, 'Denúncia recebida pelo canal público.');

        return [
            'report_id' => $reportId,
            'uuid' => $uuid,
            'protocol' => $protocol,
            'password' => $plainPassword,
            'committee_id' => $committeeId,
        ];
    }

    public function findByProtocol(string $protocol): ?array
    {
        $sql = 'SELECT * FROM adms_whistleblowing_reports WHERE protocol = :protocol LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':protocol', strtoupper(trim($protocol)));
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function verifyProtocolAndPassword(string $protocol, string $password): ?array
    {
        $report = $this->findByProtocol($protocol);
        if (!$report) {
            return null;
        }

        if (!WhistleblowingProtocolService::verifyAccessPassword($password, (string) $report['password_hash'])) {
            return null;
        }

        return $report;
    }

    public function getReportById(int $id): ?array
    {
        $sql = 'SELECT r.*, u.name AS assigned_name, c.name AS committee_name
                FROM adms_whistleblowing_reports r
                LEFT JOIN adms_users u ON r.assigned_user_id = u.id
                LEFT JOIN adms_whistleblowing_committees c ON r.committee_id = c.id
                WHERE r.id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrateReport($row) : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function hydrateReport(array $row): array
    {
        try {
            $content = $this->encryption->decryptJson((string) ($row['content_encrypted'] ?? ''));
            $row['description'] = (string) ($content['description'] ?? '');
            $row['involved'] = (string) ($content['involved'] ?? '');
        } catch (\Throwable) {
            $row['description'] = '';
            $row['involved'] = '';
        }

        $row['is_reporter_identified'] = !empty($row['is_reporter_identified']);
        $row['reporter_name'] = '';
        $row['reporter_email'] = '';
        $row['reporter_phone'] = '';
        if (!empty($row['reporter_contact_encrypted'])) {
            try {
                $contact = $this->encryption->decryptJson((string) $row['reporter_contact_encrypted']);
                $row['reporter_name'] = (string) ($contact['name'] ?? '');
                $row['reporter_email'] = (string) ($contact['email'] ?? '');
                $row['reporter_phone'] = (string) ($contact['phone'] ?? '');
            } catch (\Throwable) {
                $row['reporter_name'] = '';
                $row['reporter_email'] = '';
                $row['reporter_phone'] = '';
            }
        }

        unset($row['content_encrypted'], $row['password_hash'], $row['reporter_contact_encrypted']);

        return $row;
    }

    /**
     * Versão pública: sem dados internos sensíveis.
     *
     * @return array<string, mixed>
     */
    public function hydrateReportForWhistleblower(array $row): array
    {
        $hydrated = $this->hydrateReport($row);

        return [
            'protocol' => $hydrated['protocol'] ?? '',
            'category' => $hydrated['category'] ?? '',
            'risk_level' => $hydrated['risk_level'] ?? '',
            'status' => $hydrated['status'] ?? '',
            'description' => $hydrated['description'] ?? '',
            'involved' => $hydrated['involved'] ?? '',
            'created_at' => $hydrated['created_at'] ?? '',
            'closed_at' => $hydrated['closed_at'] ?? null,
            'id' => (int) ($hydrated['id'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function getAllReports(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        [$where, $params] = $this->buildFilters($filters);

        $sql = "SELECT r.id, r.uuid, r.protocol, r.category, r.risk_level, r.status,
                       r.assigned_user_id, r.committee_id, r.first_response_at, r.closed_at, r.archived_at,
                       r.created_at, r.updated_at,
                       u.name AS assigned_name, c.name AS committee_name
                FROM adms_whistleblowing_reports r
                LEFT JOIN adms_users u ON r.assigned_user_id = u.id
                LEFT JOIN adms_whistleblowing_committees c ON r.committee_id = c.id
                {$where}
                ORDER BY
                    CASE r.risk_level
                        WHEN 'Crítico' THEN 1
                        WHEN 'Alto' THEN 2
                        WHEN 'Médio' THEN 3
                        WHEN 'Baixo' THEN 4
                        ELSE 5
                    END ASC,
                    r.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function getTotalReports(array $filters = []): int
    {
        [$where, $params] = $this->buildFilters($filters);
        $sql = "SELECT COUNT(*) FROM adms_whistleblowing_reports r {$where}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function updateReport(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        $allowed = ['status', 'assigned_user_id', 'first_response_at', 'closed_at', 'risk_level', 'category'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if ($fields === []) {
            return false;
        }

        $fields[] = 'updated_at = :updated_at';
        $params[':updated_at'] = date('Y-m-d H:i:s');

        $sql = 'UPDATE adms_whistleblowing_reports SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);

        return $stmt->execute($params);
    }

    public function logStatusChange(int $reportId, ?string $fromStatus, string $toStatus, ?int $userId, ?string $notes = null): void
    {
        $notesEncrypted = null;
        if ($notes !== null && $notes !== '') {
            $notesEncrypted = $this->encryption->encrypt($notes);
        }

        $sql = 'INSERT INTO adms_whistleblowing_status_log
                (report_id, from_status, to_status, user_id, notes_encrypted, created_at)
                VALUES (:report_id, :from_status, :to_status, :user_id, :notes_encrypted, :created_at)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            ':report_id' => $reportId,
            ':from_status' => $fromStatus,
            ':to_status' => $toStatus,
            ':user_id' => $userId,
            ':notes_encrypted' => $notesEncrypted,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getStatusLog(int $reportId): array
    {
        $sql = 'SELECT sl.*, u.name AS user_name
                FROM adms_whistleblowing_status_log sl
                LEFT JOIN adms_users u ON sl.user_id = u.id
                WHERE sl.report_id = :report_id
                ORDER BY sl.created_at ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':report_id', $reportId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$row) {
            if (!empty($row['notes_encrypted'])) {
                try {
                    $row['notes'] = $this->encryption->decrypt((string) $row['notes_encrypted']);
                } catch (\Throwable) {
                    $row['notes'] = '';
                }
            } else {
                $row['notes'] = '';
            }
            unset($row['notes_encrypted']);
        }
        unset($row);

        return $rows;
    }

    private function generateUniqueProtocol(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $protocol = WhistleblowingProtocolService::generateProtocol();
            if (!$this->findByProtocol($protocol)) {
                return $protocol;
            }
        }

        throw new \RuntimeException('Não foi possível gerar protocolo único.');
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildFilters(array $filters): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = 'r.protocol LIKE :search';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['status'])) {
            $conditions[] = 'r.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['category'])) {
            $conditions[] = 'r.category = :category';
            $params[':category'] = $filters['category'];
        }
        if (!empty($filters['risk_level'])) {
            $conditions[] = 'r.risk_level = :risk_level';
            $params[':risk_level'] = $filters['risk_level'];
        }
        if (!empty($filters['assigned_user_id'])) {
            $conditions[] = 'r.assigned_user_id = :assigned_user_id';
            $params[':assigned_user_id'] = (int) $filters['assigned_user_id'];
        }
        if (!empty($filters['date_from'])) {
            $conditions[] = 'DATE(r.created_at) >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = 'DATE(r.created_at) <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }
        if (isset($filters['include_archived']) && (string) $filters['include_archived'] !== '1') {
            $conditions[] = 'r.archived_at IS NULL';
        }

        $this->appendScopeConditions($filters, $conditions, $params);

        $where = $conditions !== [] ? 'WHERE ' . implode(' AND ', $conditions) : '';

        return [$where, $params];
    }

    /**
     * Restringe listagem a comitês do operador (ou denúncias atribuídas a ele).
     *
     * @param array<string, mixed> $filters
     * @param list<string> $conditions
     * @param array<string, mixed> $params
     */
    private function appendScopeConditions(array $filters, array &$conditions, array &$params): void
    {
        if (!isset($filters['scope_user_id'])) {
            return;
        }

        $userId = (int) $filters['scope_user_id'];
        $committeeIds = $filters['scope_committee_ids'] ?? [];
        $committeeIds = is_array($committeeIds)
            ? array_values(array_unique(array_filter(array_map('intval', $committeeIds))))
            : [];

        if ($committeeIds === [] && $userId <= 0) {
            $conditions[] = '1 = 0';

            return;
        }

        if ($committeeIds === []) {
            $conditions[] = 'r.assigned_user_id = :scope_user_id';
            $params[':scope_user_id'] = $userId;

            return;
        }

        $inParts = [];
        foreach ($committeeIds as $i => $cid) {
            $key = ':scope_committee_' . $i;
            $inParts[] = $key;
            $params[$key] = $cid;
        }

        if ($userId > 0) {
            $conditions[] = '(r.committee_id IN (' . implode(', ', $inParts) . ') OR r.assigned_user_id = :scope_user_id)';
            $params[':scope_user_id'] = $userId;
        } else {
            $conditions[] = 'r.committee_id IN (' . implode(', ', $inParts) . ')';
        }
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function getDashboardStats(array $filters = []): array
    {
        $conn = $this->getConnection();
        [$scopeWhere, $scopeParams] = $this->buildScopeWhereClause($filters);
        $base = 'FROM adms_whistleblowing_reports r WHERE r.archived_at IS NULL' . $scopeWhere;

        $total = $this->scopedCount($conn, "SELECT COUNT(*) {$base}", $scopeParams);
        $open = $this->scopedCount($conn, "SELECT COUNT(*) {$base} AND r.status != 'Encerrada'", $scopeParams);
        $critical = $this->scopedCount($conn, "SELECT COUNT(*) {$base} AND r.risk_level = 'Crítico' AND r.status != 'Encerrada'", $scopeParams);
        $pending = $this->scopedCount($conn, "SELECT COUNT(*) {$base} AND r.status IN ('Recebida', 'Em triagem')", $scopeParams);
        $investigation = $this->scopedCount($conn, "SELECT COUNT(*) {$base} AND r.status IN ('Investigação', 'Comitê', 'Em análise')", $scopeParams);

        $avgResponseStmt = $conn->prepare(
            "SELECT AVG(TIMESTAMPDIFF(HOUR, r.created_at, r.first_response_at))
             FROM adms_whistleblowing_reports r
             WHERE r.archived_at IS NULL AND r.first_response_at IS NOT NULL{$scopeWhere}"
        );
        foreach ($scopeParams as $key => $value) {
            $avgResponseStmt->bindValue($key, $value);
        }
        $avgResponseStmt->execute();
        $avgResponse = $avgResponseStmt->fetchColumn();

        $avgClosureStmt = $conn->prepare(
            "SELECT AVG(TIMESTAMPDIFF(HOUR, r.created_at, r.closed_at))
             FROM adms_whistleblowing_reports r
             WHERE r.archived_at IS NULL AND r.closed_at IS NOT NULL{$scopeWhere}"
        );
        foreach ($scopeParams as $key => $value) {
            $avgClosureStmt->bindValue($key, $value);
        }
        $avgClosureStmt->execute();
        $avgClosure = $avgClosureStmt->fetchColumn();

        $listFilters = array_merge($filters, ['include_archived' => '0']);

        return [
            'total' => $total,
            'open' => $open,
            'critical' => $critical,
            'pending' => $pending,
            'investigation' => $investigation,
            'avg_response_hours' => round((float) ($avgResponse ?: 0), 1),
            'avg_closure_hours' => round((float) ($avgClosure ?: 0), 1),
            'by_status' => $this->countGroupScoped('status', $base, $scopeParams),
            'by_category' => $this->countGroupScoped('category', $base, $scopeParams),
            'by_risk' => $this->countGroupScoped('risk_level', $base, $scopeParams),
            'recent' => $this->getAllReports(1, 5, $listFilters),
            'aging' => $this->getAgingReports($filters, 15),
            'sla_overdue' => $this->countSlaOverdueFirstResponse($filters),
        ];
    }

    /**
     * Denúncias abertas ordenadas por dias sem atualização (aging).
     *
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function getAgingReports(array $filters = [], int $limit = 15): array
    {
        $limit = max(1, min(100, $limit));
        [$scopeWhere, $scopeParams] = $this->buildScopeWhereClause($filters);

        $sql = "SELECT r.id, r.protocol, r.category, r.risk_level, r.status, r.created_at, r.updated_at,
                       DATEDIFF(NOW(), r.updated_at) AS days_idle,
                       DATEDIFF(NOW(), r.created_at) AS days_open,
                       c.name AS committee_name
                FROM adms_whistleblowing_reports r
                LEFT JOIN adms_whistleblowing_committees c ON r.committee_id = c.id
                WHERE r.archived_at IS NULL AND r.status != 'Encerrada'{$scopeWhere}
                ORDER BY days_idle DESC, r.risk_level ASC
                LIMIT {$limit}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($scopeParams as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Denúncias abertas sem primeira resposta há mais de 72h (SLA padrão).
     *
     * @param array<string, mixed> $filters
     */
    public function countSlaOverdueFirstResponse(array $filters = [], int $slaHours = 72): int
    {
        [$scopeWhere, $scopeParams] = $this->buildScopeWhereClause($filters);
        $sql = "SELECT COUNT(*) FROM adms_whistleblowing_reports r
                WHERE r.archived_at IS NULL
                  AND r.status != 'Encerrada'
                  AND r.first_response_at IS NULL
                  AND TIMESTAMPDIFF(HOUR, r.created_at, NOW()) > :sla_hours{$scopeWhere}";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':sla_hours', $slaHours, PDO::PARAM_INT);
        foreach ($scopeParams as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildScopeWhereClause(array $filters): array
    {
        $conditions = [];
        $params = [];
        $this->appendScopeConditions($filters, $conditions, $params);
        if ($conditions === []) {
            return ['', []];
        }

        return [' AND ' . implode(' AND ', $conditions), $params];
    }

    /**
     * @param array<string, mixed> $params
     */
    private function scopedCount(\PDO $conn, string $sql, array $params): int
    {
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * @param array<string, mixed> $params
     * @return list<array<string, mixed>>
     */
    private function countGroupScoped(string $field, string $baseWhere, array $params): array
    {
        $allowed = ['status', 'category', 'risk_level'];
        if (!in_array($field, $allowed, true)) {
            return [];
        }

        $sql = "SELECT r.{$field} AS label, COUNT(*) AS total {$baseWhere} GROUP BY r.{$field} ORDER BY total DESC";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Calcula prazos LGPD a partir da data de encerramento da denúncia.
     *
     * @return array{retention_archive_at: string, retention_delete_at: string}
     */
    public function computeRetentionSchedule(string $closedAt): array
    {
        $configRepo = new \App\adms\Models\Repository\WhistleblowingConfigRepository();
        $archiveYears = $configRepo->getRetentionArchiveYears();
        $deleteYears = $configRepo->getRetentionDeleteYears();
        $base = strtotime($closedAt);
        if ($base === false) {
            $base = time();
        }

        return [
            'retention_archive_at' => date('Y-m-d H:i:s', strtotime('+' . $archiveYears . ' years', $base)),
            'retention_delete_at' => date('Y-m-d H:i:s', strtotime('+' . $deleteYears . ' years', $base)),
        ];
    }

    public function scheduleRetentionFromClosure(int $reportId, string $closedAt): bool
    {
        $schedule = $this->computeRetentionSchedule($closedAt);
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_whistleblowing_reports
             SET retention_archive_at = :archive_at,
                 retention_delete_at = :delete_at,
                 updated_at = :updated_at
             WHERE id = :id AND archived_at IS NULL'
        );

        return $stmt->execute([
            ':archive_at' => $schedule['retention_archive_at'],
            ':delete_at' => $schedule['retention_delete_at'],
            ':updated_at' => date('Y-m-d H:i:s'),
            ':id' => $reportId,
        ]);
    }

    public function clearRetentionSchedule(int $reportId): bool
    {
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_whistleblowing_reports
             SET closed_at = NULL,
                 retention_archive_at = NULL,
                 retention_delete_at = NULL,
                 updated_at = :updated_at
             WHERE id = :id AND archived_at IS NULL'
        );

        return $stmt->execute([
            ':updated_at' => date('Y-m-d H:i:s'),
            ':id' => $reportId,
        ]);
    }

    /**
     * Recalcula prazos de denúncias encerradas (não arquivadas) após alteração da política.
     */
    public function recalculateRetentionForClosedReports(): int
    {
        $configRepo = new \App\adms\Models\Repository\WhistleblowingConfigRepository();
        $archiveYears = $configRepo->getRetentionArchiveYears();
        $deleteYears = $configRepo->getRetentionDeleteYears();

        $sql = 'UPDATE adms_whistleblowing_reports
                SET retention_archive_at = DATE_ADD(closed_at, INTERVAL :archive_years YEAR),
                    retention_delete_at = DATE_ADD(closed_at, INTERVAL :delete_years YEAR),
                    updated_at = NOW()
                WHERE status = :status
                  AND closed_at IS NOT NULL
                  AND archived_at IS NULL';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            ':archive_years' => $archiveYears,
            ':delete_years' => $deleteYears,
            ':status' => 'Encerrada',
        ]);

        return $stmt->rowCount();
    }

    /**
     * @return list<int>
     */
    public function getReportIdsDueForDeletion(): array
    {
        $sql = 'SELECT id FROM adms_whistleblowing_reports
                WHERE closed_at IS NOT NULL
                  AND retention_delete_at IS NOT NULL
                  AND retention_delete_at <= :now';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':now' => date('Y-m-d H:i:s')]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    public function archiveExpiredReports(): int
    {
        $sql = 'UPDATE adms_whistleblowing_reports
                SET archived_at = :now, updated_at = :now2
                WHERE archived_at IS NULL
                  AND closed_at IS NOT NULL
                  AND retention_archive_at IS NOT NULL
                  AND retention_archive_at <= :now3';
        $now = date('Y-m-d H:i:s');
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':now' => $now, ':now2' => $now, ':now3' => $now]);

        return $stmt->rowCount();
    }

    public function deleteReportById(int $id): bool
    {
        $stmt = $this->getConnection()->prepare('DELETE FROM adms_whistleblowing_reports WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listRawReportsContent(): array
    {
        try {
            $stmt = $this->getConnection()->query(
                'SELECT id, content_encrypted FROM adms_whistleblowing_reports ORDER BY id ASC'
            );

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function updateContentEncrypted(int $id, string $encrypted): bool
    {
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_whistleblowing_reports SET content_encrypted = :enc, updated_at = :updated_at WHERE id = :id'
        );

        return $stmt->execute([
            ':enc' => $encrypted,
            ':updated_at' => date('Y-m-d H:i:s'),
            ':id' => $id,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listRawStatusNotes(): array
    {
        try {
            $stmt = $this->getConnection()->query(
                "SELECT id, notes_encrypted FROM adms_whistleblowing_status_log
                 WHERE notes_encrypted IS NOT NULL AND notes_encrypted <> '' ORDER BY id ASC"
            );

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function updateStatusNotesEncrypted(int $id, string $encrypted): bool
    {
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_whistleblowing_status_log SET notes_encrypted = :enc WHERE id = :id'
        );

        return $stmt->execute([':enc' => $encrypted, ':id' => $id]);
    }
}
