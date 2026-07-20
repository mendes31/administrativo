<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\TalentNominationService;
use PHPUnit\Framework\TestCase;

final class TalentNominationServiceTest extends TestCase
{
    public function testRejectsMissingCycle(): void
    {
        $service = new TalentNominationService();
        $result = $service->validatePayload([
            'user_id' => 1,
            'performance_cycle_id' => 0,
        ]);

        self::assertFalse($result['ok']);
        self::assertStringContainsString('Ciclo', $result['error'] ?? '');
    }

    public function testRejectsInvalidNineBox(): void
    {
        $service = new TalentNominationService();
        $result = $service->validatePayload([
            'user_id' => 1,
            'performance_cycle_id' => 1,
            'nine_box' => 12,
        ], true);

        self::assertFalse($result['ok']);
        self::assertStringContainsString('Box', $result['error'] ?? '');
    }

    public function testAcceptsValidUpdatePayload(): void
    {
        $service = new TalentNominationService();
        $result = $service->validatePayload([
            'status' => 'active',
            'nine_box' => 9,
            'notes' => 'Estrela do ciclo',
        ], true);

        self::assertTrue($result['ok']);
        self::assertSame(9, $result['data']['nine_box']);
        self::assertSame('active', $result['data']['status']);
    }
}
