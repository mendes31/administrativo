<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\TestCase;

final class AiAnalystIsolationContractTest extends TestCase
{
    public function testAiAnalystHasNoDirectSapSqlApiDependency(): void
    {
        $root = PROJECT_ROOT . '/app/adms/Models/Services/AiAnalyst';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        $source = '';
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $source .= (string) file_get_contents($file->getPathname());
            }
        }

        self::assertStringNotContainsString('SapReportApiService', $source);
        self::assertStringNotContainsString('sql.execute', $source);
        self::assertStringNotContainsString('hana.query', $source);
        self::assertStringContainsString('ChatDynamicReportService', $source);
    }

    public function testCurrentEndpointDefaultsToLegacyAndKeepsBothExistingTransports(): void
    {
        $source = (string) file_get_contents(
            PROJECT_ROOT . '/app/adms/Controllers/settings/McpChatApi.php'
        );

        self::assertStringContainsString("\$payload['mode'] ?? ''", $source);
        self::assertStringContainsString("\$requestedMode = 'auto'", $source);
        self::assertStringContainsString("strcasecmp(\$endpoint, 'local:internal')", $source);
        self::assertStringContainsString('curl_init($endpoint)', $source);
    }
}
