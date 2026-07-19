<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class EntrevistaComunicacaoPreflightContractTest extends TestCase
{
    public function testCliScriptIsDryRunByDefaultAndHasNoSmtp(): void
    {
        $script = $this->readProjectFile('scripts/rh_entrevista_comunicacoes_preflight.php');

        self::assertStringContainsString('PHP_SAPI !== \'cli\'', $script);
        self::assertStringContainsString('RH_ENTREVISTA_PREFLIGHT_APPLY', $script);
        self::assertStringContainsString('--apply', $script);
        self::assertStringNotContainsString('SendEmailService', $script);
    }

    public function testServiceNeverMentionsSendEmail(): void
    {
        $service = $this->readProjectFile(
            'app/adms/Models/Services/RhEntrevistaComunicacaoPreflightService.php'
        );

        self::assertStringContainsString('STATUS_READY', $service);
        self::assertStringContainsString('STATUS_BLOCKED', $service);
        self::assertStringContainsString('template_version anterior à versão SMTP (v2)', $service);
        self::assertStringNotContainsString('SendEmailService', $service);
        self::assertStringNotContainsString('mail(', $service);
    }

    public function testRepositorySupportsPreflightBatch(): void
    {
        $repo = $this->readProjectFile(
            'app/adms/Models/Repository/RhEntrevistaComunicacoesRepository.php'
        );

        self::assertStringContainsString('listRecordedForPreflight', $repo);
        self::assertStringContainsString('updateStatus', $repo);
        self::assertStringContainsString('STATUS_READY', $repo);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
