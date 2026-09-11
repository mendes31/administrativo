<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\SstTreinamentoStatusService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SstTreinamentoStatusService::class)]
final class SstTreinamentoStatusServiceTest extends TestCase
{
    public function testCalcularDataValidadeSomaMeses(): void
    {
        self::assertSame(
            '2025-06-15',
            SstTreinamentoStatusService::calcularDataValidade('2024-06-15', 12)
        );
    }

    public function testCalcularDataValidadeSemMesesRetornaNulo(): void
    {
        self::assertNull(SstTreinamentoStatusService::calcularDataValidade('2024-06-15', 0));
        self::assertNull(SstTreinamentoStatusService::calcularDataValidade('', 12));
    }

    public function testStatusVencidoQuandoValidadePassou(): void
    {
        $status = (new SstTreinamentoStatusService())->calculateStatus([
            'data_realizacao' => '2020-01-01',
            'data_validade' => '2020-12-01',
        ]);
        self::assertSame('vencido', $status);
    }

    public function testStatusPendenteSemRealizacao(): void
    {
        $status = (new SstTreinamentoStatusService())->calculateStatus([
            'data_realizacao' => null,
        ]);
        self::assertSame('pendente', $status);
    }

    public function testStatusDentroDoPrazoComValidadeFutura(): void
    {
        $validade = (new \DateTimeImmutable('today'))->modify('+90 days')->format('Y-m-d');
        $status = (new SstTreinamentoStatusService())->calculateStatus([
            'data_realizacao' => '2024-01-01',
            'data_validade' => $validade,
        ]);
        self::assertSame('dentro_do_prazo', $status);
    }
}
