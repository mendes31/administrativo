<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Tools;

use App\adms\Models\Services\AiAnalyst\Auth\AiActorContext;
use App\adms\Models\Services\AiAnalyst\Policy\PolicyDecision;

/**
 * Compara 2–5 resultados já obtidos e monta análise numérica.
 * Não consulta SAP nem escolhe relatório.
 */
final class ComparePeriodsTool implements AiToolInterface
{
    private const MAX_PERIODS = 5;

    public function definition(): AiToolDefinition
    {
        return new AiToolDefinition(
            'analysis.compare',
            'Compara 2 a 5 passos anteriores de report.run_approved, calcula variações e resume a tendência.',
            ['business'],
            [],
            [
                'step_ids' => 'array',
                'left' => 'string',
                'right' => 'string',
            ]
        );
    }

    public function authorize(AiActorContext $actor, string $profile, array $arguments): PolicyDecision
    {
        if (!in_array($profile, $this->definition()->profiles, true)) {
            return PolicyDecision::deny('Perfil sem permissão para comparar períodos.');
        }
        if ($actor->userId < 1) {
            return PolicyDecision::deny('Usuário inválido para comparar períodos.');
        }

        $stepIds = $this->stepIds($arguments);
        if ($stepIds === null) {
            return PolicyDecision::deny(
                'A comparação exige step_ids (2 a ' . self::MAX_PERIODS . ') ou left/right de passos anteriores.'
            );
        }

        return PolicyDecision::allow();
    }

    public function execute(AiToolExecutionContext $context, array $arguments): AiToolResult
    {
        $stepIds = $this->stepIds($arguments);
        if ($stepIds === null) {
            return new AiToolResult(false, 'Não há identificadores de períodos válidos para comparar.');
        }

        $metrics = [];
        foreach ($stepIds as $stepId) {
            $result = $context->resultOf($stepId);
            $metric = $result !== null ? $this->metric($result) : null;
            if ($metric === null) {
                return new AiToolResult(
                    false,
                    'Não há resultado numérico no passo «' . $stepId . '» para comparar.'
                );
            }
            $metrics[] = $metric;
        }

        $binding = $metrics[0]['binding'];
        if ($binding === '') {
            return new AiToolResult(false, 'A comparação exige binding funcional nos resultados.');
        }
        foreach ($metrics as $metric) {
            if ($metric['binding'] !== $binding) {
                return new AiToolResult(false, 'A comparação exige a mesma série em todos os períodos.');
            }
        }

        $simulated = false;
        foreach ($metrics as $metric) {
            if ($metric['source_type'] === 'simulated_report') {
                $simulated = true;
                break;
            }
        }

        $label = $metrics[0]['label'] !== '' ? $metrics[0]['label'] : 'Resultado';
        $rows = [];
        $labels = [];
        $values = [];
        $evidence = [];
        $previous = null;
        foreach ($metrics as $metric) {
            $absolute = null;
            $percent = null;
            if ($previous !== null) {
                $absolute = $metric['value'] - $previous['value'];
                $percent = abs($previous['value']) > 0.00001
                    ? ($absolute / $previous['value']) * 100
                    : null;
            }
            $rows[] = $this->row($metric['period_label'], $metric['value'], $absolute, $percent);
            $labels[] = $metric['period_label'];
            $values[] = $metric['value'];
            $evidence[] = [
                'label' => $label . ' — ' . $metric['period_label'],
                'source_type' => $metric['source_type'],
                'source_id' => $metric['binding'],
            ];
            $previous = $metric;
        }

        $analysis = $this->analysis($label, $metrics, $simulated);

        return new AiToolResult(true, $analysis, [
            'name' => $label,
            'source_type' => $simulated ? 'simulated_comparison' : 'comparison',
            'analysis' => [
                'period_count' => count($metrics),
                'best_period' => $this->extreme($metrics, true)['period_label'],
                'worst_period' => $this->extreme($metrics, false)['period_label'],
                'first_to_last_absolute' => $metrics[array_key_last($metrics)]['value'] - $metrics[0]['value'],
            ],
            'rows' => $rows,
            'rows_display' => array_map(fn (array $row): array => [
                'Período' => $row['Período'],
                'Valor' => $this->money((float) $row['Valor']),
                'Variação' => $row['Variação'] === null ? '' : $this->money((float) $row['Variação']),
                'Variação %' => $row['Variação %'] === null ? '' : $this->percent((float) $row['Variação %']),
            ], $rows),
            'chart' => [
                'type' => 'bar',
                'title' => $label,
                'labels' => $labels,
                'values' => $values,
            ],
            'evidence' => $evidence,
        ]);
    }

    /**
     * @param array<string, mixed> $arguments
     * @return list<string>|null
     */
    private function stepIds(array $arguments): ?array
    {
        $raw = $arguments['step_ids'] ?? null;
        if (is_array($raw) && array_is_list($raw) && $raw !== []) {
            $ids = [];
            foreach ($raw as $item) {
                if (!is_string($item) || !$this->isStepId($item)) {
                    return null;
                }
                $ids[] = $item;
            }
        } else {
            $left = $arguments['left'] ?? null;
            $right = $arguments['right'] ?? null;
            if (!is_string($left) || !is_string($right) || !$this->isStepId($left) || !$this->isStepId($right)) {
                return null;
            }
            $ids = [$left, $right];
        }

        $unique = array_values(array_unique($ids));
        if (count($unique) !== count($ids)) {
            return null;
        }
        if (count($ids) < 2 || count($ids) > self::MAX_PERIODS) {
            return null;
        }

        return $ids;
    }

    /** @return array{binding: string, label: string, period_label: string, value: float, source_type: string}|null */
    private function metric(AiToolResult $result): ?array
    {
        if (!$result->success || !is_array($result->data['metric'] ?? null)) {
            return null;
        }

        $metric = $result->data['metric'];
        if (!isset($metric['value']) || !is_numeric($metric['value']) || !is_string($metric['period_label'] ?? null)) {
            return null;
        }

        return [
            'binding' => trim((string) ($metric['binding'] ?? '')),
            'label' => trim((string) ($metric['label'] ?? '')),
            'period_label' => trim((string) $metric['period_label']),
            'value' => (float) $metric['value'],
            'source_type' => trim((string) ($metric['source_type'] ?? '')),
        ];
    }

    /** @return array{Período: string, Valor: float, Variação: float|null, Variação %: float|null} */
    private function row(string $period, float $value, ?float $absolute, ?float $percent): array
    {
        return [
            'Período' => $period,
            'Valor' => $value,
            'Variação' => $absolute,
            'Variação %' => $percent,
        ];
    }

    /**
     * @param list<array{binding: string, label: string, period_label: string, value: float, source_type: string}> $metrics
     */
    private function analysis(string $label, array $metrics, bool $simulated): string
    {
        $parts = [];
        $parts[] = $label . ' em ' . count($metrics) . ' períodos:';

        foreach ($metrics as $index => $metric) {
            $line = '• ' . $metric['period_label'] . ': ' . $this->money($metric['value']);
            if ($index > 0) {
                $prev = $metrics[$index - 1];
                $absolute = $metric['value'] - $prev['value'];
                $percent = abs($prev['value']) > 0.00001 ? ($absolute / $prev['value']) * 100 : null;
                if (abs($absolute) < 0.005) {
                    $line .= ' (estável vs ' . $prev['period_label'] . ')';
                } else {
                    $direction = $absolute > 0 ? 'acima' : 'abaixo';
                    $line .= ' (' . $this->money(abs($absolute)) . ' ' . $direction
                        . ' de ' . $prev['period_label']
                        . ($percent === null ? '' : ', ' . $this->percent($percent))
                        . ')';
                }
            }
            $parts[] = $line;
        }

        $best = $this->extreme($metrics, true);
        $worst = $this->extreme($metrics, false);
        $parts[] = 'Maior valor: ' . $best['period_label'] . ' (' . $this->money($best['value']) . '). '
            . 'Menor valor: ' . $worst['period_label'] . ' (' . $this->money($worst['value']) . ').';

        $first = $metrics[0];
        $last = $metrics[array_key_last($metrics)];
        $span = $last['value'] - $first['value'];
        if (abs($span) < 0.005) {
            $parts[] = 'Do primeiro ao último período o valor ficou estável.';
        } else {
            $direction = $span > 0 ? 'crescimento' : 'queda';
            $percent = abs($first['value']) > 0.00001 ? ($span / $first['value']) * 100 : null;
            $parts[] = 'Do primeiro ao último período houve ' . $direction . ' de '
                . $this->money(abs($span))
                . ($percent === null ? '' : ' (' . $this->percent($percent) . ')')
                . '.';
        }

        if ($simulated) {
            $parts[] = 'Os valores são simulados e não vêm do SAP.';
        }

        return implode("\n", $parts);
    }

    /**
     * @param list<array{binding: string, label: string, period_label: string, value: float, source_type: string}> $metrics
     * @return array{binding: string, label: string, period_label: string, value: float, source_type: string}
     */
    private function extreme(array $metrics, bool $best): array
    {
        $pick = $metrics[0];
        foreach ($metrics as $metric) {
            if ($best ? $metric['value'] > $pick['value'] : $metric['value'] < $pick['value']) {
                $pick = $metric;
            }
        }

        return $pick;
    }

    private function money(float $value): string
    {
        $prefix = $value < 0 ? '-R$ ' : 'R$ ';

        return $prefix . number_format(abs($value), 2, ',', '.');
    }

    private function percent(float $value): string
    {
        return number_format($value, 1, ',', '.') . '%';
    }

    private function isStepId(string $value): bool
    {
        return preg_match('/^[a-z][a-z0-9_-]{0,30}$/', $value) === 1;
    }
}
