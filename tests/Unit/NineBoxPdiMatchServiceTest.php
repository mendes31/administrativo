<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\NineBoxPdiMatchService;
use PHPUnit\Framework\TestCase;

final class NineBoxPdiMatchServiceTest extends TestCase
{
    public function testGuidanceCoversAllBoxes(): void
    {
        for ($box = 1; $box <= 9; $box++) {
            $guide = NineBoxPdiMatchService::guidanceForBox($box);
            self::assertNotNull($guide);
            self::assertNotSame('', $guide['title']);
            self::assertNotSame('', $guide['description']);
        }
        self::assertNull(NineBoxPdiMatchService::guidanceForBox(0));
        self::assertNull(NineBoxPdiMatchService::guidanceForBox(10));
    }

    public function testBuildSuggestedPlanPayload(): void
    {
        $result = NineBoxPdiMatchService::buildSuggestedPlanPayload(
            15,
            3,
            9,
            [
                'name' => 'Ciclo 2026',
                'period_start' => '2026-01-01',
                'period_end' => '2026-12-31',
            ],
            7
        );

        self::assertTrue($result['ok']);
        self::assertSame(15, $result['data']['user_id']);
        self::assertSame(3, $result['data']['performance_cycle_id']);
        self::assertSame(7, $result['data']['manager_id']);
        self::assertSame('draft', $result['data']['status']);
        self::assertStringContainsString('Box 9', $result['data']['title']);
        self::assertStringContainsString('Ciclo 2026', $result['data']['title']);
        self::assertStringContainsString('9BOX', $result['data']['description']);
        self::assertSame('2026-01-01', $result['data']['period_start']);
    }

    public function testRejectsInvalidBoxInPayload(): void
    {
        $result = NineBoxPdiMatchService::buildSuggestedPlanPayload(
            1,
            1,
            99,
            ['period_start' => '2026-01-01', 'period_end' => '2026-12-31']
        );

        self::assertFalse($result['ok']);
        self::assertStringContainsString('Box', $result['error'] ?? '');
    }

    public function testRejectsCycleWithoutPeriod(): void
    {
        $result = NineBoxPdiMatchService::buildSuggestedPlanPayload(
            1,
            1,
            5,
            ['name' => 'Sem datas']
        );

        self::assertFalse($result['ok']);
        self::assertStringContainsString('período', mb_strtolower($result['error'] ?? ''));
    }
}
