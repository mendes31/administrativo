<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Contrato do service transacional entrevista + pipeline (Fase 1).
 */
#[CoversNothing]
final class CandidaturaMovimentacaoServiceContractTest extends TestCase
{
    public function testServiceOwnsSingleTransactionBoundary(): void
    {
        $source = $this->readProjectFile(
            'app/adms/Models/Services/RhCandidaturaMovimentacaoService.php'
        );

        self::assertStringContainsString('function atualizarEntrevistaComReflexoPipeline', $source);
        self::assertStringContainsString('$ownsTransaction', $source);
        self::assertStringContainsString('beginTransaction()', $source);
        self::assertStringContainsString('atualizarStatusVinculo(', $source);
        self::assertStringContainsString('rollBack()', $source);
    }

    public function testStatusUpdateJoinsOuterTransactionAndSkipsInterviewResync(): void
    {
        $source = $this->readProjectFile(
            'app/adms/Models/Repository/RhVagasRepository.php'
        );

        $pos = strpos($source, 'function atualizarStatusVinculo');
        self::assertNotFalse($pos);
        $end = strpos($source, 'function getCandidatosByVaga', $pos);
        self::assertNotFalse($end);
        $block = substr($source, $pos, $end - $pos);

        self::assertStringContainsString('$ownsTransaction = !$pdo->inTransaction()', $block);
        self::assertStringContainsString('ORIGEM_ENTREVISTA', $block);
        self::assertStringContainsString(
            'Falha ao sincronizar resultado das entrevistas do vínculo.',
            $block
        );
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
