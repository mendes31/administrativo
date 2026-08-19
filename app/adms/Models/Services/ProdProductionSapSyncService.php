<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\production\ProdProductionCacheRepository;
use DateInterval;
use DateTimeImmutable;
use Exception;
use Throwable;

/**
 * Sincroniza produção SAP/BEAS (HANA) → cache MySQL.
 *
 * Fonte homologada (PCP):
 * - produzido = entradas de estoque OIGN/IGN1 com U_beas_belnrid, ItemCode 4%,
 *   depósitos TJQP/APQP e posição principal BEAS_FTPOS.STUFE = 0;
 * - início real = MIN(BEAS_ARBZEIT.ANFZEIT);
 * - refugo = BEAS_ARBZEIT MENGE_GUT_RM / MENGE_SCHLECHT_RM;
 * - planejado = BEAS_FTPOS.MENGE (STUFE = 0).
 *
 * OWOR permanece só se as tabelas BEAS não existirem.
 */
class ProdProductionSapSyncService
{
    public const LOOKBACK_MONTHS = 24;
    public const INCREMENTAL_OVERLAP_DAYS = 7;
    public const ITEM_PREFIX = '4';

    /** @var list<string> */
    public const WAREHOUSES = ['TJQP', 'APQP'];

    /** @var list<string> */
    public const BEAS_CANDIDATE_TABLES = [
        'BEAS_FTHAUPT',
        'BEAS_FTPOS',
        'BEAS_FTAPL',
        'BEAS_FTSTL',
        'BEAS_ARBZEIT',
        'BEAS_FTZEIT',
    ];

    private SapReportApiService $sap;
    private ProdProductionCacheRepository $repo;

    public function __construct(?SapReportApiService $sap = null, ?ProdProductionCacheRepository $repo = null)
    {
        $this->sap = $sap ?? new SapReportApiService();
        $this->repo = $repo ?? new ProdProductionCacheRepository();
    }

    public function needsDailyRefresh(): bool
    {
        if (!$this->repo->tableExists()) {
            return false;
        }
        if ($this->repo->countReceipts() === 0 && $this->repo->countRows() === 0) {
            return true;
        }
        $state = $this->repo->getSyncState();
        $last = $state['last_success_at'] ?? null;
        if ($last === null || $last === '') {
            return true;
        }
        $lastDay = substr((string) $last, 0, 10);
        $hoje = (new DateTimeImmutable('today'))->format('Y-m-d');
        return $lastDay < $hoje;
    }

    /**
     * @return array{ran: bool, skipped: bool, reason?: string, result?: array<string, mixed>, error?: string}
     */
    public function syncIfStaleDaily(): array
    {
        if (!$this->needsDailyRefresh()) {
            return ['ran' => false, 'skipped' => true, 'reason' => 'already_synced_today'];
        }

        $lockPath = dirname(__DIR__, 4) . '/storage/cache/prod_production_sync.lock';
        $lockDir = dirname($lockPath);
        if (!is_dir($lockDir)) {
            @mkdir($lockDir, 0775, true);
        }

        $fh = @fopen($lockPath, 'c+');
        if ($fh === false) {
            return ['ran' => false, 'skipped' => true, 'reason' => 'lock_unavailable'];
        }

        if (!flock($fh, LOCK_EX | LOCK_NB)) {
            fclose($fh);
            return ['ran' => false, 'skipped' => true, 'reason' => 'sync_in_progress'];
        }

        try {
            if (!$this->needsDailyRefresh()) {
                return ['ran' => false, 'skipped' => true, 'reason' => 'already_synced_today'];
            }
            $result = $this->sync('incremental');
            return ['ran' => true, 'skipped' => false, 'result' => $result];
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function sync(string $mode = 'incremental'): array
    {
        @set_time_limit(0);
        ini_set('memory_limit', '512M');

        $mode = strtolower(trim($mode));
        if (!in_array($mode, ['full', 'incremental', 'today'], true)) {
            $mode = 'incremental';
        }

        if (!$this->repo->tableExists()) {
            throw new Exception(
                'Tabelas de cache de produção ausentes. Execute: php vendor/bin/phinx migrate -c database/phinx.php'
            );
        }

        $range = $this->resolveSyncRange($mode);
        $from = $range['from'];
        $to = $range['to'];
        $toExclusive = $to->add(new DateInterval('P1D'));
        $beasFound = $this->probeBeasTables();
        $beasList = implode(', ', $beasFound);
        $useBeas = in_array('BEAS_FTHAUPT', $beasFound, true)
            && in_array('BEAS_FTPOS', $beasFound, true);
        $source = $useBeas ? 'beas' : 'owor';

        $runId = $this->repo->createSyncRun([
            'sync_mode' => $mode,
            'source' => $source,
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
            'status' => 'running',
            'started_at' => date('Y-m-d H:i:s'),
        ]);

        $stats = [
            'success' => false,
            'message' => '',
            'sync_mode' => $mode,
            'source' => $source,
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
            'rows_fetched' => 0,
            'rows_upserted' => 0,
            'beas_tables' => $beasList !== '' ? $beasList : null,
        ];

        try {
            if ($useBeas) {
                $receipts = $this->fetchBeasReceipts($from, $toExclusive);
                $positions = $this->fetchBeasPositions($from, $toExclusive);
                $starts = $this->fetchBeasFirstStarts($from, $toExclusive);
                $scrap = in_array('BEAS_ARBZEIT', $beasFound, true)
                    ? $this->fetchBeasScrapDays($from, $toExclusive)
                    : [];

                $startByBelnr = [];
                foreach ($starts as $row) {
                    $id = (int) $this->col($row, ['BELNR_ID', 'DocEntry']);
                    if ($id > 0) {
                        $startByBelnr[$id] = $this->dateOrNull($this->col($row, ['InicioReal', 'ANFZEIT']));
                    }
                }

                $mappedWo = [];
                foreach ($positions as $row) {
                    $item = $this->mapBeasPosition($row, $startByBelnr);
                    if ($item !== null) {
                        $mappedWo[] = $item;
                    }
                }

                $mappedReceipts = [];
                foreach ($receipts as $row) {
                    $item = $this->mapReceiptRow($row);
                    if ($item !== null) {
                        $mappedReceipts[] = $item;
                    }
                }

                $mappedScrap = [];
                foreach ($scrap as $row) {
                    $item = $this->mapScrapDay($row);
                    if ($item !== null) {
                        $mappedScrap[] = $item;
                    }
                }

                $upWo = $this->repo->upsertBatch($mappedWo);
                $upRc = $this->repo->upsertReceipts($mappedReceipts);
                $upSc = $this->repo->upsertScrapDays($mappedScrap);
                $this->repo->applyCompletedFromReceipts();

                $stats['rows_fetched'] = count($receipts) + count($positions);
                $stats['rows_upserted'] = $upWo + $upRc + $upSc;
                $stats['source'] = 'beas';
                $source = 'beas';
            } else {
                $raw = $this->fetchOworOrders($from, $to);
                $mapped = [];
                foreach ($raw as $row) {
                    $item = $this->mapOworRow($row);
                    if ($item !== null) {
                        $mapped[] = $item;
                    }
                }
                $stats['rows_fetched'] = count($raw);
                $stats['rows_upserted'] = $this->repo->upsertBatch($mapped);
                $stats['source'] = 'owor';
                $source = 'owor';
            }

            $now = date('Y-m-d H:i:s');
            $extra = $beasList !== ''
                ? ' Tabelas BEAS detectadas: ' . $beasList . '.'
                : ' Nenhuma tabela BEAS_* detectada no probe; usando OWOR.';
            $stats['success'] = true;
            $stats['message'] = sprintf(
                'Sync %s (%s): %d linhas gravadas no cache.%s',
                $mode,
                $source,
                $stats['rows_upserted'],
                $extra
            );

            $this->repo->finishSyncRun($runId, [
                'source' => $source,
                'date_from' => $stats['date_from'],
                'date_to' => $stats['date_to'],
                'rows_fetched' => $stats['rows_fetched'],
                'rows_upserted' => $stats['rows_upserted'],
                'status' => 'completed',
                'finished_at' => $now,
            ]);
            $this->repo->saveSyncState([
                'last_mode' => $mode,
                'last_status' => 'success',
                'last_source' => $source,
                'last_from_date' => $stats['date_from'],
                'last_to_date' => $stats['date_to'],
                'last_success_at' => $now,
                'rows_upserted' => $stats['rows_upserted'],
                'beas_tables' => $stats['beas_tables'],
                'message' => $stats['message'],
                'updated_at' => $now,
            ]);
        } catch (Throwable $e) {
            $stats['message'] = $e->getMessage();
            $now = date('Y-m-d H:i:s');
            $this->repo->finishSyncRun($runId, [
                'source' => $source,
                'date_from' => $stats['date_from'],
                'date_to' => $stats['date_to'],
                'rows_fetched' => $stats['rows_fetched'],
                'rows_upserted' => $stats['rows_upserted'],
                'status' => 'failed',
                'error_log' => $e->getMessage(),
                'finished_at' => $now,
            ]);
            $prev = $this->repo->getSyncState();
            $this->repo->saveSyncState([
                'last_mode' => $mode,
                'last_status' => 'failed',
                'last_source' => $source,
                'last_from_date' => $stats['date_from'],
                'last_to_date' => $stats['date_to'],
                'last_success_at' => $prev['last_success_at'] ?? null,
                'rows_upserted' => $stats['rows_upserted'],
                'beas_tables' => $stats['beas_tables'],
                'message' => $e->getMessage(),
                'updated_at' => $now,
            ]);
            throw $e;
        }

        return $stats;
    }

    /**
     * @return list<string>
     */
    public function probeBeasTables(): array
    {
        $found = [];
        foreach (self::BEAS_CANDIDATE_TABLES as $table) {
            try {
                $sql = 'SELECT TOP 1 1 AS ok FROM "' . $table . '"';
                $this->querySql($sql);
                $found[] = $table;
            } catch (Throwable) {
                // tabela ausente ou sem permissão
            }
        }
        return $found;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchOworOrders(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $fromS = $from->format('Y-m-d');
        $toS = $to->format('Y-m-d');
        $sql = <<<SQL
SELECT
    T0."DocEntry",
    T0."DocNum",
    T0."ItemCode",
    IFNULL(T1."ItemName", '') AS "ItemName",
    IFNULL(T2."ItmsGrpNam", '') AS "ProductName",
    IFNULL(T0."Warehouse", '') AS "Warehouse",
    T0."Status",
    IFNULL(T0."PlannedQty", 0) AS "PlannedQty",
    IFNULL(T0."CmpltQty", 0) AS "CmpltQty",
    IFNULL(T0."RjctQty", 0) AS "RjctQty",
    T0."StartDate",
    T0."DueDate",
    T0."CloseDate"
FROM OWOR T0
LEFT JOIN OITM T1 ON T0."ItemCode" = T1."ItemCode"
LEFT JOIN OITB T2 ON T1."ItmsGrpCod" = T2."ItmsGrpCod"
WHERE T0."Status" <> 'C'
  AND (
        T0."StartDate" BETWEEN '{$fromS}' AND '{$toS}'
        OR T0."CloseDate" BETWEEN '{$fromS}' AND '{$toS}'
        OR T0."Status" IN ('P', 'R')
      )
SQL;
        return $this->querySql($sql);
    }

    /**
     * Query 8 do PCP — grain do cache de produzido (HANA).
     *
     * @return list<array<string, mixed>>
     */
    private function fetchBeasReceipts(DateTimeImmutable $from, DateTimeImmutable $toExclusive): array
    {
        $fromS = $from->format('Y-m-d');
        $toEx = $toExclusive->format('Y-m-d');
        $whs = $this->warehouseSqlList();
        $prefix = self::ITEM_PREFIX;
        $sql = <<<SQL
SELECT
    H."DocEntry" AS "OignEntry",
    L."LineNum",
    H."DocNum" AS "OignDocNum",
    H."DocDate",
    L."U_beas_belnrid" AS "BELNR_ID",
    L."ItemCode",
    IFNULL(L."Dscription", '') AS "ItemName",
    L."Quantity",
    L."WhsCode"
FROM OIGN H
INNER JOIN IGN1 L ON L."DocEntry" = H."DocEntry"
WHERE H."CANCELED" = 'N'
  AND H."DocDate" >= '{$fromS}' AND H."DocDate" < '{$toEx}'
  AND L."U_beas_belnrid" IS NOT NULL
  AND L."ItemCode" LIKE '{$prefix}%'
  AND L."WhsCode" IN ({$whs})
  AND EXISTS (
      SELECT 1 FROM BEAS_FTPOS P
      WHERE P."BELNR_ID" = L."U_beas_belnrid"
        AND P."ItemCode" = L."ItemCode"
        AND P."STUFE" = 0
  )
SQL;
        return $this->querySql($sql);
    }

    /**
     * Posições principais (STUFE=0) para plano, status e snapshot de abertas.
     *
     * @return list<array<string, mixed>>
     */
    private function fetchBeasPositions(DateTimeImmutable $from, DateTimeImmutable $toExclusive): array
    {
        $fromS = $from->format('Y-m-d');
        $toEx = $toExclusive->format('Y-m-d');
        $whs = $this->warehouseSqlList();
        $prefix = self::ITEM_PREFIX;
        $sql = <<<SQL
SELECT
    P."BELNR_ID" AS "DocEntry",
    IFNULL(H."AUFTRAG", P."BELNR_ID") AS "DocNum",
    P."BELPOS_ID",
    P."ItemCode",
    IFNULL(P."ItemName", '') AS "ItemName",
    IFNULL(P."WhsCode", '') AS "Warehouse",
    IFNULL(P."MENGE", 0) AS "PlannedQty",
    IFNULL(H."ABGKZ", 'N') AS "ClosedFlag",
    IFNULL(P."ABGKZ", 'N') AS "PosClosed",
    IFNULL(H."PLANNINGORDER", 0) AS "PlanningOrder",
    H."BELDAT" AS "OrderDate",
    P."LIEFERDATUM" AS "DueDate",
    IFNULL(P."ABGKZ_DATE", '') AS "CloseDate"
FROM BEAS_FTPOS P
INNER JOIN BEAS_FTHAUPT H ON H."BELNR_ID" = P."BELNR_ID"
WHERE P."STUFE" = 0
  AND P."ItemCode" LIKE '{$prefix}%'
  AND P."WhsCode" IN ({$whs})
  AND (
        H."BELDAT" >= '{$fromS}' AND H."BELDAT" < '{$toEx}'
        OR (P."ABGKZ_DATE" IS NOT NULL AND P."ABGKZ_DATE" <> '' AND P."ABGKZ_DATE" >= '{$fromS}' AND P."ABGKZ_DATE" < '{$toEx}')
        OR IFNULL(H."ABGKZ", 'N') <> 'J'
      )
SQL;
        return $this->querySql($sql);
    }

    /**
     * Query 5 do PCP — primeiro apontamento da OP no período.
     *
     * @return list<array<string, mixed>>
     */
    private function fetchBeasFirstStarts(DateTimeImmutable $from, DateTimeImmutable $toExclusive): array
    {
        $fromS = $from->format('Y-m-d');
        $toEx = $toExclusive->format('Y-m-d');
        $whs = $this->warehouseSqlList();
        $prefix = self::ITEM_PREFIX;
        $sql = <<<SQL
SELECT I."BELNR_ID", I."InicioReal"
FROM (
    SELECT A."BELNR_ID", MIN(A."ANFZEIT") AS "InicioReal"
    FROM BEAS_ARBZEIT A
    WHERE A."ANFZEIT" < '{$toEx}'
    GROUP BY A."BELNR_ID"
    HAVING MIN(A."ANFZEIT") >= '{$fromS}'
) I
WHERE EXISTS (
    SELECT 1 FROM BEAS_FTPOS P
    WHERE P."BELNR_ID" = I."BELNR_ID" AND P."STUFE" = 0
      AND P."ItemCode" LIKE '{$prefix}%'
      AND P."WhsCode" IN ({$whs})
)
SQL;
        try {
            return $this->querySql($sql);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Query 6 do PCP — sucata apontada no período, agregada por dia/depósito.
     *
     * @return list<array<string, mixed>>
     */
    private function fetchBeasScrapDays(DateTimeImmutable $from, DateTimeImmutable $toExclusive): array
    {
        $fromS = $from->format('Y-m-d');
        $toEx = $toExclusive->format('Y-m-d');
        $whs = $this->warehouseSqlList();
        $prefix = self::ITEM_PREFIX;
        $sql = <<<SQL
SELECT
    TO_DATE(A."DocDate") AS "DocDate",
    P."WhsCode",
    IFNULL(SUM(A."MENGE_GUT_RM"), 0) AS "QtyGood",
    IFNULL(SUM(A."MENGE_SCHLECHT_RM"), 0) AS "QtyScrap"
FROM BEAS_ARBZEIT A
INNER JOIN BEAS_FTPOS P
  ON P."BELNR_ID" = A."BELNR_ID" AND P."BELPOS_ID" = A."BELPOS_ID"
WHERE A."DocDate" >= '{$fromS}' AND A."DocDate" < '{$toEx}'
  AND P."STUFE" = 0 AND P."ItemCode" LIKE '{$prefix}%'
  AND P."WhsCode" IN ({$whs})
GROUP BY TO_DATE(A."DocDate"), P."WhsCode"
SQL;
        try {
            return $this->querySql($sql);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>|null
     */
    private function mapReceiptRow(array $row): ?array
    {
        $entry = (int) $this->col($row, ['OignEntry', 'DocEntry']);
        $item = $this->col($row, ['ItemCode']);
        $date = $this->dateOrNull($this->col($row, ['DocDate']));
        if ($entry <= 0 || $item === '' || $date === null) {
            return null;
        }
        $whs = strtoupper($this->col($row, ['WhsCode', 'Warehouse']));
        return [
            'oign_doc_entry' => $entry,
            'line_num' => (int) $this->col($row, ['LineNum']),
            'oign_doc_num' => (int) $this->col($row, ['OignDocNum', 'DocNum']),
            'doc_date' => $date,
            'belnr_id' => (int) $this->col($row, ['BELNR_ID']),
            'item_code' => $item,
            'item_name' => $this->col($row, ['ItemName', 'Dscription']),
            'warehouse' => $whs,
            'qty' => $this->num($row, ['Quantity']),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string|null> $startByBelnr
     * @return array<string, mixed>|null
     */
    private function mapBeasPosition(array $row, array $startByBelnr): ?array
    {
        $entry = (int) $this->col($row, ['DocEntry', 'BELNR_ID']);
        $item = $this->col($row, ['ItemCode']);
        if ($entry <= 0 || $item === '') {
            return null;
        }
        $closed = strtoupper($this->col($row, ['ClosedFlag', 'PosClosed', 'ABGKZ']));
        $planning = (int) $this->num($row, ['PlanningOrder']);
        if ($closed === 'J') {
            $status = 'L';
        } elseif ($planning === 1) {
            $status = 'P';
        } else {
            $status = 'R';
        }
        $whs = strtoupper($this->col($row, ['Warehouse', 'WhsCode']));
        $start = $startByBelnr[$entry] ?? null;
        return [
            'source' => 'beas',
            'doc_entry' => $entry,
            'doc_num' => (int) $this->col($row, ['DocNum', 'AUFTRAG']),
            'item_code' => $item,
            'item_name' => $this->col($row, ['ItemName']),
            'product_name' => $item,
            'line_name' => $whs,
            'warehouse' => $whs,
            'status_code' => $status,
            'status_label' => $this->statusLabel($status),
            'qty_planned' => $this->num($row, ['PlannedQty', 'MENGE']),
            'qty_completed' => 0.0,
            'qty_scrap' => 0.0,
            'order_date' => $this->dateOrNull($this->col($row, ['OrderDate', 'BELDAT'])),
            'start_date' => $start,
            'due_date' => $this->dateOrNull($this->col($row, ['DueDate', 'LIEFERDATUM'])),
            'close_date' => $status === 'L'
                ? $this->dateOrNull($this->col($row, ['CloseDate', 'ABGKZ_DATE']))
                : null,
            'cycle_hours' => null,
            'downtime_hours' => null,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>|null
     */
    private function mapScrapDay(array $row): ?array
    {
        $date = $this->dateOrNull($this->col($row, ['DocDate']));
        if ($date === null) {
            return null;
        }
        return [
            'doc_date' => $date,
            'warehouse' => strtoupper($this->col($row, ['WhsCode', 'Warehouse'])),
            'qty_good' => $this->num($row, ['QtyGood']),
            'qty_scrap' => $this->num($row, ['QtyScrap']),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>|null
     */
    private function mapOworRow(array $row): ?array
    {
        $entry = (int) $this->col($row, ['DocEntry']);
        $item = $this->col($row, ['ItemCode']);
        if ($entry <= 0 || $item === '') {
            return null;
        }
        $status = strtoupper($this->col($row, ['Status']));
        $whs = $this->col($row, ['Warehouse']);
        return [
            'source' => 'owor',
            'doc_entry' => $entry,
            'doc_num' => (int) $this->col($row, ['DocNum']),
            'item_code' => $item,
            'item_name' => $this->col($row, ['ItemName']),
            'product_name' => $this->col($row, ['ProductName']),
            'line_name' => $whs,
            'warehouse' => $whs,
            'status_code' => $status,
            'status_label' => $this->statusLabel($status),
            'qty_planned' => $this->num($row, ['PlannedQty']),
            'qty_completed' => $this->num($row, ['CmpltQty']),
            'qty_scrap' => $this->num($row, ['RjctQty']),
            'order_date' => $this->dateOrNull($this->col($row, ['StartDate'])),
            'start_date' => $this->dateOrNull($this->col($row, ['StartDate'])),
            'due_date' => $this->dateOrNull($this->col($row, ['DueDate'])),
            'close_date' => $this->dateOrNull($this->col($row, ['CloseDate'])),
            'cycle_hours' => null,
            'downtime_hours' => null,
        ];
    }

    private function statusLabel(string $code): string
    {
        return match ($code) {
            'P' => 'Planejada',
            'R' => 'Em produção',
            'L' => 'Concluída',
            'C' => 'Cancelada',
            default => $code !== '' ? $code : '—',
        };
    }

    private function warehouseSqlList(): string
    {
        $parts = [];
        foreach (self::WAREHOUSES as $whs) {
            $parts[] = "'" . str_replace("'", "''", $whs) . "'";
        }
        return implode(', ', $parts);
    }

    /**
     * @return array{from: DateTimeImmutable, to: DateTimeImmutable}
     */
    private function resolveSyncRange(string $mode): array
    {
        $hoje = new DateTimeImmutable('today');

        if ($mode === 'today') {
            return ['from' => $hoje, 'to' => $hoje];
        }

        if ($mode === 'full') {
            $from = (new DateTimeImmutable('first day of this month'))
                ->sub(new DateInterval('P' . (self::LOOKBACK_MONTHS - 1) . 'M'));
            return ['from' => $from, 'to' => $hoje];
        }

        $state = $this->repo->getSyncState();
        $lastSuccess = $state['last_success_at'] ?? null;
        $lastTo = $state['last_to_date'] ?? null;
        $rowCount = $this->repo->countReceipts() + $this->repo->countRows();

        if ($rowCount === 0 || !$lastSuccess) {
            $from = (new DateTimeImmutable('first day of this month'))
                ->sub(new DateInterval('P' . (self::LOOKBACK_MONTHS - 1) . 'M'));
            return ['from' => $from, 'to' => $hoje];
        }

        $anchor = $lastTo
            ? DateTimeImmutable::createFromFormat('!Y-m-d', (string) $lastTo)
            : DateTimeImmutable::createFromFormat('!Y-m-d', substr((string) $lastSuccess, 0, 10));
        if (!$anchor) {
            $anchor = $hoje->sub(new DateInterval('P7D'));
        }

        $from = $anchor->sub(new DateInterval('P' . self::INCREMENTAL_OVERLAP_DAYS . 'D'));
        return ['from' => $from, 'to' => $hoje];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function querySql(string $sql): array
    {
        $result = $this->sap->execute($sql);
        $data = $result['data'] ?? [];
        return is_array($data) ? $data : [];
    }

    /**
     * @param array<string, mixed> $row
     * @param list<string> $keys
     */
    private function col(array $row, array $keys): string
    {
        $normalized = [];
        foreach ($row as $k => $v) {
            $normalized[strtolower((string) $k)] = $v;
        }
        foreach ($keys as $key) {
            $lk = strtolower($key);
            if (array_key_exists($lk, $normalized) && $normalized[$lk] !== null && $normalized[$lk] !== '') {
                return trim((string) $normalized[$lk]);
            }
        }
        return '';
    }

    /**
     * @param array<string, mixed> $row
     * @param list<string> $keys
     */
    private function num(array $row, array $keys): float
    {
        $raw = $this->col($row, $keys);
        if ($raw === '') {
            return 0.0;
        }
        return (float) str_replace(',', '.', $raw);
    }

    private function dateOrNull(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        $value = substr($value, 0, 10);
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $d ? $d->format('Y-m-d') : null;
    }
}
