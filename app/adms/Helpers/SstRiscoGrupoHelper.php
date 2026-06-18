<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/** Grupos de risco ocupacional conforme NR-01 (PGR/GRO). */
final class SstRiscoGrupoHelper
{
    public const FISICO = 'Físico';
    public const QUIMICO = 'Químico';
    public const BIOLOGICO = 'Biológico';
    public const ERGONOMICO = 'Ergonômico';
    public const ACIDENTE_MECANICO = 'Acidente/Mecânico';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::FISICO,
            self::QUIMICO,
            self::BIOLOGICO,
            self::ERGONOMICO,
            self::ACIDENTE_MECANICO,
        ];
    }

    public static function isValid(?string $value): bool
    {
        return $value !== null && $value !== '' && in_array($value, self::all(), true);
    }
}
