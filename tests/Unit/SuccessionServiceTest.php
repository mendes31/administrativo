<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\SuccessionService;
use PHPUnit\Framework\TestCase;

final class SuccessionServiceTest extends TestCase
{
    public function testRejectsMissingPosition(): void
    {
        $service = new SuccessionService();
        $result = $service->createCritical(['position_id' => 0], 1);
        self::assertFalse($result['ok']);
        self::assertStringContainsString('Cargo', $result['error'] ?? '');
    }

    public function testRejectsInvalidRiskWithoutDbHitOnPositionZero(): void
    {
        $service = new SuccessionService();
        $result = $service->createCritical([
            'position_id' => 0,
            'risk_level' => 'ultra',
        ], 1);
        self::assertFalse($result['ok']);
        // Falha primeiro no cargo obrigatório (antes do risco / DB).
        self::assertStringContainsString('Cargo', $result['error'] ?? '');
    }

    public function testReadinessConstants(): void
    {
        self::assertContains('ready_now', SuccessionService::READINESS);
        self::assertContains('emergency', SuccessionService::READINESS);
        self::assertContains('high', SuccessionService::RISK_LEVELS);
    }
}
