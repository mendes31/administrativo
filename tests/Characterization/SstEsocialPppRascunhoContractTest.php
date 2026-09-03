<?php

declare(strict_types=1);

namespace Tests\Characterization;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Trava de rascunho: S-2240≠EPI, sem S-2245, PPP/eSocial rotulados não oficiais.
 */
#[CoversNothing]
final class SstEsocialPppRascunhoContractTest extends TestCase
{
    public function testApplyTreinamentoNaoGeraS2245(): void
    {
        $source = $this->readProjectFile('app/adms/Controllers/sst/SstApplyTreinamento.php');
        self::assertStringNotContainsString('SstEsocialPayloadService', $source);
        self::assertStringNotContainsString('EVENTO_TREINAMENTO', $source);
    }

    public function testSincronizacaoNaoPercorreEpiNemTreinamento(): void
    {
        $source = $this->readProjectFile('app/adms/Models/Services/SstEsocialPayloadService.php');
        self::assertStringContainsString('SstEsocialPolicy::assertGeracaoPermitida', $source);
        self::assertStringNotContainsString('adms_sst_epi_entregas', $source);
        self::assertStringNotContainsString('adms_sst_treinamento_aplicacoes', $source);
        self::assertStringNotContainsString('function buildS2240', $source);
        self::assertStringNotContainsString('function buildS2245', $source);
    }

    public function testTelasDeclaramNaoOficialEOcultamS2245(): void
    {
        $fila = $this->readProjectFile('app/adms/Views/sst/esocial_eventos/list.php');
        self::assertStringContainsString('Não oficial', $fila);
        self::assertStringContainsString('sst_rascunho_oficial_alert.php', $fila);

        $vinculo = $this->readProjectFile('app/adms/Views/sst/treinamento_vinculos/view.php');
        self::assertStringNotContainsString('S-2245', $vinculo);
        self::assertStringNotContainsString('sst-generate-esocial-evento', $vinculo);

        $ppp = $this->readProjectFile('app/adms/Views/sst/ppp/list.php');
        self::assertStringContainsString('Não oficial', $ppp);

        $pdf = $this->readProjectFile('app/adms/Controllers/sst/SstExportPppPdf.php');
        self::assertStringContainsString('NÃO OFICIAL', $pdf);
        self::assertStringContainsString('showWatermarkText', $pdf);
    }

    public function testAdrRegistraADecisao(): void
    {
        $adr = $this->readProjectFile('docs/08_ADR/ADR-0012_SST_ESOCIAL_PPP_RASCUNHO.md');
        self::assertStringContainsString('S-2240', $adr);
        self::assertStringContainsString('S-2245', $adr);
        self::assertStringContainsString('não oficiais', mb_strtolower($adr));
    }

    private function readProjectFile(string $relativePath): string
    {
        $source = file_get_contents(PROJECT_ROOT . '/' . $relativePath);
        self::assertNotFalse($source, "Não foi possível ler {$relativePath}");

        return $source;
    }
}
