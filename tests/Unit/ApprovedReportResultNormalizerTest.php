<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\AiAnalyst\Adapters\ApprovedReportResultNormalizer;
use App\adms\Models\Services\AiAnalyst\Adapters\FakeReportRunner;
use PHPUnit\Framework\TestCase;

final class ApprovedReportResultNormalizerTest extends TestCase
{
    public function testAttachesMetricFromChartValuesForComparison(): void
    {
        $normalizer = new ApprovedReportResultNormalizer();
        $result = $normalizer->withMetric([
            'ok' => true,
            'resposta' => 'ok',
            'data' => [
                'name' => 'Vendas por mês',
                'visualization_type' => 'bar',
                'chart' => [
                    'type' => 'bar',
                    'labels' => ['A', 'B'],
                    'values' => [1000.5, 500.25],
                ],
                'rows' => [
                    ['Cliente' => 'A', 'Valor' => 1000.5],
                    ['Cliente' => 'B', 'Valor' => 500.25],
                ],
            ],
        ], FakeReportRunner::BINDING, 1, 2025);

        self::assertSame('approved_report', $result['data']['source_type']);
        self::assertSame(1500.75, $result['data']['metric']['value']);
        self::assertSame(FakeReportRunner::BINDING, $result['data']['metric']['binding']);
        self::assertSame('janeiro/2025', $result['data']['metric']['period_label']);
        self::assertSame('Vendas por mês', $result['data']['metric']['label']);
        self::assertSame('approved_report', $result['data']['metric']['source_type']);
    }

    public function testExtractsTotalFromRowsWhenChartIsMissing(): void
    {
        $normalizer = new ApprovedReportResultNormalizer();
        $value = $normalizer->extractNumericTotal([
            'name' => 'Vendas',
            'visualization_type' => 'table',
            'rows' => [
                ['Item' => 'X', 'Total' => 10],
                ['Item' => 'Y', 'Total' => 20],
            ],
        ]);

        self::assertSame(30.0, $value);
    }

    public function testSkipsMetricWhenNoNumericSeriesExists(): void
    {
        $normalizer = new ApprovedReportResultNormalizer();
        $result = $normalizer->withMetric([
            'ok' => true,
            'data' => [
                'name' => 'Lista',
                'rows' => [
                    ['Nome' => 'A'],
                    ['Nome' => 'B'],
                ],
            ],
        ], FakeReportRunner::BINDING, 6, 2026);

        self::assertSame('approved_report', $result['data']['source_type']);
        self::assertArrayNotHasKey('metric', $result['data']);
    }
}
