<?php

namespace App\adms\Models\Repository;

use PDO;
use App\adms\Models\Services\DbConnection;

/**
 * Sessões no banco: apenas linhas "ativas" (registro vigente).
 * Invalidação = DELETE (não mantém histórico em adms_sessions; log de acessos é adms_log_acessos).
 */
class AdmsSessionsRepository extends DbConnection
{
    protected string $table = 'adms_sessions';

    public function saveSession(int $userId, string $sessionId): void
    {
        @file_put_contents(__DIR__ . '/../../../logs/session_investigar.log',
            date('Y-m-d H:i:s') . " [saveSession] user_id={$userId} session_id_param={$sessionId} php_session_id=" . session_id() . PHP_EOL,
            FILE_APPEND
        );
        $this->invalidateAllSessionsByUserId($userId);

        $sql = "INSERT INTO {$this->table} (user_id, session_id, status, created_at, updated_at)
                VALUES (:user_id, :session_id, 'ativa', NOW(), NOW())";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function getSessionByUserId(int $userId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getSessionBySessionId(string $sessionId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE session_id = :session_id LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ?: null;
    }

    public function deleteSessionByUserId(int $userId): void
    {
        $sql = "DELETE FROM {$this->table} WHERE user_id = :user_id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function deleteSessionBySessionId(string $sessionId): void
    {
        $sql = "DELETE FROM {$this->table} WHERE session_id = :session_id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function invalidateSessionByUserId(int $userId): void
    {
        @file_put_contents(__DIR__ . '/../../../logs/session_investigar.log',
            date('Y-m-d H:i:s') . " [invalidateSessionByUserId] user_id={$userId} php_session_id=" . session_id() . PHP_EOL,
            FILE_APPEND
        );
        $this->invalidateAllSessionsByUserId($userId);
    }

    public function invalidateAllSessionsByUserId(int $userId): void
    {
        @file_put_contents(__DIR__ . '/../../../logs/session_investigar.log',
            date('Y-m-d H:i:s') . " [invalidateAllSessionsByUserId] user_id={$userId} php_session_id=" . session_id() . PHP_EOL,
            FILE_APPEND
        );
        $sql = "DELETE FROM {$this->table} WHERE user_id = :user_id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function updateSessionActivity(int $userId, string $sessionId): bool
    {
        try {
            @file_put_contents(__DIR__ . '/../../../logs/session_investigar.log',
                date('Y-m-d H:i:s') . " [updateSessionActivity] user_id={$userId} session_id_param={$sessionId} php_session_id=" . session_id() . PHP_EOL,
                FILE_APPEND
            );
            $conn = $this->getConnection();

            $sql = "INSERT INTO {$this->table} (user_id, session_id, status, created_at, updated_at)
                    VALUES (:user_id, :session_id, 'ativa', NOW(), NOW())
                    ON DUPLICATE KEY UPDATE
                        status = 'ativa',
                        updated_at = NOW()";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
            $stmt->execute();

            return true;
        } catch (\Exception $e) {
            error_log("Erro ao atualizar atividade da sessão: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retorna todas as sessões ativas de um usuário
     */
    public function getActiveSessionsByUserId(int $userId): array
    {
        @file_put_contents(__DIR__ . '/../../../logs/session_investigar.log',
            date('Y-m-d H:i:s') . " [getActiveSessionsByUserId] user_id={$userId} php_session_id=" . session_id() . PHP_EOL,
            FILE_APPEND
        );
        $sql = "SELECT id, user_id, session_id, status, created_at, updated_at
                FROM {$this->table}
                WHERE user_id = :user_id AND status = 'ativa'";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getSessionByUserIdAndSessionId(int $userId, string $sessionId): ?array
    {
        @file_put_contents(__DIR__ . '/../../../logs/session_investigar.log',
            date('Y-m-d H:i:s') . " [getSessionByUserIdAndSessionId] user_id={$userId} session_id_param={$sessionId} php_session_id=" . session_id() . PHP_EOL,
            FILE_APPEND
        );
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id AND session_id = :session_id LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Sessões com heartbeat recente + dados básicos do usuário (usuários “online” na consulta).
     * Linhas antigas com status ativa mas sem atualização há mais de $maxIdleSeconds segundos não entram.
     *
     * @param int $maxIdleSeconds Janela máxima desde a última atividade (updated_at), mínimo 60.
     * @return array<int, array<string, mixed>>
     */
    public function listActiveSessionsWithUsers(int $maxIdleSeconds = 1800): array
    {
        $maxIdleSeconds = max(60, $maxIdleSeconds);
        $sql = "SELECT s.id AS session_row_id, s.user_id, s.session_id, s.status, s.created_at, s.updated_at,
                       u.name AS user_name, u.email AS user_email, u.username AS user_username,
                       u.image AS user_image
                FROM {$this->table} s
                INNER JOIN adms_users u ON u.id = s.user_id
                WHERE s.status = 'ativa'
                  AND s.updated_at >= DATE_SUB(NOW(), INTERVAL :idle SECOND)
                ORDER BY s.updated_at DESC";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':idle', $maxIdleSeconds, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    public function invalidateSessionByUserIdAndSessionId(int $userId, string $sessionId): void
    {
        @file_put_contents(__DIR__ . '/../../../logs/session_investigar.log',
            date('Y-m-d H:i:s') . " [invalidateSessionByUserIdAndSessionId] user_id={$userId} session_id_param={$sessionId} php_session_id=" . session_id() . PHP_EOL,
            FILE_APPEND
        );
        $sql = "DELETE FROM {$this->table} WHERE user_id = :user_id AND session_id = :session_id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
        $stmt->execute();
    }
}
