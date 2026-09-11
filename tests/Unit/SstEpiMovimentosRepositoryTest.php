<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Repository\SstEpiMovimentosRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SstEpiMovimentosRepository::class)]
final class SstEpiMovimentosRepositoryTest extends TestCase
{
    #[DataProvider('caNumeros')]
    public function testCaNumeroAsStringNormalizaIntEString(int|string|null $input, string $expected): void
    {
        self::assertSame($expected, SstEpiMovimentosRepository::caNumeroAsString($input));
    }

    /** @return list<array{0: int|string|null, 1: string}> */
    public static function caNumeros(): array
    {
        return [
            [12345, '12345'],
            ['12345', '12345'],
            ['  ab12  ', 'AB12'],
            [null, ''],
            ['', ''],
        ];
    }

    public function testChaveDeArrayNumericaDoCaNaoQuebraTipagemString(): void
    {
        $porCa = [];
        $ca = SstEpiMovimentosRepository::caNumeroAsString('12345');
        $porCa[$ca] = ['ca_numero' => $ca];

        foreach ($porCa as $caKey => $info) {
            $safe = SstEpiMovimentosRepository::caNumeroAsString($info['ca_numero'] ?? $caKey);
            self::assertSame('12345', $safe);
        }
    }
}
