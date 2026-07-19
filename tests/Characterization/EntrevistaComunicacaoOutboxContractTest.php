<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class EntrevistaComunicacaoOutboxContractTest extends TestCase
{
    public function testMigrationCreatesOutboxAndComunicacoes(): void
    {
        $source = $this->readProjectFile(
            'database/migrations/20260719200000_create_domain_event_outbox_and_rh_entrevista_comunicacoes.php'
        );

        self::assertStringContainsString('adms_domain_event_outbox', $source);
        self::assertStringContainsString('rh_entrevista_comunicacoes', $source);
        self::assertStringContainsString('idempotency_key', $source);
        self::assertStringContainsString('recorded', $source);
    }

    public function testServiceEnqueuesWithoutSendingEmail(): void
    {
        $service = $this->readProjectFile(
            'app/adms/Models/Services/RhCandidaturaMovimentacaoService.php'
        );

        self::assertStringContainsString('DomainEventOutboxRepository', $service);
        self::assertStringContainsString('EntrevistaAgendada', $service);
        self::assertStringContainsString('EntrevistaReagendada', $service);
        self::assertStringContainsString('RhEntrevistaComunicacoesRepository', $service);
        self::assertStringContainsString('criarEntrevista', $service);
        self::assertStringNotContainsString('SendEmailService', $service);
    }

    public function testCreateUsesTransactionalServiceAndViewListsComunicacoes(): void
    {
        $create = $this->readProjectFile('app/adms/Controllers/rh/RhEntrevistasCreate.php');
        self::assertStringContainsString('criarEntrevista', $create);

        $view = $this->readProjectFile('app/adms/Views/rh/entrevistas/view.php');
        self::assertStringContainsString('Histórico de comunicações', $view);
        self::assertStringContainsString('Envio automático ainda não habilitado', $view);
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
