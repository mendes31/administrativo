<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Helpers\SstEquipamentoCodigoHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SstEquipamentoCodigoHelper::class)]
final class SstEquipamentoCodigoHelperTest extends TestCase
{
    public function testFormatEExtraiSequencia(): void
    {
        self::assertSame('EXT00001', SstEquipamentoCodigoHelper::format('ext', 1));
        self::assertSame('HID00042', SstEquipamentoCodigoHelper::format('HID', 42));
        self::assertSame(1, SstEquipamentoCodigoHelper::extractNumero('EXT00001', 'EXT'));
        self::assertSame(42, SstEquipamentoCodigoHelper::extractNumero('HID00042', 'hid'));
        self::assertNull(SstEquipamentoCodigoHelper::extractNumero('EXT1', 'EXT'));
        self::assertNull(SstEquipamentoCodigoHelper::extractNumero('ABC00001', 'EXT'));
    }
}
