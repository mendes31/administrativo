<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\AiAnalyst\Feature\AiAnalystFeatureFlags;
use PHPUnit\Framework\TestCase;

final class AiAnalystFeatureFlagsTest extends TestCase
{
    public function testAllFlagsAreFailClosedWhenConfigurationIsMissing(): void
    {
        $flags = new AiAnalystFeatureFlags();

        self::assertFalse($flags->enabled());
        self::assertFalse($flags->businessEnabled());
        self::assertFalse($flags->technicalEnabled());
        self::assertFalse($flags->plannerEnabled());
        self::assertFalse($flags->toolExecutionEnabled());
        self::assertFalse($flags->realReportsEnabled());
        self::assertFalse($flags->modeSelectorEnabled());
        self::assertFalse($flags->hanaMetadataEnabled());
    }

    public function testCapabilitiesRequireTheMasterFlag(): void
    {
        $flags = new AiAnalystFeatureFlags([
            'AI_ANALYST_BUSINESS_ENABLED' => 'true',
            'AI_ANALYST_TECHNICAL_ENABLED' => 'true',
            'AI_ANALYST_PLANNER_ENABLED' => 'true',
            'AI_ANALYST_TOOL_EXECUTION_ENABLED' => 'true',
            'AI_ANALYST_REAL_REPORTS_ENABLED' => 'true',
            'AI_ANALYST_MODE_SELECTOR_ENABLED' => 'true',
            'AI_ANALYST_HANA_METADATA_ENABLED' => 'true',
        ]);

        self::assertFalse($flags->businessEnabled());
        self::assertFalse($flags->technicalEnabled());
        self::assertFalse($flags->plannerEnabled());
        self::assertFalse($flags->toolExecutionEnabled());
        self::assertFalse($flags->realReportsEnabled());
        self::assertFalse($flags->modeSelectorEnabled());
        self::assertFalse($flags->hanaMetadataEnabled());
    }

    public function testBooleanValuesAreParsedStrictly(): void
    {
        $flags = new AiAnalystFeatureFlags([
            'AI_ANALYST_ENABLED' => 'yes',
            'AI_ANALYST_BUSINESS_ENABLED' => '1',
            'AI_ANALYST_TECHNICAL_ENABLED' => true,
            'AI_ANALYST_PLANNER_ENABLED' => 'invalid',
            'AI_ANALYST_TOOL_EXECUTION_ENABLED' => 'off',
            'AI_ANALYST_REAL_REPORTS_ENABLED' => 'false',
            'AI_ANALYST_MODE_SELECTOR_ENABLED' => '1',
            'AI_ANALYST_HANA_METADATA_ENABLED' => 'false',
        ]);

        self::assertTrue($flags->enabled());
        self::assertTrue($flags->businessEnabled());
        self::assertTrue($flags->technicalEnabled());
        self::assertFalse($flags->plannerEnabled());
        self::assertFalse($flags->toolExecutionEnabled());
        self::assertFalse($flags->realReportsEnabled());
        self::assertTrue($flags->modeSelectorEnabled());
        self::assertFalse($flags->hanaMetadataEnabled());
    }
}
