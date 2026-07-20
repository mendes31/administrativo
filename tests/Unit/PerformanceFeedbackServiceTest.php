<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\PerformanceFeedbackService;
use PHPUnit\Framework\TestCase;

final class PerformanceFeedbackServiceTest extends TestCase
{
    public function testRejectsEmptyText(): void
    {
        $service = new PerformanceFeedbackService();
        $result = $service->validatePayload([
            'employee_id' => 1,
            'feedback_type' => 'general',
            'feedback_text' => '   ',
        ]);
        self::assertFalse($result['ok']);
        self::assertStringContainsString('Texto', $result['error'] ?? '');
    }

    public function testRejectsInvalidType(): void
    {
        $service = new PerformanceFeedbackService();
        $result = $service->validatePayload([
            'employee_id' => 1,
            'feedback_type' => 'xyz',
            'feedback_text' => 'ok',
        ]);
        self::assertFalse($result['ok']);
        self::assertStringContainsString('Tipo', $result['error'] ?? '');
    }

    public function testPrivateVisibility(): void
    {
        $service = new PerformanceFeedbackService();
        $feedback = [
            'employee_id' => 10,
            'given_by' => 20,
            'is_public' => 0,
            'is_anonymous' => 0,
            'given_by_name' => 'Autor',
        ];
        self::assertTrue($service->canView($feedback, 10, false));
        self::assertTrue($service->canView($feedback, 20, false));
        self::assertFalse($service->canView($feedback, 99, false));
        self::assertTrue($service->canView($feedback, 99, true));
    }

    public function testAnonymousDisplay(): void
    {
        $service = new PerformanceFeedbackService();
        $feedback = [
            'given_by' => 20,
            'is_anonymous' => 1,
            'given_by_name' => 'Maria',
        ];
        self::assertSame('Anônimo', $service->displayAuthorName($feedback, 10, false));
        self::assertStringContainsString('Maria', $service->displayAuthorName($feedback, 20, false));
        self::assertStringContainsString('Maria', $service->displayAuthorName($feedback, 1, true));
    }

    public function testOnlyAuthorCanEdit(): void
    {
        $service = new PerformanceFeedbackService();
        $feedback = ['given_by' => 20];
        self::assertTrue($service->canEdit($feedback, 20, false));
        self::assertFalse($service->canEdit($feedback, 10, false));
        self::assertTrue($service->canEdit($feedback, 10, true));
    }
}
