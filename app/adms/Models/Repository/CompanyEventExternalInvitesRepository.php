<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class CompanyEventExternalInvitesRepository extends DbConnection
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function listByEvent(int $eventId): array
    {
        if ($eventId <= 0) {
            return [];
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_company_event_external_invites
             WHERE event_id = :e
             ORDER BY full_name ASC, id ASC'
        );
        $stmt->bindValue(':e', $eventId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByToken(string $token): ?array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_company_event_external_invites WHERE token = :t LIMIT 1'
        );
        $stmt->execute([':t' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForEvent(int $eventId, int $inviteId): ?array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_company_event_external_invites WHERE id = :id AND event_id = :e LIMIT 1'
        );
        $stmt->bindValue(':id', $inviteId, PDO::PARAM_INT);
        $stmt->bindValue(':e', $eventId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function phoneExists(int $eventId, string $phone): bool
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT id FROM adms_company_event_external_invites WHERE event_id = :e AND phone = :p LIMIT 1'
        );
        $stmt->bindValue(':e', $eventId, PDO::PARAM_INT);
        $stmt->bindValue(':p', $phone);
        $stmt->execute();

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function add(int $eventId, string $name, string $reason, string $phone, int $createdBy): int
    {
        $token = bin2hex(random_bytes(32));
        $stmt = $this->getConnection()->prepare(
            'INSERT INTO adms_company_event_external_invites
                (event_id, full_name, invite_reason, phone, status, token, created_by, created_at, updated_at)
             VALUES
                (:e, :n, :r, :p, \'pending\', :t, :u, NOW(), NOW())'
        );
        $stmt->bindValue(':e', $eventId, PDO::PARAM_INT);
        $stmt->bindValue(':n', $name);
        $stmt->bindValue(':r', $reason);
        $stmt->bindValue(':p', $phone);
        $stmt->bindValue(':t', $token);
        $stmt->bindValue(':u', $createdBy, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $this->getConnection()->lastInsertId();
    }

    public function setStatus(int $eventId, int $inviteId, string $status): bool
    {
        if (!in_array($status, ['pending', 'confirmed', 'declined'], true)) {
            return false;
        }
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_company_event_external_invites
             SET status = :s,
                 responded_at = CASE WHEN :s_resp IN (\'confirmed\', \'declined\') THEN NOW() ELSE NULL END,
                 updated_at = NOW()
             WHERE id = :id AND event_id = :e'
        );
        $stmt->bindValue(':s', $status);
        $stmt->bindValue(':s_resp', $status);
        $stmt->bindValue(':id', $inviteId, PDO::PARAM_INT);
        $stmt->bindValue(':e', $eventId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function setStatusByToken(string $token, string $status): bool
    {
        if (!in_array($status, ['confirmed', 'declined'], true)) {
            return false;
        }
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_company_event_external_invites
             SET status = :s, responded_at = NOW(), updated_at = NOW()
             WHERE token = :t'
        );
        $stmt->execute([':s' => $status, ':t' => $token]);

        return $stmt->rowCount() > 0;
    }

    public function delete(int $eventId, int $inviteId): bool
    {
        $stmt = $this->getConnection()->prepare(
            'DELETE FROM adms_company_event_external_invites WHERE id = :id AND event_id = :e'
        );
        $stmt->bindValue(':id', $inviteId, PDO::PARAM_INT);
        $stmt->bindValue(':e', $eventId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
