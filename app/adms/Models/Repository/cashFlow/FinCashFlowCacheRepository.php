<?php

declare(strict_types=1);

namespace App\adms\Models\Repository\cashFlow;

use App\adms\Models\Services\DbConnection;
use DateTimeImmutable;
use PDO;

class FinCashFlowCacheRepository extends DbConnection
{
    public function tableExists(): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }
        try {
            $stmt = $this->getConnection()->query("SHOW TABLES LIKE 'adms_fin_cash_daily'");
            $exists = (bool) $stmt->fetchColumn();
        } catch (\Throwable) {
            $exists = false;
        }
        return $exists;
    }

    public function countDailyRows(): int
    {
        if (!$this->tableExists()) {
            return 0;
        }
        return (int) $this->getConnection()->query('SELECT COUNT(*) FROM adms_fin_cash_daily')->fetchColumn();
    }

    /**
     * @return array<string, mixed>
     */
    public function getSyncState(): array
    {
        try {
            $stmt = $this->getConnection()->query('SELECT * FROM adms_fin_cash_sync_state WHERE id = 1 LIMIT 1');
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createSyncRun(array $data): int
    {
        $stmt = $this->getConnection()->prepare(
            'INSERT INTO adms_fin_cash_sync_runs
                (sync_mode, date_from, date_to, rows_fetched, rows_upserted, status, executed_by, started_at)
             VALUES
                (:mode, :from, :to, 0, 0, :status, :user, :started)'
        );
        $stmt->execute([
            ':mode' => $data['sync_mode'],
            ':from' => $data['date_from'] ?? null,
            ':to' => $data['date_to'] ?? null,
            ':status' => $data['status'] ?? 'running',
            ':user' => $data['executed_by'] ?? ($_SESSION['user_id'] ?? null),
            ':started' => $data['started_at'] ?? date('Y-m-d H:i:s'),
        ]);
        return (int) $this->getConnection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function finishSyncRun(int $id, array $data): void
    {
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_fin_cash_sync_runs SET
                date_from = :from,
                date_to = :to,
                rows_fetched = :fetched,
                rows_upserted = :upserted,
                status = :status,
                error_log = :error,
                message = :message,
                finished_at = :finished
             WHERE id = :id'
        );
        $stmt->execute([
            ':from' => $data['date_from'] ?? null,
            ':to' => $data['date_to'] ?? null,
            ':fetched' => $data['rows_fetched'] ?? 0,
            ':upserted' => $data['rows_upserted'] ?? 0,
            ':status' => $data['status'] ?? 'completed',
            ':error' => $data['error_log'] ?? null,
            ':message' => $data['message'] ?? null,
            ':finished' => $data['finished_at'] ?? date('Y-m-d H:i:s'),
            ':id' => $id,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function saveSyncState(array $data): void
    {
        $stmt = $this->getConnection()->prepare(
            'INSERT INTO adms_fin_cash_sync_state
                (id, last_mode, last_status, last_from_date, last_to_date, last_success_at, rows_upserted, message, updated_at)
             VALUES
                (1, :mode, :status, :from, :to, :success_at, :rows, :message, :updated)
             ON DUPLICATE KEY UPDATE
                last_mode = VALUES(last_mode),
                last_status = VALUES(last_status),
                last_from_date = VALUES(last_from_date),
                last_to_date = VALUES(last_to_date),
                last_success_at = VALUES(last_success_at),
                rows_upserted = VALUES(rows_upserted),
                message = VALUES(message),
                updated_at = VALUES(updated_at)'
        );
        $stmt->execute([
            ':mode' => $data['last_mode'] ?? null,
            ':status' => $data['last_status'] ?? null,
            ':from' => $data['last_from_date'] ?? null,
            ':to' => $data['last_to_date'] ?? null,
            ':success_at' => $data['last_success_at'] ?? null,
            ':rows' => $data['rows_upserted'] ?? 0,
            ':message' => $data['message'] ?? null,
            ':updated' => $data['updated_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listSyncRuns(int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = $this->getConnection()->query(
            "SELECT * FROM adms_fin_cash_sync_runs ORDER BY id DESC LIMIT {$limit}"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function replaceOpening(DateTimeImmutable $asOf, array $rows): int
    {
        $pdo = $this->getConnection();
        $date = $asOf->format('Y-m-d');
        $pdo->prepare('DELETE FROM adms_fin_cash_opening WHERE as_of_date = :d')->execute([':d' => $date]);
        if ($rows === []) {
            return 0;
        }
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare(
            'INSERT INTO adms_fin_cash_opening (as_of_date, sap_gl_account, balance, synced_at)
             VALUES (:d, :gl, :bal, :now)'
        );
        $count = 0;
        foreach ($rows as $row) {
            $gl = trim((string) ($row['sap_gl_account'] ?? ''));
            if ($gl === '') {
                continue;
            }
            $stmt->execute([
                ':d' => $date,
                ':gl' => $gl,
                ':bal' => (float) ($row['balance'] ?? 0),
                ':now' => $now,
            ]);
            $count++;
        }
        return $count;
    }

    /**
     * @return array<string, float> gl => balance
     */
    public function getOpeningMap(string $asOfDate): array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT sap_gl_account, balance FROM adms_fin_cash_opening WHERE as_of_date = :d'
        );
        $stmt->execute([':d' => $asOfDate]);
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $map[(string) $row['sap_gl_account']] = (float) $row['balance'];
        }
        return $map;
    }

    public function latestOpeningDate(): ?string
    {
        $val = $this->getConnection()->query('SELECT MAX(as_of_date) FROM adms_fin_cash_opening')->fetchColumn();
        return $val ? (string) $val : null;
    }

    public function deleteDailyRange(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        $stmt = $this->getConnection()->prepare(
            'DELETE FROM adms_fin_cash_daily WHERE movement_date >= :from AND movement_date <= :to'
        );
        $stmt->execute([
            ':from' => $from->format('Y-m-d'),
            ':to' => $to->format('Y-m-d'),
        ]);
        return $stmt->rowCount();
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    public function insertDailyBatch(array $rows): int
    {
        if ($rows === []) {
            return 0;
        }
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO adms_fin_cash_daily
                    (movement_date, sap_gl_account, sap_bpl_id, inflow, outflow, internal_in, internal_out, synced_at)
                VALUES
                    (:d, :gl, :bpl, :inflow, :outflow, :iin, :iout, :now)
                ON DUPLICATE KEY UPDATE
                    inflow = VALUES(inflow),
                    outflow = VALUES(outflow),
                    internal_in = VALUES(internal_in),
                    internal_out = VALUES(internal_out),
                    synced_at = VALUES(synced_at)';
        $pdo = $this->getConnection();
        $stmt = $pdo->prepare($sql);
        $count = 0;
        $pdo->beginTransaction();
        try {
            foreach ($rows as $row) {
                $stmt->execute([
                    ':d' => $row['movement_date'],
                    ':gl' => $row['sap_gl_account'],
                    ':bpl' => (int) ($row['sap_bpl_id'] ?? 0),
                    ':inflow' => (float) ($row['inflow'] ?? 0),
                    ':outflow' => (float) ($row['outflow'] ?? 0),
                    ':iin' => (float) ($row['internal_in'] ?? 0),
                    ':iout' => (float) ($row['internal_out'] ?? 0),
                    ':now' => $now,
                ]);
                $count++;
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return $count;
    }

    public function replaceForecasts(array $rows): int
    {
        $pdo = $this->getConnection();
        $pdo->exec('DELETE FROM adms_fin_cash_forecasts');
        if ($rows === []) {
            return 0;
        }
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare(
            'INSERT INTO adms_fin_cash_forecasts
                (source_type, due_date, sap_bpl_id, card_code, card_name, doc_entry, doc_num,
                 installment_id, original_amount, paid_amount, open_amount, synced_at)
             VALUES
                (:src, :due, :bpl, :code, :name, :entry, :num, :inst, :orig, :paid, :open, :now)'
        );
        $count = 0;
        $pdo->beginTransaction();
        try {
            foreach ($rows as $row) {
                $stmt->execute([
                    ':src' => $row['source_type'],
                    ':due' => $row['due_date'],
                    ':bpl' => (int) ($row['sap_bpl_id'] ?? 0),
                    ':code' => $row['card_code'] ?? '',
                    ':name' => $row['card_name'] ?? '',
                    ':entry' => (int) ($row['doc_entry'] ?? 0),
                    ':num' => (int) ($row['doc_num'] ?? 0),
                    ':inst' => (int) ($row['installment_id'] ?? 1),
                    ':orig' => (float) ($row['original_amount'] ?? 0),
                    ':paid' => (float) ($row['paid_amount'] ?? 0),
                    ':open' => (float) ($row['open_amount'] ?? 0),
                    ':now' => $now,
                ]);
                $count++;
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return $count;
    }

    /**
     * @param list<string> $glAccounts
     * @return list<array<string, mixed>>
     */
    public function getDaily(string $from, string $to, array $glAccounts = [], ?int $branchId = null): array
    {
        if (!$this->tableExists()) {
            return [];
        }
        $sql = 'SELECT movement_date, sap_gl_account, sap_bpl_id, inflow, outflow, internal_in, internal_out
                FROM adms_fin_cash_daily
                WHERE movement_date >= :from AND movement_date <= :to';
        $params = [':from' => $from, ':to' => $to];
        if ($glAccounts !== []) {
            $placeholders = [];
            foreach (array_values($glAccounts) as $i => $gl) {
                $key = ':gl' . $i;
                $placeholders[] = $key;
                $params[$key] = $gl;
            }
            $sql .= ' AND sap_gl_account IN (' . implode(',', $placeholders) . ')';
        }
        if ($branchId !== null && $branchId >= 0) {
            $sql .= ' AND sap_bpl_id = :bpl';
            $params[':bpl'] = $branchId;
        }
        $sql .= ' ORDER BY movement_date ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param list<string> $glAccounts
     * @return list<array<string, mixed>>
     */
    public function getForecasts(?string $from, ?string $to, ?string $sourceType = null, ?int $branchId = null): array
    {
        $sql = 'SELECT * FROM adms_fin_cash_forecasts WHERE 1=1';
        $params = [];
        if ($from) {
            $sql .= ' AND due_date >= :from';
            $params[':from'] = $from;
        }
        if ($to) {
            $sql .= ' AND due_date <= :to';
            $params[':to'] = $to;
        }
        if ($sourceType) {
            $sql .= ' AND source_type = :src';
            $params[':src'] = $sourceType;
        }
        if ($branchId !== null && $branchId >= 0) {
            $sql .= ' AND sap_bpl_id = :bpl';
            $params[':bpl'] = $branchId;
        }
        $sql .= ' ORDER BY due_date ASC, card_name ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array{sap_bpl_id:int}>
     */
    public function distinctBranches(): array
    {
        $stmt = $this->getConnection()->query(
            'SELECT DISTINCT sap_bpl_id FROM adms_fin_cash_daily WHERE sap_bpl_id > 0
             UNION
             SELECT DISTINCT sap_bpl_id FROM adms_fin_cash_forecasts WHERE sap_bpl_id > 0
             ORDER BY sap_bpl_id'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
