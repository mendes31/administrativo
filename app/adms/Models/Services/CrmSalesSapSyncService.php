<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\crm\CrmSalesFactRepository;
use DateInterval;
use DateTimeImmutable;
use Exception;
use Throwable;

/**
 * Sincroniza vendas SAP (HANA) → cache MySQL para o Dashboard de Vendas CRM.
 *
 * Modos:
 * - full: últimos 36 meses (somente CLI — 1ª carga / reprocessamento)
 * - incremental: desde o último sucesso (overlap de 3 dias) — botão web + 1º acesso do dia
 * - today: só o dia corrente (opcional/CLI)
 *
 * Agendamento recomendado:
 * - Lazy: no 1º acesso do dia ao endpoint de dados, dispara incremental (com lock)
 * - Manual: botão "Atualizar agora" = só incremental
 * - Cron CLI (opcional, madrugada): php scripts/sync_crm_sales_sap.php
 */
class CrmSalesSapSyncService
{
    public const VIEW_NAME = 'VW_CRM_VENDAS_LINHA';
    public const LOOKBACK_MONTHS = 36;
    public const INCREMENTAL_OVERLAP_DAYS = 3;

    private SapReportApiService $sap;
    private CrmSalesFactRepository $repo;
    private ?bool $viewAvailable = null;

    public function __construct(?SapReportApiService $sap = null, ?CrmSalesFactRepository $repo = null)
    {
        $this->sap = $sap ?? new SapReportApiService();
        $this->repo = $repo ?? new CrmSalesFactRepository();
    }

    /**
     * True se ainda não houve sync com sucesso hoje (ou cache vazio).
     */
    public function needsDailyRefresh(): bool
    {
        if (!$this->repo->tableExists()) {
            return false;
        }
        if ($this->repo->countRows() === 0) {
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
     * Dispara incremental no 1º acesso do dia. Usa flock para não empilhar syncs.
     *
     * @return array{ran: bool, skipped: bool, reason?: string, result?: array<string, mixed>}
     */
    public function syncIfStaleDaily(): array
    {
        if (!$this->needsDailyRefresh()) {
            return ['ran' => false, 'skipped' => true, 'reason' => 'already_synced_today'];
        }

        $lockPath = dirname(__DIR__, 4) . '/storage/cache/crm_sales_sync.lock';
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
            // Reavalia após obter o lock (outro processo pode ter sincronizado)
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
     * @return array{
     *   success: bool,
     *   message: string,
     *   sync_mode: string,
     *   source: string|null,
     *   date_from: string|null,
     *   date_to: string|null,
     *   rows_fetched: int,
     *   rows_upserted: int,
     *   months_processed: int
     * }
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
                'Tabelas de cache CRM vendas ausentes. Execute: vendor/bin/phinx migrate'
            );
        }

        $range = $this->resolveSyncRange($mode);
        $source = $this->isViewAvailable() ? 'view' : 'cte';

        $runId = $this->repo->createSyncRun([
            'sync_mode' => $mode,
            'source' => $source,
            'date_from' => $range['from']->format('Y-m-d'),
            'date_to' => $range['to']->format('Y-m-d'),
            'status' => 'running',
            'started_at' => date('Y-m-d H:i:s'),
        ]);

        $stats = [
            'success' => false,
            'message' => '',
            'sync_mode' => $mode,
            'source' => $source,
            'date_from' => $range['from']->format('Y-m-d'),
            'date_to' => $range['to']->format('Y-m-d'),
            'rows_fetched' => 0,
            'rows_upserted' => 0,
            'months_processed' => 0,
        ];

        try {
            $cursor = $range['from']->modify('first day of this month');
            $endMonth = $range['to']->modify('first day of this month');

            while ($cursor <= $endMonth) {
                $chunkFrom = $cursor;
                $chunkTo = $cursor->modify('last day of this month');
                if ($chunkFrom < $range['from']) {
                    $chunkFrom = $range['from'];
                }
                if ($chunkTo > $range['to']) {
                    $chunkTo = $range['to'];
                }

                // Busca no SAP antes de apagar o MySQL — evita buraco se a API falhar
                $rows = $this->fetchAggregatedChunk($source, $chunkFrom, $chunkTo);
                $stats['rows_fetched'] += count($rows);

                $mapped = [];
                foreach ($rows as $raw) {
                    $mappedRow = $this->mapRawRow($raw);
                    if ($mappedRow !== null) {
                        $mapped[] = $mappedRow;
                    }
                }

                // Reescreve o intervalo do chunk (evita duplicar grãos ao re-agregar)
                $this->repo->deleteByDateRange($chunkFrom, $chunkTo);
                if ($mapped !== []) {
                    $stats['rows_upserted'] += $this->repo->upsertBatch($mapped);
                }

                $stats['months_processed']++;
                $cursor = $cursor->add(new DateInterval('P1M'));
            }

            $stats['success'] = true;
            $stats['message'] = sprintf(
                'Sync %s OK (%s): %d fatos em %d mês(es), período %s a %s.',
                $mode,
                $source,
                $stats['rows_upserted'],
                $stats['months_processed'],
                $stats['date_from'],
                $stats['date_to']
            );

            $now = date('Y-m-d H:i:s');
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
                'message' => $e->getMessage(),
                'updated_at' => $now,
            ]);
            throw $e;
        }

        return $stats;
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

        // incremental
        $state = $this->repo->getSyncState();
        $lastSuccess = $state['last_success_at'] ?? null;
        $lastTo = $state['last_to_date'] ?? null;
        $rowCount = $this->repo->countRows();

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
        $maxFrom = (new DateTimeImmutable('first day of this month'))
            ->sub(new DateInterval('P' . (self::LOOKBACK_MONTHS - 1) . 'M'));
        if ($from < $maxFrom) {
            $from = $maxFrom;
        }

        return ['from' => $from, 'to' => $hoje];
    }

    public function isViewAvailable(): bool
    {
        if ($this->viewAvailable !== null) {
            return $this->viewAvailable;
        }

        try {
            $this->sap->execute(
                'SELECT TOP 1 1 AS "ok" FROM "' . self::VIEW_NAME . '"'
            );
            $this->viewAvailable = true;
        } catch (Exception $e) {
            $this->viewAvailable = false;
            error_log(
                'CrmSalesSapSync: VIEW ' . self::VIEW_NAME .
                ' indisponível, usando CTE. ' . $e->getMessage()
            );
        }

        return $this->viewAvailable;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchAggregatedChunk(
        string $source,
        DateTimeImmutable $from,
        DateTimeImmutable $to
    ): array {
        $sql = $this->buildAggregatedSql($source, $from, $to);
        $result = $this->sap->execute($sql);
        $data = $result['data'] ?? [];
        return is_array($data) ? $data : [];
    }

    private function buildAggregatedSql(
        string $source,
        DateTimeImmutable $from,
        DateTimeImmutable $to
    ): string {
        $dateFilter = 'T0."DocDate" >= ' . $this->quoteDate($from)
            . ' AND T0."DocDate" <= ' . $this->quoteDate($to);

        if ($source === 'view') {
            $base = 'SELECT * FROM "' . self::VIEW_NAME . '" T0 WHERE ' . $dateFilter;
        } else {
            $base = $this->cteBody($dateFilter);
        }

        return 'SELECT
            src."DocDate" AS "DocDate",
            src."AnoMes" AS "AnoMes",
            src."TipoDocumento" AS "TipoDocumento",
            src."CardCode" AS "CardCode",
            MAX(src."Cliente") AS "Cliente",
            IFNULL(src."Vendedor", \'\') AS "Vendedor",
            IFNULL(src."GrupoCliente", \'\') AS "GrupoCliente",
            IFNULL(src."Regiao", \'\') AS "Regiao",
            IFNULL(src."GrupoItem", \'\') AS "GrupoItem",
            SUM(src."ValorLiquidoSinalizado") AS "ValorLiquido",
            COUNT(*) AS "QtdLinhas"
         FROM (' . $base . ') src
         GROUP BY
            src."DocDate",
            src."AnoMes",
            src."TipoDocumento",
            src."CardCode",
            IFNULL(src."Vendedor", \'\'),
            IFNULL(src."GrupoCliente", \'\'),
            IFNULL(src."Regiao", \'\'),
            IFNULL(src."GrupoItem", \'\')';
    }

    private function cteBody(string $dateFilter): string
    {
        return 'SELECT
    \'Fatura\' AS "TipoDocumento",
    T0."DocDate" AS "DocDate",
    TO_VARCHAR(T0."DocDate", \'YYYY-MM\') AS "AnoMes",
    T0."CardCode" AS "CardCode",
    T2."CardName" AS "Cliente",
    T3."SlpName" AS "Vendedor",
    T5."GroupName" AS "GrupoCliente",
    IFNULL(NULLIF(T8."descript", \'\'), IFNULL(NULLIF(T2."State1", \'\'), \'Sem região\')) AS "Regiao",
    T6."ItmsGrpNam" AS "GrupoItem",
    T1."LineTotal" AS "ValorLiquidoSinalizado"
FROM OINV T0
INNER JOIN INV1 T1 ON T1."DocEntry" = T0."DocEntry"
INNER JOIN OCRD T2 ON T2."CardCode" = T0."CardCode"
LEFT JOIN OSLP T3 ON T3."SlpCode" = T0."SlpCode"
LEFT JOIN OITM T4 ON T4."ItemCode" = T1."ItemCode"
LEFT JOIN OITB T6 ON T6."ItmsGrpCod" = T4."ItmsGrpCod"
LEFT JOIN OCRG T5 ON T5."GroupCode" = T2."GroupCode"
LEFT JOIN OTER T8 ON T8."territryID" = T2."Territory"
WHERE T0."CANCELED" = \'N\' AND ' . $dateFilter . '
UNION ALL
SELECT
    \'Devolucao\' AS "TipoDocumento",
    T0."DocDate" AS "DocDate",
    TO_VARCHAR(T0."DocDate", \'YYYY-MM\') AS "AnoMes",
    T0."CardCode" AS "CardCode",
    T2."CardName" AS "Cliente",
    T3."SlpName" AS "Vendedor",
    T5."GroupName" AS "GrupoCliente",
    IFNULL(NULLIF(T8."descript", \'\'), IFNULL(NULLIF(T2."State1", \'\'), \'Sem região\')) AS "Regiao",
    T6."ItmsGrpNam" AS "GrupoItem",
    (T1."LineTotal" * -1) AS "ValorLiquidoSinalizado"
FROM ORIN T0
INNER JOIN RIN1 T1 ON T1."DocEntry" = T0."DocEntry"
INNER JOIN OCRD T2 ON T2."CardCode" = T0."CardCode"
LEFT JOIN OSLP T3 ON T3."SlpCode" = T0."SlpCode"
LEFT JOIN OITM T4 ON T4."ItemCode" = T1."ItemCode"
LEFT JOIN OITB T6 ON T6."ItmsGrpCod" = T4."ItmsGrpCod"
LEFT JOIN OCRG T5 ON T5."GroupCode" = T2."GroupCode"
LEFT JOIN OTER T8 ON T8."territryID" = T2."Territory"
WHERE T0."CANCELED" = \'N\' AND ' . $dateFilter;
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<string, mixed>|null
     */
    private function mapRawRow(array $raw): ?array
    {
        $get = static function (array $row, string ...$keys): mixed {
            foreach ($keys as $k) {
                if (array_key_exists($k, $row) && $row[$k] !== null) {
                    return $row[$k];
                }
                $upper = strtoupper($k);
                if (array_key_exists($upper, $row) && $row[$upper] !== null) {
                    return $row[$upper];
                }
            }
            return null;
        };

        $docDateRaw = $get($raw, 'DocDate', 'doc_date');
        $docDate = $this->normalizeDate($docDateRaw);
        if ($docDate === null) {
            return null;
        }

        $tipo = trim((string) ($get($raw, 'TipoDocumento') ?? ''));
        if ($tipo === '') {
            return null;
        }
        // Normaliza acentos/variantes
        if (stripos($tipo, 'dev') !== false) {
            $tipo = 'Devolucao';
        } else {
            $tipo = 'Fatura';
        }

        $cardCode = trim((string) ($get($raw, 'CardCode') ?? ''));
        if ($cardCode === '') {
            return null;
        }

        $vendedor = trim((string) ($get($raw, 'Vendedor') ?? ''));
        if ($vendedor === '') {
            $vendedor = 'Sem vendedor';
        }
        $grupoCli = trim((string) ($get($raw, 'GrupoCliente') ?? ''));
        if ($grupoCli === '') {
            $grupoCli = 'Sem grupo';
        }
        $regiao = trim((string) ($get($raw, 'Regiao') ?? ''));
        if ($regiao === '') {
            $regiao = 'Sem região';
        }
        $grupoItem = trim((string) ($get($raw, 'GrupoItem') ?? ''));
        if ($grupoItem === '') {
            $grupoItem = 'Sem grupo de item';
        }
        $anoMes = trim((string) ($get($raw, 'AnoMes') ?? ''));
        if ($anoMes === '') {
            $anoMes = substr($docDate, 0, 7);
        }

        $grain = implode('|', [
            $docDate,
            $tipo,
            $cardCode,
            $vendedor,
            $grupoCli,
            $regiao,
            $grupoItem,
        ]);

        return [
            'grain_hash' => sha1($grain),
            'doc_date' => $docDate,
            'ano_mes' => $anoMes,
            'tipo_documento' => $tipo,
            'card_code' => $cardCode,
            'cliente' => mb_substr(trim((string) ($get($raw, 'Cliente') ?? '')), 0, 255),
            'vendedor' => mb_substr($vendedor, 0, 150),
            'grupo_cliente' => mb_substr($grupoCli, 0, 150),
            'regiao' => mb_substr($regiao, 0, 150),
            'grupo_item' => mb_substr($grupoItem, 0, 150),
            'valor_liquido' => (float) ($get($raw, 'ValorLiquido', 'ValorLiquidoSinalizado') ?? 0),
            'qtd_linhas' => max(0, (int) ($get($raw, 'QtdLinhas') ?? 0)),
        ];
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof DateTimeImmutable) {
            return $value->format('Y-m-d');
        }
        $s = trim((string) $value);
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $s, $m)) {
            return $m[1];
        }
        // HANA às vezes devolve /Date(ms)/ ou timestamp
        if (preg_match('/(\d{4})(\d{2})(\d{2})/', $s, $m)) {
            return $m[1] . '-' . $m[2] . '-' . $m[3];
        }
        $ts = strtotime($s);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    private function quoteDate(DateTimeImmutable $d): string
    {
        return "'" . $d->format('Y-m-d') . "'";
    }
}
