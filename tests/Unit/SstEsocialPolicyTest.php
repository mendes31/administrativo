<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\SstEsocialGeracaoBloqueadaException;
use App\adms\Models\Services\SstEsocialPayloadService;
use App\adms\Models\Services\SstEsocialPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SstEsocialPolicy::class)]
#[CoversClass(SstEsocialPayloadService::class)]
final class SstEsocialPolicyTest extends TestCase
{
    #[DataProvider('eventosBloqueados')]
    public function testGeracaoBloqueadaParaS2240ES2245(string $tipo): void
    {
        self::assertTrue(SstEsocialPolicy::isGeracaoBloqueada($tipo));
        $this->expectException(SstEsocialGeracaoBloqueadaException::class);
        SstEsocialPolicy::assertGeracaoPermitida($tipo);
    }

    /** @return list<list<string>> */
    public static function eventosBloqueados(): array
    {
        return [
            [SstEsocialPolicy::EVENTO_CONDICOES_AMBIENTAIS],
            [SstEsocialPolicy::EVENTO_TREINAMENTO_LEGADO],
        ];
    }

    public function testRascunhoPermitidoApenasS2210ES2220(): void
    {
        self::assertTrue(SstEsocialPolicy::isRascunhoPermitido('S-2210'));
        self::assertTrue(SstEsocialPolicy::isRascunhoPermitido('S-2220'));
        self::assertFalse(SstEsocialPolicy::isRascunhoPermitido('S-2221'));
        self::assertFalse(SstEsocialPolicy::isRascunhoPermitido('S-2240'));
        self::assertFalse(SstEsocialPolicy::isRascunhoPermitido('S-2245'));
    }

    public function testPayloadServiceRecusaS2245SemPersistir(): void
    {
        $this->expectException(SstEsocialGeracaoBloqueadaException::class);
        (new SstEsocialPayloadService())->gerarOuAtualizar('S-2245', 'adms_sst_treinamento_aplicacoes', 1);
    }

    public function testPayloadServiceRecusaS2240SemPersistir(): void
    {
        $this->expectException(SstEsocialGeracaoBloqueadaException::class);
        (new SstEsocialPayloadService())->gerarOuAtualizar('S-2240', 'adms_sst_epi_entregas', 1);
    }

    public function testAvisosDeclaramNaoOficial(): void
    {
        self::assertStringContainsString('não oficial', mb_strtolower(SstEsocialPolicy::avisoFila()));
        self::assertStringContainsString('não transmite', mb_strtolower(SstEsocialPolicy::avisoFila()));
        self::assertStringContainsString('não oficial', mb_strtolower(SstEsocialPolicy::avisoPpp()));
    }
}
