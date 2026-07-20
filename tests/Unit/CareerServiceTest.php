<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\CareerService;
use PHPUnit\Framework\TestCase;

final class CareerServiceTest extends TestCase
{
    public function testRejectsTrackWithoutName(): void
    {
        $service = new CareerService();
        $result = $service->createTrack(['name' => ''], 1);
        self::assertFalse($result['ok']);
        self::assertStringContainsString('Nome', $result['error'] ?? '');
    }

    public function testRejectsPromotionWithoutUser(): void
    {
        $service = new CareerService();
        $result = $service->createPromotion([
            'user_id' => 0,
            'to_position_id' => 1,
            'effective_date' => '2026-07-20',
        ], 1);
        self::assertFalse($result['ok']);
        self::assertStringContainsString('Colaborador', $result['error'] ?? '');
    }

    public function testStatusConstants(): void
    {
        self::assertContains('applied', CareerService::PROMO_STATUSES);
        self::assertContains('active', CareerService::TRACK_STATUSES);
    }
}
