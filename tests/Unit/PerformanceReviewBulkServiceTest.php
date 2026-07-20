<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\PerformanceReviewBulkService;
use PHPUnit\Framework\TestCase;

final class PerformanceReviewBulkServiceTest extends TestCase
{
    public function testRejectsInvalidReviewType(): void
    {
        $service = new PerformanceReviewBulkService();
        $result = $service->validateOptions(['review_type' => 'xyz']);
        self::assertFalse($result['ok']);
        self::assertStringContainsString('Tipo', $result['error'] ?? '');
    }

    public function testRequiresDepartmentWhenScopeDepartment(): void
    {
        $service = new PerformanceReviewBulkService();
        $result = $service->validateOptions([
            'review_type' => '180',
            'scope' => 'department',
            'department_id' => 0,
        ]);
        self::assertFalse($result['ok']);
        self::assertStringContainsString('departamento', strtolower($result['error'] ?? ''));
    }

    public function testRequiresFixedReviewer(): void
    {
        $service = new PerformanceReviewBulkService();
        $result = $service->validateOptions([
            'review_type' => '180',
            'scope' => 'all_active',
            'reviewer_mode' => 'fixed',
            'reviewer_id' => 0,
        ]);
        self::assertFalse($result['ok']);
        self::assertStringContainsString('avaliador', strtolower($result['error'] ?? ''));
    }

    public function testAcceptsValidOptions(): void
    {
        $service = new PerformanceReviewBulkService();
        $result = $service->validateOptions([
            'review_type' => '360',
            'scope' => 'manager',
            'manager_id' => 5,
            'reviewer_mode' => 'supervisor',
            'skip_existing' => '1',
        ]);
        self::assertTrue($result['ok']);
        self::assertSame('360', $result['data']['review_type'] ?? null);
        self::assertTrue($result['data']['skip_existing'] ?? false);
    }
}
