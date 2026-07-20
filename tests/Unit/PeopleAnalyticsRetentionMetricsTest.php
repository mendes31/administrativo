<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\PeopleAnalyticsMetricsService;
use PHPUnit\Framework\TestCase;

final class PeopleAnalyticsRetentionMetricsTest extends TestCase
{
    public function testRetentionRateIsComplementOfTurnover(): void
    {
        $svc = new PeopleAnalyticsMetricsService();
        $users = [
            [
                'id' => 1,
                'status' => 'Ativo',
                'data_admissao' => '2020-01-01',
                'data_desligamento' => null,
            ],
            [
                'id' => 2,
                'status' => 'Inativo',
                'data_admissao' => '2024-01-01',
                'data_desligamento' => '2024-02-01',
                'tipo_impacto_desligamento' => 'regrettable',
            ],
        ];
        $ret = $svc->computeRetention(
            $users,
            [],
            '2024-01-01',
            '2024-12-31',
            1,
            20.0,
            1,
            ['regrettable' => 1]
        );
        self::assertSame('80.00', $ret['retention_rate']);
        self::assertFalse($ret['costs_available']);
        self::assertSame(1, $ret['early_turnover_lt_90']['count']);
        self::assertSame(1, $ret['impact_quality']['regrettable']);
    }

    public function testTenureBands(): void
    {
        $svc = new PeopleAnalyticsMetricsService();
        $users = [
            [
                'id' => 1,
                'status' => 'Inativo',
                'data_admissao' => '2023-01-01',
                'data_desligamento' => '2024-06-01', // ~517 days -> d90_1y? 517 < 365? no -> y1_3y
            ],
            [
                'id' => 2,
                'status' => 'Inativo',
                'data_admissao' => '2024-01-01',
                'data_desligamento' => '2024-02-01', // ~31 days -> lt_90d
            ],
        ];
        $ret = $svc->computeRetention($users, [], '2024-01-01', '2024-12-31', 2, 10.0, 0, []);
        self::assertSame(1, $ret['termination_tenure_bands']['lt_90d']);
        self::assertSame(1, $ret['termination_tenure_bands']['y1_3y']);
    }
}
