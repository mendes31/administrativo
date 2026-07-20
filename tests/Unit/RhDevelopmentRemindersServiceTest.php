<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\RhDevelopmentRemindersService;
use PHPUnit\Framework\TestCase;

final class RhDevelopmentRemindersServiceTest extends TestCase
{
    public function testDryRunDoesNotRequireSendToggles(): void
    {
        $svc = new RhDevelopmentRemindersService();
        $result = $svc->run(false, 10);
        self::assertTrue($result['dry_run']);
        self::assertFalse($result['disabled']);
        self::assertArrayHasKey('open_pulse_campaigns', $result['summary']);
        self::assertArrayHasKey('overdue_pdi_actions', $result['summary']);
        self::assertArrayHasKey('draft_reviews_open_cycle', $result['summary']);
        self::assertIsArray($result['summary']['recipients']);
    }

    public function testSendWithoutTogglesIsDisabled(): void
    {
        $svc = new RhDevelopmentRemindersService();
        $result = $svc->run(true, 5);
        // Com toggles default off, deve reportar disabled (ou sent=0 sem falha crítica)
        self::assertFalse($result['dry_run']);
        self::assertTrue($result['disabled'] || $result['sent'] === 0);
    }
}
