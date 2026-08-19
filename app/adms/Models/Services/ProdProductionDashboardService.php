<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\production\ProdProductionCacheRepository;
use DateInterval;
use DateTimeImmutable;
use Exception;

/**
 * Dashboard de Produção — agregações a partir do cache MySQL.
 *
 * O HANA é consultado apenas pelo ProdProductionSapSyncService (CLI/cron/botão).
 */
class ProdProductionDashboardService
{
    public const MAX_MONTHS = 24;

    private ProdProductionCacheRepository $repo;

    public function __construct(?ProdProductionCacheRepository $repo = null)
    {
        $this->repo = $repo ?? new ProdProductionCacheRepository();
    }

    /**
     * @param array{periodo?: string, date_from?: string, date_to?: string, linha?: string|null} $filters
     * @return array<string, mixed>
     */
    public function getDashboardData(array $filters): array
    {
        if (!$this->repo->tableExists()) {
            throw new Exception(
                'Cache de produção não instalado. Execute a migration e a sincronização SAP/BEAS.'
            );
        }

        $range = $this->resolveDateRange($filters);
        $prev = $this->previousRange($range['from'], $range['to']);
        $linha = $this->trimOrNull($filters['linha'] ?? null);
        $from = $range['from']->format('Y-m-d');
        $to = $range['to']->format('Y-m-d');
        $prevFrom = $prev['from']->format('Y-m-d');
        $prevTo = $prev['to']->format('Y-m-d');
        $hoje = (new DateTimeImmutable('today'))->format('Y-m-d');

        $rowCount = $this->repo->countRows();
        $receiptCount = $this->repo->countReceipts();
        $sync = $this->repo->getSyncState();
        $linhas = $this->repo->listLinhas();
        $cacheEmpty = $receiptCount === 0 && $rowCount === 0;

        $emptyPayload = [
            'success' => true,
            'source' => 'mysql_cache',
            'cache_empty' => $cacheEmpty,
            'mapping_pending' => ($sync['last_status'] ?? '') === 'mapping_pending',
        ];

        $kpis = $cacheEmpty
            ? $this->emptyKpis()
            : $this->repo->fetchPeriodKpis($from, $to, $linha);
        $kpisPrev = $cacheEmpty
            ? $this->emptyKpis()
            : $this->repo->fetchPeriodKpis($prevFrom, $prevTo, $linha);
        $open = $cacheEmpty
            ? ['andamento' => 0, 'atraso' => 0]
            : $this->repo->fetchOpenSnapshot($linha, $hoje);

        $planejado = (float) $kpis['planejado'];
        $volume = (float) $kpis['volume'];
        $aderencia = $planejado > 0 ? round(($volume / $planejado) * 100, 1) : null;
        $planejadoPrev = (float) $kpisPrev['planejado'];
        $volumePrev = (float) $kpisPrev['volume'];
        $aderenciaPrev = $planejadoPrev > 0 ? round(($volumePrev / $planejadoPrev) * 100, 1) : null;
        $iniciadas = (int) $kpis['iniciadas'];
        $concluidas = (int) $kpis['concluidas'];
        $taxaConclusao = $iniciadas > 0 ? round(($concluidas / $iniciadas) * 100, 1) : null;
        $refugo = (float) $kpis['refugo'];
        $refugoBom = (float) ($kpis['refugo_bom'] ?? 0);
        $taxaRefugo = ($refugoBom + $refugo) > 0 ? round(($refugo / ($refugoBom + $refugo)) * 100, 2) : null;

        $meses = $this->listAnoMesInRange($range);
        $monthly = $cacheEmpty ? [] : $this->repo->fetchMonthly($from, $to, $linha);
        $monthlyByKey = [];
        foreach ($monthly as $row) {
            $monthlyByKey[$row['ano_mes']] = $row;
        }
        $orderMonths = $cacheEmpty
            ? ['iniciadas' => [], 'concluidas' => []]
            : $this->repo->fetchMonthlyOrders($from, $to, $linha);

        $serieSkus = [];
        $serieProdutos = [];
        $serieVolume = [];
        $seriePlanejado = [];
        $serieIni = [];
        $serieConc = [];
        $labels = [];
        foreach ($meses as $ym) {
            $labels[] = $this->labelMes($ym);
            $row = $monthlyByKey[$ym] ?? null;
            $serieSkus[] = $row['skus'] ?? 0;
            $serieProdutos[] = $row['produtos'] ?? 0;
            $serieVolume[] = $row['volume'] ?? 0;
            $seriePlanejado[] = $row['planejado'] ?? 0;
            $serieIni[] = $orderMonths['iniciadas'][$ym] ?? 0;
            $serieConc[] = $orderMonths['concluidas'][$ym] ?? 0;
        }

        $hasShopFloor = $this->repo->hasShopFloorFacts();

        $warning = null;
        if ($cacheEmpty) {
            $warning = 'Cache vazio. Clique em Atualizar agora ou rode php scripts/sync_prod_production_sap.php --full';
        }

        return $emptyPayload + [
            'periodo' => [
                'chave' => $range['chave'],
                'date_from' => $from,
                'date_to' => $to,
                'label' => $this->periodLabel($range),
                'meses' => $meses,
            ],
            'filtros_ativos' => array_filter(['linha' => $linha], static fn ($v) => $v !== null),
            'filtros_opcoes' => ['linhas' => $linhas],
            'sync' => [
                'last_success_at' => $sync['last_success_at'] ?? null,
                'last_status' => $sync['last_status'] ?? 'never',
                'last_source' => $sync['last_source'] ?? null,
                'last_mode' => $sync['last_mode'] ?? null,
                'rows_upserted' => (int) ($sync['rows_upserted'] ?? 0),
                'beas_tables' => $sync['beas_tables'] ?? null,
                'message' => $sync['message'] ?? null,
                'cache_rows' => $rowCount,
                'cache_receipts' => $receiptCount,
            ],
            'kpis' => [
                'skus' => $kpis['skus'],
                'skus_delta' => $this->deltaAbs((int) round((float) $kpis['skus']), (int) round((float) $kpisPrev['skus'])),
                'produtos' => $kpis['produtos'],
                'produtos_delta' => $this->deltaAbs((int) $kpis['produtos'], (int) $kpisPrev['produtos']),
                'volume' => $volume,
                'volume_delta_pct' => $this->deltaPct($volume, $volumePrev),
                'iniciadas' => $iniciadas,
                'iniciadas_delta_pct' => $this->deltaPct((float) $iniciadas, (float) $kpisPrev['iniciadas']),
                'concluidas' => $concluidas,
                'concluidas_delta_pct' => $this->deltaPct((float) $concluidas, (float) $kpisPrev['concluidas']),
                'taxa_conclusao' => $taxaConclusao,
                'andamento' => $open['andamento'],
                'atraso' => $open['atraso'],
                'aderencia' => $aderencia,
                'aderencia_delta_pp' => ($aderencia !== null && $aderenciaPrev !== null)
                    ? round($aderencia - $aderenciaPrev, 1)
                    : null,
                'taxa_refugo' => $taxaRefugo,
                'lead_dias' => $kpis['lead_dias'] !== null ? round((float) $kpis['lead_dias'], 1) : null,
                'oee' => null,
                'disponibilidade' => null,
                'performance' => null,
                'qualidade' => null,
                'downtime_horas' => null,
            ],
            'series' => [
                'labels' => $labels,
                'ano_mes' => $meses,
                'skus' => $serieSkus,
                'produtos' => $serieProdutos,
                'volume' => $serieVolume,
                'planejado' => $seriePlanejado,
                'iniciadas' => $serieIni,
                'concluidas' => $serieConc,
            ],
            'linhas' => $cacheEmpty ? [] : $this->repo->fetchByLine($from, $to, $linha),
            'ordens_abertas' => $cacheEmpty ? [] : $this->repo->fetchOpenOrders($linha, $hoje),
            'top_skus' => $cacheEmpty ? [] : $this->repo->fetchTopSkus($from, $to, $linha),
            'shop_floor' => [
                'available' => $hasShopFloor,
                'note' => $hasShopFloor
                    ? null
                    : 'OEE, paradas e ciclo médio entram na V1.1, após o mapeamento das tabelas de apontamento BEAS.',
            ],
            'warning' => $warning,
        ];
    }

    /**
     * @return array{skus: int, produtos: int, volume: float, planejado: float, refugo: float, iniciadas: int, concluidas: int, lead_dias: null}
     */
    private function emptyKpis(): array
    {
        return [
            'skus' => 0.0,
            'produtos' => 0,
            'volume' => 0.0,
            'planejado' => 0.0,
            'refugo' => 0.0,
            'refugo_bom' => 0.0,
            'iniciadas' => 0,
            'concluidas' => 0,
            'lead_dias' => null,
        ];
    }

    /**
     * @param array{periodo?: string, date_from?: string, date_to?: string} $filters
     * @return array{chave: string, from: DateTimeImmutable, to: DateTimeImmutable}
     */
    private function resolveDateRange(array $filters): array
    {
        $hoje = new DateTimeImmutable('today');
        $chave = (string) ($filters['periodo'] ?? '30');

        if ($chave === 'personalizado') {
            $from = DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($filters['date_from'] ?? ''));
            $to = DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($filters['date_to'] ?? ''));
            if (!$from || !$to || $from > $to) {
                throw new Exception('Período personalizado inválido. Informe data início e data fim.');
            }
            $maxFrom = $hoje->sub(new DateInterval('P' . self::MAX_MONTHS . 'M'));
            if ($from < $maxFrom) {
                $from = $maxFrom;
            }
            return ['chave' => $chave, 'from' => $from, 'to' => $to];
        }

        if ($chave === 'mes_atual') {
            return [
                'chave' => $chave,
                'from' => new DateTimeImmutable('first day of this month'),
                'to' => $hoje,
            ];
        }
        if ($chave === 'mes_anterior') {
            $first = new DateTimeImmutable('first day of last month');
            return [
                'chave' => $chave,
                'from' => $first,
                'to' => $first->modify('last day of this month'),
            ];
        }
        if ($chave === 'ano_atual') {
            return [
                'chave' => $chave,
                'from' => new DateTimeImmutable($hoje->format('Y') . '-01-01'),
                'to' => $hoje,
            ];
        }

        $days = (int) $chave;
        if (!in_array($days, [7, 30, 90], true)) {
            $days = 30;
            $chave = '30';
        }
        $from = $hoje->sub(new DateInterval('P' . ($days - 1) . 'D'));
        return ['chave' => $chave, 'from' => $from, 'to' => $hoje];
    }

    /**
     * @return array{from: DateTimeImmutable, to: DateTimeImmutable}
     */
    private function previousRange(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $days = (int) $from->diff($to)->format('%a') + 1;
        $prevTo = $from->sub(new DateInterval('P1D'));
        $prevFrom = $prevTo->sub(new DateInterval('P' . ($days - 1) . 'D'));
        return ['from' => $prevFrom, 'to' => $prevTo];
    }

    /**
     * @param array{chave: string, from: DateTimeImmutable, to: DateTimeImmutable} $range
     * @return list<string>
     */
    private function listAnoMesInRange(array $range): array
    {
        $cursor = $range['from']->modify('first day of this month');
        $end = $range['to']->modify('first day of this month');
        $out = [];
        while ($cursor <= $end) {
            $out[] = $cursor->format('Y-m');
            $cursor = $cursor->modify('+1 month');
        }
        return $out;
    }

    /**
     * @param array{chave: string, from: DateTimeImmutable, to: DateTimeImmutable} $range
     */
    private function periodLabel(array $range): string
    {
        $fmt = fn (DateTimeImmutable $d) => $d->format('d/m/Y');
        return $fmt($range['from']) . ' — ' . $fmt($range['to']);
    }

    private function labelMes(string $ym): string
    {
        $meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
        $parts = explode('-', $ym);
        $m = isset($parts[1]) ? (int) $parts[1] : 1;
        $label = $meses[max(1, min(12, $m)) - 1];
        return $label . '/' . substr($parts[0] ?? '', 2);
    }

    /**
     * @return array{abs: int, dir: string}
     */
    private function deltaAbs(int $now, int $prev): array
    {
        $d = $now - $prev;
        return [
            'abs' => $d,
            'dir' => $d > 0 ? 'up' : ($d < 0 ? 'down' : 'flat'),
        ];
    }

    private function deltaPct(float $now, float $prev): ?float
    {
        if ($prev == 0.0) {
            return $now > 0 ? 100.0 : null;
        }
        return round((($now - $prev) / $prev) * 100, 1);
    }

    private function trimOrNull(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = trim((string) $v);
        return $s === '' ? null : $s;
    }
}
