<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class McpChatAiAnalystContractTest extends TestCase
{
    public function testExistingAuthenticationAndPermissionGuardsRemainInPlace(): void
    {
        $source = $this->controllerSource();

        self::assertStringContainsString("empty(\$_SESSION['user_id'])", $source);
        self::assertStringContainsString("buttonPermission(['McpChat'])", $source);
        self::assertStringContainsString("in_array('McpChat', \$perms, true)", $source);
    }

    public function testTransparentRoutingDefaultsToAutoWhenAiIsEnabled(): void
    {
        $source = $this->controllerSource();

        self::assertStringContainsString("\$payload['mode'] ?? ''", $source);
        self::assertStringContainsString('modeSelectorEnabled()', $source);
        self::assertStringContainsString("\$requestedMode = 'auto'", $source);
        self::assertStringContainsString("\$requestedMode !== 'legacy'", $source);
        self::assertStringContainsString(
            '$result = $agent->handle($message, $authContext);',
            $source
        );
    }

    public function testExternalMcpRequestStillContainsOnlyTheMessage(): void
    {
        $source = str_replace("\r\n", "\n", $this->controllerSource());

        self::assertStringContainsString(
            "\$requestBody = json_encode([\n            'message' => \$message,\n        ]",
            $source
        );
    }

    public function testLocalLegacyResponseContractRemainsAvailable(): void
    {
        $source = $this->controllerSource();
        $localBranch = strpos($source, "strcasecmp(\$endpoint, 'local:internal')");
        $legacyAgent = strpos($source, 'new \\App\\adms\\Models\\Services\\InternalChat\\LocalInternalChatAgent');
        $externalRequest = strpos($source, '$requestBody = json_encode([');

        self::assertNotFalse($localBranch);
        self::assertNotFalse($legacyAgent);
        self::assertNotFalse($externalRequest);
        self::assertGreaterThan($localBranch, $legacyAgent);
        self::assertGreaterThan($legacyAgent, $externalRequest);
        self::assertStringContainsString("'mode' => 'local:internal'", $source);
    }

    public function testAnalyticBranchSelectsTheReportRunnerInsteadOfCallingSapDirectly(): void
    {
        $source = $this->controllerSource();

        self::assertStringContainsString('new AiReportRunnerSelector()', $source);
        self::assertStringContainsString('CatalogDescribeTableTool', $source);
        self::assertStringContainsString('AiColumnsResolverSelector', $source);
        self::assertStringNotContainsString('ChatDynamicReportService', $source);
        self::assertStringNotContainsString('SapReportApiService', $source);
    }

    private function controllerSource(): string
    {
        $source = file_get_contents(
            PROJECT_ROOT . '/app/adms/Controllers/settings/McpChatApi.php'
        );

        self::assertNotFalse($source, 'Nao foi possivel ler McpChatApi.php');

        return $source;
    }
}
