<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\Imports\SstEquipamentoTiposImportProfile;
use App\adms\Models\Services\Imports\SstEquipamentosImportProfile;
use App\adms\Models\Services\Imports\SstImportValues;
use PHPUnit\Framework\TestCase;

final class SstEquipamentosImportProfileTest extends TestCase
{
    public function testDateParsesIsoAndBr(): void
    {
        self::assertSame('2026-01-15', SstImportValues::date('2026-01-15'));
        self::assertSame('2026-01-15', SstImportValues::date('15/01/2026'));
        self::assertSame('2026-01-15', SstImportValues::date('15-01-2026'));
        self::assertNull(SstImportValues::date(''));
        self::assertNull(SstImportValues::date('abc'));
    }

    public function testEquipamentoStatusAliases(): void
    {
        self::assertSame('Ativo', SstImportValues::equipamentoStatus('sim'));
        self::assertSame('Inativo', SstImportValues::equipamentoStatus('não'));
        self::assertSame('Baixado', SstImportValues::equipamentoStatus('baixa'));
        self::assertSame('Bloqueado', SstImportValues::equipamentoStatus('bloqueado'));
        self::assertNull(SstImportValues::equipamentoStatus(''));
    }

    public function testPeriodicidadeMeses(): void
    {
        self::assertSame(1, SstImportValues::periodicidadeMeses('Mensal'));
        self::assertSame(12, SstImportValues::periodicidadeMeses('anual'));
        self::assertSame(6, SstImportValues::periodicidadeMeses('6'));
        self::assertNull(SstImportValues::periodicidadeMeses('4'));
        self::assertNull(SstImportValues::periodicidadeMeses(''));
    }

    public function testTiposAndEquipamentosDeclareTemplateFields(): void
    {
        $tipos = new SstEquipamentoTiposImportProfile();
        $eq = new SstEquipamentosImportProfile();
        self::assertSame('ImportCenterSst', $tipos->permission());
        self::assertSame('ImportCenterSst', $eq->permission());
        self::assertCount(count($tipos->fields()), $tipos->sampleRow());
        self::assertCount(count($eq->fields()), $eq->sampleRow());
        self::assertArrayHasKey('prefixo', $tipos->fields());
        self::assertArrayHasKey('tipo', $eq->fields());
        self::assertContains('codigo', $eq->keyFields());
        self::assertSame('Laboratório Tiaraju', $eq->sampleRow()[6]);
        self::assertStringContainsString('único por site', $eq->fields()['codigo']);
    }
}
