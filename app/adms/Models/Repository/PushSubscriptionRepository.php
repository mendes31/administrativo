<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class PushSubscriptionRepository extends DbConnection
{
    public function upsert(int $userId, array $subscription): bool
    {
        if (!$this->tableExists() || $userId <= 0) {
            return false;
        }

        $endpoint = trim((string) ($subscription['endpoint'] ?? ''));
        $publicKey = trim((string) ($subscription['keys']['p256dh'] ?? $subscription['public_key'] ?? ''));
        $authToken = trim((string) ($subscription['keys']['auth'] ?? $subscription['auth_token'] ?? ''));
        $contentEncoding = trim((string) ($subscription['contentEncoding'] ?? $subscription['content_encoding'] ?? 'aes128gcm'));
        $userAgent = trim((string) ($subscription['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? '')));

        if ($endpoint === '' || $publicKey === '' || $authToken === '') {
            return false;
        }

        if ($contentEncoding === '') {
            $contentEncoding = 'aes128gcm';
        }

        $endpointHash = hash('sha256', $endpoint);

        $existing = $this->findByEndpointHash($endpointHash);
        if ($existing !== null) {
            $sql = 'UPDATE adms_push_subscriptions SET
                        user_id = :user_id,
                        endpoint = :endpoint,
                        public_key = :public_key,
                        auth_token = :auth_token,
                        content_encoding = :content_encoding,
                        user_agent = :user_agent,
                        updated_at = NOW()
                    WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', (int) $existing['id'], PDO::PARAM_INT);
        } else {
            $sql = 'INSERT INTO adms_push_subscriptions (
                        user_id, endpoint_hash, endpoint, public_key, auth_token, content_encoding, user_agent, created_at, updated_at
                    ) VALUES (
                        :user_id, :endpoint_hash, :endpoint, :public_key, :auth_token, :content_encoding, :user_agent, NOW(), NOW()
                    )';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':endpoint_hash', $endpointHash);
        }

        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':endpoint', $endpoint);
        $stmt->bindValue(':public_key', $publicKey);
        $stmt->bindValue(':auth_token', $authToken);
        $stmt->bindValue(':content_encoding', $contentEncoding);
        $stmt->bindValue(':user_agent', $userAgent !== '' ? $userAgent : null, $userAgent !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);

        return $stmt->execute();
    }

    public function deleteByEndpoint(int $userId, string $endpoint): bool
    {
        if (!$this->tableExists() || $userId <= 0 || trim($endpoint) === '') {
            return false;
        }

        $endpointHash = hash('sha256', $endpoint);
        $sql = 'DELETE FROM adms_push_subscriptions WHERE user_id = :user_id AND endpoint_hash = :endpoint_hash';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':endpoint_hash', $endpointHash);

        return $stmt->execute();
    }

    public function deleteByIdForUser(int $userId, int $subscriptionId): bool
    {
        if (!$this->tableExists() || $userId <= 0 || $subscriptionId <= 0) {
            return false;
        }

        $sql = 'DELETE FROM adms_push_subscriptions WHERE id = :id AND user_id = :user_id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $subscriptionId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function deleteById(int $subscriptionId): bool
    {
        if (!$this->tableExists() || $subscriptionId <= 0) {
            return false;
        }

        $sql = 'DELETE FROM adms_push_subscriptions WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $subscriptionId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listByUserId(int $userId): array
    {
        if (!$this->tableExists() || $userId <= 0) {
            return [];
        }

        $sql = 'SELECT id, user_id, endpoint_hash, endpoint, public_key, auth_token, content_encoding, user_agent, created_at, updated_at
                FROM adms_push_subscriptions
                WHERE user_id = :user_id
                ORDER BY updated_at DESC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function userHasSubscription(int $userId): bool
    {
        return $this->listByUserId($userId) !== [];
    }

    public function findByEndpointHash(string $endpointHash): ?array
    {
        if (!$this->tableExists()) {
            return null;
        }

        $sql = 'SELECT * FROM adms_push_subscriptions WHERE endpoint_hash = :endpoint_hash LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':endpoint_hash', $endpointHash);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function tableExists(): bool
    {
        $stmt = $this->getConnection()->query("SHOW TABLES LIKE 'adms_push_subscriptions'");
        if ($stmt === false) {
            return false;
        }

        return (bool) $stmt->fetch(PDO::FETCH_NUM);
    }
}
