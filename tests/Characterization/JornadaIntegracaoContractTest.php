<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class JornadaIntegracaoContractTest extends TestCase
{
    public function testServicePublishesOutboxAndLnt(): void
    {
        $service = $this->readProjectFile(
            'app/adms/Models/Services/RhJornadaIntegracaoService.php'
        );
        self::assertStringContainsString('jornada.ColaboradorAdmitido', $service);
        self::assertStringContainsString('jornada.LotacaoAlterada', $service);
        self::assertStringContainsString('jornada.ColaboradorDesligado', $service);
        self::assertStringContainsString('TrainingLntEventService', $service);
        self::assertStringContainsString('DomainEventOutboxRepository', $service);
    }

    public function testProducersCallIntegracao(): void
    {
        foreach ([
            'app/adms/Models/Services/RhConversaoAdmissaoService.php' => 'aposAdmissao',
            'app/adms/Models/Services/RhMovimentacaoService.php' => 'aposMovimentacao',
            'app/adms/Models/Services/RhOffboardingService.php' => 'aposDesligamento',
        ] as $file => $method) {
            $src = $this->readProjectFile($file);
            self::assertStringContainsString('RhJornadaIntegracaoService', $src);
            self::assertStringContainsString($method, $src);
        }
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
