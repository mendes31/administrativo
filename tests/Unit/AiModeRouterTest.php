<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\AiAnalyst\Auth\AiActorContext;
use App\adms\Models\Services\AiAnalyst\Feature\AiAnalystFeatureFlags;
use App\adms\Models\Services\AiAnalyst\Routing\AiModeRouter;
use PHPUnit\Framework\TestCase;

final class AiModeRouterTest extends TestCase
{
    public function testLegacyModeRemainsAvailableWithAllFlagsDisabled(): void
    {
        $decision = (new AiModeRouter(new AiAnalystFeatureFlags()))
            ->decide('legacy', $this->actor());

        self::assertTrue($decision->allowed);
        self::assertSame('legacy', $decision->effectiveMode);
    }

    public function testBusinessModeRequiresMasterAndProfileFlags(): void
    {
        $disabled = (new AiModeRouter(new AiAnalystFeatureFlags([
            'AI_ANALYST_BUSINESS_ENABLED' => true,
        ])))->decide('business', $this->actor());
        $enabled = (new AiModeRouter(new AiAnalystFeatureFlags([
            'AI_ANALYST_ENABLED' => true,
            'AI_ANALYST_BUSINESS_ENABLED' => true,
        ])))->decide('business', $this->actor());

        self::assertFalse($disabled->allowed);
        self::assertTrue($enabled->allowed);
        self::assertSame('business', $enabled->effectiveMode);
    }

    public function testTechnicalModeRequiresFullSystemAccess(): void
    {
        $flags = new AiAnalystFeatureFlags([
            'AI_ANALYST_ENABLED' => true,
            'AI_ANALYST_TECHNICAL_ENABLED' => true,
        ]);
        $router = new AiModeRouter($flags);

        self::assertFalse($router->decide('technical', $this->actor(false))->allowed);
        self::assertTrue($router->decide('technical', $this->actor(true))->allowed);
    }

    public function testUnknownModeIsDeniedFailClosed(): void
    {
        $decision = (new AiModeRouter(new AiAnalystFeatureFlags([
            'AI_ANALYST_ENABLED' => true,
        ])))->decide('unexpected', $this->actor(true));

        self::assertFalse($decision->allowed);
        self::assertSame('legacy', $decision->effectiveMode);
    }

    private function actor(bool $fullSystemAccess = false): AiActorContext
    {
        return new AiActorContext(10, $fullSystemAccess, ['report.list', 'report.run']);
    }
}
