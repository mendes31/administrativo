<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/** NRs comuns para referência em treinamentos SST. */
final class SstTreinamentoNrHelper
{
    /** @return list<string> */
    public static function all(): array
    {
        return [
            'NR-1',
            'NR-4',
            'NR-5',
            'NR-6',
            'NR-7',
            'NR-9',
            'NR-10',
            'NR-11',
            'NR-12',
            'NR-17',
            'NR-18',
            'NR-20',
            'NR-23',
            'NR-26',
            'NR-33',
            'NR-35',
        ];
    }

    public static function isValid(?string $nr): bool
    {
        if ($nr === null || $nr === '') {
            return true;
        }

        return in_array($nr, self::all(), true);
    }
}
