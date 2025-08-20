<?php

namespace App\adms\Models\Repository;

use PDO;
use App\adms\Models\Services\DbConnection;

class AdmsSessionsRepository extends DbConnection
{
    protected string $table = 'adms_sessions';

    public function saveSession(int $userId, string $sessionId): void
    {
        $sql = "INSERT INTO {$this->table} (user_id, session_id, status, created_at) VALUES (:user_id, :session_id, 'ativa', NOW()) ON DUPLICATE KEY UPDATE session_id = :session_id, status = 'ativa', created_at = NOW()";
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
        $sql = "UPDATE {$this->table} SET status = 'invalidada' WHERE user_id = :user_id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function invalidateAllSessionsByUserId(int $userId): void
    {
        $sql = "UPDATE {$this->table} SET status = 'invalidada' WHERE user_id = :user_id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function updateSessionActivity(int $userId, string $sessionId): void
    {
        $sql = "UPDATE {$this->table} SET updated_at = NOW() WHERE user_id = :user_id AND session_id = :session_id AND status = 'ativa'";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function getSessionByUserIdAndSessionId(int $userId, string $sessionId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id AND session_id = :session_id LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function invalidateSessionByUserIdAndSessionId(int $userId, string $sessionId): void
    {
        $sql = "UPDATE {$this->table} SET status = 'invalidada' WHERE user_id = :user_id AND session_id = :session_id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
        $stmt->execute();
    }
} 