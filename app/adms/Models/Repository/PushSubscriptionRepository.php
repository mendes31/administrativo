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
            // Evita duplicar combinação usuário + mesmas chaves (subscription equivalente).
            $duplicateKeys = $this->findByUserAndKeys($userId, $publicKey, $authToken);
            if ($duplicateKeys !== null) {
                $sql = 'UPDATE adms_push_subscriptions SET
                            endpoint = :endpoint,
                            endpoint_hash = :endpoint_hash,
                            content_encoding = :content_encoding,
                            user_agent = :user_agent,
                            updated_at = NOW()
                        WHERE id = :id';
                $stmt = $this->getConnection()->prepare($sql);
                $stmt->bindValue(':id', (int) $duplicateKeys['id'], PDO::PARAM_INT);
                $stmt->bindValue(':endpoint', $endpoint);
                $stmt->bindValue(':endpoint_hash', $endpointHash);
                $stmt->bindValue(':content_encoding', $contentEncoding);
                $stmt->bindValue(':user_agent', $userAgent !== '' ? $userAgent : null, $userAgent !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $ok = $stmt->execute();
                if ($ok) {
                    $this->removeStaleSubscriptionsForUser($userId, $endpointHash, $userAgent);
                }

                return $ok;
            } else {
                $sql = 'INSERT INTO adms_push_subscriptions (
                            user_id, endpoint_hash, endpoint, public_key, auth_token, content_encoding, user_agent, created_at, updated_at
                        ) VALUES (
                            :user_id, :endpoint_hash, :endpoint, :public_key, :auth_token, :content_encoding, :user_agent, NOW(), NOW()
                        )';
                $stmt = $this->getConnection()->prepare($sql);
                $stmt->bindValue(':endpoint_hash', $endpointHash);
            }
        }

        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':endpoint', $endpoint);
        $stmt->bindValue(':public_key', $publicKey);
        $stmt->bindValue(':auth_token', $authToken);
        $stmt->bindValue(':content_encoding', $contentEncoding);
        $stmt->bindValue(':user_agent', $userAgent !== '' ? $userAgent : null, $userAgent !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);

        $ok = $stmt->execute();
        if ($ok) {
            $this->removeStaleSubscriptionsForUser($userId, $endpointHash, $userAgent);
        }

        return $ok;
    }

    /**
     * Remove inscrições antigas do mesmo usuário no mesmo navegador/dispositivo (user_agent).
     */
    private function removeStaleSubscriptionsForUser(int $userId, string $keepEndpointHash, string $userAgent): void
    {
        if (!$this->tableExists() || $userId <= 0 || $keepEndpointHash === '') {
            return;
        }

        if ($userAgent !== '') {
            $sql = 'DELETE FROM adms_push_subscriptions
                    WHERE user_id = :user_id
                      AND endpoint_hash <> :keep_endpoint_hash
                      AND user_agent = :user_agent';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':keep_endpoint_hash', $keepEndpointHash);
            $stmt->bindValue(':user_agent', $userAgent);
            $stmt->execute();
        }
    }

    private function findByUserAndKeys(int $userId, string $publicKey, string $authToken): ?array
    {
        if (!$this->tableExists() || $userId <= 0 || $publicKey === '' || $authToken === '') {
            return null;
        }

        $sql = 'SELECT * FROM adms_push_subscriptions
                WHERE user_id = :user_id
                  AND public_key = :public_key
                  AND auth_token = :auth_token
                ORDER BY updated_at DESC
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':public_key', $publicKey);
        $stmt->bindValue(':auth_token', $authToken);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
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

    public function countByUserId(int $userId): int
    {
        return count($this->listByUserId($userId));
    }

    public function countAll(): int
    {
        if (!$this->tableExists()) {
            return 0;
        }

        $sql = 'SELECT COUNT(*) FROM adms_push_subscriptions';
        $stmt = $this->getConnection()->query($sql);

        return (int) ($stmt->fetchColumn() ?: 0);
    }

    /**
     * Lote para verificação de inscrições inválidas (cron / manutenção).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listAllForMaintenance(int $limit = 200, int $offset = 0): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $limit = max(1, min(500, $limit));
        $offset = max(0, $offset);

        $sql = 'SELECT id, user_id, endpoint_hash, endpoint, public_key, auth_token, content_encoding, user_agent, created_at, updated_at
                FROM adms_push_subscriptions
                ORDER BY updated_at ASC
                LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function hasEndpointForUser(int $userId, string $endpoint): bool
    {
        if ($userId <= 0 || trim($endpoint) === '') {
            return false;
        }

        $endpointHash = hash('sha256', $endpoint);
        $row = $this->findByEndpointHash($endpointHash);
        if ($row === null) {
            return false;
        }

        return (int) ($row['user_id'] ?? 0) === $userId;
    }

    /**
     * Lista dispositivos com push ativo para exibição no perfil (sem chaves sensíveis).
     *
     * @return array<int, array{id:int, label:string, endpoint:string, updated_at:string, updated_at_fmt:string}>
     */
    public function listDevicesForUser(int $userId): array
    {
        if (!$this->tableExists() || $userId <= 0) {
            return [];
        }

        $devices = [];
        foreach ($this->listByUserId($userId) as $row) {
            $updatedAt = (string) ($row['updated_at'] ?? '');
            $devices[] = [
                'id' => (int) ($row['id'] ?? 0),
                'label' => $this->formatDeviceLabel($row),
                'endpoint' => (string) ($row['endpoint'] ?? ''),
                'updated_at' => $updatedAt,
                'updated_at_fmt' => $updatedAt !== ''
                    ? date('d/m/Y H:i', strtotime($updatedAt))
                    : '',
            ];
        }

        return $devices;
    }

    /**
     * @param array<string, mixed> $row
     */
    public function getDeviceLabel(array $row): string
    {
        return $this->formatDeviceLabel($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function formatDeviceLabel(array $row): string
    {
        $endpoint = (string) ($row['endpoint'] ?? '');
        $ua = (string) ($row['user_agent'] ?? '');

        if (str_contains($endpoint, 'notify.windows.com')) {
            return 'Windows — Microsoft Edge';
        }

        if (str_contains($endpoint, 'fcm.googleapis.com')) {
            if (stripos($ua, 'Android') !== false || stripos($ua, 'Mobile') !== false) {
                $browser = stripos($ua, 'Edg') !== false
                    ? 'Edge'
                    : (stripos($ua, 'Chrome') !== false ? 'Chrome' : 'Navegador');
                return 'Android — ' . $browser;
            }

            $browser = stripos($ua, 'Edg') !== false
                ? 'Microsoft Edge'
                : (stripos($ua, 'Chrome') !== false ? 'Chrome' : 'Navegador');
            return 'Windows — ' . $browser;
        }

        if (str_contains($endpoint, 'mozilla.com')) {
            return 'Firefox';
        }

        if ($ua === '') {
            return 'Dispositivo desconhecido';
        }

        if (stripos($ua, 'Android') !== false) {
            $browser = stripos($ua, 'Edg') !== false
                ? 'Edge'
                : (stripos($ua, 'Chrome') !== false ? 'Chrome' : 'Navegador');
            return 'Android — ' . $browser;
        }

        if (stripos($ua, 'Windows') !== false) {
            $browser = stripos($ua, 'Edg') !== false
                ? 'Microsoft Edge'
                : (stripos($ua, 'Chrome') !== false ? 'Chrome' : 'Navegador');
            return 'Windows — ' . $browser;
        }

        if (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) {
            return stripos($ua, 'CriOS') !== false ? 'iOS — Chrome' : 'iOS — Safari/PWA';
        }

        if (stripos($ua, 'Mac OS') !== false || stripos($ua, 'Macintosh') !== false) {
            $browser = stripos($ua, 'Chrome') !== false
                ? 'Chrome'
                : (stripos($ua, 'Safari') !== false ? 'Safari' : 'Navegador');
            return 'macOS — ' . $browser;
        }

        return mb_strlen($ua) > 72 ? mb_substr($ua, 0, 72) . '…' : $ua;
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
