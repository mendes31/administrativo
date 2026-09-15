<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\crm\CrmSalesFactRepository;
use App\adms\Models\Repository\crm\CrmSalesUsageNatureRepository;
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
 * Recorte alinhado à query de indicadores de venda, sem Usage IN:
 * - CANCELED = N, DocType = I, SeqCode <> 34
 * - Valor: LineTotal − DiscSum (rateado na linha)
 * - Grupos OITB 104 e 106; todas as utilizações (natureza no Portal)
 * - Fonte SAP: sempre CTE (OINV/ORIN). Não consulta VIEW no HANA.
 *
 * Agendamento recomendado:
 * - Lazy: no 1º acesso do dia ao endpoint de dados, dispara incremental (com lock)
 * - Manual: botão "Atualizar agora" = só incremental
 * - Cron CLI (opcional, madrugada): php scripts/sync_crm_sales_sap.php
 */
class CrmSalesSapSyncService
{
    public const LOOKBACK_MONTHS = 36;
    public const INCREMENTAL_OVERLAP_DAYS = 3;
    /** Códigos OITB (não usar 400/700 — esses números só aparecem no nome do grupo). */
    public const ITEM_GROUP_CODES = [104, 106];

    /** Série SAP excluída na query de indicadores de venda (SeqCode 34). */
    public const EXCLUDED_SEQ_CODE = 34;
    /** Janela de sync: meses grandes estouram o limite de linhas da API SAP e perdem notas. */
    private const CHUNK_DAYS = 7;
    private const SAP_PAGE_SIZE = 500;

    private SapReportApiService $sap;
    private CrmSalesFactRepository $repo;
    private CrmSalesUsageNatureRepository $usageRepo;

    public function __construct(
        ?SapReportApiService $sap = null,
        ?CrmSalesFactRepository $repo = null,
        ?CrmSalesUsageNatureRepository $usageRepo = null
    ) {
        $this->sap = $sap ?? new SapReportApiService();
        $this->repo = $repo ?? new CrmSalesFactRepository();
        $this->usageRepo = $usageRepo ?? new CrmSalesUsageNatureRepository();
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
        $source = 'cte';

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
            $cursor = $range['from'];
            while ($cursor <= $range['to']) {
                $chunkFrom = $cursor;
                $chunkTo = $cursor->add(new DateInterval('P' . (self::CHUNK_DAYS - 1) . 'D'));
                if ($chunkTo > $range['to']) {
                    $chunkTo = $range['to'];
                }

                // Busca no SAP antes de apagar o MySQL — evita buraco se a API falhar
                $rows = $this->fetchAggregatedChunk($chunkFrom, $chunkTo);
                $stats['rows_fetched'] += count($rows);

                $mapped = [];
                foreach ($rows as $raw) {
                    $mappedRow = $this->mapRawRow($raw);
                    if ($mappedRow !== null) {
                        $mapped[] = $mappedRow;
                        $this->usageRepo->upsertFromSync(
                            (int) $mappedRow['usage_id'],
                            (string) $mappedRow['usage_name']
                        );
                    }
                }

                // Nunca apagar o mês se o SAP devolveu 0 linhas (protege contra API/schema
                // desatualizado ou filtro que falhou). Só reescreve quando há fatos novos.
                if ($mapped === []) {
                    $stats['months_processed']++;
                    $cursor = $chunkTo->add(new DateInterval('P1D'));
                    continue;
                }

                $this->repo->deleteByDateRange($chunkFrom, $chunkTo);
                $stats['rows_upserted'] += $this->repo->upsertBatch($mapped);

                $stats['months_processed']++;
                $cursor = $chunkTo->add(new DateInterval('P1D'));
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

    private function itemGroupFilterSql(string $itemAlias = 'T4', string $groupAlias = 'T6'): string
    {
        $codes = implode(', ', self::ITEM_GROUP_CODES);
        return '('
            . $itemAlias . '."ItmsGrpCod" IN (' . $codes . ')'
            . ' OR UPPER(IFNULL(' . $groupAlias . '."ItmsGrpNam", \'\')) LIKE \'%PROD ACABADO%\''
            . ' OR UPPER(IFNULL(' . $groupAlias . '."ItmsGrpNam", \'\')) LIKE \'%USO/CONS%\''
            . ' OR UPPER(IFNULL(' . $groupAlias . '."ItmsGrpNam", \'\')) LIKE \'%USO E CONSUMO%\''
            . ')';
    }

    /** Notas de item, não canceladas, fora da série 34 (query de indicadores). */
    private function documentFilterSql(): string
    {
        return 'T0."CANCELED" = \'N\''
            . ' AND T0."DocType" = \'I\''
            . ' AND IFNULL(T0."SeqCode", 0) <> ' . self::EXCLUDED_SEQ_CODE;
    }

    /** LineTotal menos rateio do DiscSum da nota. */
    private function lineNetSql(int $sinal): string
    {
        $share = 'IFNULL(T0."DiscSum", 0) * T1."LineTotal" / NULLIF(SUM(T1."LineTotal") OVER (PARTITION BY T0."DocEntry"), 0)';
        $net = 'T1."LineTotal" - IFNULL(' . $share . ', 0)';
        return $sinal < 0 ? '(' . $net . ') * -1' : $net;
    }

    private function lineDiscountSql(int $sinal): string
    {
        $line = '((T1."Price" * T1."Quantity") - T1."LineTotal")';
        $share = 'IFNULL(IFNULL(T0."DiscSum", 0) * T1."LineTotal" / NULLIF(SUM(T1."LineTotal") OVER (PARTITION BY T0."DocEntry"), 0), 0)';
        $expr = $line . ' + ' . $share;
        return $sinal < 0 ? '(' . $expr . ') * -1' : $expr;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchAggregatedChunk(
        DateTimeImmutable $from,
        DateTimeImmutable $to
    ): array {
        return array_merge(
            $this->fetchAggregatedPaged($from, $to, 'Fatura'),
            $this->fetchAggregatedPaged($from, $to, 'Devolucao')
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchAggregatedPaged(
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        string $tipoDocumento
    ): array {
        $all = [];
        $offset = 0;
        $firstKey = null;
        $pages = 0;
        do {
            $sql = $this->buildAggregatedSql($from, $to, $tipoDocumento)
                . ' ORDER BY "DocDate", "CardCode", "ItemCode", "CodUtilizacao"'
                . ' LIMIT ' . self::SAP_PAGE_SIZE . ' OFFSET ' . $offset;
            try {
                $result = $this->sap->execute($sql);
            } catch (Exception $e) {
                if ($offset > 0) {
                    throw $e;
                }
                $result = $this->sap->execute(
                    $this->buildAggregatedSql($from, $to, $tipoDocumento)
                );
                $page = $result['data'] ?? [];
                return is_array($page) ? $page : [];
            }
            $page = $result['data'] ?? [];
            if (!is_array($page) || $page === []) {
                break;
            }
            $pageKey = json_encode($page[0] ?? null);
            if ($offset > 0 && $pageKey === $firstKey) {
                break;
            }
            if ($offset === 0) {
                $firstKey = $pageKey;
            }
            foreach ($page as $row) {
                $all[] = $row;
            }
            $offset += self::SAP_PAGE_SIZE;
            $pages++;
        } while (count($page) >= self::SAP_PAGE_SIZE && $pages < 40);

        return $all;
    }

    private function buildAggregatedSql(
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        string $tipoDocumento
    ): string {
        $dateFilter = 'T0."DocDate" >= ' . $this->quoteDate($from)
            . ' AND T0."DocDate" <= ' . $this->quoteDate($to);
        $tipoSql = $tipoDocumento === 'Devolucao' ? 'Devolucao' : 'Fatura';
        $base = $this->cteBody($dateFilter, $tipoSql === 'Devolucao');

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
            IFNULL(src."ItemCode", \'\') AS "ItemCode",
            MAX(IFNULL(src."DescricaoItem", \'\')) AS "DescricaoItem",
            IFNULL(src."CodUtilizacao", 0) AS "CodUtilizacao",
            MAX(IFNULL(src."Utilizacao", \'\')) AS "Utilizacao",
            SUM(src."ValorLiquidoSinalizado") AS "ValorLiquido",
            SUM(src."QuantidadeLiq") AS "Quantidade",
            SUM(src."ValorBrutoSinalizado") AS "ValorBruto",
            SUM(src."ValorDescontoSinalizado") AS "ValorDesconto",
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
            IFNULL(src."GrupoItem", \'\'),
            IFNULL(src."ItemCode", \'\'),
            IFNULL(src."CodUtilizacao", 0)';
    }

    private function cteBody(string $dateFilter, bool $devolucao): string
    {
        $itemFilter = $this->itemGroupFilterSql('T4', 'T6');
        $docFilter = $this->documentFilterSql();
        if ($devolucao) {
            return 'SELECT
    \'Devolucao\' AS "TipoDocumento",
    T0."DocDate" AS "DocDate",
    TO_VARCHAR(T0."DocDate", \'YYYY-MM\') AS "AnoMes",
    T0."CardCode" AS "CardCode",
    T2."CardName" AS "Cliente",
    T3."SlpName" AS "Vendedor",
    T5."GroupName" AS "GrupoCliente",
    IFNULL(NULLIF(T8."descript", \'\'), IFNULL(NULLIF(T2."State1", \'\'), \'Sem região\')) AS "Regiao",
    T6."ItmsGrpNam" AS "GrupoItem",
    IFNULL(T1."ItemCode", \'\') AS "ItemCode",
    IFNULL(T1."Dscription", \'\') AS "DescricaoItem",
    IFNULL(T1."Usage", 0) AS "CodUtilizacao",
    IFNULL(T9."Usage", \'Sem utilização\') AS "Utilizacao",
    ' . $this->lineNetSql(-1) . ' AS "ValorLiquidoSinalizado",
    (T1."Quantity" * -1) AS "QuantidadeLiq",
    (T1."Price" * T1."Quantity" * -1) AS "ValorBrutoSinalizado",
    ' . $this->lineDiscountSql(-1) . ' AS "ValorDescontoSinalizado"
FROM ORIN T0
INNER JOIN RIN1 T1 ON T1."DocEntry" = T0."DocEntry"
INNER JOIN OCRD T2 ON T2."CardCode" = T0."CardCode"
LEFT JOIN OSLP T3 ON T3."SlpCode" = T0."SlpCode"
LEFT JOIN OITM T4 ON T4."ItemCode" = T1."ItemCode"
LEFT JOIN OITB T6 ON T6."ItmsGrpCod" = T4."ItmsGrpCod"
LEFT JOIN OCRG T5 ON T5."GroupCode" = T2."GroupCode"
LEFT JOIN OTER T8 ON T8."territryID" = T2."Territory"
LEFT JOIN OUSG T9 ON T9."ID" = T1."Usage"
WHERE ' . $docFilter . ' AND ' . $dateFilter . ' AND ' . $itemFilter;
        }

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
    IFNULL(T1."ItemCode", \'\') AS "ItemCode",
    IFNULL(T1."Dscription", \'\') AS "DescricaoItem",
    IFNULL(T1."Usage", 0) AS "CodUtilizacao",
    IFNULL(T9."Usage", \'Sem utilização\') AS "Utilizacao",
    ' . $this->lineNetSql(1) . ' AS "ValorLiquidoSinalizado",
    T1."Quantity" AS "QuantidadeLiq",
    (T1."Price" * T1."Quantity") AS "ValorBrutoSinalizado",
    ' . $this->lineDiscountSql(1) . ' AS "ValorDescontoSinalizado"
FROM OINV T0
INNER JOIN INV1 T1 ON T1."DocEntry" = T0."DocEntry"
INNER JOIN OCRD T2 ON T2."CardCode" = T0."CardCode"
LEFT JOIN OSLP T3 ON T3."SlpCode" = T0."SlpCode"
LEFT JOIN OITM T4 ON T4."ItemCode" = T1."ItemCode"
LEFT JOIN OITB T6 ON T6."ItmsGrpCod" = T4."ItmsGrpCod"
LEFT JOIN OCRG T5 ON T5."GroupCode" = T2."GroupCode"
LEFT JOIN OTER T8 ON T8."territryID" = T2."Territory"
LEFT JOIN OUSG T9 ON T9."ID" = T1."Usage"
WHERE ' . $docFilter . ' AND ' . $dateFilter . ' AND ' . $itemFilter;
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
        $itemCode = trim((string) ($get($raw, 'ItemCode') ?? ''));
        $itemName = trim((string) ($get($raw, 'DescricaoItem') ?? ''));
        $anoMes = trim((string) ($get($raw, 'AnoMes') ?? ''));
        if ($anoMes === '') {
            $anoMes = substr($docDate, 0, 7);
        }

        $usageId = (int) ($get($raw, 'CodUtilizacao') ?? 0);
        $usageName = trim((string) ($get($raw, 'Utilizacao') ?? ''));
        if ($usageName === '') {
            $usageName = 'Sem utilização';
        }

        $grain = implode('|', [
            $docDate,
            $tipo,
            $cardCode,
            $vendedor,
            $grupoCli,
            $regiao,
            $grupoItem,
            $itemCode,
            (string) $usageId,
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
            'item_code' => mb_substr($itemCode, 0, 50),
            'item_name' => mb_substr($itemName, 0, 255),
            'usage_id' => $usageId,
            'usage_name' => mb_substr($usageName, 0, 150),
            'valor_liquido' => (float) ($get($raw, 'ValorLiquido', 'ValorLiquidoSinalizado') ?? 0),
            'quantidade' => (float) ($get($raw, 'Quantidade', 'QuantidadeLiq') ?? 0),
            'valor_bruto' => (float) ($get($raw, 'ValorBruto', 'ValorBrutoSinalizado') ?? 0),
            'valor_desconto' => (float) ($get($raw, 'ValorDesconto', 'ValorDescontoSinalizado') ?? 0),
            'qtd_linhas' => max(0, (int) ($get($raw, 'QtdLinhas') ?? 0)),
        ];
    }

    private function quoteDate(DateTimeImmutable $d): string
    {
        return 'TO_DATE(\'' . $d->format('Y-m-d') . '\', \'YYYY-MM-DD\')';
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
        if (preg_match('#/Date\((\d+)\)#', $s, $m)) {
            $n = (int) $m[1];
            if ($n > 10000000000) {
                $n = intdiv($n, 1000);
            }
            return gmdate('Y-m-d', $n) ?: null;
        }
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $s, $m)) {
            return $m[1];
        }
        if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $s, $m)) {
            return $m[1] . '-' . $m[2] . '-' . $m[3];
        }
        $ts = strtotime($s);
        return $ts ? date('Y-m-d', $ts) : null;
    }
}
