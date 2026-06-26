<?php

declare(strict_types=1);

namespace App\adms\Models\Repository\inventory;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\InvCostEnergyRedistributionService;
use PDO;

class InvCostExpensePoolsRepository extends DbConnection
{
    /**
     * @return list<array<string, mixed>>
     */
    public function getByPeriodWithRules(int $periodId): array
    {
        $sql = 'SELECT p.*,
                       r.id AS rule_id,
                       r.criterion,
                       r.weight_pct
                FROM inv_cost_expense_pools p
                LEFT JOIN inv_cost_allocation_rules r ON r.expense_pool_id = p.id
                WHERE p.inv_cost_period_id = :period_id
                ORDER BY p.account_code ASC, p.id ASC, r.id ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $pools = [];
        foreach ($rows as $row) {
            $poolId = (int)$row['id'];
            if (!isset($pools[$poolId])) {
                $pools[$poolId] = [
                    'id' => $poolId,
                    'inv_cost_period_id' => (int)$row['inv_cost_period_id'],
                    'dre_import_id' => $row['dre_import_id'] !== null ? (int)$row['dre_import_id'] : null,
                    'source' => (string)($row['source'] ?? 'DRE'),
                    'account_code' => (string)($row['account_code'] ?? ''),
                    'description' => (string)($row['description'] ?? ''),
                    'amount' => (float)($row['amount'] ?? 0),
                    'area' => $row['area'] ?? null,
                    'redistribution_group' => $row['redistribution_group'] ?? null,
                    'rules' => [],
                ];
            }
            if (!empty($row['rule_id'])) {
                $pools[$poolId]['rules'][] = [
                    'id' => (int)$row['rule_id'],
                    'criterion' => (int)$row['criterion'],
                    'weight_pct' => (float)$row['weight_pct'],
                ];
            }
        }

        return array_values($pools);
    }

    public function sumAmountByPeriod(int $periodId): float
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT COALESCE(SUM(amount), 0) FROM inv_cost_expense_pools WHERE inv_cost_period_id = :period_id'
        );
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $stmt->execute();

        return (float)$stmt->fetchColumn();
    }

    public function deleteByPeriod(int $periodId): void
    {
        $stmt = $this->getConnection()->prepare('DELETE FROM inv_cost_expense_pools WHERE inv_cost_period_id = :period_id');
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    public function insertBatch(int $periodId, ?int $importId, array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $count = 0;
        $sql = 'INSERT INTO inv_cost_expense_pools
                (inv_cost_period_id, dre_import_id, source, account_code, description, amount, area, redistribution_group, created_at, updated_at)
                VALUES (:period_id, :import_id, :source, :account_code, :description, :amount, :area, :redistribution_group, :created_at, :updated_at)';
        $stmt = $this->getConnection()->prepare($sql);

        foreach ($rows as $row) {
            $amount = $this->parseAmount($row['amount'] ?? 0);
            if ($amount === 0.0 && trim((string)($row['account_code'] ?? '')) === '') {
                continue;
            }
            $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
            $stmt->bindValue(':import_id', $importId, $importId ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':source', (string)($row['source'] ?? 'DRE'));
            $stmt->bindValue(':account_code', trim((string)($row['account_code'] ?? '')));
            $stmt->bindValue(':description', trim((string)($row['description'] ?? '')) ?: null);
            $stmt->bindValue(':amount', $amount);
            $stmt->bindValue(':area', trim((string)($row['area'] ?? '')) ?: null);
            $stmt->bindValue(':redistribution_group', trim((string)($row['redistribution_group'] ?? '')) ?: null);
            $stmt->bindValue(':created_at', $now);
            $stmt->bindValue(':updated_at', $now);
            $stmt->execute();
            $count++;
        }

        return $count;
    }

    /**
     * Importa sem apagar o período: atualiza conta existente ou insere nova; remove duplicatas da mesma conta.
     *
     * @param list<array<string, mixed>> $rows
     * @return array{inserted: int, updated: int, deduped: int}
     */
    public function upsertImportRows(int $periodId, ?int $importId, array $rows): array
    {
        $inserted = 0;
        $updated = 0;
        $deduped = 0;

        foreach ($rows as $row) {
            $amount = $this->parseAmount($row['amount'] ?? 0);
            $accountCode = trim((string)($row['account_code'] ?? ''));
            if ($amount === 0.0 && $accountCode === '') {
                continue;
            }

            if (InvCostEnergyRedistributionService::isEnergyAccountCode($accountCode)
                && !InvCostEnergyRedistributionService::isAlreadySplitAccount($accountCode)) {
                $this->deleteEnergySlicesForBase($periodId, $accountCode);
            }

            $existingIds = $this->findPoolIdsByPeriodAndAccount($periodId, $accountCode);
            if ($existingIds === []) {
                $this->insertOne($periodId, $importId, $row);
                $inserted++;
                continue;
            }

            $keepId = $existingIds[0];
            $this->updatePoolFromImport($keepId, $importId, $row);
            $updated++;

            for ($i = 1, $n = count($existingIds); $i < $n; $i++) {
                $this->deleteById($existingIds[$i]);
                $deduped++;
            }
        }

        return ['inserted' => $inserted, 'updated' => $updated, 'deduped' => $deduped];
    }

    /**
     * @return list<int>
     */
    public function findPoolIdsByPeriodAndAccount(int $periodId, string $accountCode): array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT id FROM inv_cost_expense_pools
             WHERE inv_cost_period_id = :period_id AND account_code = :account_code
             ORDER BY id ASC'
        );
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $stmt->bindValue(':account_code', trim($accountCode));
        $stmt->execute();
        $ids = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) ?: [] as $id) {
            $ids[] = (int)$id;
        }

        return $ids;
    }

    /**
     * Remove fatias 29-D / 29-C / 29-H antes de reimportar ou redistribuir a conta pai.
     */
    public function deleteEnergySlicesForBase(int $periodId, string $baseCode): void
    {
        $base = trim($baseCode) ?: '29';
        foreach (['-D', '-C', '-H'] as $suffix) {
            foreach ($this->findPoolIdsByPeriodAndAccount($periodId, $base . $suffix) as $poolId) {
                $this->deleteById($poolId);
            }
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    public function updatePoolFromImport(int $poolId, ?int $importId, array $row): void
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'UPDATE inv_cost_expense_pools
                SET dre_import_id = :import_id,
                    source = :source,
                    description = :description,
                    amount = :amount,
                    area = :area,
                    redistribution_group = :redistribution_group,
                    updated_at = :updated_at
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $amount = $this->parseAmount($row['amount'] ?? 0);
        $stmt->bindValue(':import_id', $importId, $importId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':source', (string)($row['source'] ?? 'DRE'));
        $stmt->bindValue(':description', trim((string)($row['description'] ?? '')) ?: null);
        $stmt->bindValue(':amount', $amount);
        $stmt->bindValue(':area', trim((string)($row['area'] ?? '')) ?: null);
        $stmt->bindValue(':redistribution_group', trim((string)($row['redistribution_group'] ?? '')) ?: null);
        $stmt->bindValue(':updated_at', $now);
        $stmt->bindValue(':id', $poolId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function deleteById(int $poolId): void
    {
        if ($poolId <= 0) {
            return;
        }
        $stmt = $this->getConnection()->prepare('DELETE FROM inv_cost_expense_pools WHERE id = :id');
        $stmt->bindValue(':id', $poolId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * @param array<string, mixed> $row
     */
    public function insertOne(int $periodId, ?int $importId, array $row): int
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO inv_cost_expense_pools
                (inv_cost_period_id, dre_import_id, source, account_code, description, amount, area, redistribution_group, created_at, updated_at)
                VALUES (:period_id, :import_id, :source, :account_code, :description, :amount, :area, :redistribution_group, :created_at, :updated_at)';
        $stmt = $this->getConnection()->prepare($sql);
        $amount = $this->parseAmount($row['amount'] ?? 0);
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $stmt->bindValue(':import_id', $importId, $importId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':source', (string)($row['source'] ?? 'DRE'));
        $stmt->bindValue(':account_code', trim((string)($row['account_code'] ?? '')));
        $stmt->bindValue(':description', trim((string)($row['description'] ?? '')) ?: null);
        $stmt->bindValue(':amount', $amount);
        $stmt->bindValue(':area', trim((string)($row['area'] ?? '')) ?: null);
        $stmt->bindValue(':redistribution_group', trim((string)($row['redistribution_group'] ?? '')) ?: null);
        $stmt->bindValue(':created_at', $now);
        $stmt->bindValue(':updated_at', $now);
        $stmt->execute();

        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * Contas de energia ainda não fatiadas (ex.: 29, sem sufixo -D/-C/-H).
     *
     * @return list<array<string, mixed>>
     */
    public function findEnergyAccountsForSplit(int $periodId): array
    {
        $pools = $this->getByPeriodWithRules($periodId);
        $found = [];
        foreach ($pools as $pool) {
            $code = trim((string)($pool['account_code'] ?? ''));
            $desc = mb_strtolower(trim((string)($pool['description'] ?? '')), 'UTF-8');
            if (InvCostEnergyRedistributionService::isAlreadySplitAccount($code)) {
                continue;
            }
            $isEnergy = InvCostEnergyRedistributionService::isEnergyAccountCode($code)
                || (str_contains($desc, 'energia') && str_contains($desc, 'el'));
            if (!$isEnergy) {
                continue;
            }
            $found[] = $pool;
        }

        return $found;
    }

    public function sumEnergyAccountsForSplit(int $periodId): float
    {
        $total = 0.0;
        foreach ($this->findEnergyAccountsForSplit($periodId) as $pool) {
            $total += (float)($pool['amount'] ?? 0);
        }

        return round($total, 4);
    }

    private function parseAmount(mixed $value): float
    {
        if (is_string($value)) {
            $value = str_replace(['.', ' '], ['', ''], $value);
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? round((float)$value, 4) : 0.0;
    }
}
