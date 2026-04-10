<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class PayrollDocumentOtpRepository extends DbConnection
{
    public function invalidateOpenForDocumentIds(array $documentIds): void
    {
        if ($documentIds === [] || !$this->hasTable()) {
            return;
        }
        $ids = array_values(array_filter(array_map('intval', $documentIds), fn (int $i) => $i > 0));
        if ($ids === []) {
            return;
        }
        $in = implode(',', $ids);
        $this->getConnection()->exec(
            "UPDATE adms_payroll_document_otp_challenges SET consumed_at = NOW()
             WHERE employee_payroll_document_id IN ({$in}) AND consumed_at IS NULL"
        );
    }

    public function countChallengesLastHour(int $userId, int $documentId): int
    {
        if (!$this->hasTable()) {
            return 0;
        }
        $sql = 'SELECT COUNT(*) FROM adms_payroll_document_otp_challenges
                WHERE user_id = :uid AND employee_payroll_document_id = :did
                AND created_at >= (NOW() - INTERVAL 1 HOUR)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':did', $documentId, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    public function createChallenge(
        int $documentId,
        int $userId,
        string $codeHash,
        string $expiresAtMysql,
        int $maxAttempts,
        string $channel
    ): int {
        $sql = 'INSERT INTO adms_payroll_document_otp_challenges
            (employee_payroll_document_id, user_id, code_hash, expires_at, attempts, max_attempts, channel, consumed_at, created_at)
            VALUES (:did, :uid, :hash, :exp, 0, :maxa, :ch, NULL, NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':did', $documentId, PDO::PARAM_INT);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':hash', $codeHash, PDO::PARAM_STR);
        $stmt->bindValue(':exp', $expiresAtMysql, PDO::PARAM_STR);
        $stmt->bindValue(':maxa', $maxAttempts, PDO::PARAM_INT);
        $stmt->bindValue(':ch', $channel, PDO::PARAM_STR);
        $stmt->execute();

        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * Marca OTPs anteriores não consumidos como consumidos ao emitir novo.
     */
    public function consumeOpenForDocumentUser(int $documentId, int $userId): void
    {
        if (!$this->hasTable()) {
            return;
        }
        $sql = 'UPDATE adms_payroll_document_otp_challenges SET consumed_at = NOW()
                WHERE employee_payroll_document_id = :did AND user_id = :uid AND consumed_at IS NULL';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':did', $documentId, PDO::PARAM_INT);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findLatestOpenChallenge(int $documentId, int $userId): ?array
    {
        if (!$this->hasTable()) {
            return null;
        }
        $sql = 'SELECT * FROM adms_payroll_document_otp_challenges
                WHERE employee_payroll_document_id = :did AND user_id = :uid AND consumed_at IS NULL
                ORDER BY id DESC LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':did', $documentId, PDO::PARAM_INT);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function incrementAttempts(int $challengeId): void
    {
        $sql = 'UPDATE adms_payroll_document_otp_challenges SET attempts = attempts + 1 WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $challengeId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function markConsumed(int $challengeId): void
    {
        $sql = 'UPDATE adms_payroll_document_otp_challenges SET consumed_at = NOW() WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $challengeId, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function hasTable(): bool
    {
        try {
            $this->getConnection()->query('SELECT 1 FROM adms_payroll_document_otp_challenges LIMIT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
