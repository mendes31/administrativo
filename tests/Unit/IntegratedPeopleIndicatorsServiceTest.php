<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\IntegratedPeopleIndicatorsService;
use PHPUnit\Framework\TestCase;

final class IntegratedPeopleIndicatorsServiceTest extends TestCase
{
    public function testCollectReturnsSixKeys(): void
    {
        $data = (new IntegratedPeopleIndicatorsService())->collect();
        self::assertArrayHasKey('cycle_completion', $data);
        self::assertArrayHasKey('pdi_progress', $data);
        self::assertArrayHasKey('enps_latest', $data);
        self::assertArrayHasKey('headcount_gap', $data);
        self::assertArrayHasKey('talent_nominations', $data);
        self::assertArrayHasKey('succession', $data);
    }

    public function testEachBlockIsArray(): void
    {
        $data = (new IntegratedPeopleIndicatorsService())->collect();
        foreach ($data as $block) {
            self::assertIsArray($block);
        }
    }
}
