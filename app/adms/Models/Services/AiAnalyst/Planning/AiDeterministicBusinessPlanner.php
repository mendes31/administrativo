<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Planning;

use App\adms\Models\Services\AiAnalyst\Adapters\FakeReportRunner;
use App\adms\Models\Services\AiAnalyst\Catalog\AiTechnicalCatalogService;
use App\adms\Models\Services\AiAnalyst\Tools\AiToolDefinition;

/**
 * Resolve no PHP a comparação da série simulada de vendas.
 * Usa o catálogo técnico só como dica semântica (sem SQL).
 */
final class AiDeterministicBusinessPlanner implements AiPlannerInterface
{
    /** @var array<string, int> Nomes e abreviações comuns, do mais longo ao mais curto. */
    private const MONTHS = [
        'janeiro' => 1,
        'fevereiro' => 2,
        'março' => 3,
        'marco' => 3,
        'abril' => 4,
        'maio' => 5,
        'junho' => 6,
        'julho' => 7,
        'agosto' => 8,
        'setembro' => 9,
        'outubro' => 10,
        'novembro' => 11,
        'dezembro' => 12,
        'jan' => 1,
        'fev' => 2,
        'abr' => 4,
        'mai' => 5,
        'jun' => 6,
        'jul' => 7,
        'ago' => 8,
        'set' => 9,
        'out' => 10,
        'nov' => 11,
        'dez' => 12,
        'mar' => 3,
    ];

    /** @var array<string, list<string>> Domínio de negócio => tabelas semânticas típicas */
    private const DOMAIN_TABLE_HINTS = [
        'sales' => ['OINV', 'ORDR', 'INV1', 'RDR1'],
    ];

    public function __construct(
        private readonly AiPlannerInterface $fallback,
        private readonly ?int $defaultYear = null,
        private readonly ?AiTechnicalCatalogService $catalog = null,
        private readonly bool $liveReports = false,
        private readonly ?string $sessionDomain = null,
        private readonly ?string $sessionBinding = null
    ) {
    }

    private function provider(): string
    {
        return $this->liveReports ? 'local-approved' : 'local-simulated';
    }

    public function plan(string $message, string $profile, array $tools): AiPlannerResult
    {
        if ($profile !== 'business' || !$this->hasTool($tools, 'report.run_approved')) {
            return $this->fallback->plan($message, $profile, $tools);
        }

        $normalized = mb_strtolower(trim($message));
        if ($this->mentionsForeignDomain($normalized)) {
            return $this->clarify(
                'Nesta etapa a análise inteligente cobre a série de vendas (simulada ou relatório homologado). '
                . 'Compras, estoque, financeiro e produção ficam para uma fase seguinte — sem SQL livre.'
            );
        }

        $yearSeries = $this->extractYearSeries($normalized);
        $periods = $this->periods($normalized);
        $binding = $this->resolveBinding($normalized, $periods, $yearSeries !== null);
        if ($binding === null) {
            return $this->clarify(
                'Por enquanto consigo comparar ou consultar a série de vendas. '
                . 'Ex.: «compare vendas de janeiro e junho de 2026» ou «analise os 12 meses de 2025».'
            );
        }

        $semanticHint = $this->semanticHint($normalized, $binding);

        // «12 meses de 2025» / «compare o ano 2025» → uma série anual filtrada (não 12 steps).
        if ($yearSeries !== null) {
            return new AiPlannerResult([
                'goal' => 'year_series',
                'semantic_hint' => $semanticHint,
                'steps' => [[
                    'step_id' => 's1',
                    'tool' => 'report.run_approved',
                    'arguments' => [
                        'report_binding' => $binding,
                        'year' => $yearSeries,
                    ],
                    'depends_on' => [],
                ]],
            ], $this->provider());
        }

        $maxPeriods = 5;
        if (count($periods) > $maxPeriods) {
            return $this->clarify(
                'Informe no máximo ' . $maxPeriods . ' períodos para comparar a série de vendas '
                . '(ex.: janeiro/2024, janeiro/2025 e janeiro/2026). '
                . 'Para o ano inteiro, diga «analise os 12 meses de 2025».'
            );
        }

        if (count($periods) >= 2 && $this->hasTool($tools, 'analysis.compare')) {
            $steps = [];
            $stepIds = [];
            foreach ($periods as $index => $period) {
                $stepId = 's' . ($index + 1);
                $steps[] = $this->reportStep($stepId, $period, $binding);
                $stepIds[] = $stepId;
            }
            $compareId = 's' . (count($periods) + 1);
            $steps[] = [
                'step_id' => $compareId,
                'tool' => 'analysis.compare',
                'arguments' => ['step_ids' => $stepIds],
                'depends_on' => $stepIds,
            ];

            return new AiPlannerResult([
                'goal' => 'compare_periods',
                'semantic_hint' => $semanticHint,
                'steps' => $steps,
            ], $this->provider());
        }

        if (count($periods) === 1 && ($this->mentionsSales($normalized) || $this->sessionDomain === 'sales')) {
            return new AiPlannerResult([
                'goal' => $this->liveReports ? 'approved_period' : 'simulated_period',
                'semantic_hint' => $semanticHint,
                'steps' => [$this->reportStep('s1', $periods[0], $binding)],
            ], $this->provider());
        }

        return $this->fallback->plan($message, $profile, $tools);
    }

    /** @param array{month: int, year: int} $period @return array<string, mixed> */
    private function reportStep(string $stepId, array $period, string $binding): array
    {
        return [
            'step_id' => $stepId,
            'tool' => 'report.run_approved',
            'arguments' => [
                'report_binding' => $binding,
                'month' => $period['month'],
                'year' => $period['year'],
            ],
            'depends_on' => [],
        ];
    }

    /** @param list<array{month: int, year: int}> $periods */
    private function resolveBinding(string $message, array $periods, bool $yearSeries = false): ?string
    {
        if ($this->mentionsSales($message) || $yearSeries) {
            return $this->sessionBinding !== null && $this->sessionBinding !== ''
                ? $this->sessionBinding
                : FakeReportRunner::BINDING;
        }

        // Follow-up só com períodos após análise de vendas na sessão.
        if ($this->sessionDomain === 'sales' && $periods !== []) {
            return $this->sessionBinding !== null && $this->sessionBinding !== ''
                ? $this->sessionBinding
                : FakeReportRunner::BINDING;
        }

        return null;
    }

    /** Detecta pedido de série anual: «12 meses de 2025», «ano 2025», «compare 2025». */
    private function extractYearSeries(string $message): ?int
    {
        if (preg_match(
            '/\b(?:12\s*meses|todos\s+os\s+meses|ano\s+(?:inteiro\s+)?(?:de\s+)?|durante\s+(?:todo\s+)?(?:o\s+)?ano\s+(?:de\s+)?)\s*(20\d{2}|\d{2})\b/iu',
            $message,
            $match
        ) === 1) {
            return $this->normalizeYear((int) $match[1]);
        }

        if (
            preg_match('/\b(?:comparar|compare|analis[ae]r?|avali[ae]r?)\b/iu', $message) === 1
            && preg_match('/\bmeses?\b/iu', $message) === 1
            && preg_match('/\b(20\d{2})\b/u', $message, $match) === 1
        ) {
            return $this->normalizeYear((int) $match[1]);
        }

        return null;
    }

    /**
     * @return array{domain: string, binding: string, tables: list<string>, note: string}
     */
    private function semanticHint(string $message, string $binding): array
    {
        $domain = $this->mentionsSales($message) ? 'sales' : 'unknown';
        $tables = self::DOMAIN_TABLE_HINTS[$domain] ?? [];

        if ($this->catalog !== null && $domain === 'sales') {
            $hits = $this->catalog->search('vendas faturamento nota fiscal pedido', 4);
            $fromCatalog = [];
            foreach ($hits as $hit) {
                $table = strtoupper((string) ($hit['table_name'] ?? ''));
                if ($table !== '' && $table !== 'BEAS_*') {
                    $fromCatalog[] = $table;
                }
            }
            if ($fromCatalog !== []) {
                $tables = array_values(array_unique(array_merge($fromCatalog, $tables)));
            }
        }

        return [
            'domain' => $domain,
            'binding' => $binding,
            'tables' => array_slice($tables, 0, 6),
            'note' => 'Dica semântica do catálogo para montar a análise; nenhum SQL foi gerado nem executado.',
        ];
    }

    private function clarify(string $message): AiPlannerResult
    {
        return new AiPlannerResult([
            'goal' => 'clarify',
            'steps' => [],
            'clarification' => $message,
        ], $this->provider());
    }

    /** @param list<AiToolDefinition> $tools */
    private function hasTool(array $tools, string $name): bool
    {
        foreach ($tools as $tool) {
            if ($tool->name === $name) {
                return true;
            }
        }

        return false;
    }

    private function mentionsForeignDomain(string $message): bool
    {
        return preg_match(
            '/(?<![\p{L}])(?:compras?|fornecedores?|estoques?|invent[aá]rio|financeir[oa]s?|produ[cç][aã]o|contas\s+a\s+(?:pagar|receber))(?![\p{L}])/u',
            $message
        ) === 1;
    }

    private function mentionsSales(string $message): bool
    {
        return preg_match('/(?<![\p{L}])(?:vendas?|faturamento|receita)(?![\p{L}])/u', $message) === 1;
    }

    /** @return list<array{month: int, year: int}> */
    private function periods(string $message): array
    {
        $periods = [];
        $pairedOffsets = [];

        // «janeiro 2024», «janeiro de 2026», «jan/2025», «jan/25»
        $monthNames = array_keys(self::MONTHS);
        usort($monthNames, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
        $monthAlt = implode('|', array_map(static fn (string $name): string => preg_quote($name, '/'), $monthNames));
        if (preg_match_all(
            '/(?<![\p{L}])(' . $monthAlt . ')(?:\s*\/\s*|\s+de\s+|\s+)((?:20)?\d{2})(?!\d)/iu',
            $message,
            $named,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE
        ) > 0) {
            foreach ($named as $match) {
                $month = self::MONTHS[mb_strtolower($match[1][0])] ?? null;
                if ($month === null) {
                    continue;
                }
                $year = $this->normalizeYear((int) $match[2][0]);
                $periods[$year . '-' . $month] = ['month' => $month, 'year' => $year];
                $pairedOffsets[(int) $match[1][1]] = true;
            }
        }

        if (preg_match_all(
            '/(?<!\d)(0?[1-9]|1[0-2])\s*[\/\-]\s*((?:20)?\d{2})(?!\d)/u',
            $message,
            $numeric,
            PREG_SET_ORDER
        ) > 0) {
            foreach ($numeric as $match) {
                $month = (int) $match[1];
                $year = $this->normalizeYear((int) $match[2]);
                $periods[$year . '-' . $month] = ['month' => $month, 'year' => $year];
            }
        }

        // «agosto e julho de 2026»: agosto sem ano + julho de 2026
        $hits = [];
        foreach (self::MONTHS as $name => $month) {
            if (preg_match_all(
                '/(?<![\p{L}])' . preg_quote($name, '/') . '(?![\p{L}])/u',
                $message,
                $found,
                PREG_OFFSET_CAPTURE
            ) < 1) {
                continue;
            }
            foreach ($found[0] as $match) {
                $offset = (int) $match[1];
                if (isset($pairedOffsets[$offset])) {
                    continue;
                }
                $hits[] = ['offset' => $offset, 'month' => $month];
            }
        }
        usort($hits, static fn (array $a, array $b): int => $a['offset'] <=> $b['offset']);

        preg_match_all('/(?<!\d)(20\d{2})(?!\d)/u', $message, $years);
        $yearList = array_map('intval', $years[1] ?? []);
        $uniqueYears = array_values(array_unique($yearList));

        if ($hits !== []) {
            if (count($yearList) === count($hits)) {
                foreach ($hits as $index => $hit) {
                    $periodYear = $yearList[$index];
                    $periods[$periodYear . '-' . $hit['month']] = [
                        'month' => $hit['month'],
                        'year' => $periodYear,
                    ];
                }
            } elseif (count($uniqueYears) === 1) {
                $sharedYear = $uniqueYears[0];
                foreach ($hits as $hit) {
                    $periods[$sharedYear . '-' . $hit['month']] = [
                        'month' => $hit['month'],
                        'year' => $sharedYear,
                    ];
                }
            } elseif ($yearList === [] && $periods === []) {
                $sharedYear = $this->defaultYear();
                foreach ($hits as $hit) {
                    $periods[$sharedYear . '-' . $hit['month']] = [
                        'month' => $hit['month'],
                        'year' => $sharedYear,
                    ];
                }
            }
        }

        $list = array_values($periods);
        usort($list, $this->chronological(...));

        return $list;
    }

    private function defaultYear(): int
    {
        return $this->defaultYear ?? (int) date('Y');
    }

    private function normalizeYear(int $year): int
    {
        if ($year >= 100) {
            return $year;
        }

        return $year >= 70 ? 1900 + $year : 2000 + $year;
    }

    /** @param array{month: int, year: int} $left @param array{month: int, year: int} $right */
    private function chronological(array $left, array $right): int
    {
        return ($left['year'] * 12 + $left['month']) <=> ($right['year'] * 12 + $right['month']);
    }
}
