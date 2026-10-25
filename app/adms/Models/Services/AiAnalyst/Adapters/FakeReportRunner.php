<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Adapters;

/**
 * Série local de demonstração. Não consulta banco, SAP nem a API de relatórios.
 */
final class FakeReportRunner implements ApprovedReportRunnerInterface
{
    public const BINDING = 'sales_summary';

    /** @var array<int, string> */
    private const MONTHS = [
        1 => 'janeiro',
        2 => 'fevereiro',
        3 => 'março',
        4 => 'abril',
        5 => 'maio',
        6 => 'junho',
        7 => 'julho',
        8 => 'agosto',
        9 => 'setembro',
        10 => 'outubro',
        11 => 'novembro',
        12 => 'dezembro',
    ];

    /** @var array<int, array<int, float>> */
    private const SALES = [
        2026 => [
            1 => 120000.0,
            2 => 118500.0,
            3 => 132000.0,
            4 => 128400.0,
            5 => 141200.0,
            6 => 139800.0,
            7 => 150000.0,
            8 => 172500.0,
            9 => 168200.0,
            10 => 175400.0,
        ],
    ];

    public function usesLiveReports(): bool
    {
        return false;
    }

    public function listForUser(int $userId): array
    {
        if ($userId < 1) {
            return [];
        }

        return [[
            'binding' => self::BINDING,
            'name' => 'Vendas simuladas',
            'description' => 'Série local de demonstração, de janeiro a outubro de 2026. Não consulta SAP.',
            'examples' => ['Compare vendas de agosto e julho de 2026'],
            'visualization_type' => 'bar',
            'source_type' => 'simulated_report',
        ]];
    }

    public function run(int $userId, string $binding, ?int $month = null, ?int $year = null): array
    {
        if ($userId < 1) {
            return ['ok' => false, 'resposta' => 'Usuário inválido para a série simulada.'];
        }

        $binding = trim($binding);
        if ($binding === '' || preg_match('/^#?\d+$/', $binding) === 1 || $binding !== self::BINDING) {
            return ['ok' => false, 'resposta' => 'A série simulada disponível é sales_summary.'];
        }

        if ($month === null && $year !== null) {
            return $this->seriesForYear($year);
        }

        if ($month === null || $year === null) {
            return $this->series();
        }

        $value = self::SALES[$year][$month] ?? null;
        if ($value === null) {
            return [
                'ok' => false,
                'resposta' => 'A série simulada não tem ' . $this->periodLabel($month, $year)
                    . '. Períodos disponíveis: janeiro/2026 a outubro/2026.',
            ];
        }

        $label = $this->periodLabel($month, $year);
        $metric = $this->metric($month, $year, $value);

        return [
            'ok' => true,
            'resposta' => 'Vendas simuladas de ' . $label . ': ' . $this->money($value) . '. Este valor não vem do SAP.',
            'data' => [
                'name' => 'Vendas simuladas',
                'source_type' => 'simulated_report',
                'metric' => $metric,
                'rows' => [[
                    'Período' => $label,
                    'Valor' => $value,
                ]],
                'rows_display' => [[
                    'Período' => $label,
                    'Valor' => $this->money($value),
                ]],
                'chart' => [
                    'type' => 'bar',
                    'title' => 'Vendas simuladas',
                    'labels' => [$label],
                    'values' => [$value],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function series(): array
    {
        return $this->buildSeries(null);
    }

    /** @return array<string, mixed> */
    private function seriesForYear(int $year): array
    {
        if (!isset(self::SALES[$year]) || self::SALES[$year] === []) {
            return [
                'ok' => false,
                'resposta' => 'A série simulada não tem o ano ' . $year
                    . '. Períodos disponíveis: janeiro/2026 a outubro/2026.',
            ];
        }

        return $this->buildSeries($year);
    }

    /** @return array<string, mixed> */
    private function buildSeries(?int $onlyYear): array
    {
        $rows = [];
        $display = [];
        $labels = [];
        $values = [];
        foreach (self::SALES as $year => $months) {
            if ($onlyYear !== null && $year !== $onlyYear) {
                continue;
            }
            foreach ($months as $month => $value) {
                $label = $this->periodLabel($month, $year);
                $rows[] = ['Período' => $label, 'Valor' => $value];
                $display[] = ['Período' => $label, 'Valor' => $this->money($value)];
                $labels[] = $label;
                $values[] = $value;
            }
        }

        $scope = $onlyYear !== null
            ? 'do ano ' . $onlyYear
            : 'de janeiro/2026 a outubro/2026';

        $data = [
            'name' => 'Vendas simuladas',
            'source_type' => 'simulated_report',
            'rows' => $rows,
            'rows_display' => $display,
            'chart' => [
                'type' => 'bar',
                'title' => 'Vendas simuladas',
                'labels' => $labels,
                'values' => $values,
            ],
        ];
        if ($onlyYear !== null) {
            $data['filter_year'] = $onlyYear;
            if ($values !== []) {
                $data['metric'] = [
                    'binding' => self::BINDING,
                    'label' => 'Vendas simuladas',
                    'period_label' => (string) $onlyYear,
                    'year' => $onlyYear,
                    'value' => array_sum($values),
                    'source_type' => 'simulated_report',
                ];
            }
        }

        return [
            'ok' => true,
            'resposta' => 'Série simulada de vendas ' . $scope . '. Estes valores não vêm do SAP.',
            'data' => $data,
        ];
    }

    /** @return array<string, mixed> */
    private function metric(int $month, int $year, float $value): array
    {
        return [
            'binding' => self::BINDING,
            'label' => 'Vendas simuladas',
            'period_label' => $this->periodLabel($month, $year),
            'month' => $month,
            'year' => $year,
            'value' => $value,
            'source_type' => 'simulated_report',
        ];
    }

    private function periodLabel(int $month, int $year): string
    {
        $name = self::MONTHS[$month] ?? (string) $month;

        return $name . '/' . $year;
    }

    private function money(float $value): string
    {
        $prefix = $value < 0 ? '-R$ ' : 'R$ ';

        return $prefix . number_format(abs($value), 2, ',', '.');
    }
}
