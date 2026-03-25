<?php

namespace App\adms\Models\Repository;

use PDO;
use App\adms\Models\Services\DbConnection;

class AdmsSessionsRepository extends DbConnection
{
    protected string $table = 'adms_sessions';

    public function saveSession(int $userId, string $sessionId): void
    {
        @file_put_contents(__DIR__ . '/../../../logs/session_investigar.log',
            date('Y-m-d H:i:s') . " [saveSession] user_id={$userId} session_id_param={$sessionId} php_session_id=" . session_id() . PHP_EOL,
            FILE_APPEND
        );
        // Primeiro, invalidar todas as sessões antigas do usuário
        $this->invalidateAllSessionsByUserId($userId);
        
        // Depois, criar a nova sessão
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
        $sql = "UPDATE {$this->table} SET status = 'invalidada' WHERE user_id = :user_id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function invalidateAllSessionsByUserId(int $userId): void
    {
        @file_put_contents(__DIR__ . '/../../../logs/session_investigar.log',
            date('Y-m-d H:i:s') . " [invalidateAllSessionsByUserId] user_id={$userId} php_session_id=" . session_id() . PHP_EOL,
            FILE_APPEND
        );
        $sql = "UPDATE {$this->table} SET status = 'invalidada' WHERE user_id = :user_id";
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

            // 1) Sessão já marcada como ativa
            $sql = "UPDATE {$this->table} SET updated_at = NOW() WHERE user_id = :user_id AND session_id = :session_id AND status = 'ativa'";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                return true;
            }

            // 2) Linha existe mas status != 'ativa' (ou drift) — reativa e atualiza
            $sql2 = "UPDATE {$this->table} SET status = 'ativa', updated_at = NOW() WHERE user_id = :user_id AND session_id = :session_id";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt2->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
            $stmt2->execute();
            if ($stmt2->rowCount() > 0) {
                return true;
            }

            // 3) Sem linha no BD: usuário autenticado no PHP mas registro ausente — insere
            $sql3 = "INSERT INTO {$this->table} (user_id, session_id, status, created_at, updated_at)
                     VALUES (:user_id, :session_id, 'ativa', NOW(), NOW())";
            $stmt3 = $conn->prepare($sql3);
            $stmt3->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt3->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
            $stmt3->execute();

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

    public function invalidateSessionByUserIdAndSessionId(int $userId, string $sessionId): void
    {
        @file_put_contents(__DIR__ . '/../../../logs/session_investigar.log',
            date('Y-m-d H:i:s') . " [invalidateSessionByUserIdAndSessionId] user_id={$userId} session_id_param={$sessionId} php_session_id=" . session_id() . PHP_EOL,
            FILE_APPEND
        );
        $sql = "UPDATE {$this->table} SET status = 'invalidada' WHERE user_id = :user_id AND session_id = :session_id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
        $stmt->execute();
    }
} 