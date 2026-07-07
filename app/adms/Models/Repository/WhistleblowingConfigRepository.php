<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

/**
 * Configuração do Canal de Denúncias (cron LGPD + chave de criptografia).
 */
class WhistleblowingConfigRepository extends DbConnection
{
    private static ?array $cachedRow = null;

    public static function clearCache(): void
    {
        self::$cachedRow = null;
    }

    /** @return array<string, mixed> */
    public function getRow(): array
    {
        if (self::$cachedRow !== null) {
            return self::$cachedRow;
        }

        try {
            if (!$this->tableExists()) {
                self::$cachedRow = [];

                return [];
            }
            $stmt = $this->getConnection()->query('SELECT * FROM adms_whistleblowing_config ORDER BY id ASC LIMIT 1');
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            self::$cachedRow = $row ?: [];
        } catch (\Throwable) {
            self::$cachedRow = [];
        }

        return self::$cachedRow;
    }

    public function getHttpCronToken(): string
    {
        return trim((string) ($this->getRow()['http_cron_token'] ?? ''));
    }

    public function hasHttpCronToken(): bool
    {
        return $this->getHttpCronToken() !== '';
    }

    public function getEncryptionKey(): string
    {
        return trim((string) ($this->getRow()['encryption_key'] ?? ''));
    }

    public function hasEncryptionKey(): bool
    {
        return $this->getEncryptionKey() !== '';
    }

    public function saveHttpCronToken(string $token): bool
    {
        return $this->saveFields(['http_cron_token' => trim($token)]);
    }

    public function saveEncryptionKey(string $key): bool
    {
        return $this->saveFields(['encryption_key' => trim($key)]);
    }

    /**
     * @param array<string, string|null> $fields
     */
    private function saveFields(array $fields): bool
    {
        if (!$this->tableExists()) {
            return false;
        }

        $oldData = $this->getRow();
        $conn = $this->getConnection();
        $userId = (int) ($_SESSION['user_id'] ?? 1);

        if (!empty($oldData['id'])) {
            $sets = [];
            $params = [':id' => (int) $oldData['id']];
            foreach ($fields as $col => $val) {
                $sets[] = "{$col} = :{$col}";
                $params[":{$col}"] = ($val === '' || $val === null) ? null : $val;
            }
            $sets[] = 'updated_at = NOW()';
            $sql = 'UPDATE adms_whistleblowing_config SET ' . implode(', ', $sets) . ' WHERE id = :id';
            $stmt = $conn->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, $v === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            }
            $ok = $stmt->execute();
        } else {
            $cols = array_keys($fields);
            $placeholders = array_map(fn ($c) => ':' . $c, $cols);
            $sql = 'INSERT INTO adms_whistleblowing_config (' . implode(', ', $cols) . ', created_at, updated_at)
                    VALUES (' . implode(', ', $placeholders) . ', NOW(), NOW())';
            $stmt = $conn->prepare($sql);
            foreach ($fields as $col => $val) {
                $stmt->bindValue(':' . $col, ($val === '' || $val === null) ? null : $val, ($val === '' || $val === null) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            }
            $ok = $stmt->execute();
        }

        if ($ok) {
            self::clearCache();
            $newData = $this->getRow();
            $recordId = (int) ($newData['id'] ?? $oldData['id'] ?? 0);
            if ($recordId > 0) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_whistleblowing_config',
                    $recordId,
                    $userId,
                    empty($oldData['id']) ? 'INSERT' : 'UPDATE',
                    $oldData,
                    $newData
                );
            }
        }

        return $ok;
    }

    private function tableExists(): bool
    {
        try {
            $stmt = $this->getConnection()->query("SHOW TABLES LIKE 'adms_whistleblowing_config'");

            return (bool) $stmt->fetchColumn();
        } catch (\Throwable) {
            return false;
        }
    }
}
