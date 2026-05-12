<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
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
        $sel = $this->getConnection()->query(
            "SELECT * FROM adms_payroll_document_otp_challenges WHERE employee_payroll_document_id IN ({$in}) AND consumed_at IS NULL"
        );
        $beforeRows = $sel ? ($sel->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        $this->getConnection()->exec(
            "UPDATE adms_payroll_document_otp_challenges SET consumed_at = NOW()
             WHERE employee_payroll_document_id IN ({$in}) AND consumed_at IS NULL"
        );
        $actor = (int) ($_SESSION['user_id'] ?? 0) > 0 ? (int) $_SESSION['user_id'] : 1;
        foreach ($beforeRows as $r) {
            $cid = (int) ($r['id'] ?? 0);
            if ($cid <= 0) {
                continue;
            }
            $after = $this->getRawChallengeRow($cid);
            if (is_array($after)) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_payroll_document_otp_challenges',
                    $cid,
                    $actor,
                    'UPDATE',
                    $this->redactChallengeRow($r),
                    $this->redactChallengeRow($after)
                );
            }
        }
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

        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $snap = $this->getRawChallengeRow($newId);
            if (is_array($snap)) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_payroll_document_otp_challenges',
                    $newId,
                    $userId,
                    'INSERT',
                    [],
                    $this->redactChallengeRow($snap)
                );
            }
        }

        return $newId;
    }

    /**
     * Marca OTPs anteriores não consumidos como consumidos ao emitir novo.
     */
    public function consumeOpenForDocumentUser(int $documentId, int $userId): void
    {
        if (!$this->hasTable()) {
            return;
        }
        $sql = 'SELECT * FROM adms_payroll_document_otp_challenges
                WHERE employee_payroll_document_id = :did AND user_id = :uid AND consumed_at IS NULL';
        $sel = $this->getConnection()->prepare($sql);
        $sel->bindValue(':did', $documentId, PDO::PARAM_INT);
        $sel->bindValue(':uid', $userId, PDO::PARAM_INT);
        $sel->execute();
        $beforeRows = $sel->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_payroll_document_otp_challenges SET consumed_at = NOW()
                WHERE employee_payroll_document_id = :did AND user_id = :uid AND consumed_at IS NULL'
        );
        $stmt->bindValue(':did', $documentId, PDO::PARAM_INT);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $actor = (int) ($_SESSION['user_id'] ?? 0) > 0 ? (int) $_SESSION['user_id'] : $userId;
        foreach ($beforeRows as $r) {
            $cid = (int) ($r['id'] ?? 0);
            if ($cid <= 0) {
                continue;
            }
            $after = $this->getRawChallengeRow($cid);
            if (is_array($after)) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_payroll_document_otp_challenges',
                    $cid,
                    $actor,
                    'UPDATE',
                    $this->redactChallengeRow($r),
                    $this->redactChallengeRow($after)
                );
            }
        }
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

    /**
     * @return array<string, mixed>|null
     */
    private function getRawChallengeRow(int $id): ?array
    {
        if (!$this->hasTable() || $id <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare('SELECT * FROM adms_payroll_document_otp_challenges WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function getDocumentIdForChallenge(int $challengeId): ?int
    {
        if (!$this->hasTable() || $challengeId <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT employee_payroll_document_id FROM adms_payroll_document_otp_challenges WHERE id = :id LIMIT 1'
        );
        $stmt->bindValue(':id', $challengeId, PDO::PARAM_INT);
        $stmt->execute();
        $v = $stmt->fetchColumn();
        if ($v === false || $v === null) {
            return null;
        }
        $id = (int) $v;

        return $id > 0 ? $id : null;
    }

    public function incrementAttempts(int $challengeId): void
    {
        $before = $this->getRawChallengeRow($challengeId);
        $sql = 'UPDATE adms_payroll_document_otp_challenges SET attempts = attempts + 1 WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $challengeId, PDO::PARAM_INT);
        $stmt->execute();
        if (is_array($before) && $stmt->rowCount() > 0) {
            $after = $this->getRawChallengeRow($challengeId);
            if (is_array($after)) {
                $actor = (int) ($_SESSION['user_id'] ?? 0) > 0 ? (int) $_SESSION['user_id'] : (int) ($before['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_payroll_document_otp_challenges',
                    $challengeId,
                    $actor,
                    'UPDATE',
                    $this->redactChallengeRow($before),
                    $this->redactChallengeRow($after)
                );
            }
        }
    }

    public function markConsumed(int $challengeId): void
    {
        $before = $this->getRawChallengeRow($challengeId);
        $sql = 'UPDATE adms_payroll_document_otp_challenges SET consumed_at = NOW() WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $challengeId, PDO::PARAM_INT);
        $stmt->execute();
        if (is_array($before) && $stmt->rowCount() > 0) {
            $after = $this->getRawChallengeRow($challengeId);
            if (is_array($after)) {
                $actor = (int) ($_SESSION['user_id'] ?? 0) > 0 ? (int) $_SESSION['user_id'] : (int) ($before['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_payroll_document_otp_challenges',
                    $challengeId,
                    $actor,
                    'UPDATE',
                    $this->redactChallengeRow($before),
                    $this->redactChallengeRow($after)
                );
            }
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function redactChallengeRow(array $row): array
    {
        $o = $row;
        if (array_key_exists('code_hash', $o) && $o['code_hash'] !== null && $o['code_hash'] !== '') {
            $o['code_hash'] = '[redacted]';
        }

        return $o;
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
