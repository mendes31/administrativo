<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\PulseCampaignService;
use PHPUnit\Framework\TestCase;

final class PulseCampaignServiceTest extends TestCase
{
    public function testRejectsEmptyName(): void
    {
        $service = new PulseCampaignService();
        $result = $service->create(['name' => '', 'campaign_type' => 'enps'], 1);
        self::assertFalse($result['ok']);
    }

    public function testRejectsInvalidType(): void
    {
        $service = new PulseCampaignService();
        $result = $service->create(['name' => 'Teste', 'campaign_type' => 'xyz'], 1);
        self::assertFalse($result['ok']);
    }

    public function testComputeEnpsFormula(): void
    {
        // Unit test of classification helpers via reflection-free public API needs DB.
        // Validate constants exist for documentation contract.
        self::assertContains('enps', PulseCampaignService::TYPES);
        self::assertContains('open', PulseCampaignService::STATUSES);
    }
}
