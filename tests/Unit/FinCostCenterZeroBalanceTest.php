<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\FinCostCenterSapService;
use App\adms\Models\Services\SapReportApiService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class FinCostCenterZeroBalanceTest extends TestCase
{
    public function testArvoreOmitsCentersWithZeroNetBalance(): void
    {
        $grain = [
            $this->row('TIARAJU', 'TIA_PD', 'P&D', 5000.0),
            $this->row('TIARAJU', 'TIA_TI', 'TI', 0.0),
        ];
        $arvore = $this->invoke('buildArvore', [$grain]);

        self::assertCount(1, $arvore);
        self::assertSame('TIARAJU', $arvore[0]['unidade']);
        self::assertSame(1, $arvore[0]['qtd_centros']);
        self::assertSame('TIA_PD', $arvore[0]['centros'][0]['centro']);
        self::assertSame(5000.0, $arvore[0]['centros'][0]['valor']);
    }

    public function testDropZeroBalancesRemovesCanceledNetRows(): void
    {
        $grain = [
            $this->row('TIARAJU', 'TIA_TI', 'TI', 100.0),
            $this->row('TIARAJU', 'TIA_TI', 'TI', -100.0, '5.01.02.01.25'),
            $this->row('TIARAJU', 'TIA_PD', 'P&D', 50.0),
        ];
        // Simula pós-merge já líquido por chave; dropZeroBalances age na linha.
        $kept = $this->invoke('dropZeroBalances', [[
            $this->row('TIARAJU', 'TIA_TI', 'TI', 0.0),
            $this->row('TIARAJU', 'TIA_PD', 'P&D', 50.0),
        ]]);

        self::assertCount(1, $kept);
        self::assertSame('TIA_PD', $kept[0]['centro']);
    }

    /**
     * @param list<array<string, mixed>> $args
     * @return mixed
     */
    private function invoke(string $method, array $args): mixed
    {
        $service = new FinCostCenterSapService(new class extends SapReportApiService {
            public function __construct()
            {
            }
        });
        $ref = new ReflectionMethod(FinCostCenterSapService::class, $method);
        $ref->setAccessible(true);

        return $ref->invokeArgs($service, $args);
    }

    /** @return array<string, mixed> */
    private function row(string $unid, string $centro, string $nome, float $valor, string $account = '5.01.02.01.24'): array
    {
        return [
            'ano_mes' => '2026-10',
            'unidade' => $unid,
            'departamento' => '',
            'centro' => $centro,
            'nome_centro' => $unid . ' - ' . $nome,
            'account' => $account,
            'acct_name' => 'Conta teste',
            'regra' => '',
            'regra_revisar' => '',
            'status' => 'Alocado',
            'debito' => max(0.0, $valor),
            'credito' => max(0.0, -$valor),
            'valor' => $valor,
        ];
    }
}
