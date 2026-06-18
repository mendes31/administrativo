<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/** Nexo / natureza da lesão ou afastamento (decisão clínica/legal, não vem do CID). */
final class SstNaturezaLesaoHelper
{
    public const DOENCA_COMUM = 'Doença comum';
    public const DOENCA_OCUPACIONAL = 'Doença ocupacional';
    public const ACIDENTE_TRABALHO = 'Acidente de trabalho';
    public const ACIDENTE_TRAJETO = 'Acidente de trajeto';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::DOENCA_COMUM,
            self::DOENCA_OCUPACIONAL,
            self::ACIDENTE_TRABALHO,
            self::ACIDENTE_TRAJETO,
        ];
    }

    public static function isValid(?string $value): bool
    {
        return $value !== null && $value !== '' && in_array($value, self::all(), true);
    }
}
