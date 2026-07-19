<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class EntrevistaComunicacaoWorkerContractTest extends TestCase
{
    public function testWorkerCliIsDryRunByDefaultAndDoubleGated(): void
    {
        $script = $this->readProjectFile('scripts/rh_entrevista_comunicacoes_worker.php');

        self::assertStringContainsString("PHP_SAPI !== 'cli'", $script);
        self::assertStringContainsString('isSendEnabled', $script);
        self::assertStringContainsString('--send', $script);
        self::assertStringContainsString('$send = false', $script);
    }

    public function testSendToggleIsSpecificAndStoredInEmailConfig(): void
    {
        $service = $this->readProjectFile(
            'app/adms/Models/Services/RhEntrevistaComunicacaoWorkerService.php'
        );
        self::assertStringContainsString('isRhEntrevistaSendEnabled', $service);

        $repo = $this->readProjectFile(
            'app/adms/Models/Repository/AdmsEmailConfigRepository.php'
        );
        self::assertStringContainsString('rh_entrevista_send_enabled', $repo);

        $migration = $this->readProjectFile(
            'database/migrations/20260719233000_add_rh_entrevista_send_toggle_to_adms_email_config.php'
        );
        self::assertStringContainsString("'default' => 0", $migration);

        $view = $this->readProjectFile('app/adms/Views/settings/emailConfig.php');
        self::assertStringContainsString('RH_ENTREVISTA_SEND_ENABLED', $view);
    }

    public function testWorkerUsesExistingSmtpAndNonProductionRecipient(): void
    {
        $service = $this->readProjectFile(
            'app/adms/Models/Services/RhEntrevistaComunicacaoWorkerService.php'
        );

        self::assertStringContainsString('SendEmailService::sendEmail', $service);
        self::assertStringContainsString('AppEnvironmentHelper::isNonProduction', $service);
        self::assertStringContainsString('test_recipient', $service);
        self::assertStringContainsString('template_version', $service);
        self::assertStringContainsString('uncertain', $service);
    }

    public function testRepositoryClaimsAndFinishesBothRecordsAtomically(): void
    {
        $repo = $this->readProjectFile(
            'app/adms/Models/Repository/RhEntrevistaComunicacoesRepository.php'
        );

        self::assertStringContainsString('listReadyForWorker', $repo);
        self::assertStringContainsString('claimForProcessing', $repo);
        self::assertStringContainsString('WHERE id = :id AND status = :ready', $repo);
        self::assertStringContainsString('markSent', $repo);
        self::assertStringContainsString('markFailed', $repo);
        self::assertStringContainsString('$pdo->beginTransaction()', $repo);
    }

    public function testMigrationUpgradesOnlyUnsentTemplateSnapshots(): void
    {
        $migration = $this->readProjectFile(
            'database/migrations/20260719232000_prepare_rh_entrevista_email_worker.php'
        );

        self::assertStringContainsString('template_version = 2', $migration);
        self::assertStringContainsString(
            "status IN ('recorded', 'ready', 'blocked')",
            $migration
        );
        self::assertStringContainsString('idx_rh_entrevista_comunicacoes_worker', $migration);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
