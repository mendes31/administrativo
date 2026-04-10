<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Token HTTP para o cron de lembretes de ciência (folha RH).
 */
class PayrollCronConfigRepository extends DbConnection
{
    /** @return array<string, mixed> */
    public function getRow(): array
    {
        try {
            $stmt = $this->getConnection()->query('SELECT * FROM adms_payroll_cron_config ORDER BY id ASC LIMIT 1');
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function getHttpCronToken(): string
    {
        return trim((string)($this->getRow()['http_cron_token'] ?? ''));
    }

    public function hasHttpCronToken(): bool
    {
        return $this->getHttpCronToken() !== '';
    }

    public function saveHttpCronToken(string $token): bool
    {
        $token = trim($token);
        $row = $this->getRow();
        $conn = $this->getConnection();
        if (!empty($row['id'])) {
            $stmt = $conn->prepare('UPDATE adms_payroll_cron_config SET http_cron_token = :t, updated_at = NOW() WHERE id = :id');
            $stmt->bindValue(':id', (int)$row['id'], PDO::PARAM_INT);
            $stmt->bindValue(':t', $token, $token === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);

            return $stmt->execute();
        }
        $stmt = $conn->prepare('INSERT INTO adms_payroll_cron_config (http_cron_token, created_at, updated_at) VALUES (:t, NOW(), NOW())');
        $stmt->bindValue(':t', $token, $token === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);

        return $stmt->execute();
    }
}
