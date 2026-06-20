<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Periodicidade de vistoria de equipamentos de segurança (em meses).
 */
final class SstEquipamentoPeriodicidadeHelper
{
    /** @return array<int, string> meses => rótulo */
    public static function options(): array
    {
        return [
            1 => 'Mensal',
            2 => 'Bimestral',
            3 => 'Trimestral',
            6 => 'Semestral',
            12 => 'Anual',
        ];
    }

    public static function label(int $meses): string
    {
        return self::options()[$meses] ?? ($meses . ' meses');
    }

    public static function isValid(int $meses): bool
    {
        return array_key_exists($meses, self::options());
    }
}
