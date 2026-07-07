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
     * @return array{report_id: int, uuid: string, protocol: string, password: string}|null
     */
    public function createAnonymousReport(array $content, string $category, string $riskLevel): ?array
    {
        $uuid = WhistleblowingProtocolService::generateUuid();
        $protocol = $this->generateUniqueProtocol();
        $plainPassword = WhistleblowingProtocolService::generateAccessPassword();
        $passwordHash = WhistleblowingProtocolService::hashAccessPassword($plainPassword);

        $encrypted = $this->encryption->encryptJson([
            'description' => $content['description'] ?? '',
            'involved' => $content['involved'] ?? '',
        ]);

        $now = date('Y-m-d H:i:s');
        $archiveAt = date('Y-m-d H:i:s', strtotime('+5 years'));
        $deleteAt = date('Y-m-d H:i:s', strtotime('+10 years'));

        $committeesRepo = new WhistleblowingCommitteesRepository();
        $committeeId = $committeesRepo->findCommitteeIdByCategory($category);
        $assignedUserId = $committeeId !== null ? $committeesRepo->getFirstMemberUserId($committeeId) : null;

        $sql = 'INSERT INTO adms_whistleblowing_reports
                (uuid, protocol, password_hash, category, risk_level, content_encrypted, status,
                 committee_id, assigned_user_id,
                 retention_archive_at, retention_delete_at, created_at, updated_at)
                VALUES
                (:uuid, :protocol, :password_hash, :category, :risk_level, :content_encrypted, :status,
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
            ':status' => 'Recebida',
            ':committee_id' => $committeeId,
            ':assigned_user_id' => $assignedUserId,
            ':retention_archive_at' => $archiveAt,
            ':retention_delete_at' => $deleteAt,
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

        unset($row['content_encrypted'], $row['password_hash']);

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

        $where = $conditions !== [] ? 'WHERE ' . implode(' AND ', $conditions) : '';

        return [$where, $params];
    }

    public function getDashboardStats(): array
    {
        $conn = $this->getConnection();
        $base = 'FROM adms_whistleblowing_reports r WHERE r.archived_at IS NULL';

        $total = (int) $conn->query("SELECT COUNT(*) {$base}")->fetchColumn();
        $open = (int) $conn->query("SELECT COUNT(*) {$base} AND r.status != 'Encerrada'")->fetchColumn();
        $critical = (int) $conn->query("SELECT COUNT(*) {$base} AND r.risk_level = 'Crítico' AND r.status != 'Encerrada'")->fetchColumn();
        $pending = (int) $conn->query("SELECT COUNT(*) {$base} AND r.status IN ('Recebida', 'Em triagem')")->fetchColumn();
        $investigation = (int) $conn->query("SELECT COUNT(*) {$base} AND r.status IN ('Investigação', 'Comitê', 'Em análise')")->fetchColumn();

        $avgResponse = $conn->query(
            "SELECT AVG(TIMESTAMPDIFF(HOUR, r.created_at, r.first_response_at))
             FROM adms_whistleblowing_reports r
             WHERE r.archived_at IS NULL AND r.first_response_at IS NOT NULL"
        )->fetchColumn();

        $avgClosure = $conn->query(
            "SELECT AVG(TIMESTAMPDIFF(HOUR, r.created_at, r.closed_at))
             FROM adms_whistleblowing_reports r
             WHERE r.archived_at IS NULL AND r.closed_at IS NOT NULL"
        )->fetchColumn();

        return [
            'total' => $total,
            'open' => $open,
            'critical' => $critical,
            'pending' => $pending,
            'investigation' => $investigation,
            'avg_response_hours' => round((float) ($avgResponse ?: 0), 1),
            'avg_closure_hours' => round((float) ($avgClosure ?: 0), 1),
            'by_status' => $this->countGroup('status', $base),
            'by_category' => $this->countGroup('category', $base),
            'by_risk' => $this->countGroup('risk_level', $base),
            'recent' => $this->getAllReports(1, 5, ['include_archived' => '0']),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function countGroup(string $field, string $baseWhere): array
    {
        $allowed = ['status', 'category', 'risk_level'];
        if (!in_array($field, $allowed, true)) {
            return [];
        }

        $sql = "SELECT r.{$field} AS label, COUNT(*) AS total {$baseWhere} GROUP BY r.{$field} ORDER BY total DESC";
        return $this->getConnection()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<int>
     */
    public function getReportIdsDueForDeletion(): array
    {
        $sql = 'SELECT id FROM adms_whistleblowing_reports
                WHERE retention_delete_at IS NOT NULL AND retention_delete_at <= :now';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':now' => date('Y-m-d H:i:s')]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    public function archiveExpiredReports(): int
    {
        $sql = 'UPDATE adms_whistleblowing_reports
                SET archived_at = :now, updated_at = :now2
                WHERE archived_at IS NULL
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
}
