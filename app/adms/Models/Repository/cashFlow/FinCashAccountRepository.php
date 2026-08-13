<?php

declare(strict_types=1);

namespace App\adms\Models\Repository\cashFlow;

use App\adms\Models\Services\DbConnection;
use PDO;

class FinCashAccountRepository extends DbConnection
{
    public function tableExists(): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }
        try {
            $stmt = $this->getConnection()->query("SHOW TABLES LIKE 'adms_fin_cash_accounts'");
            $exists = (bool) $stmt->fetchColumn();
        } catch (\Throwable) {
            $exists = false;
        }
        return $exists;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getAll(bool $activeOnly = false): array
    {
        if (!$this->tableExists()) {
            return [];
        }
        $sql = 'SELECT * FROM adms_fin_cash_accounts';
        if ($activeOnly) {
            $sql .= ' WHERE active = 1';
        }
        $sql .= ' ORDER BY account_type ASC, description ASC, sap_gl_account ASC';
        return $this->getConnection()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getIncludedInCashFlow(): array
    {
        if (!$this->tableExists()) {
            return [];
        }
        $stmt = $this->getConnection()->query(
            'SELECT * FROM adms_fin_cash_accounts
             WHERE active = 1 AND include_in_cash_flow = 1
             ORDER BY description ASC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getById(int $id): ?array
    {
        if (!$this->tableExists() || $id <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare('SELECT * FROM adms_fin_cash_accounts WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * @param array<string, mixed> $row
     */
    public function upsertFromSap(array $row): void
    {
        if (!$this->tableExists()) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO adms_fin_cash_accounts
                    (sap_gl_account, bank_code, branch, bank_account, description, account_type,
                     sap_bpl_id, credit_limit, active, include_in_cash_flow, include_in_availability,
                     type_locked, created_at, updated_at)
                VALUES
                    (:gl, :bank_code, :branch, :bank_account, :description, :account_type,
                     :bpl, 0, 1, 1, 1, 0, :now, :now)
                ON DUPLICATE KEY UPDATE
                    bank_code = VALUES(bank_code),
                    branch = VALUES(branch),
                    bank_account = VALUES(bank_account),
                    description = IF(type_locked = 1, description, VALUES(description)),
                    account_type = IF(type_locked = 1, account_type, VALUES(account_type)),
                    sap_bpl_id = VALUES(sap_bpl_id),
                    updated_at = VALUES(updated_at)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            ':gl' => $row['sap_gl_account'],
            ':bank_code' => $row['bank_code'] ?? null,
            ':branch' => $row['branch'] ?? null,
            ':bank_account' => $row['bank_account'] ?? null,
            ':description' => $row['description'] ?? $row['sap_gl_account'],
            ':account_type' => $row['account_type'] ?? 'BANK',
            ':bpl' => $row['sap_bpl_id'] ?? null,
            ':now' => $now,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        if (!$this->tableExists() || $id <= 0) {
            return false;
        }
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_fin_cash_accounts SET
                description = :description,
                account_type = :account_type,
                credit_limit = :credit_limit,
                active = :active,
                include_in_cash_flow = :include_cf,
                include_in_availability = :include_av,
                type_locked = 1,
                updated_at = :updated_at
             WHERE id = :id'
        );
        return $stmt->execute([
            ':description' => $data['description'],
            ':account_type' => $data['account_type'],
            ':credit_limit' => $data['credit_limit'],
            ':active' => !empty($data['active']) ? 1 : 0,
            ':include_cf' => !empty($data['include_in_cash_flow']) ? 1 : 0,
            ':include_av' => !empty($data['include_in_availability']) ? 1 : 0,
            ':updated_at' => date('Y-m-d H:i:s'),
            ':id' => $id,
        ]);
    }
}
