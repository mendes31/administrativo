<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Adapters;

use App\adms\Models\Services\InternalChat\ChatDynamicReportService;

/**
 * Normaliza o payload do relatório homologado para o núcleo analítico.
 * Não executa SQL: só deriva métrica a partir de linhas/gráfico já retornados.
 */
final class ApprovedReportResultNormalizer
{
    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    public function withMetric(
        array $result,
        string $functionalBinding,
        ?int $month,
        ?int $year
    ): array {
        if (empty($result['ok']) || !is_array($result['data'] ?? null)) {
            return $result;
        }

        $data = $result['data'];
        $data['source_type'] = 'approved_report';

        if ($year !== null) {
            $value = $this->extractNumericTotal($data);
            if ($value !== null) {
                $label = trim((string) ($data['name'] ?? 'Relatório'));
                $periodLabel = $month !== null
                    ? ChatDynamicReportService::monthYearLabel($month, $year)
                    : (string) $year;
                $metric = [
                    'binding' => $functionalBinding,
                    'label' => $label !== '' ? $label : 'Relatório',
                    'period_label' => $periodLabel,
                    'year' => $year,
                    'value' => $value,
                    'source_type' => 'approved_report',
                ];
                if ($month !== null) {
                    $metric['month'] = $month;
                }
                $data['metric'] = $metric;
            }
        }

        $result['data'] = $data;

        return $result;
    }

    /** @param array<string, mixed> $data */
    public function extractNumericTotal(array $data): ?float
    {
        $chart = $data['chart'] ?? null;
        if (is_array($chart) && is_array($chart['values'] ?? null) && $chart['values'] !== []) {
            $sum = 0.0;
            $count = 0;
            foreach ($chart['values'] as $value) {
                if (is_numeric($value)) {
                    $sum += (float) $value;
                    $count++;
                }
            }
            if ($count > 0) {
                return $sum;
            }
        }

        $rows = $data['rows'] ?? null;
        if (!is_array($rows) || $rows === [] || !is_array($rows[0] ?? null)) {
            return null;
        }

        $chart = ChatDynamicReportService::buildChartFromRows(
            array_values(array_filter($rows, 'is_array')),
            (string) ($data['visualization_type'] ?? 'table')
        );
        if ($chart === null || !is_array($chart['values'] ?? null) || $chart['values'] === []) {
            return null;
        }

        $sum = 0.0;
        foreach ($chart['values'] as $value) {
            $sum += (float) $value;
        }

        return $sum;
    }
}
