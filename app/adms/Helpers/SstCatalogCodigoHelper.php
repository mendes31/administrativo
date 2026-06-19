<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/** Gera códigos incrementais PREFIX + número com zeros à esquerda (ex.: EX0001, RIS002). */
final class SstCatalogCodigoHelper
{
    /**
     * @param list<string|null> $codigosExistentes
     * @param callable(string): bool|null $exists Verifica colisão fora da lista (ex.: existsCodigo no repositório)
     */
    public static function proximo(string $prefix, int $digitos, array $codigosExistentes, ?callable $exists = null): string
    {
        $prefix = strtoupper($prefix);
        $pattern = '/^' . preg_quote($prefix, '/') . '(\d+)$/';
        $max = 0;

        foreach ($codigosExistentes as $codigo) {
            $c = strtoupper(trim((string) $codigo));
            if ($c === '') {
                continue;
            }
            if (preg_match($pattern, $c, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        $n = $max + 1;
        do {
            $candidate = $prefix . str_pad((string) $n, $digitos, '0', STR_PAD_LEFT);
            $n++;
        } while ($exists !== null && $exists($candidate));

        return $candidate;
    }
}
