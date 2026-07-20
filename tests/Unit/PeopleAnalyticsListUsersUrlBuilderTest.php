<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\PeopleAnalyticsListUsersUrlBuilder;
use PHPUnit\Framework\TestCase;

final class PeopleAnalyticsListUsersUrlBuilderTest extends TestCase
{
    public function testActiveMapsDesligadoZeroAndClearsPeriod(): void
    {
        $q = PeopleAnalyticsListUsersUrlBuilder::queryParams([
            'period_start' => '2026-01-01',
            'period_end' => '2026-07-19',
            'sexo' => 'F',
            'departamento_ids' => [5],
            'cargo_ids' => [9],
        ], PeopleAnalyticsListUsersUrlBuilder::INTENT_ACTIVE);

        self::assertSame(PeopleAnalyticsListUsersUrlBuilder::FROM_VALUE, $q['from']);
        self::assertSame('0', $q['desligado']);
        self::assertSame('F', $q['sexo']);
        self::assertSame(5, $q['departamento_id']);
        self::assertSame(9, $q['cargo_id']);
        self::assertSame('', $q['periodo_tipo']);
        self::assertSame('', $q['data_de']);
        self::assertSame('', $q['status']);
    }

    public function testTerminationsMapsPeriodAndDesligado(): void
    {
        $q = PeopleAnalyticsListUsersUrlBuilder::queryParams([
            'period_start' => '2026-01-01',
            'period_end' => '2026-07-19',
            'departamento_ids' => [1, 2],
            'cargo_ids' => [],
        ], PeopleAnalyticsListUsersUrlBuilder::INTENT_TERMINATIONS);

        self::assertSame('desligamento', $q['periodo_tipo']);
        self::assertSame('1', $q['desligado']);
        self::assertSame('2026-01-01', $q['data_de']);
        self::assertSame('2026-07-19', $q['data_ate']);
        self::assertSame('', $q['departamento_id']);
    }

    public function testAdmissionsClearsDesligadoAndSetsPeriod(): void
    {
        $q = PeopleAnalyticsListUsersUrlBuilder::queryParams([
            'period_start' => '2026-01-01',
            'period_end' => '2026-07-19',
            'filhos' => 'S',
        ], PeopleAnalyticsListUsersUrlBuilder::INTENT_ADMISSIONS);

        self::assertSame('admissao', $q['periodo_tipo']);
        self::assertSame('', $q['desligado']);
        self::assertSame('S', $q['filhos']);
        self::assertSame('2026-01-01', $q['data_de']);
        self::assertSame('2026-07-19', $q['data_ate']);
        self::assertArrayHasKey('from', $q);
    }

    public function testUniverseOmitsStatusAndPeriodButSendsCleanSnapshot(): void
    {
        $q = PeopleAnalyticsListUsersUrlBuilder::queryParams([
            'period_start' => '2026-01-01',
            'period_end' => '2026-07-19',
            'departamento_ids' => [3],
        ], PeopleAnalyticsListUsersUrlBuilder::INTENT_UNIVERSE);

        self::assertSame(3, $q['departamento_id']);
        self::assertSame('', $q['desligado']);
        self::assertSame('', $q['periodo_tipo']);
        self::assertSame('', $q['data_de']);
        self::assertSame(PeopleAnalyticsListUsersUrlBuilder::FROM_VALUE, $q['from']);
    }

    public function testUrlBuildsQueryStringWithFromFlag(): void
    {
        $url = PeopleAnalyticsListUsersUrlBuilder::url(
            'http://localhost/adm/',
            [
                'period_start' => '2026-01-01',
                'period_end' => '2026-07-19',
            ],
            PeopleAnalyticsListUsersUrlBuilder::INTENT_ADMISSIONS
        );

        self::assertStringStartsWith('http://localhost/adm/list-users?', $url);
        self::assertStringContainsString('from=people-analytics', $url);
        self::assertStringContainsString('periodo_tipo=admissao', $url);
        self::assertStringContainsString('desligado=', $url);
        self::assertStringNotContainsString('desligado=1', $url);
    }
}
