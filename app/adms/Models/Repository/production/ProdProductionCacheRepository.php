<?php

declare(strict_types=1);

namespace App\adms\Models\Repository\production;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Cache MySQL do Dashboard de Produção (ordens SAP/BEAS + estado de sync).
 */
class ProdProductionCacheRepository extends DbConnection
{
    public function tableExists(): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }
        try {
            $stmt = $this->getConnection()->query("SHOW TABLES LIKE 'adms_prod_wo_fact'");
            $exists = (bool) $stmt->fetchColumn();
        } catch (\Throwable) {
            $exists = false;
        }
        return $exists;
    }

    public function countRows(): int
    {
        if (!$this->tableExists()) {
            return 0;
        }
        $stmt = $this->getConnection()->query('SELECT COUNT(*) FROM adms_prod_wo_fact');
        return (int) $stmt->fetchColumn();
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    public function upsertBatch(array $rows): int
    {
        if ($rows === [] || !$this->tableExists()) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $hasOrderDate = $this->woHasOrderDate();
        $orderCol = $hasOrderDate ? ', order_date' : '';
        $orderVal = $hasOrderDate ? ', :order_date' : '';
        $orderUpd = $hasOrderDate ? 'order_date = COALESCE(VALUES(order_date), order_date),' : '';

        $sql = "INSERT INTO adms_prod_wo_fact (
                    source, doc_entry, doc_num, item_code, item_name, product_name,
                    line_name, warehouse, status_code, status_label,
                    qty_planned, qty_completed, qty_scrap
                    {$orderCol},
                    start_date, due_date, close_date, cycle_hours, downtime_hours,
                    synced_at, created_at, updated_at
                ) VALUES (
                    :source, :doc_entry, :doc_num, :item_code, :item_name, :product_name,
                    :line_name, :warehouse, :status_code, :status_label,
                    :qty_planned, :qty_completed, :qty_scrap
                    {$orderVal},
                    :start_date, :due_date, :close_date, :cycle_hours, :downtime_hours,
                    :synced_at, :created_at, :updated_at
                )
                ON DUPLICATE KEY UPDATE
                    doc_num = VALUES(doc_num),
                    item_code = VALUES(item_code),
                    item_name = VALUES(item_name),
                    product_name = VALUES(product_name),
                    line_name = VALUES(line_name),
                    warehouse = VALUES(warehouse),
                    status_code = VALUES(status_code),
                    status_label = VALUES(status_label),
                    qty_planned = VALUES(qty_planned),
                    {$orderUpd}
                    start_date = COALESCE(VALUES(start_date), start_date),
                    due_date = VALUES(due_date),
                    close_date = VALUES(close_date),
                    cycle_hours = VALUES(cycle_hours),
                    downtime_hours = VALUES(downtime_hours),
                    synced_at = VALUES(synced_at),
                    updated_at = VALUES(updated_at)";

        $pdo = $this->getConnection();
        $stmt = $pdo->prepare($sql);
        $count = 0;
        $pdo->beginTransaction();
        try {
            foreach ($rows as $row) {
                $params = [
                    ':source' => (string) ($row['source'] ?? 'owor'),
                    ':doc_entry' => (int) ($row['doc_entry'] ?? 0),
                    ':doc_num' => (int) ($row['doc_num'] ?? 0),
                    ':item_code' => (string) ($row['item_code'] ?? ''),
                    ':item_name' => (string) ($row['item_name'] ?? ''),
                    ':product_name' => (string) ($row['product_name'] ?? ''),
                    ':line_name' => (string) ($row['line_name'] ?? ''),
                    ':warehouse' => (string) ($row['warehouse'] ?? ''),
                    ':status_code' => (string) ($row['status_code'] ?? ''),
                    ':status_label' => (string) ($row['status_label'] ?? ''),
                    ':qty_planned' => (float) ($row['qty_planned'] ?? 0),
                    ':qty_completed' => (float) ($row['qty_completed'] ?? 0),
                    ':qty_scrap' => (float) ($row['qty_scrap'] ?? 0),
                    ':start_date' => $row['start_date'] ?? null,
                    ':due_date' => $row['due_date'] ?? null,
                    ':close_date' => $row['close_date'] ?? null,
                    ':cycle_hours' => $row['cycle_hours'] ?? null,
                    ':downtime_hours' => $row['downtime_hours'] ?? null,
                    ':synced_at' => $now,
                    ':created_at' => $now,
                    ':updated_at' => $now,
                ];
                if ($hasOrderDate) {
                    $params[':order_date'] = $row['order_date'] ?? null;
                }
                $stmt->execute($params);
                $count++;
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return $count;
    }

    public function receiptTableExists(): bool
    {
        return $this->mysqlTableExists('adms_prod_receipt_fact');
    }

    public function scrapTableExists(): bool
    {
        return $this->mysqlTableExists('adms_prod_scrap_day');
    }

    public function countReceipts(): int
    {
        if (!$this->receiptTableExists()) {
            return 0;
        }
        $stmt = $this->getConnection()->query('SELECT COUNT(*) FROM adms_prod_receipt_fact');
        return (int) $stmt->fetchColumn();
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    public function upsertReceipts(array $rows): int
    {
        if ($rows === [] || !$this->receiptTableExists()) {
            return 0;
        }
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO adms_prod_receipt_fact (
                    oign_doc_entry, line_num, oign_doc_num, doc_date, belnr_id,
                    item_code, item_name, warehouse, qty, synced_at, created_at, updated_at
                ) VALUES (
                    :oign_doc_entry, :line_num, :oign_doc_num, :doc_date, :belnr_id,
                    :item_code, :item_name, :warehouse, :qty, :synced_at, :created_at, :updated_at
                )
                ON DUPLICATE KEY UPDATE
                    oign_doc_num = VALUES(oign_doc_num),
                    doc_date = VALUES(doc_date),
                    belnr_id = VALUES(belnr_id),
                    item_code = VALUES(item_code),
                    item_name = VALUES(item_name),
                    warehouse = VALUES(warehouse),
                    qty = VALUES(qty),
                    synced_at = VALUES(synced_at),
                    updated_at = VALUES(updated_at)';
        $pdo = $this->getConnection();
        $stmt = $pdo->prepare($sql);
        $count = 0;
        $pdo->beginTransaction();
        try {
            foreach ($rows as $row) {
                $stmt->execute([
                    ':oign_doc_entry' => (int) ($row['oign_doc_entry'] ?? 0),
                    ':line_num' => (int) ($row['line_num'] ?? 0),
                    ':oign_doc_num' => (int) ($row['oign_doc_num'] ?? 0),
                    ':doc_date' => (string) ($row['doc_date'] ?? ''),
                    ':belnr_id' => (int) ($row['belnr_id'] ?? 0),
                    ':item_code' => (string) ($row['item_code'] ?? ''),
                    ':item_name' => (string) ($row['item_name'] ?? ''),
                    ':warehouse' => (string) ($row['warehouse'] ?? ''),
                    ':qty' => (float) ($row['qty'] ?? 0),
                    ':synced_at' => $now,
                    ':created_at' => $now,
                    ':updated_at' => $now,
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
     * @param list<array<string, mixed>> $rows
     */
    public function upsertScrapDays(array $rows): int
    {
        if ($rows === [] || !$this->scrapTableExists()) {
            return 0;
        }
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO adms_prod_scrap_day (
                    doc_date, warehouse, qty_good, qty_scrap, synced_at, created_at, updated_at
                ) VALUES (
                    :doc_date, :warehouse, :qty_good, :qty_scrap, :synced_at, :created_at, :updated_at
                )
                ON DUPLICATE KEY UPDATE
                    qty_good = VALUES(qty_good),
                    qty_scrap = VALUES(qty_scrap),
                    synced_at = VALUES(synced_at),
                    updated_at = VALUES(updated_at)';
        $pdo = $this->getConnection();
        $stmt = $pdo->prepare($sql);
        $count = 0;
        $pdo->beginTransaction();
        try {
            foreach ($rows as $row) {
                $stmt->execute([
                    ':doc_date' => (string) ($row['doc_date'] ?? ''),
                    ':warehouse' => (string) ($row['warehouse'] ?? ''),
                    ':qty_good' => (float) ($row['qty_good'] ?? 0),
                    ':qty_scrap' => (float) ($row['qty_scrap'] ?? 0),
                    ':synced_at' => $now,
                    ':created_at' => $now,
                    ':updated_at' => $now,
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

    public function applyCompletedFromReceipts(): void
    {
        if (!$this->tableExists() || !$this->receiptTableExists()) {
            return;
        }
        $this->getConnection()->exec(
            "UPDATE adms_prod_wo_fact w
             INNER JOIN (
                SELECT belnr_id, item_code, SUM(qty) AS qty
                FROM adms_prod_receipt_fact
                GROUP BY belnr_id, item_code
             ) r ON r.belnr_id = w.doc_entry AND r.item_code = w.item_code AND w.source = 'beas'
             SET w.qty_completed = r.qty, w.updated_at = NOW()"
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSyncState(): ?array
    {
        try {
            $stmt = $this->getConnection()->query(
                'SELECT * FROM adms_prod_sap_sync_state WHERE id = 1 LIMIT 1'
            );
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function saveSyncState(array $data): void
    {
        $sql = 'INSERT INTO adms_prod_sap_sync_state (
                    id, last_mode, last_status, last_source, last_from_date, last_to_date,
                    last_success_at, rows_upserted, beas_tables, message, updated_at
                ) VALUES (
                    1, :last_mode, :last_status, :last_source, :last_from_date, :last_to_date,
                    :last_success_at, :rows_upserted, :beas_tables, :message, :updated_at
                )
                ON DUPLICATE KEY UPDATE
                    last_mode = VALUES(last_mode),
                    last_status = VALUES(last_status),
                    last_source = VALUES(last_source),
                    last_from_date = VALUES(last_from_date),
                    last_to_date = VALUES(last_to_date),
                    last_success_at = VALUES(last_success_at),
                    rows_upserted = VALUES(rows_upserted),
                    beas_tables = VALUES(beas_tables),
                    message = VALUES(message),
                    updated_at = VALUES(updated_at)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            ':last_mode' => $data['last_mode'] ?? null,
            ':last_status' => $data['last_status'] ?? null,
            ':last_source' => $data['last_source'] ?? null,
            ':last_from_date' => $data['last_from_date'] ?? null,
            ':last_to_date' => $data['last_to_date'] ?? null,
            ':last_success_at' => $data['last_success_at'] ?? null,
            ':rows_upserted' => (int) ($data['rows_upserted'] ?? 0),
            ':beas_tables' => $data['beas_tables'] ?? null,
            ':message' => $data['message'] ?? null,
            ':updated_at' => $data['updated_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createSyncRun(array $data): int
    {
        $sql = 'INSERT INTO adms_prod_sap_sync_runs (
                    sync_mode, source, date_from, date_to, rows_fetched, rows_upserted,
                    status, error_log, started_at, finished_at
                ) VALUES (
                    :sync_mode, :source, :date_from, :date_to, :rows_fetched, :rows_upserted,
                    :status, :error_log, :started_at, :finished_at
                )';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            ':sync_mode' => (string) ($data['sync_mode'] ?? 'incremental'),
            ':source' => $data['source'] ?? null,
            ':date_from' => $data['date_from'] ?? null,
            ':date_to' => $data['date_to'] ?? null,
            ':rows_fetched' => (int) ($data['rows_fetched'] ?? 0),
            ':rows_upserted' => (int) ($data['rows_upserted'] ?? 0),
            ':status' => (string) ($data['status'] ?? 'running'),
            ':error_log' => $data['error_log'] ?? null,
            ':started_at' => (string) ($data['started_at'] ?? date('Y-m-d H:i:s')),
            ':finished_at' => $data['finished_at'] ?? null,
        ]);
        return (int) $this->getConnection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function finishSyncRun(int $id, array $data): void
    {
        if ($id <= 0) {
            return;
        }
        $sql = 'UPDATE adms_prod_sap_sync_runs SET
                    source = :source,
                    date_from = :date_from,
                    date_to = :date_to,
                    rows_fetched = :rows_fetched,
                    rows_upserted = :rows_upserted,
                    status = :status,
                    error_log = :error_log,
                    finished_at = :finished_at
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':source' => $data['source'] ?? null,
            ':date_from' => $data['date_from'] ?? null,
            ':date_to' => $data['date_to'] ?? null,
            ':rows_fetched' => (int) ($data['rows_fetched'] ?? 0),
            ':rows_upserted' => (int) ($data['rows_upserted'] ?? 0),
            ':status' => (string) ($data['status'] ?? 'completed'),
            ':error_log' => $data['error_log'] ?? null,
            ':finished_at' => $data['finished_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return list<string>
     */
    public function listLinhas(): array
    {
        if (!$this->tableExists()) {
            return [];
        }
        $out = [];
        if ($this->receiptTableExists()) {
            $stmt = $this->getConnection()->query(
                "SELECT nome FROM (
                    SELECT DISTINCT warehouse AS nome FROM adms_prod_receipt_fact
                    WHERE warehouse IS NOT NULL AND warehouse <> ''
                    UNION
                    SELECT DISTINCT line_name AS nome FROM adms_prod_wo_fact
                    WHERE line_name IS NOT NULL AND line_name <> ''
                 ) t
                 ORDER BY nome"
            );
        } else {
            $stmt = $this->getConnection()->query(
                "SELECT DISTINCT line_name AS nome FROM adms_prod_wo_fact
                 WHERE line_name IS NOT NULL AND line_name <> ''
                 ORDER BY nome"
            );
        }
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[] = (string) $row['nome'];
        }
        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchPeriodKpis(string $from, string $to, ?string $linha): array
    {
        $volume = 0.0;
        $produtos = 0;
        if ($this->receiptTableExists()) {
            [$rcWhere, $rcParams] = $this->receiptWhere($from, $to, $linha);
            $stmt = $this->getConnection()->prepare(
                "SELECT COALESCE(SUM(qty), 0) AS volume, COUNT(DISTINCT item_code) AS produtos
                 FROM adms_prod_receipt_fact
                 WHERE {$rcWhere}"
            );
            $stmt->execute($rcParams);
            $rc = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $volume = (float) ($rc['volume'] ?? 0);
            $produtos = (int) ($rc['produtos'] ?? 0);
        }

        $orderCol = $this->woHasOrderDate() ? 'order_date' : 'start_date';
        $sql = "SELECT
                    COALESCE(SUM(CASE WHEN {$orderCol} BETWEEN :fromp AND :top THEN qty_planned ELSE 0 END), 0) AS planejado,
                    SUM(CASE WHEN start_date BETWEEN :froms AND :tos THEN 1 ELSE 0 END) AS iniciadas,
                    SUM(CASE WHEN close_date BETWEEN :fromc AND :toc AND status_code = 'L' THEN 1 ELSE 0 END) AS concluidas,
                    AVG(CASE
                        WHEN close_date BETWEEN :froml AND :tol AND start_date IS NOT NULL AND close_date IS NOT NULL
                        THEN DATEDIFF(close_date, start_date) END) AS lead_dias
                FROM adms_prod_wo_fact";
        $woParams = [
            ':fromp' => $from,
            ':top' => $to,
            ':froms' => $from,
            ':tos' => $to,
            ':fromc' => $from,
            ':toc' => $to,
            ':froml' => $from,
            ':tol' => $to,
        ];
        if ($linha !== null && $linha !== '') {
            $sql .= ' WHERE line_name = :linha';
            $woParams[':linha'] = $linha;
        }
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($woParams);
        $wo = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $qtyGood = 0.0;
        $qtyScrap = 0.0;
        if ($this->scrapTableExists()) {
            [$scWhere, $scParams] = $this->scrapWhere($from, $to, $linha);
            $stmt = $this->getConnection()->prepare(
                "SELECT COALESCE(SUM(qty_good), 0) AS qty_good, COALESCE(SUM(qty_scrap), 0) AS qty_scrap
                 FROM adms_prod_scrap_day
                 WHERE {$scWhere}"
            );
            $stmt->execute($scParams);
            $sc = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $qtyGood = (float) ($sc['qty_good'] ?? 0);
            $qtyScrap = (float) ($sc['qty_scrap'] ?? 0);
        }

        return [
            'skus' => $volume,
            'produtos' => $produtos,
            'volume' => $volume,
            'planejado' => (float) ($wo['planejado'] ?? 0),
            'refugo' => $qtyScrap,
            'refugo_bom' => $qtyGood,
            'iniciadas' => (int) ($wo['iniciadas'] ?? 0),
            'concluidas' => (int) ($wo['concluidas'] ?? 0),
            'lead_dias' => $wo['lead_dias'] !== null ? (float) $wo['lead_dias'] : null,
        ];
    }

    /**
     * @return array{andamento: int, atraso: int}
     */
    public function fetchOpenSnapshot(?string $linha, string $hoje): array
    {
        $sql = "SELECT
                    SUM(CASE WHEN status_code IN ('P','R') THEN 1 ELSE 0 END) AS andamento,
                    SUM(CASE WHEN status_code IN ('P','R') AND due_date IS NOT NULL AND due_date < :hoje THEN 1 ELSE 0 END) AS atraso
                FROM adms_prod_wo_fact
                WHERE status_code IN ('P','R')";
        $params = [':hoje' => $hoje];
        if ($linha !== null && $linha !== '') {
            $sql .= ' AND line_name = :linha';
            $params[':linha'] = $linha;
        }
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'andamento' => (int) ($row['andamento'] ?? 0),
            'atraso' => (int) ($row['atraso'] ?? 0),
        ];
    }

    /**
     * @return list<array{ano_mes: string, skus: int, produtos: int, iniciadas: int, concluidas: int, volume: float, planejado: float}>
     */
    public function fetchMonthly(string $from, string $to, ?string $linha): array
    {
        $byKey = [];
        if ($this->receiptTableExists()) {
            [$rcWhere, $rcParams] = $this->receiptWhere($from, $to, $linha);
            $sql = "SELECT DATE_FORMAT(doc_date, '%Y-%m') AS ano_mes,
                           COALESCE(SUM(qty), 0) AS volume,
                           COUNT(DISTINCT item_code) AS produtos
                    FROM adms_prod_receipt_fact
                    WHERE {$rcWhere}
                    GROUP BY ano_mes";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($rcParams);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $ym = (string) $row['ano_mes'];
                $vol = (float) $row['volume'];
                $byKey[$ym] = [
                    'ano_mes' => $ym,
                    'skus' => $vol,
                    'produtos' => (int) $row['produtos'],
                    'iniciadas' => 0,
                    'concluidas' => 0,
                    'volume' => $vol,
                    'planejado' => 0.0,
                ];
            }
        }

        $orderCol = $this->woHasOrderDate() ? 'order_date' : 'start_date';
        [$lineSql, $lineParams] = $this->lineOnlyWhere($linha, false);
        $sqlPlan = "SELECT DATE_FORMAT({$orderCol}, '%Y-%m') AS ano_mes, COALESCE(SUM(qty_planned), 0) AS planejado
                    FROM adms_prod_wo_fact
                    WHERE {$orderCol} BETWEEN :from AND :to {$lineSql}
                    GROUP BY ano_mes";
        $stmt = $this->getConnection()->prepare($sqlPlan);
        $stmt->execute($lineParams + [':from' => $from, ':to' => $to]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ym = (string) $row['ano_mes'];
            if (!isset($byKey[$ym])) {
                $byKey[$ym] = [
                    'ano_mes' => $ym,
                    'skus' => 0,
                    'produtos' => 0,
                    'iniciadas' => 0,
                    'concluidas' => 0,
                    'volume' => 0.0,
                    'planejado' => 0.0,
                ];
            }
            $byKey[$ym]['planejado'] = (float) $row['planejado'];
        }
        ksort($byKey);
        return array_values($byKey);
    }

    /**
     * Séries mensais de iniciadas/concluídas pela data própria (não pelo coalesce).
     *
     * @return array{iniciadas: array<string, int>, concluidas: array<string, int>}
     */
    public function fetchMonthlyOrders(string $from, string $to, ?string $linha): array
    {
        [$lineSql, $lineParams] = $this->lineOnlyWhere($linha, false);
        $pdo = $this->getConnection();

        $sqlIni = "SELECT DATE_FORMAT(start_date, '%Y-%m') AS ano_mes, COUNT(*) AS qtd
                   FROM adms_prod_wo_fact
                   WHERE start_date BETWEEN :from AND :to {$lineSql}
                   GROUP BY ano_mes";
        $stmt = $pdo->prepare($sqlIni);
        $stmt->execute($lineParams + [':from' => $from, ':to' => $to]);
        $iniciadas = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $iniciadas[(string) $row['ano_mes']] = (int) $row['qtd'];
        }

        $sqlFim = "SELECT DATE_FORMAT(close_date, '%Y-%m') AS ano_mes, COUNT(*) AS qtd
                   FROM adms_prod_wo_fact
                   WHERE close_date BETWEEN :from AND :to AND status_code = 'L' {$lineSql}
                   GROUP BY ano_mes";
        $stmt = $pdo->prepare($sqlFim);
        $stmt->execute($lineParams + [':from' => $from, ':to' => $to]);
        $concluidas = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $concluidas[(string) $row['ano_mes']] = (int) $row['qtd'];
        }

        return ['iniciadas' => $iniciadas, 'concluidas' => $concluidas];
    }

    /**
     * @return list<array{linha: string, planejado: float, concluido: float, andamento: int, atraso: int}>
     */
    public function fetchByLine(string $from, string $to, ?string $linha): array
    {
        $concluido = [];
        if ($this->receiptTableExists()) {
            [$rcWhere, $rcParams] = $this->receiptWhere($from, $to, $linha);
            $stmt = $this->getConnection()->prepare(
                "SELECT CASE WHEN warehouse = '' THEN 'Sem linha' ELSE warehouse END AS linha,
                        COALESCE(SUM(qty), 0) AS concluido
                 FROM adms_prod_receipt_fact
                 WHERE {$rcWhere}
                 GROUP BY linha"
            );
            $stmt->execute($rcParams);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $concluido[(string) $row['linha']] = (float) $row['concluido'];
            }
        }

        $orderCol = $this->woHasOrderDate() ? 'order_date' : 'start_date';
        $sql = "SELECT CASE WHEN line_name = '' THEN 'Sem linha' ELSE line_name END AS linha,
                       COALESCE(SUM(qty_planned), 0) AS planejado
                FROM adms_prod_wo_fact
                WHERE {$orderCol} BETWEEN :from AND :to";
        $params = [':from' => $from, ':to' => $to];
        if ($linha !== null && $linha !== '') {
            $sql .= ' AND line_name = :linha';
            $params[':linha'] = $linha;
        }
        $sql .= ' GROUP BY linha';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $planejado = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $planejado[(string) $row['linha']] = (float) $row['planejado'];
        }

        $keys = array_unique(array_merge(array_keys($concluido), array_keys($planejado)));
        $out = [];
        foreach ($keys as $nome) {
            $out[] = [
                'linha' => $nome,
                'planejado' => $planejado[$nome] ?? 0.0,
                'concluido' => $concluido[$nome] ?? 0.0,
            ];
        }
        usort($out, static fn ($a, $b) => $b['concluido'] <=> $a['concluido']);
        return array_slice($out, 0, 12);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchOpenOrders(?string $linha, string $hoje, int $limit = 20): array
    {
        $sql = "SELECT doc_num, item_code, item_name, line_name, warehouse,
                       start_date, due_date, status_code, status_label,
                       qty_planned, qty_completed
                FROM adms_prod_wo_fact
                WHERE status_code IN ('P','R')";
        $params = [];
        if ($linha !== null && $linha !== '') {
            $sql .= ' AND line_name = :linha';
            $params[':linha'] = $linha;
        }
        $sql .= ' ORDER BY CASE WHEN due_date IS NOT NULL AND due_date < :hoje THEN 0 ELSE 1 END, due_date ASC, doc_num DESC LIMIT '
            . (int) $limit;
        $params[':hoje'] = $hoje;
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $due = $row['due_date'] ?? null;
            $late = $due !== null && $due < $hoje;
            $planned = (float) $row['qty_planned'];
            $done = (float) $row['qty_completed'];
            $out[] = [
                'op' => (int) $row['doc_num'],
                'sku' => (string) $row['item_code'],
                'item' => (string) $row['item_name'],
                'linha' => (string) ($row['line_name'] ?: $row['warehouse'] ?: '—'),
                'inicio' => $row['start_date'],
                'prazo' => $due,
                'status_code' => (string) $row['status_code'],
                'status' => $late ? 'late' : 'running',
                'status_label' => $late ? 'Atrasada' : (string) ($row['status_label'] ?: 'Em produção'),
                'progresso' => $planned > 0 ? round(min(100, ($done / $planned) * 100), 1) : 0,
            ];
        }
        return $out;
    }

    /**
     * @return list<array{sku: string, item: string, volume: float}>
     */
    public function fetchTopSkus(string $from, string $to, ?string $linha, int $limit = 20): array
    {
        if (!$this->receiptTableExists()) {
            return [];
        }
        [$where, $params] = $this->receiptWhere($from, $to, $linha);
        $sql = "SELECT item_code, MAX(item_name) AS item_name, SUM(qty) AS volume
                FROM adms_prod_receipt_fact
                WHERE {$where} AND qty > 0
                GROUP BY item_code
                ORDER BY volume DESC
                LIMIT " . (int) $limit;
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[] = [
                'sku' => (string) $row['item_code'],
                'item' => (string) $row['item_name'],
                'volume' => (float) $row['volume'],
            ];
        }
        return $out;
    }

    public function hasShopFloorFacts(): bool
    {
        if ($this->scrapTableExists()) {
            $stmt = $this->getConnection()->query('SELECT COUNT(*) FROM adms_prod_scrap_day');
            if ((int) $stmt->fetchColumn() > 0) {
                return true;
            }
        }
        if (!$this->tableExists()) {
            return false;
        }
        $stmt = $this->getConnection()->query(
            'SELECT COUNT(*) FROM adms_prod_wo_fact WHERE cycle_hours IS NOT NULL OR downtime_hours IS NOT NULL'
        );
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function receiptWhere(string $from, string $to, ?string $linha): array
    {
        $parts = ['doc_date BETWEEN :pfrom AND :pto'];
        $params = [':pfrom' => $from, ':pto' => $to];
        if ($linha !== null && $linha !== '') {
            $parts[] = 'warehouse = :linha';
            $params[':linha'] = $linha;
        }
        return [implode(' AND ', $parts), $params];
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function scrapWhere(string $from, string $to, ?string $linha): array
    {
        $parts = ['doc_date BETWEEN :pfrom AND :pto'];
        $params = [':pfrom' => $from, ':pto' => $to];
        if ($linha !== null && $linha !== '') {
            $parts[] = 'warehouse = :linha';
            $params[':linha'] = $linha;
        }
        return [implode(' AND ', $parts), $params];
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function periodWhere(string $from, string $to, ?string $linha): array
    {
        $parts = ['(start_date BETWEEN :pfrom AND :pto OR close_date BETWEEN :pfrom2 AND :pto2)'];
        $params = [':pfrom' => $from, ':pto' => $to, ':pfrom2' => $from, ':pto2' => $to];
        if ($linha !== null && $linha !== '') {
            $parts[] = 'line_name = :linha';
            $params[':linha'] = $linha;
        }
        return [implode(' AND ', $parts), $params];
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function lineOnlyWhere(?string $linha, bool $asFullWhere = true): array
    {
        if ($linha === null || $linha === '') {
            return [$asFullWhere ? '1=1' : '', []];
        }
        $sql = $asFullWhere ? 'line_name = :linha' : ' AND line_name = :linha';
        return [$sql, [':linha' => $linha]];
    }

    private function woHasOrderDate(): bool
    {
        static $has = null;
        if ($has !== null) {
            return $has;
        }
        try {
            $stmt = $this->getConnection()->query("SHOW COLUMNS FROM adms_prod_wo_fact LIKE 'order_date'");
            $has = (bool) $stmt->fetchColumn();
        } catch (\Throwable) {
            $has = false;
        }
        return $has;
    }

    private function mysqlTableExists(string $table): bool
    {
        static $cache = [];
        if (array_key_exists($table, $cache)) {
            return $cache[$table];
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return false;
        }
        try {
            $stmt = $this->getConnection()->query("SHOW TABLES LIKE '{$table}'");
            $cache[$table] = (bool) $stmt->fetchColumn();
        } catch (\Throwable) {
            $cache[$table] = false;
        }
        return $cache[$table];
    }
}
